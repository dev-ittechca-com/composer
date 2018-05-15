<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * User preferences form
 *
 * @package PhpMyAdmin
 */
declare(strict_types=1);

namespace PhpMyAdmin\Config\Forms\Page;

use PhpMyAdmin\Config\Forms\BaseForm;
use PhpMyAdmin\Config\Forms\User\MainForm;

class BrowseForm extends BaseForm
{
    public static function getForms()
    {
        return [
            'Browse' => MainForm::getForms()['Browse']
        ];
    }
}
