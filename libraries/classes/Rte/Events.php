<?php

declare(strict_types=1);

namespace PhpMyAdmin\Rte;

use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Html\Generator;
use PhpMyAdmin\Message;
use PhpMyAdmin\Response;
use PhpMyAdmin\Template;
use PhpMyAdmin\Url;
use PhpMyAdmin\Util;
use function count;
use function explode;
use function htmlentities;
use function htmlspecialchars;
use function in_array;
use function intval;
use function mb_strpos;
use function mb_strtoupper;
use function sprintf;
use const ENT_QUOTES;

/**
 * Functions for event management.
 */
class Events
{
    /** @var DatabaseInterface */
    private $dbi;

    /** @var Template */
    private $template;

    /** @var Response */
    private $response;

    /**
     * @param DatabaseInterface $dbi      DatabaseInterface instance.
     * @param Template          $template Template instance.
     * @param Response          $response Response instance.
     */
    public function __construct(DatabaseInterface $dbi, Template $template, $response)
    {
        $this->dbi = $dbi;
        $this->template = $template;
        $this->response = $response;
    }

    /**
     * Sets required globals
     *
     * @return void
     */
    public function setGlobals()
    {
        global $event_status, $event_type, $event_interval;

        $event_status = [
            'query' => [
                'ENABLE',
                'DISABLE',
                'DISABLE ON SLAVE',
            ],
            'display' => [
                'ENABLED',
                'DISABLED',
                'SLAVESIDE_DISABLED',
            ],
        ];
        $event_type = [
            'RECURRING',
            'ONE TIME',
        ];
        $event_interval = [
            'YEAR',
            'QUARTER',
            'MONTH',
            'DAY',
            'HOUR',
            'MINUTE',
            'WEEK',
            'SECOND',
            'YEAR_MONTH',
            'DAY_HOUR',
            'DAY_MINUTE',
            'DAY_SECOND',
            'HOUR_MINUTE',
            'HOUR_SECOND',
            'MINUTE_SECOND',
        ];
    }

    /**
     * Main function for the events functionality
     *
     * @return void
     */
    public function main()
    {
        global $db, $table, $pmaThemeImage, $text_dir;

        $this->setGlobals();
        /**
         * Process all requests
         */
        $this->handleEditor();
        $this->export();

        $items = $this->dbi->getEvents($db);
        $hasPrivilege = Util::currentUserHasPrivilege('EVENT', $db);
        $isAjax = $this->response->isAjax() && empty($_REQUEST['ajax_page_request']);

        $rows = '';
        foreach ($items as $item) {
            $sqlDrop = sprintf(
                'DROP EVENT IF EXISTS %s',
                Util::backquote($item['name'])
            );
            $rows .= $this->template->render('rte/events/row', [
                'db' => $db,
                'table' => $table,
                'event' => $item,
                'has_privilege' => $hasPrivilege,
                'sql_drop' => $sqlDrop,
                'row_class' => $isAjax ? 'ajaxInsert hide' : '',
            ]);
        }

        echo $this->template->render('rte/events/list', [
            'db' => $db,
            'table' => $table,
            'items' => $items,
            'rows' => $rows,
            'select_all_arrow_src' => $pmaThemeImage . 'arrow_' . $text_dir . '.png',
        ]);

        echo $this->template->render('rte/events/footer', [
            'db' => $db,
            'table' => $table,
            'has_privilege' => Util::currentUserHasPrivilege('EVENT', $db, $table),
            'toggle_button' => $this->getFooterToggleButton(),
        ]);
    }

    /**
     * Handles editor requests for adding or editing an item
     *
     * @return void
     */
    public function handleEditor()
    {
        global $db, $table, $errors, $message;

        if (! empty($_POST['editor_process_add'])
            || ! empty($_POST['editor_process_edit'])
        ) {
            $sql_query = '';

            $item_query = $this->getQueryFromRequest();

            if (! count($errors)) { // set by PhpMyAdmin\Rte\Routines::getQueryFromRequest()
                // Execute the created query
                if (! empty($_POST['editor_process_edit'])) {
                    // Backup the old trigger, in case something goes wrong
                    $create_item = $this->dbi->getDefinition(
                        $db,
                        'EVENT',
                        $_POST['item_original_name']
                    );
                    $drop_item = 'DROP EVENT '
                        . Util::backquote($_POST['item_original_name'])
                        . ";\n";
                    $result = $this->dbi->tryQuery($drop_item);
                    if (! $result) {
                        $errors[] = sprintf(
                            __('The following query has failed: "%s"'),
                            htmlspecialchars($drop_item)
                        )
                        . '<br>'
                        . __('MySQL said: ') . $this->dbi->getError();
                    } else {
                        $result = $this->dbi->tryQuery($item_query);
                        if (! $result) {
                            $errors[] = sprintf(
                                __('The following query has failed: "%s"'),
                                htmlspecialchars($item_query)
                            )
                            . '<br>'
                            . __('MySQL said: ') . $this->dbi->getError();
                            // We dropped the old item, but were unable to create
                            // the new one. Try to restore the backup query
                            $result = $this->dbi->tryQuery($create_item);
                            $errors = $this->checkResult($result, $create_item, $errors);
                        } else {
                            $message = Message::success(
                                __('Event %1$s has been modified.')
                            );
                            $message->addParam(
                                Util::backquote($_POST['item_name'])
                            );
                            $sql_query = $drop_item . $item_query;
                        }
                    }
                } else {
                    // 'Add a new item' mode
                    $result = $this->dbi->tryQuery($item_query);
                    if (! $result) {
                        $errors[] = sprintf(
                            __('The following query has failed: "%s"'),
                            htmlspecialchars($item_query)
                        )
                        . '<br><br>'
                        . __('MySQL said: ') . $this->dbi->getError();
                    } else {
                        $message = Message::success(
                            __('Event %1$s has been created.')
                        );
                        $message->addParam(
                            Util::backquote($_POST['item_name'])
                        );
                        $sql_query = $item_query;
                    }
                }
            }

            if (count($errors)) {
                $message = Message::error(
                    '<b>'
                    . __(
                        'One or more errors have occurred while processing your request:'
                    )
                    . '</b>'
                );
                $message->addHtml('<ul>');
                foreach ($errors as $string) {
                    $message->addHtml('<li>' . $string . '</li>');
                }
                $message->addHtml('</ul>');
            }

            $output = Generator::getMessage($message, $sql_query);

            if ($this->response->isAjax()) {
                if ($message->isSuccess()) {
                    $events = $this->dbi->getEvents($db, $_POST['item_name']);
                    $event = $events[0];
                    $this->response->addJSON(
                        'name',
                        htmlspecialchars(
                            mb_strtoupper($_POST['item_name'])
                        )
                    );
                    if (! empty($event)) {
                        $sqlDrop = sprintf(
                            'DROP EVENT IF EXISTS %s',
                            Util::backquote($event['name'])
                        );
                        $this->response->addJSON(
                            'new_row',
                            $this->template->render('rte/events/row', [
                                'db' => $db,
                                'table' => $table,
                                'event' => $event,
                                'has_privilege' => Util::currentUserHasPrivilege('EVENT', $db),
                                'sql_drop' => $sqlDrop,
                                'row_class' => '',
                            ])
                        );
                    }
                    $this->response->addJSON('insert', ! empty($event));
                    $this->response->addJSON('message', $output);
                } else {
                    $this->response->setRequestStatus(false);
                    $this->response->addJSON('message', $message);
                }
                exit;
            }
        }
        /**
         * Display a form used to add/edit a trigger, if necessary
         */
        if (count($errors)
            || (empty($_POST['editor_process_add'])
            && empty($_POST['editor_process_edit'])
            && (! empty($_REQUEST['add_item'])
            || ! empty($_REQUEST['edit_item'])
            || ! empty($_POST['item_changetype'])))
        ) { // FIXME: this must be simpler than that
            $operation = '';
            $title = '';
            $item = null;
            $mode = '';
            if (! empty($_POST['item_changetype'])) {
                $operation = 'change';
            }
            // Get the data for the form (if any)
            if (! empty($_REQUEST['add_item'])) {
                $title = __('Add event');
                $item = $this->getDataFromRequest();
                $mode = 'add';
            } elseif (! empty($_REQUEST['edit_item'])) {
                $title = __('Edit event');
                if (! empty($_REQUEST['item_name'])
                    && empty($_POST['editor_process_edit'])
                    && empty($_POST['item_changetype'])
                ) {
                    $item = $this->getDataFromName($_REQUEST['item_name']);
                    if ($item !== false) {
                        $item['item_original_name'] = $item['item_name'];
                    }
                } else {
                    $item = $this->getDataFromRequest();
                }
                $mode = 'edit';
            }
            $this->sendEditor($mode, $item, $title, $db, $operation);
        }
    }

    /**
     * This function will generate the values that are required to for the editor
     *
     * @return array    Data necessary to create the editor.
     */
    public function getDataFromRequest()
    {
        $retval = [];
        $indices = [
            'item_name',
            'item_original_name',
            'item_status',
            'item_execute_at',
            'item_interval_value',
            'item_interval_field',
            'item_starts',
            'item_ends',
            'item_definition',
            'item_preserve',
            'item_comment',
            'item_definer',
        ];
        foreach ($indices as $index) {
            $retval[$index] = $_POST[$index] ?? '';
        }
        $retval['item_type']        = 'ONE TIME';
        $retval['item_type_toggle'] = 'RECURRING';
        if (isset($_POST['item_type']) && $_POST['item_type'] == 'RECURRING') {
            $retval['item_type']        = 'RECURRING';
            $retval['item_type_toggle'] = 'ONE TIME';
        }
        return $retval;
    }

    /**
     * This function will generate the values that are required to complete
     * the "Edit event" form given the name of a event.
     *
     * @param string $name The name of the event.
     *
     * @return array|bool Data necessary to create the editor.
     */
    public function getDataFromName($name)
    {
        global $db;

        $retval = [];
        $columns = '`EVENT_NAME`, `STATUS`, `EVENT_TYPE`, `EXECUTE_AT`, '
                 . '`INTERVAL_VALUE`, `INTERVAL_FIELD`, `STARTS`, `ENDS`, '
                 . '`EVENT_DEFINITION`, `ON_COMPLETION`, `DEFINER`, `EVENT_COMMENT`';
        $where   = 'EVENT_SCHEMA ' . Util::getCollateForIS() . '='
                 . "'" . $this->dbi->escapeString($db) . "' "
                 . "AND EVENT_NAME='" . $this->dbi->escapeString($name) . "'";
        $query   = 'SELECT ' . $columns . ' FROM `INFORMATION_SCHEMA`.`EVENTS` WHERE ' . $where . ';';
        $item    = $this->dbi->fetchSingleRow($query);
        if (! $item) {
            return false;
        }
        $retval['item_name']   = $item['EVENT_NAME'];
        $retval['item_status'] = $item['STATUS'];
        $retval['item_type']   = $item['EVENT_TYPE'];
        if ($retval['item_type'] == 'RECURRING') {
            $retval['item_type_toggle'] = 'ONE TIME';
        } else {
            $retval['item_type_toggle'] = 'RECURRING';
        }
        $retval['item_execute_at']     = $item['EXECUTE_AT'];
        $retval['item_interval_value'] = $item['INTERVAL_VALUE'];
        $retval['item_interval_field'] = $item['INTERVAL_FIELD'];
        $retval['item_starts']         = $item['STARTS'];
        $retval['item_ends']           = $item['ENDS'];
        $retval['item_preserve']       = '';
        if ($item['ON_COMPLETION'] == 'PRESERVE') {
            $retval['item_preserve']   = " checked='checked'";
        }
        $retval['item_definition'] = $item['EVENT_DEFINITION'];
        $retval['item_definer']    = $item['DEFINER'];
        $retval['item_comment']    = $item['EVENT_COMMENT'];

        return $retval;
    }

    /**
     * Displays a form used to add/edit an event
     *
     * @param string $mode      If the editor will be used to edit an event
     *                          or add a new one: 'edit' or 'add'.
     * @param string $operation If the editor was previously invoked with
     *                          JS turned off, this will hold the name of
     *                          the current operation
     * @param array  $item      Data for the event returned by
     *                          getDataFromRequest() or getDataFromName()
     *
     * @return string   HTML code for the editor.
     */
    public function getEditorForm($mode, $operation, array $item)
    {
        global $db, $table, $event_status, $event_type, $event_interval;

        $modeToUpper = mb_strtoupper($mode);

        // Escape special characters
        $need_escape = [
            'item_original_name',
            'item_name',
            'item_type',
            'item_execute_at',
            'item_interval_value',
            'item_starts',
            'item_ends',
            'item_definition',
            'item_definer',
            'item_comment',
        ];
        foreach ($need_escape as $index) {
            $item[$index] = htmlentities((string) $item[$index], ENT_QUOTES);
        }
        $original_data = '';
        if ($mode == 'edit') {
            $original_data = "<input name='item_original_name' "
                           . "type='hidden' value='" . $item['item_original_name'] . "'>\n";
        }
        // Handle some logic first
        if ($operation == 'change') {
            if ($item['item_type'] == 'RECURRING') {
                $item['item_type']         = 'ONE TIME';
                $item['item_type_toggle']  = 'RECURRING';
            } else {
                $item['item_type']         = 'RECURRING';
                $item['item_type_toggle']  = 'ONE TIME';
            }
        }
        if ($item['item_type'] == 'ONE TIME') {
            $isrecurring_class = ' hide';
            $isonetime_class   = '';
        } else {
            $isrecurring_class = '';
            $isonetime_class   = ' hide';
        }
        // Create the output
        $retval  = '';
        $retval .= '<!-- START ' . $modeToUpper . " EVENT FORM -->\n\n";
        $retval .= '<form class="rte_form" action="' . Url::getFromRoute('/database/events') . '" method="post">' . "\n";
        $retval .= "<input name='" . $mode . "_item' type='hidden' value='1'>\n";
        $retval .= $original_data;
        $retval .= Url::getHiddenInputs($db, $table) . "\n";
        $retval .= "<fieldset>\n";
        $retval .= '<legend>' . __('Details') . "</legend>\n";
        $retval .= "<table class='rte_table'>\n";
        $retval .= "<tr>\n";
        $retval .= '    <td>' . __('Event name') . "</td>\n";
        $retval .= "    <td><input type='text' name='item_name' \n";
        $retval .= "               value='" . $item['item_name'] . "'\n";
        $retval .= "               maxlength='64'></td>\n";
        $retval .= "</tr>\n";
        $retval .= "<tr>\n";
        $retval .= '    <td>' . __('Status') . "</td>\n";
        $retval .= "    <td>\n";
        $retval .= "        <select name='item_status'>\n";
        foreach ($event_status['display'] as $key => $value) {
            $selected = '';
            if (! empty($item['item_status']) && $item['item_status'] == $value) {
                $selected = " selected='selected'";
            }
            $retval .= '<option' . $selected . '>' . $value . '</option>';
        }
        $retval .= "        </select>\n";
        $retval .= "    </td>\n";
        $retval .= "</tr>\n";

        $retval .= "<tr>\n";
        $retval .= '    <td>' . __('Event type') . "</td>\n";
        $retval .= "    <td>\n";
        if ($this->response->isAjax()) {
            $retval .= "        <select name='item_type'>";
            foreach ($event_type as $key => $value) {
                $selected = '';
                if (! empty($item['item_type']) && $item['item_type'] == $value) {
                    $selected = " selected='selected'";
                }
                $retval .= '<option' . $selected . '>' . $value . '</option>';
            }
            $retval .= "        </select>\n";
        } else {
            $retval .= "        <input name='item_type' type='hidden' \n";
            $retval .= "               value='" . $item['item_type'] . "'>\n";
            $retval .= "        <div class='font_weight_bold text-center w-50'>\n";
            $retval .= '            ' . $item['item_type'] . "\n";
            $retval .= "        </div>\n";
            $retval .= "        <input type='submit'\n";
            $retval .= "               name='item_changetype' class='w-50'\n";
            $retval .= "               value='";
            $retval .= sprintf(__('Change to %s'), $item['item_type_toggle']);
            $retval .= "'>\n";
        }
        $retval .= "    </td>\n";
        $retval .= "</tr>\n";
        $retval .= "<tr class='onetime_event_row " . $isonetime_class . "'>\n";
        $retval .= '    <td>' . __('Execute at') . "</td>\n";
        $retval .= "    <td class='nowrap'>\n";
        $retval .= "        <input type='text' name='item_execute_at'\n";
        $retval .= "               value='" . $item['item_execute_at'] . "'\n";
        $retval .= "               class='datetimefield'>\n";
        $retval .= "    </td>\n";
        $retval .= "</tr>\n";
        $retval .= "<tr class='recurring_event_row " . $isrecurring_class . "'>\n";
        $retval .= '    <td>' . __('Execute every') . "</td>\n";
        $retval .= "    <td>\n";
        $retval .= "        <input class='w-50' type='text'\n";
        $retval .= "               name='item_interval_value'\n";
        $retval .= "               value='" . $item['item_interval_value'] . "'>\n";
        $retval .= "        <select class='w-50' name='item_interval_field'>";
        foreach ($event_interval as $key => $value) {
            $selected = '';
            if (! empty($item['item_interval_field'])
                && $item['item_interval_field'] == $value
            ) {
                $selected = " selected='selected'";
            }
            $retval .= '<option' . $selected . '>' . $value . '</option>';
        }
        $retval .= "        </select>\n";
        $retval .= "    </td>\n";
        $retval .= "</tr>\n";
        $retval .= "<tr class='recurring_event_row" . $isrecurring_class . "'>\n";
        $retval .= '    <td>' . _pgettext('Start of recurring event', 'Start');
        $retval .= "    </td>\n";
        $retval .= "    <td class='nowrap'>\n";
        $retval .= "        <input type='text'\n name='item_starts'\n";
        $retval .= "               value='" . $item['item_starts'] . "'\n";
        $retval .= "               class='datetimefield'>\n";
        $retval .= "    </td>\n";
        $retval .= "</tr>\n";
        $retval .= "<tr class='recurring_event_row" . $isrecurring_class . "'>\n";
        $retval .= '    <td>' . _pgettext('End of recurring event', 'End') . "</td>\n";
        $retval .= "    <td class='nowrap'>\n";
        $retval .= "        <input type='text' name='item_ends'\n";
        $retval .= "               value='" . $item['item_ends'] . "'\n";
        $retval .= "               class='datetimefield'>\n";
        $retval .= "    </td>\n";
        $retval .= "</tr>\n";
        $retval .= "<tr>\n";
        $retval .= '    <td>' . __('Definition') . "</td>\n";
        $retval .= "    <td><textarea name='item_definition' rows='15' cols='40'>";
        $retval .= $item['item_definition'];
        $retval .= "</textarea></td>\n";
        $retval .= "</tr>\n";
        $retval .= "<tr>\n";
        $retval .= '    <td>' . __('On completion preserve') . "</td>\n";
        $retval .= "    <td><input type='checkbox'\n";
        $retval .= "             name='item_preserve'" . $item['item_preserve'] . "></td>\n";
        $retval .= "</tr>\n";
        $retval .= "<tr>\n";
        $retval .= '    <td>' . __('Definer') . "</td>\n";
        $retval .= "    <td><input type='text' name='item_definer'\n";
        $retval .= "               value='" . $item['item_definer'] . "'></td>\n";
        $retval .= "</tr>\n";
        $retval .= "<tr>\n";
        $retval .= '    <td>' . __('Comment') . "</td>\n";
        $retval .= "    <td><input type='text' name='item_comment' maxlength='64'\n";
        $retval .= "               value='" . $item['item_comment'] . "'></td>\n";
        $retval .= "</tr>\n";
        $retval .= "</table>\n";
        $retval .= "</fieldset>\n";
        if ($this->response->isAjax()) {
            $retval .= "<input type='hidden' name='editor_process_" . $mode . "'\n";
            $retval .= "       value='true'>\n";
            $retval .= "<input type='hidden' name='ajax_request' value='true'>\n";
        } else {
            $retval .= "<fieldset class='tblFooters'>\n";
            $retval .= "    <input type='submit' name='editor_process_" . $mode . "'\n";
            $retval .= "           value='" . __('Go') . "'>\n";
            $retval .= "</fieldset>\n";
        }
        $retval .= "</form>\n\n";
        $retval .= '<!-- END ' . $modeToUpper . " EVENT FORM -->\n\n";

        return $retval;
    }

    /**
     * Composes the query necessary to create an event from an HTTP request.
     *
     * @return string  The CREATE EVENT query.
     */
    public function getQueryFromRequest()
    {
        global $errors, $event_status, $event_type, $event_interval;

        $query = 'CREATE ';
        if (! empty($_POST['item_definer'])) {
            if (mb_strpos($_POST['item_definer'], '@') !== false
            ) {
                $arr = explode('@', $_POST['item_definer']);
                $query .= 'DEFINER=' . Util::backquote($arr[0]);
                $query .= '@' . Util::backquote($arr[1]) . ' ';
            } else {
                $errors[] = __('The definer must be in the "username@hostname" format!');
            }
        }
        $query .= 'EVENT ';
        if (! empty($_POST['item_name'])) {
            $query .= Util::backquote($_POST['item_name']) . ' ';
        } else {
            $errors[] = __('You must provide an event name!');
        }
        $query .= 'ON SCHEDULE ';
        if (! empty($_POST['item_type'])
            && in_array($_POST['item_type'], $event_type)
        ) {
            if ($_POST['item_type'] == 'RECURRING') {
                if (! empty($_POST['item_interval_value'])
                    && ! empty($_POST['item_interval_field'])
                    && in_array($_POST['item_interval_field'], $event_interval)
                ) {
                    $query .= 'EVERY ' . intval($_POST['item_interval_value']) . ' ';
                    $query .= $_POST['item_interval_field'] . ' ';
                } else {
                    $errors[]
                        = __('You must provide a valid interval value for the event.');
                }
                if (! empty($_POST['item_starts'])) {
                    $query .= "STARTS '"
                        . $this->dbi->escapeString($_POST['item_starts'])
                        . "' ";
                }
                if (! empty($_POST['item_ends'])) {
                    $query .= "ENDS '"
                        . $this->dbi->escapeString($_POST['item_ends'])
                        . "' ";
                }
            } else {
                if (! empty($_POST['item_execute_at'])) {
                    $query .= "AT '"
                        . $this->dbi->escapeString($_POST['item_execute_at'])
                        . "' ";
                } else {
                    $errors[]
                        = __('You must provide a valid execution time for the event.');
                }
            }
        } else {
            $errors[] = __('You must provide a valid type for the event.');
        }
        $query .= 'ON COMPLETION ';
        if (empty($_POST['item_preserve'])) {
            $query .= 'NOT ';
        }
        $query .= 'PRESERVE ';
        if (! empty($_POST['item_status'])) {
            foreach ($event_status['display'] as $key => $value) {
                if ($value == $_POST['item_status']) {
                    $query .= $event_status['query'][$key] . ' ';
                    break;
                }
            }
        }
        if (! empty($_POST['item_comment'])) {
            $query .= "COMMENT '" . $this->dbi->escapeString(
                $_POST['item_comment']
            ) . "' ";
        }
        $query .= 'DO ';
        if (! empty($_POST['item_definition'])) {
            $query .= $_POST['item_definition'];
        } else {
            $errors[] = __('You must provide an event definition.');
        }

        return $query;
    }

    private function getFooterToggleButton(): string
    {
        global $db, $table;

        $es_state = $this->dbi->fetchValue(
            "SHOW GLOBAL VARIABLES LIKE 'event_scheduler'",
            0,
            1
        );
        $es_state = mb_strtolower($es_state);
        $options = [
            0 => [
                'label' => __('OFF'),
                'value' => 'SET GLOBAL event_scheduler="OFF"',
                'selected' => $es_state != 'on',
            ],
            1 => [
                'label' => __('ON'),
                'value' => 'SET GLOBAL event_scheduler="ON"',
                'selected' => $es_state == 'on',
            ],
        ];

        return Generator::toggleButton(
            Url::getFromRoute(
                '/sql',
                [
                    'db' => $db,
                    'table' => $table,
                    'goto' => Url::getFromRoute('/database/events', ['db' => $db]),
                ]
            ),
            'sql_query',
            $options,
            'Functions.slidingMessage(data.sql_query);'
        );
    }

    /**
     * @param resource|bool $result          Query result
     * @param string|null   $createStatement Query
     * @param array         $errors          Errors
     *
     * @return array
     */
    private function checkResult($result, $createStatement, array $errors)
    {
        if ($result) {
            return $errors;
        }

        // OMG, this is really bad! We dropped the query,
        // failed to create a new one
        // and now even the backup query does not execute!
        // This should not happen, but we better handle
        // this just in case.
        $errors[] = __('Sorry, we failed to restore the dropped event.') . '<br>'
            . __('The backed up query was:')
            . '"' . htmlspecialchars((string) $createStatement) . '"<br>'
            . __('MySQL said: ') . $this->dbi->getError();

        return $errors;
    }

    /**
     * Send editor via ajax or by echoing.
     *
     * @param string      $mode      Editor mode 'add' or 'edit'
     * @param array|false $item      Data necessary to create the editor
     * @param string      $title     Title of the editor
     * @param string      $db        Database
     * @param string      $operation Operation 'change' or ''
     *
     * @return void
     */
    private function sendEditor($mode, $item, $title, $db, $operation)
    {
        if ($item !== false) {
            $editor = $this->getEditorForm($mode, $operation, $item);
            if ($this->response->isAjax()) {
                $this->response->addJSON('message', $editor);
                $this->response->addJSON('title', $title);
            } else {
                echo "\n\n<h2>" . $title . "</h2>\n\n" . $editor;
                unset($_POST);
            }
            exit;
        } else {
            $message  = __('Error in processing request:') . ' ';
            $message .= sprintf(
                __('No event with name %1$s found in database %2$s.'),
                htmlspecialchars(Util::backquote($_REQUEST['item_name'])),
                htmlspecialchars(Util::backquote($db))
            );
            $message = Message::error($message);
            if ($this->response->isAjax()) {
                $this->response->setRequestStatus(false);
                $this->response->addJSON('message', $message);
                exit;
            } else {
                $message->display();
            }
        }
    }

    private function export(): void
    {
        global $db;

        if (empty($_GET['export_item']) || empty($_GET['item_name'])) {
            return;
        }

        $itemName = $_GET['item_name'];
        $exportData = $this->dbi->getDefinition($db, 'EVENT', $itemName);

        if (! $exportData) {
            $exportData = false;
        }

        $itemName = htmlspecialchars(Util::backquote($_GET['item_name']));
        if ($exportData !== false) {
            $exportData = htmlspecialchars(trim($exportData));
            $title = sprintf(__('Export of event %s'), $itemName);

            if ($this->response->isAjax()) {
                $this->response->addJSON('message', $exportData);
                $this->response->addJSON('title', $title);

                exit;
            }

            $exportData = '<textarea cols="40" rows="15" style="width: 100%;">'
                . $exportData . '</textarea>';
            echo "<fieldset>\n" . '<legend>' . $title . "</legend>\n"
                . $exportData . "</fieldset>\n";

            return;
        }

        $message = sprintf(
            __('Error in processing request: No event with name %1$s found in database %2$s.'),
            $itemName,
            htmlspecialchars(Util::backquote($db))
        );
        $message = Message::error($message);

        if ($this->response->isAjax()) {
            $this->response->setRequestStatus(false);
            $this->response->addJSON('message', $message);

            exit;
        }

        $message->display();
    }
}
