<?php
/**
 * Saved searches managing
 */

declare(strict_types=1);

namespace PhpMyAdmin;

use PhpMyAdmin\ConfigStorage\Features\SavedQueryByExampleSearchesFeature;
use PhpMyAdmin\Dbal\Connection;
use PhpMyAdmin\Exceptions\SavedSearchesException;

use function __;
use function count;
use function intval;
use function is_string;
use function json_decode;
use function json_encode;
use function max;
use function min;

/**
 * Saved searches managing
 */
class SavedSearches
{
    /**
     * Id
     *
     * @var int|null
     */
    private $id = null;

    /**
     * Username
     *
     * @var string
     */
    private $username = null;

    /**
     * DB name
     *
     * @var string
     */
    private $dbname = null;

    /**
     * Saved search name
     *
     * @var string
     */
    private $searchName = null;

    /**
     * Criterias
     *
     * @var array|null
     */
    private $criterias = null;

    /**
     * Setter of id
     *
     * @param int|null $searchId Id of search
     *
     * @return static
     */
    public function setId($searchId)
    {
        $searchId = (int) $searchId;
        if (empty($searchId)) {
            $searchId = null;
        }

        $this->id = $searchId;

        return $this;
    }

    /**
     * Getter of id
     *
     * @return int|null
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Setter of searchName
     *
     * @param string $searchName Saved search name
     *
     * @return static
     */
    public function setSearchName($searchName)
    {
        $this->searchName = $searchName;

        return $this;
    }

    /**
     * Getter of searchName
     *
     * @return string
     */
    public function getSearchName()
    {
        return $this->searchName;
    }

    /**
     * Setter for criterias
     *
     * @param array|string $criterias Criterias of saved searches
     * @param bool         $json      Criterias are in JSON format
     *
     * @return static
     */
    public function setCriterias(array|string $criterias, $json = false)
    {
        if ($json === true && is_string($criterias)) {
            $this->criterias = json_decode($criterias, true);

            return $this;
        }

        $aListFieldsToGet = [
            'criteriaColumn',
            'criteriaSort',
            'criteriaShow',
            'criteria',
            'criteriaAndOrRow',
            'criteriaAndOrColumn',
            'rows',
            'TableList',
        ];

        $data = [];

        $data['criteriaColumnCount'] = count($criterias['criteriaColumn']);

        foreach ($aListFieldsToGet as $field) {
            if (! isset($criterias[$field])) {
                continue;
            }

            $data[$field] = $criterias[$field];
        }

        /* Limit amount of rows */
        if (! isset($data['rows'])) {
            $data['rows'] = 0;
        } else {
            $data['rows'] = min(
                max(0, intval($data['rows'])),
                100
            );
        }

        for ($i = 0; $i <= $data['rows']; $i++) {
            $data['Or' . $i] = $criterias['Or' . $i];
        }

        $this->criterias = $data;

        return $this;
    }

    /**
     * Getter for criterias
     *
     * @return array|null
     */
    public function getCriterias()
    {
        return $this->criterias;
    }

    /**
     * Setter for username
     *
     * @param string $username Username
     *
     * @return static
     */
    public function setUsername($username)
    {
        $this->username = $username;

        return $this;
    }

    /**
     * Getter for username
     *
     * @return string
     */
    public function getUsername()
    {
        return $this->username;
    }

    /**
     * Setter for DB name
     *
     * @param string $dbname DB name
     *
     * @return static
     */
    public function setDbname($dbname)
    {
        $this->dbname = $dbname;

        return $this;
    }

    /**
     * Getter for DB name
     *
     * @return string
     */
    public function getDbname()
    {
        return $this->dbname;
    }

    /**
     * Save the search
     *
     * @throws SavedSearchesException
     */
    public function save(SavedQueryByExampleSearchesFeature $savedQueryByExampleSearchesFeature): bool
    {
        if ($this->getSearchName() == null) {
            throw new SavedSearchesException(__('Please provide a name for this bookmarked search.'));
        }

        if (
            $this->getUsername() == null
            || $this->getDbname() == null
            || $this->getSearchName() == null
            || $this->getCriterias() == null
        ) {
            throw new SavedSearchesException(__('Missing information to save the bookmarked search.'));
        }

        $savedSearchesTbl = Util::backquote($savedQueryByExampleSearchesFeature->database) . '.'
            . Util::backquote($savedQueryByExampleSearchesFeature->savedSearches);

        //If it's an insert.
        if ($this->getId() === null) {
            $wheres = [
                'search_name = ' . $GLOBALS['dbi']->quoteString($this->getSearchName(), Connection::TYPE_CONTROL),
            ];
            $existingSearches = $this->getList($savedQueryByExampleSearchesFeature, $wheres);

            if (! empty($existingSearches)) {
                throw new SavedSearchesException(__('An entry with this name already exists.'));
            }

            $sqlQuery = 'INSERT INTO ' . $savedSearchesTbl
                . '(`username`, `db_name`, `search_name`, `search_data`)'
                . ' VALUES ('
                . $GLOBALS['dbi']->quoteString($this->getUsername(), Connection::TYPE_CONTROL) . ','
                . $GLOBALS['dbi']->quoteString($this->getDbname(), Connection::TYPE_CONTROL) . ','
                . $GLOBALS['dbi']->quoteString($this->getSearchName(), Connection::TYPE_CONTROL) . ','
                . $GLOBALS['dbi']->quoteString(json_encode($this->getCriterias()), Connection::TYPE_CONTROL)
                . ')';

            $GLOBALS['dbi']->queryAsControlUser($sqlQuery);

            $this->setId($GLOBALS['dbi']->insertId());

            return true;
        }

        //Else, it's an update.
        $wheres = [
            'id != ' . $this->getId(),
            'search_name = ' . $GLOBALS['dbi']->quoteString($this->getSearchName()),
        ];
        $existingSearches = $this->getList($savedQueryByExampleSearchesFeature, $wheres);

        if (! empty($existingSearches)) {
            throw new SavedSearchesException(__('An entry with this name already exists.'));
        }

        $sqlQuery = 'UPDATE ' . $savedSearchesTbl
            . 'SET `search_name` = '
            . $GLOBALS['dbi']->quoteString($this->getSearchName(), Connection::TYPE_CONTROL) . ', '
            . '`search_data` = '
            . $GLOBALS['dbi']->quoteString(json_encode($this->getCriterias()), Connection::TYPE_CONTROL) . ' '
            . 'WHERE id = ' . $this->getId();

        return (bool) $GLOBALS['dbi']->queryAsControlUser($sqlQuery);
    }

    /**
     * Delete the search
     *
     * @throws SavedSearchesException
     */
    public function delete(SavedQueryByExampleSearchesFeature $savedQueryByExampleSearchesFeature): bool
    {
        if ($this->getId() == null) {
            throw new SavedSearchesException(__('Missing information to delete the search.'));
        }

        $savedSearchesTbl = Util::backquote($savedQueryByExampleSearchesFeature->database) . '.'
            . Util::backquote($savedQueryByExampleSearchesFeature->savedSearches);

        $sqlQuery = 'DELETE FROM ' . $savedSearchesTbl
            . 'WHERE id = ' . $GLOBALS['dbi']->quoteString((string) $this->getId(), Connection::TYPE_CONTROL);

        return (bool) $GLOBALS['dbi']->queryAsControlUser($sqlQuery);
    }

    /**
     * Load the current search from an id.
     *
     * @throws SavedSearchesException
     */
    public function load(SavedQueryByExampleSearchesFeature $savedQueryByExampleSearchesFeature): bool
    {
        if ($this->getId() == null) {
            throw new SavedSearchesException(__('Missing information to load the search.'));
        }

        $savedSearchesTbl = Util::backquote($savedQueryByExampleSearchesFeature->database)
            . '.'
            . Util::backquote($savedQueryByExampleSearchesFeature->savedSearches);
        $sqlQuery = 'SELECT id, search_name, search_data '
            . 'FROM ' . $savedSearchesTbl . ' '
            . 'WHERE id = ' . $GLOBALS['dbi']->quoteString((string) $this->getId(), Connection::TYPE_CONTROL);

        $resList = $GLOBALS['dbi']->queryAsControlUser($sqlQuery);
        $oneResult = $resList->fetchAssoc();

        if ($oneResult === []) {
            throw new SavedSearchesException(__('Error while loading the search.'));
        }

        $this->setSearchName($oneResult['search_name'])
            ->setCriterias($oneResult['search_data'], true);

        return true;
    }

    /**
     * Get the list of saved searches of a user on a DB
     *
     * @param string[] $wheres List of filters
     *
     * @return array List of saved searches or empty array on failure
     */
    public function getList(SavedQueryByExampleSearchesFeature $savedQueryByExampleSearchesFeature, array $wheres = [])
    {
        if ($this->getUsername() == null || $this->getDbname() == null) {
            return [];
        }

        $savedSearchesTbl = Util::backquote($savedQueryByExampleSearchesFeature->database)
            . '.'
            . Util::backquote($savedQueryByExampleSearchesFeature->savedSearches);
        $sqlQuery = 'SELECT id, search_name '
            . 'FROM ' . $savedSearchesTbl . ' '
            . 'WHERE '
            . 'username = ' . $GLOBALS['dbi']->quoteString($this->getUsername(), Connection::TYPE_CONTROL) . ' '
            . 'AND db_name = ' . $GLOBALS['dbi']->quoteString($this->getDbname(), Connection::TYPE_CONTROL) . ' ';

        foreach ($wheres as $where) {
            $sqlQuery .= 'AND ' . $where . ' ';
        }

        $sqlQuery .= 'order by search_name ASC ';

        $resList = $GLOBALS['dbi']->queryAsControlUser($sqlQuery);

        return $resList->fetchAllKeyPair();
    }
}
