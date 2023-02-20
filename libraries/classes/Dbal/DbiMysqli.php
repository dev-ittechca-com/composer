<?php
/**
 * Interface to the MySQL Improved extension (MySQLi)
 */

declare(strict_types=1);

namespace PhpMyAdmin\Dbal;

use mysqli;
use mysqli_sql_exception;
use PhpMyAdmin\Config\Settings\Server;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Query\Utilities;

use function __;
use function defined;
use function mysqli_get_client_info;
use function mysqli_init;
use function mysqli_report;
use function sprintf;
use function stripos;
use function trigger_error;

use const E_USER_ERROR;
use const E_USER_WARNING;
use const MYSQLI_CLIENT_COMPRESS;
use const MYSQLI_CLIENT_SSL;
use const MYSQLI_CLIENT_SSL_DONT_VERIFY_SERVER_CERT;
use const MYSQLI_OPT_LOCAL_INFILE;
use const MYSQLI_OPT_SSL_VERIFY_SERVER_CERT;
use const MYSQLI_REPORT_ERROR;
use const MYSQLI_REPORT_OFF;
use const MYSQLI_REPORT_STRICT;
use const MYSQLI_STORE_RESULT;
use const MYSQLI_USE_RESULT;

/**
 * Interface to the MySQL Improved extension (MySQLi)
 */
class DbiMysqli implements DbiExtension
{
    public function connect(string $user, string $password, Server $server): ?Connection
    {
        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

        $mysqli = mysqli_init();

        if ($mysqli === false) {
            return null;
        }

        $client_flags = 0;

        /* Optionally compress connection */
        if ($server->compress && defined('MYSQLI_CLIENT_COMPRESS')) {
            $client_flags |= MYSQLI_CLIENT_COMPRESS;
        }

        // phpcs:disable Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
        /* Optionally enable SSL */
        if ($server->ssl) {
            $client_flags |= MYSQLI_CLIENT_SSL;
            if (
                $server->ssl_key !== null && $server->ssl_key !== '' ||
                $server->ssl_cert !== null && $server->ssl_cert !== '' ||
                $server->ssl_ca !== null && $server->ssl_ca !== '' ||
                $server->ssl_ca_path !== null && $server->ssl_ca_path !== '' ||
                $server->ssl_ciphers !== null && $server->ssl_ciphers !== ''
            ) {
                $mysqli->ssl_set(
                    $server->ssl_key ?? '',
                    $server->ssl_cert ?? '',
                    $server->ssl_ca ?? '',
                    $server->ssl_ca_path ?? '',
                    $server->ssl_ciphers ?? ''
                );
            }

            /**
             * disables SSL certificate validation on mysqlnd for MySQL 5.6 or later
             *
             * @link https://bugs.php.net/bug.php?id=68344
             * @link https://github.com/phpmyadmin/phpmyadmin/pull/11838
             */
            if (! $server->ssl_verify) {
                $mysqli->options(MYSQLI_OPT_SSL_VERIFY_SERVER_CERT, (int) $server->ssl_verify);
                $client_flags |= MYSQLI_CLIENT_SSL_DONT_VERIFY_SERVER_CERT;
            }
        }

        if ($GLOBALS['cfg']['PersistentConnections']) {
            $host = 'p:' . $server->host;
        } else {
            $host = $server->host;
        }

        try {
            $mysqli->real_connect(
                $host,
                $user,
                $password,
                '',
                (int) $server->port,
                $server->socket,
                $client_flags
            );
        } catch (mysqli_sql_exception) {
            /**
             * Switch to SSL if server asked us to do so, unfortunately
             * there are more ways MySQL server can tell this:
             *
             * - MySQL 8.0 and newer should return error 3159
             * - #2001 - SSL Connection is required. Please specify SSL options and retry.
             * - #9002 - SSL connection is required. Please specify SSL options and retry.
             */
            $error_number = $mysqli->connect_errno;
            $error_message = $mysqli->connect_error;
            if (
                ! $server->ssl
                && ($error_number == 3159
                    || (($error_number == 2001 || $error_number == 9002)
                        && stripos($error_message, 'SSL Connection is required') !== false))
            ) {
                trigger_error(
                    __('SSL connection enforced by server, automatically enabling it.'),
                    E_USER_WARNING
                );

                return self::connect($user, $password, $server->withSSL(true));
            }

            if ($error_number === 1045 && $server->hide_connection_errors) {
                trigger_error(
                    sprintf(
                        __(
                            'Error 1045: Access denied for user. Additional error information'
                            . ' may be available, but is being hidden by the %s configuration directive.'
                        ),
                        '[code][doc@cfg_Servers_hide_connection_errors]'
                        . '$cfg[\'Servers\'][$i][\'hide_connection_errors\'][/doc][/code]'
                    ),
                    E_USER_ERROR
                );
            } else {
                trigger_error($error_number . ': ' . $error_message, E_USER_WARNING);
            }

            mysqli_report(MYSQLI_REPORT_OFF);

            return null;
        }

        // phpcs:enable

        $mysqli->options(MYSQLI_OPT_LOCAL_INFILE, (int) defined('PMA_ENABLE_LDI'));

        mysqli_report(MYSQLI_REPORT_OFF);

        return new Connection($mysqli);
    }

    /**
     * selects given database
     *
     * @param string|DatabaseName $databaseName database name to select
     */
    public function selectDb($databaseName, Connection $connection): bool
    {
        /** @var mysqli $mysqli */
        $mysqli = $connection->connection;

        return $mysqli->select_db((string) $databaseName);
    }

    /**
     * runs a query and returns the result
     *
     * @param string $query   query to execute
     * @param int    $options query options
     */
    public function realQuery(string $query, Connection $connection, int $options): MysqliResult|false
    {
        $method = MYSQLI_STORE_RESULT;
        if ($options === ($options | DatabaseInterface::QUERY_UNBUFFERED)) {
            $method = MYSQLI_USE_RESULT;
        }

        /** @var mysqli $mysqli */
        $mysqli = $connection->connection;

        $result = $mysqli->query($query, $method);
        if ($result === false) {
            return false;
        }

        return new MysqliResult($result);
    }

    /**
     * Run the multi query and output the results
     *
     * @param string $query multi query statement to execute
     */
    public function realMultiQuery(Connection $connection, $query): bool
    {
        /** @var mysqli $mysqli */
        $mysqli = $connection->connection;

        return $mysqli->multi_query($query);
    }

    /**
     * Check if there are any more query results from a multi query
     */
    public function moreResults(Connection $connection): bool
    {
        /** @var mysqli $mysqli */
        $mysqli = $connection->connection;

        return $mysqli->more_results();
    }

    /**
     * Prepare next result from multi_query
     */
    public function nextResult(Connection $connection): bool
    {
        /** @var mysqli $mysqli */
        $mysqli = $connection->connection;

        return $mysqli->next_result();
    }

    /**
     * Store the result returned from multi query
     *
     * @return MysqliResult|false false when empty results / result set when not empty
     */
    public function storeResult(Connection $connection): MysqliResult|false
    {
        /** @var mysqli $mysqli */
        $mysqli = $connection->connection;

        $result = $mysqli->store_result();

        return $result === false ? false : new MysqliResult($result);
    }

    /**
     * Returns a string representing the type of connection used
     *
     * @return string type of connection used
     */
    public function getHostInfo(Connection $connection)
    {
        /** @var mysqli $mysqli */
        $mysqli = $connection->connection;

        // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
        return $mysqli->host_info;
    }

    /**
     * Returns the version of the MySQL protocol used
     *
     * @return int version of the MySQL protocol used
     */
    public function getProtoInfo(Connection $connection)
    {
        /** @var mysqli $mysqli */
        $mysqli = $connection->connection;

        // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
        return (int) $mysqli->protocol_version;
    }

    /**
     * returns a string that represents the client library version
     *
     * @return string MySQL client library version
     */
    public function getClientInfo()
    {
        return mysqli_get_client_info();
    }

    /**
     * Returns last error message or an empty string if no errors occurred.
     */
    public function getError(Connection $connection): string
    {
        $GLOBALS['errno'] = 0;

        /** @var mysqli $mysqli */
        $mysqli = $connection->connection;

        $error_number = $mysqli->errno;
        $error_message = $mysqli->error;

        if ($error_number === 0 || $error_message === '') {
            return '';
        }

        // keep the error number for further check after
        // the call to getError()
        $GLOBALS['errno'] = $error_number;

        return Utilities::formatError($error_number, $error_message);
    }

    /**
     * returns the number of rows affected by last query
     *
     * @psalm-return int|numeric-string
     */
    public function affectedRows(Connection $connection): int|string
    {
        /** @var mysqli $mysqli */
        $mysqli = $connection->connection;

        // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
        return $mysqli->affected_rows;
    }

    /**
     * returns properly escaped string for use in MySQL queries
     *
     * @param string $string string to be escaped
     *
     * @return string a MySQL escaped string
     */
    public function escapeString(Connection $connection, $string)
    {
        /** @var mysqli $mysqli */
        $mysqli = $connection->connection;

        return $mysqli->real_escape_string($string);
    }

    /**
     * Prepare an SQL statement for execution.
     *
     * @param string $query The query, as a string.
     */
    public function prepare(Connection $connection, string $query): ?Statement
    {
        /** @var mysqli $mysqli */
        $mysqli = $connection->connection;
        $statement = $mysqli->prepare($query);
        if ($statement === false) {
            return null;
        }

        return new MysqliStatement($statement);
    }

    /**
     * Returns the number of warnings from the last query.
     */
    public function getWarningCount(Connection $connection): int
    {
        /** @var mysqli $mysqli */
        $mysqli = $connection->connection;

        // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
        return $mysqli->warning_count;
    }
}
