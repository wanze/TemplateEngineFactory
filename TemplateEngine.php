<?php

/**
 * TemplateEngine
 *
 * Base class for all implemented template engines.
 *
 * @author Stefan Wanzenried <stefan.wanzenried@gmail.com>
 * @license http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License, version 2
 *
 */
abstract class TemplateEngine extends Wire
{

    /**
     * Stores module configuration per implemented TemplateEngine
     *
     * @var array
     */
    protected static $loaded_config = array();

    /**
     * Filename of template file
     *
     * @var string
     */
    protected $filename = '';

    /**
     * Instance to the TemplateEngineFactory module
     *
     * @var TemplateEngineFactory
     */
    protected $factory;


    /**
     * @param string $filename Filename of template file for this instance
     */
    public function __construct($filename = '')
    {
        $this->initConfig(); // Want to have config available as early as possible
        $this->setFilename($filename);
        $this->factory = $this->wire('modules')->get('TemplateEngineFactory'); // Module is singular === singleton
    }


    /**
     * Register the template engine when installing
     */
    public function install()
    {
        $this->factory->registerEngine($this);
    }


    /**
     * Unregister template engine when uninstalling
     */
    public function uninstall()
    {
        $this->factory->unregisterEngine($this);
    }


    public function init()
    {
    }


    /**
     * Init engine, derived classes must use this method to setup the engine
     */
    abstract public function initEngine();


    /**
     * @param $key
     * @param $value
     */
    public function __set($key, $value)
    {
        $this->set($key, $value);
    }


    /**
     * Set a key/value pair to the template engine
     *
     * @param $key
     * @param $value
     */
    abstract public function set($key, $value);


    /**
     * Alias for setArray
     *
     * @param array $data
     */
    public function setMultiple($data = array())
    {
        $this->setArray($data);
    }


    /**
     * Set multiple key/value pairs to the template engine
     *
     * @param array $data
     */
    public function setArray($data = array())
    {
        foreach ($data as $key => $value) {
            $this->set($key, $value);
        }
    }


    /**
     * Render markup from template engine
     *
     * @return mixed
     */
    abstract public function render();


    /**
     * @return array
     */
    public static function getDefaultConfig()
    {
        return array(
            'templates_path' => 'templates/views/', // Relative to /site/ directory
            'global_template' => '',
            'template_files_suffix' => 'tpl',
        );
    }


    /**
     * ProcessWire does call this method and set config values from database
     * In our context, the config is loaded and available already in the constructor so just leave empty
     *
     * @param array $data
     */
    public function setConfigData(array $data = array())
    {
    }


    /**
     * Return config all implemented engines share in common
     *
     * @param array $data
     * @return InputfieldWrapper
     */
    public static function getModuleConfigInputfields(array $data)
    {
        $wrapper = new InputfieldWrapper();
        $modules = wire('modules');

        $f = $modules->get('InputfieldText');
        $f->name = 'templates_path';
        $f->label = __('Path to templates');
        $f->description = __('Relative path from the site directory where template files are stored. E.g. "templates/views/" resolves to "/site/templates/views/"');
        $f->value = $data['templates_path'];
        $f->required = 1;
        $wrapper->append($f);

        $f = $modules->get('InputfieldText');
        $f->label = __('Template files suffix');
        $f->description = __('File extension of template files');
        $f->name = 'template_files_suffix';
        $f->value = $data['template_files_suffix'];
        $f->required = 1;
        $wrapper->append($f);

        $f = $modules->get('InputfieldText');
        $f->name = 'global_template';
        $f->label = __('Global template file');
        $f->description = __('Filename of a template file that is used as main template behind the API variable');
        $f->value = $data['global_template'];
        $wrapper->append($f);

        return $wrapper;
    }


    /**
     * Get a config value
     *
     * @param $key
     * @return string|null
     */
    public function getConfig($key)
    {
        return (isset(self::$loaded_config[$this->className][$key])) ? self::$loaded_config[$this->className][$key] : null;
    }


    /**
     * Set a config value (runtime only and for all instances of the derived TemplateEngine class)
     *
     * @param $key
     * @param $value
     */
    public function setConfig($key, $value)
    {
        self::$loaded_config[$this->className][$key] = $value;
    }


    /**
     * @return string
     */
    public function getFilename()
    {
        return $this->filename;
    }


    /**
     * @param string $filename
     */
    public function setFilename($filename)
    {
        $suffix = $this->getConfig('template_files_suffix');
        if (preg_match("/\.{$suffix}$/", $filename)) {
            $this->filename = $filename;
        } else {
            $this->filename = $filename . '.' . $suffix;
        }
    }


    /**
     * Get the path where templates are stored
     *
     * @return string
     */
    public function getTemplatesPath()
    {
        $path = ltrim($this->getConfig('templates_path'), '/');

        return $this->wire('config')->paths->site . rtrim($path, '/') . '/';
    }


    /**
     * Load configuration once for all instances of TemplateEngine
     *
     */
    protected function initConfig()
    {
        if (!isset(self::$loaded_config[$this->className])) {
            $configs = $this->wire('modules')->getModuleConfigData($this);
            self::$loaded_config[$this->className] = array_merge($this->getDefaultConfig(), $configs);
        }
    }
}