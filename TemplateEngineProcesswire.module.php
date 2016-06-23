<?php
require_once('TemplateEngine.php');

/**
 * TemplateEngineProcesswire
 *
 * @author Stefan Wanzenried <stefan.wanzenried@gmail.com>
 * @license http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License, version 2
 * @version 1.0.1
 *
 */
class TemplateEngineProcesswire extends TemplateEngine implements Module, ConfigurableModule
{

    /**
     * @var TemplateFile
     */
    protected $template;


    /**
     * @param string $filename
     */
    public function __construct($filename = '')
    {
        parent::__construct($filename);
    }


    /**
     * Initialize module
     */
    public function initEngine()
    {
        $this->template = new TemplateFile($this->getTemplatesPath() . $this->getFilename());
    }


    /**
     * @return array
     */
    public static function getDefaultConfig()
    {
        $config = parent::getDefaultConfig();

        return array_merge($config, array(
            'template_files_suffix' => 'php',
        ));
    }


    /**
     * Set a key/value pair to the template
     *
     * @param $key
     * @param $value
     */
    public function set($key, $value)
    {
        $this->template->set($key, $value);
    }


    /**
     * Render markup from template file
     *
     * @return mixed
     */
    public function render()
    {
        return $this->template->render();
    }



    /**
     * Per interface Module, ConfigurableModule
     *
     */


    /**
     * @return array
     */
    public static function getModuleInfo()
    {
        return array(
            'title' => 'Template Engine ProcessWire',
            'version' => 101,
            'author' => 'Stefan Wanzenried',
            'summary' => 'ProcessWire templates for the TemplateEngineFactory',
            'href' => '',
            'singular' => false,
            'autoload' => false,
            'requires' => array('TemplateEngineFactory'),
        );
    }


    /**
     * Return an InputfieldWrapper of Inputfields used to configure the class
     *
     * @param array $data Array of config values indexed by field name
     * @return InputfieldWrapper
     */
    public static function getModuleConfigInputfields(array $data)
    {
        $data = array_merge(self::getDefaultConfig(), $data);
        $wrapper = parent::getModuleConfigInputfields($data);

        return $wrapper;
    }

}
