<?php

declare(strict_types=1);

namespace PhpMyAdmin;

use function is_bool;
use function is_string;

/**
 * @psalm-immutable
 */
final class RelationParameters
{
    /** @var string */
    public $version;
    /** @var bool */
    public $relwork;
    /** @var bool */
    public $displaywork;
    /** @var bool */
    public $bookmarkwork;
    /** @var bool */
    public $pdfwork;
    /** @var bool */
    public $commwork;
    /** @var bool */
    public $mimework;
    /** @var bool */
    public $historywork;
    /** @var bool */
    public $recentwork;
    /** @var bool */
    public $favoritework;
    /** @var bool */
    public $uiprefswork;
    /** @var bool */
    public $trackingwork;
    /** @var bool */
    public $userconfigwork;
    /** @var bool */
    public $menuswork;
    /** @var bool */
    public $navwork;
    /** @var bool */
    public $savedsearcheswork;
    /** @var bool */
    public $centralcolumnswork;
    /** @var bool */
    public $designersettingswork;
    /** @var bool */
    public $exporttemplateswork;
    /** @var bool */
    public $allworks;
    /** @var string|null */
    public $user;
    /** @var string|null */
    public $db;
    /** @var string|null */
    public $bookmark;
    /** @var string|null */
    public $centralColumns;
    /** @var string|null */
    public $columnInfo;
    /** @var string|null */
    public $designerSettings;
    /** @var string|null */
    public $exportTemplates;
    /** @var string|null */
    public $favorite;
    /** @var string|null */
    public $history;
    /** @var string|null */
    public $navigationhiding;
    /** @var string|null */
    public $pdfPages;
    /** @var string|null */
    public $recent;
    /** @var string|null */
    public $relation;
    /** @var string|null */
    public $savedsearches;
    /** @var string|null */
    public $tableCoords;
    /** @var string|null */
    public $tableInfo;
    /** @var string|null */
    public $tableUiprefs;
    /** @var string|null */
    public $tracking;
    /** @var string|null */
    public $userconfig;
    /** @var string|null */
    public $usergroups;
    /** @var string|null */
    public $users;

    public function __construct(
        string $version,
        bool $relwork,
        bool $displaywork,
        bool $bookmarkwork,
        bool $pdfwork,
        bool $commwork,
        bool $mimework,
        bool $historywork,
        bool $recentwork,
        bool $favoritework,
        bool $uiprefswork,
        bool $trackingwork,
        bool $userconfigwork,
        bool $menuswork,
        bool $navwork,
        bool $savedsearcheswork,
        bool $centralcolumnswork,
        bool $designersettingswork,
        bool $exporttemplateswork,
        bool $allworks,
        ?string $user,
        ?string $db,
        ?string $bookmark,
        ?string $centralColumns,
        ?string $columnInfo,
        ?string $designerSettings,
        ?string $exportTemplates,
        ?string $favorite,
        ?string $history,
        ?string $navigationhiding,
        ?string $pdfPages,
        ?string $recent,
        ?string $relation,
        ?string $savedsearches,
        ?string $tableCoords,
        ?string $tableInfo,
        ?string $tableUiprefs,
        ?string $tracking,
        ?string $userconfig,
        ?string $usergroups,
        ?string $users
    ) {
        $this->version = $version;
        $this->relwork = $relwork;
        $this->displaywork = $displaywork;
        $this->bookmarkwork = $bookmarkwork;
        $this->pdfwork = $pdfwork;
        $this->commwork = $commwork;
        $this->mimework = $mimework;
        $this->historywork = $historywork;
        $this->recentwork = $recentwork;
        $this->favoritework = $favoritework;
        $this->uiprefswork = $uiprefswork;
        $this->trackingwork = $trackingwork;
        $this->userconfigwork = $userconfigwork;
        $this->menuswork = $menuswork;
        $this->navwork = $navwork;
        $this->savedsearcheswork = $savedsearcheswork;
        $this->centralcolumnswork = $centralcolumnswork;
        $this->designersettingswork = $designersettingswork;
        $this->exporttemplateswork = $exporttemplateswork;
        $this->allworks = $allworks;
        $this->user = $user;
        $this->db = $db;
        $this->bookmark = $bookmark;
        $this->centralColumns = $centralColumns;
        $this->columnInfo = $columnInfo;
        $this->designerSettings = $designerSettings;
        $this->exportTemplates = $exportTemplates;
        $this->favorite = $favorite;
        $this->history = $history;
        $this->navigationhiding = $navigationhiding;
        $this->pdfPages = $pdfPages;
        $this->recent = $recent;
        $this->relation = $relation;
        $this->savedsearches = $savedsearches;
        $this->tableCoords = $tableCoords;
        $this->tableInfo = $tableInfo;
        $this->tableUiprefs = $tableUiprefs;
        $this->tracking = $tracking;
        $this->userconfig = $userconfig;
        $this->usergroups = $usergroups;
        $this->users = $users;
    }

    /**
     * @param mixed[] $params
     */
    public static function fromArray(array $params): self
    {
        $version = Version::VERSION;
        if (isset($params['version']) && is_string($params['version'])) {
            $version = $params['version'];
        }

        $relwork = false;
        if (isset($params['relwork']) && is_bool($params['relwork'])) {
            $relwork = $params['relwork'];
        }

        $displaywork = false;
        if (isset($params['displaywork']) && is_bool($params['displaywork'])) {
            $displaywork = $params['displaywork'];
        }

        $bookmarkwork = false;
        if (isset($params['bookmarkwork']) && is_bool($params['bookmarkwork'])) {
            $bookmarkwork = $params['bookmarkwork'];
        }

        $pdfwork = false;
        if (isset($params['pdfwork']) && is_bool($params['pdfwork'])) {
            $pdfwork = $params['pdfwork'];
        }

        $commwork = false;
        if (isset($params['commwork']) && is_bool($params['commwork'])) {
            $commwork = $params['commwork'];
        }

        $mimework = false;
        if (isset($params['mimework']) && is_bool($params['mimework'])) {
            $mimework = $params['mimework'];
        }

        $historywork = false;
        if (isset($params['historywork']) && is_bool($params['historywork'])) {
            $historywork = $params['historywork'];
        }

        $recentwork = false;
        if (isset($params['recentwork']) && is_bool($params['recentwork'])) {
            $recentwork = $params['recentwork'];
        }

        $favoritework = false;
        if (isset($params['favoritework']) && is_bool($params['favoritework'])) {
            $favoritework = $params['favoritework'];
        }

        $uiprefswork = false;
        if (isset($params['uiprefswork']) && is_bool($params['uiprefswork'])) {
            $uiprefswork = $params['uiprefswork'];
        }

        $trackingwork = false;
        if (isset($params['trackingwork']) && is_bool($params['trackingwork'])) {
            $trackingwork = $params['trackingwork'];
        }

        $userconfigwork = false;
        if (isset($params['userconfigwork']) && is_bool($params['userconfigwork'])) {
            $userconfigwork = $params['userconfigwork'];
        }

        $menuswork = false;
        if (isset($params['menuswork']) && is_bool($params['menuswork'])) {
            $menuswork = $params['menuswork'];
        }

        $navwork = false;
        if (isset($params['navwork']) && is_bool($params['navwork'])) {
            $navwork = $params['navwork'];
        }

        $savedsearcheswork = false;
        if (isset($params['savedsearcheswork']) && is_bool($params['savedsearcheswork'])) {
            $savedsearcheswork = $params['savedsearcheswork'];
        }

        $centralcolumnswork = false;
        if (isset($params['centralcolumnswork']) && is_bool($params['centralcolumnswork'])) {
            $centralcolumnswork = $params['centralcolumnswork'];
        }

        $designersettingswork = false;
        if (isset($params['designersettingswork']) && is_bool($params['designersettingswork'])) {
            $designersettingswork = $params['designersettingswork'];
        }

        $exporttemplateswork = false;
        if (isset($params['exporttemplateswork']) && is_bool($params['exporttemplateswork'])) {
            $exporttemplateswork = $params['exporttemplateswork'];
        }

        $allworks = false;
        if (isset($params['allworks']) && is_bool($params['allworks'])) {
            $allworks = $params['allworks'];
        }

        $user = null;
        if (isset($params['user']) && is_string($params['user'])) {
            $user = $params['user'];
        }

        $db = null;
        if (isset($params['db']) && is_string($params['db'])) {
            $db = $params['db'];
        }

        $bookmark = null;
        if (isset($params['bookmark']) && is_string($params['bookmark'])) {
            $bookmark = $params['bookmark'];
        }

        $centralColumns = null;
        if (isset($params['central_columns']) && is_string($params['central_columns'])) {
            $centralColumns = $params['central_columns'];
        }

        $columnInfo = null;
        if (isset($params['column_info']) && is_string($params['column_info'])) {
            $columnInfo = $params['column_info'];
        }

        $designerSettings = null;
        if (isset($params['designer_settings']) && is_string($params['designer_settings'])) {
            $designerSettings = $params['designer_settings'];
        }

        $exportTemplates = null;
        if (isset($params['export_templates']) && is_string($params['export_templates'])) {
            $exportTemplates = $params['export_templates'];
        }

        $favorite = null;
        if (isset($params['favorite']) && is_string($params['favorite'])) {
            $favorite = $params['favorite'];
        }

        $history = null;
        if (isset($params['history']) && is_string($params['history'])) {
            $history = $params['history'];
        }

        $navigationhiding = null;
        if (isset($params['navigationhiding']) && is_string($params['navigationhiding'])) {
            $navigationhiding = $params['navigationhiding'];
        }

        $pdfPages = null;
        if (isset($params['pdf_pages']) && is_string($params['pdf_pages'])) {
            $pdfPages = $params['pdf_pages'];
        }

        $recent = null;
        if (isset($params['recent']) && is_string($params['recent'])) {
            $recent = $params['recent'];
        }

        $relation = null;
        if (isset($params['relation']) && is_string($params['relation'])) {
            $relation = $params['relation'];
        }

        $savedsearches = null;
        if (isset($params['savedsearches']) && is_string($params['savedsearches'])) {
            $savedsearches = $params['savedsearches'];
        }

        $tableCoords = null;
        if (isset($params['table_coords']) && is_string($params['table_coords'])) {
            $tableCoords = $params['table_coords'];
        }

        $tableInfo = null;
        if (isset($params['table_info']) && is_string($params['table_info'])) {
            $tableInfo = $params['table_info'];
        }

        $tableUiprefs = null;
        if (isset($params['table_uiprefs']) && is_string($params['table_uiprefs'])) {
            $tableUiprefs = $params['table_uiprefs'];
        }

        $tracking = null;
        if (isset($params['tracking']) && is_string($params['tracking'])) {
            $tracking = $params['tracking'];
        }

        $userconfig = null;
        if (isset($params['userconfig']) && is_string($params['userconfig'])) {
            $userconfig = $params['userconfig'];
        }

        $usergroups = null;
        if (isset($params['usergroups']) && is_string($params['usergroups'])) {
            $usergroups = $params['usergroups'];
        }

        $users = null;
        if (isset($params['users']) && is_string($params['users'])) {
            $users = $params['users'];
        }

        return new self(
            $version,
            $relwork,
            $displaywork,
            $bookmarkwork,
            $pdfwork,
            $commwork,
            $mimework,
            $historywork,
            $recentwork,
            $favoritework,
            $uiprefswork,
            $trackingwork,
            $userconfigwork,
            $menuswork,
            $navwork,
            $savedsearcheswork,
            $centralcolumnswork,
            $designersettingswork,
            $exporttemplateswork,
            $allworks,
            $user,
            $db,
            $bookmark,
            $centralColumns,
            $columnInfo,
            $designerSettings,
            $exportTemplates,
            $favorite,
            $history,
            $navigationhiding,
            $pdfPages,
            $recent,
            $relation,
            $savedsearches,
            $tableCoords,
            $tableInfo,
            $tableUiprefs,
            $tracking,
            $userconfig,
            $usergroups,
            $users
        );
    }

    /**
     * @return array<string, bool|string|null>
     * @psalm-return array{
     *   version: string,
     *   relwork: bool,
     *   displaywork: bool,
     *   bookmarkwork: bool,
     *   pdfwork: bool,
     *   commwork: bool,
     *   mimework: bool,
     *   historywork: bool,
     *   recentwork: bool,
     *   favoritework: bool,
     *   uiprefswork: bool,
     *   trackingwork: bool,
     *   userconfigwork: bool,
     *   menuswork: bool,
     *   navwork: bool,
     *   savedsearcheswork: bool,
     *   centralcolumnswork: bool,
     *   designersettingswork: bool,
     *   exporttemplateswork: bool,
     *   allworks: bool,
     *   user: string|null,
     *   db: string|null,
     *   bookmark: string|null,
     *   central_columns: string|null,
     *   column_info: string|null,
     *   designer_settings: string|null,
     *   export_templates: string|null,
     *   favorite: string|null,
     *   history: string|null,
     *   navigationhiding: string|null,
     *   pdf_pages: string|null,
     *   recent: string|null,
     *   relation: string|null,
     *   savedsearches: string|null,
     *   table_coords: string|null,
     *   table_info: string|null,
     *   table_uiprefs: string|null,
     *   tracking: string|null,
     *   userconfig: string|null,
     *   usergroups: string|null,
     *   users: string|null
     * }
     */
    public function toArray(): array
    {
        return [
            'version' => $this->version,
            'relwork' => $this->relwork,
            'displaywork' => $this->displaywork,
            'bookmarkwork' => $this->bookmarkwork,
            'pdfwork' => $this->pdfwork,
            'commwork' => $this->commwork,
            'mimework' => $this->mimework,
            'historywork' => $this->historywork,
            'recentwork' => $this->recentwork,
            'favoritework' => $this->favoritework,
            'uiprefswork' => $this->uiprefswork,
            'trackingwork' => $this->trackingwork,
            'userconfigwork' => $this->userconfigwork,
            'menuswork' => $this->menuswork,
            'navwork' => $this->navwork,
            'savedsearcheswork' => $this->savedsearcheswork,
            'centralcolumnswork' => $this->centralcolumnswork,
            'designersettingswork' => $this->designersettingswork,
            'exporttemplateswork' => $this->exporttemplateswork,
            'allworks' => $this->allworks,
            'user' => $this->user,
            'db' => $this->db,
            'bookmark' => $this->bookmark,
            'central_columns' => $this->centralColumns,
            'column_info' => $this->columnInfo,
            'designer_settings' => $this->designerSettings,
            'export_templates' => $this->exportTemplates,
            'favorite' => $this->favorite,
            'history' => $this->history,
            'navigationhiding' => $this->navigationhiding,
            'pdf_pages' => $this->pdfPages,
            'recent' => $this->recent,
            'relation' => $this->relation,
            'savedsearches' => $this->savedsearches,
            'table_coords' => $this->tableCoords,
            'table_info' => $this->tableInfo,
            'table_uiprefs' => $this->tableUiprefs,
            'tracking' => $this->tracking,
            'userconfig' => $this->userconfig,
            'usergroups' => $this->usergroups,
            'users' => $this->users,
        ];
    }
}
