<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Controllers\Export\Template;

use PhpMyAdmin\Controllers\Export\Template\LoadController;
use PhpMyAdmin\Export\TemplateModel;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Relation;
use PhpMyAdmin\Template;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Tests\Stubs\ResponseRenderer;
use PhpMyAdmin\Version;

/**
 * @covers \PhpMyAdmin\Controllers\Export\Template\LoadController
 */
class LoadControllerTest extends AbstractTestCase
{
    public function testLoad(): void
    {
        global $cfg;

        $GLOBALS['server'] = 1;
        $GLOBALS['text_dir'] = 'ltr';
        $GLOBALS['PMA_PHP_SELF'] = 'index.php';

        $_SESSION['relation'][$GLOBALS['server']] = [
            'version' => Version::VERSION,
            'exporttemplateswork' => true,
            'trackingwork' => false,
            'db' => 'db',
            'export_templates' => 'table',
        ];

        $cfg['Server']['user'] = 'user';

        $response = new ResponseRenderer();
        $template = new Template();
        $request = $this->createStub(ServerRequest::class);
        $request->method('getParsedBodyParam')->willReturn('1');

        (new LoadController(
            $response,
            $template,
            new TemplateModel($this->dbi),
            new Relation($this->dbi, $template)
        ))($request);

        $this->assertTrue($response->hasSuccessState());
        $this->assertEquals(['data' => 'data1'], $response->getJSONResult());
    }
}
