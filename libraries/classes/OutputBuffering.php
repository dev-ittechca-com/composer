<?php
/**
 * Output buffering wrapper
 */

declare(strict_types=1);

namespace PhpMyAdmin;

use function defined;
use function flush;
use function function_exists;
use function header;
use function ini_get;
use function ob_end_clean;
use function ob_flush;
use function ob_get_contents;
use function ob_get_length;
use function ob_get_level;
use function ob_get_status;
use function ob_start;
use function register_shutdown_function;

/**
 * Output buffering wrapper class
 */
class OutputBuffering
{
    private static $_instance;
    private $_mode;
    private $_content;

    /** @var bool */
    private $_on;

    /**
     * Initializes class
     */
    private function __construct()
    {
        $this->_mode = $this->_getMode();
        $this->_on = false;
    }

    /**
     * This function could be used eventually to support more modes.
     *
     * @return int the output buffer mode
     */
    private function _getMode()
    {
        $mode = 0;
        if ($GLOBALS['cfg']['OBGzip'] && function_exists('ob_start')) {
            if (ini_get('output_handler') == 'ob_gzhandler') {
                // If a user sets the output_handler in php.ini to ob_gzhandler, then
                // any right frame file in phpMyAdmin will not be handled properly by
                // the browser. My fix was to check the ini file within the
                // PMA_outBufferModeGet() function.
                $mode = 0;
            } elseif (function_exists('ob_get_level') && ob_get_level() > 0) {
                // happens when php.ini's output_buffering is not Off
                ob_end_clean();
                $mode = 1;
            } else {
                $mode = 1;
            }
        }

        // Zero (0) is no mode or in other words output buffering is OFF.
        // Follow 2^0, 2^1, 2^2, 2^3 type values for the modes.
        // Useful if we ever decide to combine modes.  Then a bitmask field of
        // the sum of all modes will be the natural choice.
        return $mode;
    }

    /**
     * Returns the singleton OutputBuffering object
     *
     * @return OutputBuffering object
     */
    public static function getInstance()
    {
        if (empty(self::$_instance)) {
            self::$_instance = new OutputBuffering();
        }

        return self::$_instance;
    }

    /**
     * This function will need to run at the top of all pages if output
     * output buffering is turned on.  It also needs to be passed $mode from
     * the PMA_outBufferModeGet() function or it will be useless.
     *
     * @return void
     */
    public function start()
    {
        if ($this->_on) {
            return;
        }

        if ($this->_mode && function_exists('ob_gzhandler')) {
            ob_start('ob_gzhandler');
        }
        ob_start();
        if (! defined('TESTSUITE')) {
            header('X-ob_mode: ' . $this->_mode);
        }
        register_shutdown_function(
            [
                self::class,
                'stop',
            ]
        );
        $this->_on = true;
    }

    /**
     * This function will need to run at the bottom of all pages if output
     * buffering is turned on.  It also needs to be passed $mode from the
     * PMA_outBufferModeGet() function or it will be useless.
     *
     * @return void
     */
    public static function stop()
    {
        $buffer = self::getInstance();
        if (! $buffer->_on) {
            return;
        }

        $buffer->_on = false;
        $buffer->_content = ob_get_contents();
        if (ob_get_length() <= 0) {
            return;
        }

        ob_end_clean();
    }

    /**
     * Gets buffer content
     *
     * @return string buffer content
     */
    public function getContents()
    {
        return $this->_content;
    }

    /**
     * Flushes output buffer
     *
     * @return void
     */
    public function flush()
    {
        if (ob_get_status() && $this->_mode) {
            ob_flush();
        } else {
            flush();
        }
    }
}
