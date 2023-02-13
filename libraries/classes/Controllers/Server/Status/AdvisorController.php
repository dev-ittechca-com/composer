<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Server\Status;

use PhpMyAdmin\Advisory\Advisor;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Server\Status\Data;
use PhpMyAdmin\Template;

/**
 * Displays the advisor feature
 */
class AdvisorController extends AbstractController
{
    private Advisor $advisor;

    public function __construct(ResponseRenderer $response, Template $template, Data $data, Advisor $advisor)
    {
        parent::__construct($response, $template, $data);
        $this->advisor = $advisor;
    }

    public function __invoke(ServerRequest $request): void
    {
        $data = [];
        if ($this->data->dataLoaded) {
            $data = $this->advisor->run();
        }

        $this->render('server/status/advisor/index', ['data' => $data]);
    }
}
