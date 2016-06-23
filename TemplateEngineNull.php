<?php
require_once('TemplateEngine.php');

/**
 * TemplateEngineNull
 *
 * @author Stefan Wanzenried <stefan.wanzenried@gmail.com>
 * @license http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License, version 2
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
     * Set multiple key/value pairs to the template
     *
     * @param array $pairs
     */
    public function setMultiple($pairs = array()) {}


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
