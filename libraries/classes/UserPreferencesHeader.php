<?php
/**
 * Holds the PhpMyAdmin\UserPreferencesHeader class
 */
declare(strict_types=1);

namespace PhpMyAdmin;

use PhpMyAdmin\Html\Generator;
use Throwable;
use Twig_Error_Loader;
use Twig_Error_Runtime;
use Twig_Error_Syntax;
use function sprintf;

/**
 * Functions for displaying user preferences header
 */
class UserPreferencesHeader
{
    /**
     * Get HTML content
     *
     * @param Template $template Template object used to render data
     * @param Relation $relation Relation object
     *
     * @throws Throwable
     * @throws Twig_Error_Loader
     * @throws Twig_Error_Runtime
     * @throws Twig_Error_Syntax
     */
    public static function getContent(Template $template, Relation $relation): string
    {
        return self::displayTabs($template)
            . self::displayConfigurationSavedMessage()
            . self::sessionStorageWarning($relation);
    }

    /**
     * @param Template $template Template object used to render data
     *
     * @throws Throwable
     * @throws Twig_Error_Loader
     * @throws Twig_Error_Runtime
     * @throws Twig_Error_Syntax
     */
    protected static function displayTabs(Template $template): string
    {
        global $route;

        // build user preferences menu
        $content = Generator::getHtmlTab(
                [
                    'link' => 'index.php?route=/preferences/manage',
                    'text' => __('Manage your settings'),
                    'active' => $route === '/preferences/manage',
                ]
            ) . "\n";
        /* Second authentication factor */
        $content .= Generator::getHtmlTab(
                [
                    'link' => 'index.php?route=/preferences/two-factor',
                    'text' => __('Two-factor authentication'),
                    'active' => $route === '/preferences/two-factor',
                ]
            ) . "\n";

        $content .= Generator::getHtmlTab(
                [
                    'link' => 'index.php?route=/preferences/features',
                    'text' => __('Features'),
                    'icon' => 'b_tblops',
                    'active' => $route === '/preferences/features',
                ]
            ) . "\n";

        $content .= Generator::getHtmlTab(
                [
                    'link' => 'index.php?route=/preferences/sql',
                    'text' => __('SQL queries'),
                    'icon' => 'b_sql',
                    'active' => $route === '/preferences/sql',
                ]
            ) . "\n";

        $content .= Generator::getHtmlTab(
                [
                    'link' => 'index.php?route=/preferences/navigation',
                    'text' => __('Navigation panel'),
                    'icon' => 'b_select',
                    'active' => $route === '/preferences/navigation',
                ]
            ) . "\n";

        $content .= Generator::getHtmlTab(
                [
                    'link' => 'index.php?route=/preferences/main-panel',
                    'text' => __('Main panel'),
                    'icon' => 'b_props',
                    'active' => $route === '/preferences/main-panel',
                ]
            ) . "\n";

        $content .= Generator::getHtmlTab(
                [
                    'link' => 'index.php?route=/preferences/export',
                    'text' => __('Export'),
                    'icon' => 'b_export',
                    'active' => $route === '/preferences/export',
                ]
            ) . "\n";

        $content .= Generator::getHtmlTab(
                [
                    'link' => 'index.php?route=/preferences/import',
                    'text' => __('Import'),
                    'icon' => 'b_import',
                    'active' => $route === '/preferences/import',
                ]
            ) . "\n";

        return '<div class=container-fluid><div class=row>' .
        $template->render(
            'list/unordered',
            [
                'id' => 'topmenu2',
                'class' => 'user_prefs_tabs',
                'content' => $content,
            ]
        ) . '<div class="clearfloat"></div></div>';
    }

    protected static function displayConfigurationSavedMessage(): ?string
    {
        // show "configuration saved" message and reload navigation panel if needed
        if (! empty($_GET['saved'])) {
            return Message::rawSuccess(__('Configuration has been saved.'))
                ->getDisplay();
        }

        return null;
    }

    /**
     * @param Relation $relation Relation instance
     */
    protected static function sessionStorageWarning(Relation $relation): ?string
    {
        // warn about using session storage for settings
        $cfgRelation = $relation->getRelationsParam();
        if (! $cfgRelation['userconfigwork']) {
            $msg = __(
                'Your preferences will be saved for current session only. Storing them '
                . 'permanently requires %sphpMyAdmin configuration storage%s.'
            );
            $msg = Sanitize::sanitizeMessage(
                sprintf($msg, '[doc@linked-tables]', '[/doc]')
            );
            return Message::notice($msg)
                ->getDisplay();
        }

        return null;
    }
}
