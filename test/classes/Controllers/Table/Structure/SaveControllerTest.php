<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Controllers\Table\Structure;

use PhpMyAdmin\Controllers\Table\Structure\SaveController;
use PhpMyAdmin\Controllers\Table\StructureController;
use PhpMyAdmin\Relation;
use PhpMyAdmin\Template;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Tests\Stubs\ResponseRenderer as ResponseStub;
use PhpMyAdmin\Transformations;
use ReflectionClass;

/**
 * @covers \PhpMyAdmin\Controllers\Table\Structure\SaveController
 */
class SaveControllerTest extends AbstractTestCase
{
    public function testSaveController(): void
    {
        $GLOBALS['server'] = 1;
        $GLOBALS['text_dir'] = 'ltr';
        $GLOBALS['PMA_PHP_SELF'] = 'index.php';
        $GLOBALS['db'] = 'db';
        $GLOBALS['table'] = 'table';

        $class = new ReflectionClass(SaveController::class);
        $method = $class->getMethod('adjustColumnPrivileges');
        $method->setAccessible(true);

        $template = new Template();
        $ctrl = new SaveController(
            new ResponseStub(),
            $template,
            $GLOBALS['db'],
            $GLOBALS['table'],
            new Relation($this->dbi, $template),
            new Transformations(),
            $this->dbi,
            $this->createStub(StructureController::class)
        );

        $this->assertFalse(
            $method->invokeArgs($ctrl, [[]])
        );
    }
}
