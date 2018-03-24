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

    /**
     * @inheritdoc
     */
    public function initEngine() {}


    /**
     * @inheritdoc
     */
    public function set($key, $value) {}


    /**
     * @inheritdoc
     */
    public function setMultiple($pairs = array()) {}


    /**
     * @inheritdoc
     */
    public function render()
    {
        return '';
    }


    /**
     * @inheritdoc
     */
    protected function initConfig() {}

}
