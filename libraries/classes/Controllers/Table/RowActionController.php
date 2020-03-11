<?php
declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Table;

use PhpMyAdmin\Sql;
use PhpMyAdmin\Url;
use function is_array;

/**
 * Handle row specific actions like edit, delete, export.
 */
class RowActionController extends AbstractController
{
    public function index(): void
    {
        global $db, $goto, $pmaThemeImage, $sql_query, $table,  $disp_message, $disp_query, $action;
        global $submit_mult, $active_page, $err_url, $original_sql_query, $url_query, $original_url_query;

        if (isset($_POST['submit_mult'])) {
            $submit_mult = $_POST['submit_mult'];
            // workaround for IE problem:
        } elseif (isset($_POST['submit_mult_delete_x'])) {
            $submit_mult = 'row_delete';
        }

        if (isset($_POST['submit_mult_change_x'])
            || $submit_mult === 'row_edit' || $submit_mult === 'edit'
            || $submit_mult === 'row_copy' || $submit_mult === 'copy'
        ) {
            $this->edit();

            return;
        }

        if (isset($_POST['submit_mult_export_x']) || $submit_mult === 'row_export' || $submit_mult === 'export') {
            $this->export();

            return;
        }

        // If the 'Ask for confirmation' button was pressed, this can only come
        // from 'delete' mode, so we set it straight away.
        if (isset($_POST['mult_btn'])) {
            $submit_mult = 'row_delete';
        }

        switch ($submit_mult) {
            case 'row_delete':
                // leave as is
                break;

            case 'delete':
                $submit_mult = 'row_delete';
                break;
        }

        if (! empty($submit_mult)) {
            if (isset($_POST['goto'])
                && (! isset($_POST['rows_to_delete'])
                    || ! is_array($_POST['rows_to_delete']))
            ) {
                $this->response->setRequestStatus(false);
                $this->response->addJSON('message', __('No row selected.'));
            }

            switch ($submit_mult) {
                case 'row_delete':
                default:
                    $action = Url::getFromRoute('/table/row-action');
                    $err_url = Url::getFromRoute('/table/row-action', $GLOBALS['url_params']);
                    if (! isset($_POST['mult_btn'])) {
                        $original_sql_query = $sql_query;
                        if (! empty($url_query)) {
                            $original_url_query = $url_query;
                        }
                    }
                    include ROOT_PATH . 'libraries/mult_submits.inc.php';
                    $_url_params = $GLOBALS['url_params'];
                    $_url_params['goto'] = Url::getFromRoute('/table/sql');
                    $url_query = Url::getCommon($_url_params);

                    /**
                     * Show result of multi submit operation
                     */
                    // sql_query is not set when user does not confirm multi-delete
                    if ((! empty($submit_mult) || isset($_POST['mult_btn']))
                        && ! empty($sql_query)
                    ) {
                        $disp_message = __('Your SQL query has been executed successfully.');
                        $disp_query = $sql_query;
                    }

                    if (isset($original_sql_query)) {
                        $sql_query = $original_sql_query;
                    }

                    if (isset($original_url_query)) {
                        $url_query = $original_url_query;
                    }

                    $active_page = Url::getFromRoute('/sql');
                    $sql = new Sql();
                    $sql->executeQueryAndSendQueryResponse(
                        null,
                        false,
                        $db,
                        $table,
                        null,
                        null,
                        null,
                        null,
                        null,
                        null,
                        $goto,
                        $pmaThemeImage,
                        null,
                        null,
                        null,
                        $sql_query,
                        null,
                        null
                    );
            }
        }
    }

    private function edit(): void
    {
        global $containerBuilder, $submit_mult, $active_page, $where_clause;

        $submit_mult = $submit_mult === 'copy' ? 'row_copy' : 'row_edit';

        if ($submit_mult === 'row_copy') {
            $_POST['default_action'] = 'insert';
        }

        if (isset($_POST['goto']) && (! isset($_POST['rows_to_delete']) || ! is_array($_POST['rows_to_delete']))) {
            $this->response->setRequestStatus(false);
            $this->response->addJSON('message', __('No row selected.'));
        }

        // As we got the rows to be edited from the
        // 'rows_to_delete' checkbox, we use the index of it as the
        // indicating WHERE clause. Then we build the array which is used
        // for the /table/change script.
        $where_clause = [];
        if (isset($_POST['rows_to_delete']) && is_array($_POST['rows_to_delete'])) {
            foreach ($_POST['rows_to_delete'] as $i => $i_where_clause) {
                $where_clause[] = $i_where_clause;
            }
        }

        $active_page = Url::getFromRoute('/table/change');

        /** @var ChangeController $controller */
        $controller = $containerBuilder->get(ChangeController::class);
        $controller->index();
    }

    private function export(): void
    {
        global $containerBuilder, $submit_mult, $active_page, $single_table, $where_clause;

        $submit_mult = 'row_export';

        if (isset($_POST['goto']) && (! isset($_POST['rows_to_delete']) || ! is_array($_POST['rows_to_delete']))) {
            $this->response->setRequestStatus(false);
            $this->response->addJSON('message', __('No row selected.'));
        }

        // Needed to allow SQL export
        $single_table = true;

        // As we got the rows to be exported from the
        // 'rows_to_delete' checkbox, we use the index of it as the
        // indicating WHERE clause. Then we build the array which is used
        // for the /table/change script.
        $where_clause = [];
        if (isset($_POST['rows_to_delete']) && is_array($_POST['rows_to_delete'])) {
            foreach ($_POST['rows_to_delete'] as $i => $i_where_clause) {
                $where_clause[] = $i_where_clause;
            }
        }

        $active_page = Url::getFromRoute('/table/export');

        /** @var ExportController $controller */
        $controller = $containerBuilder->get(ExportController::class);
        $controller->index();
    }
}
