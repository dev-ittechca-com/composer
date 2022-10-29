<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\View;

use PhpMyAdmin\Controllers\AbstractController;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\DbTableExists;
use PhpMyAdmin\Html\Generator;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Message;
use PhpMyAdmin\Operations;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Template;
use PhpMyAdmin\Url;
use PhpMyAdmin\Util;

use function __;
use function strval;

/**
 * View manipulations
 */
class OperationsController extends AbstractController
{
    /** @var Operations */
    private $operations;

    /** @var DatabaseInterface */
    private $dbi;

    public function __construct(
        ResponseRenderer $response,
        Template $template,
        Operations $operations,
        DatabaseInterface $dbi
    ) {
        parent::__construct($response, $template);
        $this->operations = $operations;
        $this->dbi = $dbi;
    }

    public function __invoke(ServerRequest $request): void
    {
        $GLOBALS['urlParams'] = $GLOBALS['urlParams'] ?? null;
        $GLOBALS['reload'] = $GLOBALS['reload'] ?? null;
        $GLOBALS['result'] = $GLOBALS['result'] ?? null;
        $GLOBALS['warning_messages'] = $GLOBALS['warning_messages'] ?? null;
        $tableObject = $this->dbi->getTable($GLOBALS['db'], $GLOBALS['table']);

        $GLOBALS['errorUrl'] = $GLOBALS['errorUrl'] ?? null;
        $this->addScriptFiles(['table/operations.js']);

        $this->checkParameters(['db', 'table']);

        $GLOBALS['urlParams'] = ['db' => $GLOBALS['db'], 'table' => $GLOBALS['table']];
        $GLOBALS['errorUrl'] = Util::getScriptNameForOption($GLOBALS['cfg']['DefaultTabTable'], 'table');
        $GLOBALS['errorUrl'] .= Url::getCommon($GLOBALS['urlParams'], '&');

        DbTableExists::check($GLOBALS['db'], $GLOBALS['table']);

        $GLOBALS['urlParams']['goto'] = $GLOBALS['urlParams']['back'] = Url::getFromRoute('/view/operations');

        $message = new Message();
        $type = 'success';
        $submitoptions = $request->getParsedBodyParam('submitoptions');
        $newname = $request->getParsedBodyParam('new_name');

        if ($submitoptions !== null) {
            if ($newname !== null && $tableObject->rename(strval($newname))) {
                $message->addText($tableObject->getLastMessage());
                $GLOBALS['result'] = true;
                $GLOBALS['table'] = $tableObject->getName();
                /* Force reread after rename */
                $tableObject->getStatusInfo(null, true);
                $GLOBALS['reload'] = true;
            } else {
                    $message->addText($tableObject->getLastError());
                    $GLOBALS['result'] = false;
            }

            $GLOBALS['warning_messages'] = $this->operations->getWarningMessagesArray();
        }

        if (isset($GLOBALS['result'])) {
            // set to success by default, because result set could be empty
            // (for example, a table rename)
            if (empty($message->getString())) {
                if ($GLOBALS['result']) {
                    $message->addText(
                        __('Your SQL query has been executed successfully.')
                    );
                } else {
                    $message->addText(__('Error'));
                }

                // $result should exist, regardless of $_message
                $type = $GLOBALS['result'] ? 'success' : 'error';
            }

            if (! empty($GLOBALS['warning_messages'])) {
                $message->addMessagesString($GLOBALS['warning_messages']);
                $message->isError(true);
            }

            $this->response->addHTML(Generator::getMessage(
                $message,
                $GLOBALS['sql_query'],
                $type
            ));
        }

        $this->render('table/operations/view', [
            'db' => $GLOBALS['db'],
            'table' => $GLOBALS['table'],
            'url_params' => $GLOBALS['urlParams'],
        ]);
    }
}
