<?php
require_once('TemplateEngine.php');

/**
 * TemplateEngineNull
 *
 * @author Stefan Wanzenried <stefan.wanzenried@gmail.com>
 *
 * ProcessWire 2.x
 * Copyright (C) 2014 by Ryan Cramer
 * Licensed under GNU/GPL v2, see LICENSE.TXT
 *
 * http://processwire.com
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