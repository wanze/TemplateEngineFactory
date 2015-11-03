<?php
require_once('TemplateEngine.php');

/**
 * TemplateEngineNull
 *
 * @author Stefan Wanzenried <stefan.wanzenried@gmail.com>
 *
 */

class TemplateEngineNull extends TemplateEngine
{


    public function __construct() {}


    /**
     * Initialize module
     */
    public function initEngine() {}


    /**
     * Set a key/value pair to the template
     *
     * @param $key
     * @param $value
     */
    public function set($key, $value) {}


    /**
     * Render markup from template file
     *
     * @return mixed
     */
    public function render()
    {
        return '';
    }

}