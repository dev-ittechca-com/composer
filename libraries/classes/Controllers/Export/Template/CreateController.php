<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Export\Template;

use PhpMyAdmin\Controllers\AbstractController;
use PhpMyAdmin\Export\Template as ExportTemplate;
use PhpMyAdmin\Export\TemplateModel;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Relation;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Template;

use function is_array;
use function is_string;

final class CreateController extends AbstractController
{
    /** @var TemplateModel */
    private $model;

    /** @var Relation */
    private $relation;

    /**
     * @param ResponseRenderer $response
     */
    public function __construct(
        $response,
        Template $template,
        TemplateModel $model,
        Relation $relation
    ) {
        parent::__construct($response, $template);
        $this->model = $model;
        $this->relation = $relation;
    }

    public function __invoke(ServerRequest $request): void
    {
        global $cfg;

        /** @var string $exportType */
        $exportType = $request->getParsedBodyParam('exportType', '');
        /** @var string $templateName */
        $templateName = $request->getParsedBodyParam('templateName', '');
        /** @var string $templateData */
        $templateData = $request->getParsedBodyParam('templateData', '');
        /** @var string|null $templateId */
        $templateId = $request->getParsedBodyParam('template_id');

        $cfgRelation = $this->relation->getRelationsParam();

        if (! $cfgRelation['exporttemplateswork']) {
            return;
        }

        $template = ExportTemplate::fromArray([
            'username' => $cfg['Server']['user'],
            'exportType' => $exportType,
            'name' => $templateName,
            'data' => $templateData,
        ]);
        $result = $this->model->create($cfgRelation['db'], $cfgRelation['export_templates'], $template);

        if (is_string($result)) {
            $this->response->setRequestStatus(false);
            $this->response->addJSON('message', $result);

            return;
        }

        $templates = $this->model->getAll(
            $cfgRelation['db'],
            $cfgRelation['export_templates'],
            $template->getUsername(),
            $template->getExportType()
        );

        $this->response->setRequestStatus(true);
        $this->response->addJSON(
            'data',
            $this->template->render('export/template_options', [
                'templates' => is_array($templates) ? $templates : [],
                'selected_template' => $templateId,
            ])
        );
    }
}
