<?php
require_once(__DIR__ . '/TemplateEngineNull.php');
require_once(__DIR__ . '/TemplateEngineChunk.php');

/**
 * TemplateEngineFactory
 *
 * This module takes another approach to separate logic from markup. It turns ProcessWire templates into controllers
 * which can interact with different template engines over a new API variable.
 *
 * What this class does:
 * - Serves as factory to return instances of the current active TemplateEngine module, e.g. Smarty or Twig
 * - Provide an API variable, e.g. 'view' which can be used to interact with the template engine of current page's template
 * - Adds hooks for rendering output over template engine and clear cache(s) when modifying pages
 *
 * @author Stefan Wanzenried <stefan.wanzenried@gmail.com>
 * @license http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License, version 2
 * @version 1.1.1
 */
class TemplateEngineFactory extends WireData implements Module, ConfigurableModule
{

    /**
     * @var array
     */
    protected static $default_config = array(
        'engine' => '',
        'api_var' => 'view',
        'api_var_factory' => 'factory',
        'registered_engines' => array(),
        'active' => true,
    );

    /**
     * @var array
     */
    protected $installed_engines;


    public function __construct()
    {
        foreach (self::$default_config as $k => $v) {
            $this->set($k, $v);
        }
    }


    public function init()
    {
    }


    /**
     * Provide an API variable to access the template of current page and attach hooks.
     * If a template file is found, adds hook after Page::render to output markup from engine.
     * If template engine supports caching, adds hooks to clear cache on page save/delete.
     *
     * Note that if no engine is selected or the requested template file is not found,
     * an instance of the "TemplateEngineNull" is provided for the API variable (prevents
     * null pointers in controllers).
     *
     */
    public function ready()
    {
        if ($this->wire('page')->template->name == 'admin') {
            return;
        }
        $this->wire($this->get('api_var_factory'), $this);
        if (!$this->get('engine')) {
            $this->wire($this->get('api_var'), new TemplateEngineNull());

            return;
        }
        $engine = $this->getInstanceById($this->get('engine'));
        if (is_null($engine)) {
            $this->wire($this->get('api_var'), new TemplateEngineNull());

            return;
        }
        $this->wire($this->get('api_var'), $engine);
        $this->addHookAfter('Page::render', $this, 'hookRender', array('priority' => '100.01'));
        // If the engine supports caching, attach hooks to clear the cache when saving/deleting pages
        if (in_array('TemplateEngineCache', class_implements($engine))) {
            $this->wire('pages')->addHookAfter('save', $this, 'hookClearCache');
            $this->wire('pages')->addHookAfter('delete', $this, 'hookClearCache');
        }
    }


    /**
     * @param string $chunk_file The chunk file to load, relative to site/templates/ without file extension
     * @param array $params Additional parameters that are passed to the chunk
     * @param string $template_file The template (view) that should be used to render the chunk
     * @return TemplateEngineChunk
     */
    public function chunk($chunk_file, array $params = array(), $template_file = '')
    {
        $chunk = new TemplateEngineChunk($chunk_file, $template_file);
        $chunk->setArray($params);

        return $chunk;
    }


    /**
     * Method executed after Page::render()
     * If we are in the admin or the factory is not active, return early
     *
     * @param HookEvent $event
     */
    public function hookRender(HookEvent $event)
    {
        $page = $event->object;
        if ($page->template == 'admin' || !$this->get('active')) {
            return;
        }
        /** @var TemplateEngine $engine */
        $engine = $this->wire($this->get('api_var'));
        $event->return = $engine->render();
    }


    /**
     * Method executed after saving or deleting a page, always clear complete cache
     *
     * @param HookEvent $event
     */
    public function hookClearCache(HookEvent $event)
    {
        /** @var TemplateEngineCache $engine */
        $engine = $this->wire($this->get('api_var'));
        $engine->clearAllCache();
    }


    /**
     * Register a new engine.
     * This method is typically called when a TemplateEngine is installed.
     * You must register an engine first before it is recognized by the factory.
     *
     * @param TemplateEngine $engine
     */
    public function registerEngine(TemplateEngine $engine)
    {
        $engines = $this->get('registered_engines');
        if (!isset($engines[$engine->className])) {
            $title = preg_replace('#^TemplateEngine(.*)$#', '$1', $engine->className);
            if (!$title) $title = $engine->className;
            $engines[$engine->className] = $title;
            $this->set('registered_engines', $engines);
            $this->wire('modules')->saveModuleConfigData($this, $this->data);
            $this->wire('session')->message(sprintf($this->_("Registered Template Engine '%s'"), $title));
        }
    }


    /**
     * Unregister an engine.
     * This method is typically called when a TemplateEngine is uninstalled.
     *
     * @param TemplateEngine $engine
     */
    public function unregisterEngine(TemplateEngine $engine)
    {
        $engines = $this->get('registered_engines');
        unset($engines[$engine->className]);
        $this->set('registered_engines', $engines);
        if ($this->get('engine') == $engine->className) {
            $this->set('engine', '');
            $msg = $this->_("The uninstalled template engine was selected by the TemplateEngineFactory, reset the engine");
            $this->wire('session')->message($msg);
        }
        $this->wire('modules')->saveModuleConfigData($this, $this->data);
    }


    /**
     * Return an array of all installed TemplateEngine modules.
     * Note that a registered Engine should also be installed, this an extra check to be sure the engine is available.
     *
     * @return array
     */
    public function getInstalledEngines()
    {
        if (is_array($this->installed_engines)) {
            return $this->installed_engines;
        }
        $this->installed_engines = array();
        foreach ($this->get('registered_engines') as $class => $title) {
            if ($this->wire('modules')->isInstalled($class)) {
                $this->installed_engines[$class] = $title;
            }
        }

        return $this->installed_engines;
    }


    /**
     * Get an instance of a concrete TemplateEngine module, e.g. TemplateEngineSmarty.
     * If the template file is not existing, null is returned. In this case, the module assumes
     * that the controller does not want to render anything.
     *
     * @param string $class Class name of engine
     * @param string $filename Filename of template file (with or without suffix)
     * @param bool $as_api_var Set to true to interact with the given template file over the $view API variable
     * @return TemplateEngine|null
     * @throws WireException
     */
    public function getInstanceById($class, $filename = '', $as_api_var = false)
    {
        $installed = $this->getInstalledEngines();
        if (!in_array($class, array_keys($installed))) {
            throw new WireException("TemplateEngine with class {$class} is currently not installed");
        }
        /** @var TemplateEngine $engine */
        $engine = new $class($filename);
        if (!$filename) {
            // No filename given, either use global template file or template file of current controller
            $global_template = $engine->getConfig('global_template');
            $template = ($global_template) ? $global_template : $this->wire('page')->template->name;
            $engine->setFilename($template);
        }
        if (!is_file($engine->getTemplatesPath() . $engine->getFilename())) {
            return null;
        }
        $engine->initEngine();
        if ($as_api_var) {
            $this->wire($this->get('api_var'), $engine);
        }

        return $engine;
    }


    /**
     * Get an instance of the current active TemplateEngine module with a given filename
     *
     * @param string $filename Filename of template file (with or without suffix)
     * @param bool $as_api_var Set to true to interact with the given template file over the API variable
     * @return TemplateEngine|null
     */
    public function getInstanceByFilename($filename, $as_api_var = false)
    {
        return $this->getInstanceById($this->get('engine'), $filename, $as_api_var);
    }


    /**
     * @param string $filename Filename of template file (with or without suffix)
     * @param bool $as_api_var Set to true to interact with the given template file over the API variable
     * @return TemplateEngine|null
     */
    public function instance($filename, $as_api_var = false)
    {
        return $this->getInstanceByFilename($filename, $as_api_var);
    }


    /**
     * @param string $filename Filename of template file (with or without suffix)
     * @param bool $as_api_var Set to true to interact with the given template file over the API variable
     * @return null|TemplateEngine
     */
    public function load($filename, $as_api_var = false)
    {
        return $this->getInstanceByFilename($filename, $as_api_var);
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
            'title' => 'Template Engine Factory',
            'version' => 111,
            'author' => 'Stefan Wanzenried',
            'summary' => 'This module aims to separate logic from markup.' .
                'Turns ProcessWire templates into controllers which can interact over a new API ' .
                'variable to template engines like the native ProcessWire TemplateFile class or Smarty/Twig.',
            'href' => 'https://processwire.com/talk/topic/6833-module-templateenginefactory/',
            'singular' => true,
            'autoload' => true,
            'installs' => array('TemplateEngineProcesswire'),
        );
    }


    /**
     * Get config fields
     *
     * @param array $data Array of config values indexed by field name
     * @return InputfieldWrapper
     */
    public static function getModuleConfigInputfields(array $data)
    {
        $modules = wire('modules');
        $wrapper = new InputfieldWrapper();
        $data = array_merge(self::$default_config, $data);

        /** @var InputfieldSelect $f */
        $engines = $modules->get('TemplateEngineFactory')->getInstalledEngines();
        $f = $modules->get('InputfieldSelect');
        $f->label = __('Template Engine');
        $f->description = __('Select the template engine which is used to render your templates.');
        $f->notes = __('More config options available in the selected TemplateEngine module.');
        $f->value = $data['engine'];
        $f->name = 'engine';
        $f->addOptions($engines);
        $wrapper->append($f);

        /** @var InputfieldText $f */
        $f = $modules->get('InputfieldText');
        $f->label = __('API variable to interact with the view');
        $f->description = __('Enter the name of the API variable with which you can interact with the current active template');
        $f->name = 'api_var';
        $f->value = $data['api_var'];
        $f->required = 1;
        $wrapper->append($f);

        /** @var InputfieldText $f */
        $f = $modules->get('InputfieldText');
        $f->label = __('API variable for the TemplateEngineFactory module');
        $f->description = __('Enter the name of the API variable that returns a singleton of this module, to load chunks and views');
        $f->name = 'api_var_factory';
        $f->value = $data['api_var_factory'];
        $f->required = 1;
        $wrapper->append($f);

        return $wrapper;
    }
}
