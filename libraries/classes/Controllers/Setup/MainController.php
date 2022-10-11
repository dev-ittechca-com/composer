<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Setup;

use PhpMyAdmin\Core;
use PhpMyAdmin\Header;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Template;
use PhpMyAdmin\Url;

use function __;
use function file_exists;
use function header;
use function in_array;

final class MainController
{
    public function __invoke(ServerRequest $request): void
    {
        if (@file_exists(CONFIG_FILE) && ! $GLOBALS['cfg']['DBG']['demo']) {
            Core::fatalError(__('Configuration already exists, setup is disabled!'));
        }

        /** @var mixed $pageParam */
        $pageParam = $request->getQueryParam('page');
        $page = in_array($pageParam, ['form', 'config', 'servers'], true) ? $pageParam : 'index';

        Core::noCacheHeader();

        // Sent security-related headers
        (new Header())->sendHttpHeaders();

        if ($page === 'form') {
            echo (new FormController($GLOBALS['ConfigFile'], new Template()))([
                'formset' => $request->getQueryParam('formset'),
            ]);

            return;
        }

        if ($page === 'config') {
            echo (new ConfigController($GLOBALS['ConfigFile'], new Template()))([
                'formset' => $request->getQueryParam('formset'),
                'eol' => $request->getQueryParam('eol'),
            ]);

            return;
        }

        if ($page === 'servers') {
            $controller = new ServersController($GLOBALS['ConfigFile'], new Template());
            /** @var mixed $mode */
            $mode = $request->getQueryParam('mode');
            if ($mode === 'remove' && $request->isPost()) {
                $controller->destroy([
                    'id' => $request->getQueryParam('id'),
                ]);
                header('Location: ../setup/index.php' . Url::getCommonRaw(['route' => '/setup']));

                return;
            }

            echo $controller->index([
                'formset' => $request->getQueryParam('formset'),
                'mode' => $mode,
                'id' => $request->getQueryParam('id'),
            ]);

            return;
        }

        echo (new HomeController($GLOBALS['ConfigFile'], new Template()))([
            'formset' => $request->getQueryParam('formset'),
            'version_check' => $request->getQueryParam('version_check'),
        ]);
    }
}
