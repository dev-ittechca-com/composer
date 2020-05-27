<?php

declare(strict_types=1);

namespace PhpMyAdmin;

use function count;
use function explode;

final class ReplicationInfo
{
    public static function load(): void
    {
        global $dbi, $url_params;
        global $server_master_replication, $server_slave_replication, $server_slave_multi_replication;
        global $replication_types, $replication_info;
        global $master_variables, $slave_variables, $slave_variables_alerts, $slave_variables_oks;

        /**
         * get master replication from server
         */
        $server_master_replication = $dbi->fetchResult('SHOW MASTER STATUS');

        /**
         * set selected master server
         */
        if (! empty($_POST['master_connection'])) {
            /**
             * check for multi-master replication functionality
             */
            $server_slave_multi_replication = $dbi->fetchResult(
                'SHOW ALL SLAVES STATUS'
            );
            if ($server_slave_multi_replication) {
                $dbi->query(
                    "SET @@default_master_connection = '"
                    . $dbi->escapeString(
                        $_POST['master_connection']
                    ) . "'"
                );
                $url_params['master_connection'] = $_POST['master_connection'];
            }
        }

        /**
         * get slave replication from server
         */
        $server_slave_replication = $dbi->fetchResult('SHOW SLAVE STATUS');

        /**
         * replication types
         */
        $replication_types = [
            'master',
            'slave',
        ];

        /**
         * define variables for master status
         */
        $master_variables = [
            'File',
            'Position',
            'Binlog_Do_DB',
            'Binlog_Ignore_DB',
        ];

        /**
         * Define variables for slave status
         */
        $slave_variables  = [
            'Slave_IO_State',
            'Master_Host',
            'Master_User',
            'Master_Port',
            'Connect_Retry',
            'Master_Log_File',
            'Read_Master_Log_Pos',
            'Relay_Log_File',
            'Relay_Log_Pos',
            'Relay_Master_Log_File',
            'Slave_IO_Running',
            'Slave_SQL_Running',
            'Replicate_Do_DB',
            'Replicate_Ignore_DB',
            'Replicate_Do_Table',
            'Replicate_Ignore_Table',
            'Replicate_Wild_Do_Table',
            'Replicate_Wild_Ignore_Table',
            'Last_Errno',
            'Last_Error',
            'Skip_Counter',
            'Exec_Master_Log_Pos',
            'Relay_Log_Space',
            'Until_Condition',
            'Until_Log_File',
            'Until_Log_Pos',
            'Master_SSL_Allowed',
            'Master_SSL_CA_File',
            'Master_SSL_CA_Path',
            'Master_SSL_Cert',
            'Master_SSL_Cipher',
            'Master_SSL_Key',
            'Seconds_Behind_Master',
        ];
        /**
         * define important variables, which need to be watched for
         * correct running of replication in slave mode
         *
         * @usedby PhpMyAdmin\ReplicationGui->getHtmlForReplicationStatusTable()
         */
        // TODO change to regexp or something, to allow for negative match.
        // To e.g. highlight 'Last_Error'
        $slave_variables_alerts = [
            'Slave_IO_Running' => 'No',
            'Slave_SQL_Running' => 'No',
        ];
        $slave_variables_oks = [
            'Slave_IO_Running' => 'Yes',
            'Slave_SQL_Running' => 'Yes',
        ];

        // check which replication is available and
        // set $server_{master/slave}_status and assign values

        // replication info is more easily passed to functions
        $replication_info = [];

        foreach ($replication_types as $type) {
            if (count(${'server_' . $type . '_replication'}) > 0) {
                $replication_info[$type]['status'] = true;
            } else {
                $replication_info[$type]['status'] = false;
            }
            if (! $replication_info[$type]['status']) {
                continue;
            }

            if ($type == 'master') {
                self::fill(
                    $type,
                    'Do_DB',
                    $server_master_replication[0],
                    'Binlog_Do_DB'
                );

                self::fill(
                    $type,
                    'Ignore_DB',
                    $server_master_replication[0],
                    'Binlog_Ignore_DB'
                );
            } elseif ($type == 'slave') {
                self::fill(
                    $type,
                    'Do_DB',
                    $server_slave_replication[0],
                    'Replicate_Do_DB'
                );

                self::fill(
                    $type,
                    'Ignore_DB',
                    $server_slave_replication[0],
                    'Replicate_Ignore_DB'
                );

                self::fill(
                    $type,
                    'Do_Table',
                    $server_slave_replication[0],
                    'Replicate_Do_Table'
                );

                self::fill(
                    $type,
                    'Ignore_Table',
                    $server_slave_replication[0],
                    'Replicate_Ignore_Table'
                );

                self::fill(
                    $type,
                    'Wild_Do_Table',
                    $server_slave_replication[0],
                    'Replicate_Wild_Do_Table'
                );

                self::fill(
                    $type,
                    'Wild_Ignore_Table',
                    $server_slave_replication[0],
                    'Replicate_Wild_Ignore_Table'
                );
            }
        }
    }

    /**
     * Fill global replication_info variable.
     *
     * @param string $type               Type: master, slave
     * @param string $replicationInfoKey Key in replication_info variable
     * @param array  $mysqlInfo          MySQL data about replication
     * @param string $mysqlKey           MySQL key
     *
     * @return array
     */
    private static function fill(
        $type,
        $replicationInfoKey,
        array $mysqlInfo,
        $mysqlKey
    ) {
        global $replication_info;

        $replication_info[$type][$replicationInfoKey] = empty($mysqlInfo[$mysqlKey])
            ? []
            : explode(
                ',',
                $mysqlInfo[$mysqlKey]
            );

        return $replication_info[$type][$replicationInfoKey];
    }
}
