<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Table\Structure;

use PhpMyAdmin\Controllers\AbstractController;
use PhpMyAdmin\Controllers\Sql\SqlController;
use PhpMyAdmin\Controllers\Table\StructureController;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Template;

final class AddKeyController extends AbstractController
{
    private SqlController $sqlController;

    private StructureController $structureController;

    public function __construct(
        ResponseRenderer $response,
        Template $template,
        SqlController $sqlController,
        StructureController $structureController
    ) {
        parent::__construct($response, $template);
        $this->sqlController = $sqlController;
        $this->structureController = $structureController;
    }

    public function __invoke(ServerRequest $request): void
    {
        $GLOBALS['reload'] = $GLOBALS['reload'] ?? null;

        ($this->sqlController)($request);

        $GLOBALS['reload'] = true;

        ($this->structureController)($request);
    }
}
