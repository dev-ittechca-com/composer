<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Main loader script
 *
 * @package PhpMyAdmin
 */
declare(strict_types=1);

use FastRoute\Dispatcher;
use FastRoute\RouteCollector;
use PhpMyAdmin\Core;
use PhpMyAdmin\Message;

use function FastRoute\simpleDispatcher;

if (! defined('ROOT_PATH')) {
    define('ROOT_PATH', __DIR__ . DIRECTORY_SEPARATOR);
}

require_once ROOT_PATH . 'libraries/common.inc.php';

if (isset($_GET['route']) || isset($_POST['route'])) {
    $dispatcher = simpleDispatcher(function (RouteCollector $routes) {
        $routes->addRoute(['GET', 'POST'], '[/]', function () {
            require_once ROOT_PATH . 'libraries/entry_points/home.php';
        });
        $routes->addGroup('/database', function (RouteCollector $routes) {
            $routes->addRoute(['GET', 'POST'], '/operations', function () {
                require_once ROOT_PATH . 'libraries/entry_points/database/operations.php';
            });
            $routes->addRoute(['GET', 'POST'], '/search', function () {
                require_once ROOT_PATH . 'libraries/entry_points/database/search.php';
            });
            $routes->addRoute(['GET', 'POST'], '/structure', function () {
                require_once ROOT_PATH . 'libraries/entry_points/database/structure.php';
            });
            $routes->addRoute(['GET', 'POST'], '/tracking', function () {
                require_once ROOT_PATH . 'libraries/entry_points/database/tracking.php';
            });
        });
        $routes->addGroup('/server', function (RouteCollector $routes) {
            $routes->addRoute('GET', '/collations', function () {
                require_once ROOT_PATH . 'libraries/entry_points/server/collations.php';
            });
            $routes->addRoute(['GET', 'POST'], '/databases', function () {
                require_once ROOT_PATH . 'libraries/entry_points/server/databases.php';
            });
            $routes->addRoute('GET', '/engines', function () {
                require_once ROOT_PATH . 'libraries/entry_points/server/engines.php';
            });
            $routes->addRoute('GET', '/plugins', function () {
                require_once ROOT_PATH . 'libraries/entry_points/server/plugins.php';
            });
            $routes->addRoute(['GET', 'POST'], '/privileges', function () {
                require_once ROOT_PATH . 'libraries/entry_points/server/privileges.php';
            });
            $routes->addGroup('/status', function (RouteCollector $routes) {
                $routes->addRoute('GET', '', function () {
                    require_once ROOT_PATH . 'libraries/entry_points/server/status.php';
                });
                $routes->addRoute('GET', '/queries', function () {
                    require_once ROOT_PATH . 'libraries/entry_points/server/status/queries.php';
                });
            });
            $routes->addRoute(['GET', 'POST'], '/variables', function () {
                require_once ROOT_PATH . 'libraries/entry_points/server/variables.php';
            });
        });
        $routes->addGroup('/table', function (RouteCollector $routes) {
            $routes->addRoute(['GET', 'POST'], '/change', function () {
                require_once ROOT_PATH . 'libraries/entry_points/table/change.php';
            });
            $routes->addRoute(['GET', 'POST'], '/search', function () {
                require_once ROOT_PATH . 'libraries/entry_points/table/select.php';
            });
            $routes->addRoute(['GET', 'POST'], '/structure', function () {
                require_once ROOT_PATH . 'libraries/entry_points/table/structure.php';
            });
            $routes->addRoute(['GET', 'POST'], '/tracking', function () {
                require_once ROOT_PATH . 'libraries/entry_points/table/tracking.php';
            });
        });
    });
    $routeInfo = $dispatcher->dispatch(
        $_SERVER['REQUEST_METHOD'],
        rawurldecode($_GET['route'] ?? $_POST['route'])
    );
    if ($routeInfo[0] === Dispatcher::NOT_FOUND) {
        Message::error(sprintf(
            __('Error 404! The page %s was not found.'),
            '<code>' . ($_GET['route'] ?? $_POST['route']) . '</code>'
        ))->display();
        exit;
    } elseif ($routeInfo[0] === Dispatcher::METHOD_NOT_ALLOWED) {
        Message::error(__('Error 405! Request method not allowed.'))->display();
        exit;
    } elseif ($routeInfo[0] === Dispatcher::FOUND) {
        $handler = $routeInfo[1];
        $handler($routeInfo[2]);
        exit;
    }
}

/**
 * pass variables to child pages
 */
$drops = [
    'lang',
    'server',
    'collation_connection',
    'db',
    'table',
];
foreach ($drops as $each_drop) {
    if (array_key_exists($each_drop, $_GET)) {
        unset($_GET[$each_drop]);
    }
}
unset($drops, $each_drop);

/**
 * Black list of all scripts to which front-end must submit data.
 * Such scripts must not be loaded on home page.
 */
$target_blacklist =  [
    'import.php',
    'export.php',
];

// If we have a valid target, let's load that script instead
if (! empty($_REQUEST['target'])
    && is_string($_REQUEST['target'])
    && 0 !== strpos($_REQUEST['target'], "index")
    && ! in_array($_REQUEST['target'], $target_blacklist)
    && Core::checkPageValidity($_REQUEST['target'], [], true)
) {
    include ROOT_PATH . $_REQUEST['target'];
    exit;
}

require_once ROOT_PATH . 'libraries/entry_points/home.php';
