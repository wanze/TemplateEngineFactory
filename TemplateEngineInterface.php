<?php

/**
 * Contract for all implemented template engines.
 *
 * @author Stefan Wanzenried <stefan.wanzenried@gmail.com>
 * @license http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License, version 2
 *
 */
interface TemplateEngineInterface
{

    /**
     * Init the template engine, e.g. create and initialize object.
     */
    public function initEngine();


    /**
     * Set a key/value pair to the template engine.
     *
     * @param string $key
     * @param mixed $value
     */
    public function set($key, $value);


    /**
     * Render markup from template engine.
     *
     * @return string
     */
    public function render();

}