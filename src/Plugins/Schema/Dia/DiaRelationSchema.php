<?php
/**
 * Classes to create relation schema in Dia format.
 */

declare(strict_types=1);

namespace PhpMyAdmin\Plugins\Schema\Dia;

use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\Identifiers\DatabaseName;
use PhpMyAdmin\Plugins\Schema\ExportRelationSchema;

use function in_array;

/**
 * Dia Relation Schema Class
 *
 * Purpose of this class is to generate the Dia XML Document
 * which is used for representing the database diagrams in Dia IDE
 * This class uses Database Table and Reference Objects of Dia and with
 * the combination of these objects actually helps in preparing Dia XML.
 *
 * Dia XML is generated by using XMLWriter php extension and this class
 * inherits ExportRelationSchema class has common functionality added
 * to this class
 */
class DiaRelationSchema extends ExportRelationSchema
{
    /** @var TableStatsDia[] */
    private array $tables = [];

    /** @var RelationStatsDia[] Relations */
    private array $relations = [];

    private float $topMargin = 2.8222000598907471;

    private float $bottomMargin = 2.8222000598907471;

    private float $leftMargin = 2.8222000598907471;

    private float $rightMargin = 2.8222000598907471;

    public static int $objectId = 0;

    private Dia $dia;

    /**
     * Upon instantiation This outputs the Dia XML document
     * that user can download
     *
     * @see Dia
     * @see TableStatsDia
     * @see RelationStatsDia
     */
    public function __construct(Relation $relation, DatabaseName $db)
    {
        parent::__construct($relation, $db);

        $this->dia = new Dia();

        $this->setShowColor(isset($_REQUEST['dia_show_color']));
        $this->setShowKeys(isset($_REQUEST['dia_show_keys']));
        $this->setOrientation((string) $_REQUEST['dia_orientation']);
        $this->setPaper((string) $_REQUEST['dia_paper']);

        $this->dia->startDiaDoc(
            $this->paper,
            $this->topMargin,
            $this->bottomMargin,
            $this->leftMargin,
            $this->rightMargin,
            $this->orientation,
        );

        $alltables = $this->getTablesFromRequest();

        foreach ($alltables as $table) {
            if (isset($this->tables[$table])) {
                continue;
            }

            $this->tables[$table] = new TableStatsDia(
                $this->dia,
                $this->db->getName(),
                $table,
                $this->pageNumber,
                $this->showKeys,
                $this->offline,
            );
        }

        $seenARelation = false;
        foreach ($alltables as $oneTable) {
            $existRel = $this->relation->getForeignersInternal($this->db->getName(), $oneTable);

            $seenARelation = true;
            foreach ($existRel as $masterField => $rel) {
                // put the foreign table on the schema only if selected by the user
                if (! in_array($rel['foreign_table'], $alltables, true)) {
                    continue;
                }

                $this->addRelation(
                    $oneTable,
                    $masterField,
                    $rel['foreign_table'],
                    $rel['foreign_field'],
                    $this->showKeys,
                );
            }

            foreach ($this->relation->getForeignKeysData($this->db->getName(), $oneTable) as $oneKey) {
                if (! in_array($oneKey->refTableName, $alltables, true)) {
                    continue;
                }

                foreach ($oneKey->indexList as $index => $oneField) {
                    $this->addRelation(
                        $oneTable,
                        $oneField,
                        $oneKey->refTableName,
                        $oneKey->refIndexList[$index],
                        $this->showKeys,
                    );
                }
            }
        }

        $this->drawTables();

        if ($seenARelation) {
            $this->drawRelations();
        }

        $this->dia->endDiaDoc();
    }

    /** @return array{fileName: non-empty-string, fileData: string} */
    public function getExportInfo(): array
    {
        return ['fileName' => $this->getFileName('.dia'), 'fileData' => $this->dia->getOutputData()];
    }

    /**
     * Defines relation objects
     *
     * @see    TableStatsDia::__construct(),RelationStatsDia::__construct()
     *
     * @param string $masterTable  The master table name
     * @param string $masterField  The relation field in the master table
     * @param string $foreignTable The foreign table name
     * @param string $foreignField The relation field in the foreign table
     * @param bool   $showKeys     Whether to display ONLY keys or not
     */
    private function addRelation(
        string $masterTable,
        string $masterField,
        string $foreignTable,
        string $foreignField,
        bool $showKeys,
    ): void {
        if (! isset($this->tables[$masterTable])) {
            $this->tables[$masterTable] = new TableStatsDia(
                $this->dia,
                $this->db->getName(),
                $masterTable,
                $this->pageNumber,
                $showKeys,
            );
        }

        if (! isset($this->tables[$foreignTable])) {
            $this->tables[$foreignTable] = new TableStatsDia(
                $this->dia,
                $this->db->getName(),
                $foreignTable,
                $this->pageNumber,
                $showKeys,
            );
        }

        $this->relations[] = new RelationStatsDia(
            $this->dia,
            $this->tables[$masterTable],
            $masterField,
            $this->tables[$foreignTable],
            $foreignField,
        );
    }

    /**
     * Draws relation references
     *
     * connects master table's master field to
     * foreign table's foreign field using Dia object
     * type Database - Reference
     *
     * @see    RelationStatsDia::relationDraw()
     */
    private function drawRelations(): void
    {
        foreach ($this->relations as $relation) {
            $relation->relationDraw($this->showColor);
        }
    }

    /**
     * Draws tables
     *
     * Tables are generated using Dia object type Database - Table
     * primary fields are underlined and bold in tables
     *
     * @see    TableStatsDia::tableDraw()
     */
    private function drawTables(): void
    {
        foreach ($this->tables as $table) {
            $table->tableDraw($this->showColor);
        }
    }
}
