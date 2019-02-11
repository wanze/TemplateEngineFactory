<?php

namespace ProcessWire;

use TemplateEngineFactory\TemplateVariables;
use TemplateEngineFactory\TemplateEngineInterface;
use TemplateEngineFactory\TemplateEngineNull;
use TemplateEngineFactory\Controller;

/**
 * Provides ProcessWire integration for various template engines such as Twig.
 *
 * @see https://github.com/wanze/TemplateEngineFactory
 */
class TemplateEngineFactory extends WireData implements Module, ConfigurableModule
{
    /**
     * @var array
     */
    private static $defaultConfig = [
        'engine' => '',
        'auto_page_render' => 1,
        'api_var' => 'view',
        'enabled_templates' => [],
        'disabled_templates' => [],
        'templates_path' => 'templates/views/'
    ];

    /**
     * @var \TemplateEngineFactory\TemplateEngineInterface[]
     */
    private $engines = [];

    /**
     * @var \ProcessWire\WireArray
     */
    private $templateVariables;

    public function __construct()
    {
        parent::__construct();

        $this->templateVariables = $this->wire(new WireArray());
        $this->wire('classLoader')->addNamespace('TemplateEngineFactory', __DIR__ . '/src');
        $this->setDefaultConfig();
    }

    /**
     * @return array
     */
    public static function getModuleInfo()
    {
        return [
            'title' => 'Template Engine Factory',
            'version' => 200,
            'author' => 'Stefan Wanzenried',
            'summary' => 'Provides ProcessWire integration for various template engines such as Twig.',
            'href' => 'https://processwire.com/talk/topic/6833-module-templateenginefactory/',
            'singular' => true,
            'autoload' => true,
            'requires' => [
                'PHP>=7.0',
                'ProcessWire>=3.0',
            ],
        ];
    }

    /**
     * Initialize module by hooking into Page::render.
     */
    public function init()
    {
        if (!$this->get('auto_page_render')) {
            return;
        }

        $this->addHookBefore('Page::render', $this, 'hookBeforePageRender');
        $this->addHookAfter('Page::render', $this, 'hookAfterPageRender', ['priority' => '100.01']);
    }

    /**
     * Register a template engine.
     *
     * @param string $name
     *   The name of the template engine, e.g. "Twig" or "Plug".
     * @param \TemplateEngineFactory\TemplateEngineInterface $engine
     */
    public function registerEngine($name, TemplateEngineInterface $engine)
    {
        $this->engines[$name] = $engine;
    }

    /**
     * Get the current active template engine.
     *
     * @return \TemplateEngineFactory\TemplateEngineInterface
     */
    public function getEngine()
    {
        $name = $this->get('engine');

        return $this->engines[$name] ?? new TemplateEngineNull();
    }

    /**
     * Render the given template and data via template engine.
     *
     * @param string $template
     *   A relative path to the template file.
     * @param array $data
     *   Data passed to the template file.
     *
     * @return string
     */
    public function render($template, array $data = [])
    {
        return $this->getEngine()->render($template, $data);
    }

    /**
     * A controller wraps a ProcessWire template executing some logic and a template file of the active engine.
     *
     * @param string $controllerFile
     * @param string $templateFile
     *
     * @return \TemplateEngineFactory\Controller
     */
    public function controller($controllerFile, $templateFile)
    {
        return $this->wire(new Controller($this, $controllerFile, $templateFile));
    }

    /**
     * @return \TemplateEngineFactory\TemplateEngineInterface[]
     */
    public function getEngines()
    {
        return $this->engines;
    }

    /**
     * Hook before rendering a page.
     *
     * @param \ProcessWire\HookEvent $event
     */
    public function hookBeforePageRender(HookEvent $event)
    {
        /** @var \ProcessWire\Page $page */
        $page = $event->object;

        if (!$this->shouldRenderPage($page)) {
            return;
        }

        $this->prepareTemplateVariables($page);
    }

    /**
     * Hook after rendering a page.
     *
     * Replaces the return value of Page::render() with the output from
     * the template engine.
     *
     * @param \ProcessWire\HookEvent $event
     */
    public function hookAfterPageRender(HookEvent $event)
    {
        /** @var \ProcessWire\Page $page */
        $page = $event->object;

        if (!$this->shouldRenderPage($page)) {
            return;
        }

        $template = $this->resolveTemplate($page);
        $variables = $this->resolveTemplateVariables($page, $this->collectTemplateVariables());

        $event->return = $this->getEngine()->render($template, $variables);
    }

    /**
     * Get the name of the template that should be used to render the given page.
     *
     * By default, the module will load a template with the same name as the
     * page's (ProcessWire) template name.
     *
     * @param \ProcessWire\Page $page
     *
     * @return string
     */
    protected function ___resolveTemplate(Page $page)
    {
        return $page->get('template')->name;
    }

    /**
     * Check if the given page should be rendered via template engine.
     *
     * @param \ProcessWire\Page $page
     *
     * @return bool
     */
    protected function ___shouldRenderPage(Page $page)
    {
        // If the page is not viewable, there is nothing to render for the template engine.
        if (!$page->viewable()) {
            return false;
        }

        return $this->shouldRenderTemplate($page->get('template'));
    }

    /**
     * Get variables available in the template when rendering the given page.
     *
     * The returned array will be passed to the engine when rendering the page.
     * By default, the module includes the page being rendered as "_page".
     * Note that this page might be different from the global "page" object.
     *
     * @param \ProcessWire\Page $page
     * @param \TemplateEngineFactory\TemplateVariables $variables
     *
     * @return array
     */
    protected function ___resolveTemplateVariables(Page $page, TemplateVariables $variables)
    {
        return $variables->getArray();
    }

    /**
     * @param \ProcessWire\Template $template
     *
     * @return bool
     */
    private function shouldRenderTemplate(Template $template)
    {
        // Do not render admin pages.
        if ($template->name === 'admin') {
            return false;
        }

        // Do not render pages not having a ProcessWire template file, there is no "controller" available.
        if (!$template->filenameExists()) {
            return false;
        }

        // Check if the template is enabled or disabled.
        if (count($this->get('enabled_templates'))) {
            return in_array($template->id, $this->get('enabled_templates'));
        }

        if (count($this->get('disabled_templates'))) {
            return !in_array($template->id, $this->get('disabled_templates'));
        }

        return true;
    }

    /**
     * Initialize new template variables when rendering the given page.
     *
     * @param \ProcessWire\Page $page
     *
     * @throws \ProcessWire\WireException
     */
    private function prepareTemplateVariables(Page $page)
    {
        // Push any existing variables on the internal stack.
        $variables = $this->wire($this->get('api_var'));

        if ($variables instanceof TemplateVariables) {
            $this->templateVariables->append($variables);
        }

        $variables = $this->wire(new TemplateVariables(['_page' => $page]));

        $this->wire($this->get('api_var'), $variables);
    }

    /**
     * Get all collected template variables after rendering a page.
     *
     * The ProcessWire template aka "controller" has populated these
     * variables during the Page::render() call.
     *
     * @throws \ProcessWire\WireException
     *
     * @return \TemplateEngineFactory\TemplateVariables
     */
    private function collectTemplateVariables()
    {
        $variables = $this->wire($this->get('api_var'));

        // Restore previous variables from stack, in case of recursive rendering.
        if ($this->templateVariables->count()) {
            $this->wire($this->get('api_var'), $this->templateVariables->pop());
        }

        if ($variables instanceof TemplateVariables) {
            return $variables;
        }

        return $this->wire(new TemplateVariables());
    }

    private function setDefaultConfig()
    {
        foreach (self::$defaultConfig as $key => $value) {
            $this->set($key, $value);
        }
    }

    /**
     * @param array $data
     *
     * @throws \ProcessWire\WireException
     * @throws \ProcessWire\WirePermissionException
     *
     * @return \ProcessWire\InputfieldWrapper
     */
    public static function getModuleConfigInputfields(array $data)
    {
        $wrapper = new InputfieldWrapper();
        $data = array_merge(self::$defaultConfig, $data);

        $templates = [];
        foreach (wire('templates') as $template) {
            //Exclude system templates
            if ($template->flags & Template::flagSystem) {
                continue;
            }
            $templates[$template->id] = $template->name;
        }

        /** @var \ProcessWire\InputfieldSelect $field */
        $field = wire('modules')->get('InputfieldSelect');
        $field->label = __('Template Engine');
        $field->description = __('Select the template engine which is used to render the pages.');
        $field->notes = __('More config options might be available in the module providing this engine.');
        $field->value = $data['engine'];
        $field->name = 'engine';
        $engines = wire('modules')->get('TemplateEngineFactory')->getEngines();
        $options = [];
        foreach (array_keys($engines) as $name) {
            $options[$name] = ucfirst($name);
        }
        $field->addOptions($options);
        $wrapper->append($field);

        /** @var \ProcessWire\InputfieldText $field */
        $field = wire('modules')->get('InputfieldText');
        $field->name = 'templates_path';
        $field->label = __('Path to templates');
        $field->description = __('Relative path from the site directory where template files are stored. E.g. `templates/views/` resolves to `/site/templates/views/`.');
        $field->value = $data['templates_path'];
        $field->required = 1;
        $wrapper->append($field);

        /** @var \ProcessWire\InputfieldCheckbox $field */
        $field = wire('modules')->get('InputfieldCheckbox');
        $field->label = __('Enable automatic page rendering');
        $field->description = __('Check to delegate the rendering of pages to the template engine. You may enable or disable this behaviour for specific templates.');
        $field->name = 'auto_page_render';
        $field->attr('checked', (bool) $data['auto_page_render']);
        $wrapper->append($field);

        $field = wire('modules')->get('InputfieldText');
        $field->label = __('API variable to interact with the template engine');
        $field->description = __('Enter a name for the API variable used to pass data from the ProcessWire template (Controller) to the template engine.');
        $field->name = 'api_var';
        $field->value = $data['api_var'];
        $field->required = 1;
        $field->showIf = 'auto_page_render=1';
        $wrapper->append($field);

        $field = wire('modules')->get('InputfieldAsmSelect');
        $field->label = __('Enabled templates');
        $field->description = __('Restrict automatic page rendering to the templates selected here.');
        $field->attr('name', 'enabled_templates');
        $field->attr('value', $data['enabled_templates']);
        $field->collapsed = Inputfield::collapsedBlank;
        $field->showIf = 'auto_page_render=1';
        $field->addOptions($templates);
        $wrapper->append($field);

        $field = wire('modules')->get('InputfieldAsmSelect');
        $field->label = __('Disabled templates');
        $field->description = __('Select templates that should **not** automatically be rendered via template engine.');
        $field->notes = __('Do not use in combination with the *Enabled templates* configuration above, either enable or disable templates.');
        $field->attr('name', 'disabled_templates');
        $field->attr('value', $data['disabled_templates']);
        $field->collapsed = Inputfield::collapsedBlank;
        $field->showIf = 'auto_page_render=1';
        $field->addOptions($templates);
        $wrapper->append($field);

        return $wrapper;
    }
}
