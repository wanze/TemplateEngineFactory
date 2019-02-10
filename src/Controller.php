<?php

namespace TemplateEngineFactory;

use ProcessWire\TemplateEngineFactory;
use ProcessWire\TemplateFile;
use ProcessWire\WireData;

/**
 * A controller wraps a ProcessWire template executing some logic and a template file of the active engine, rendering the output.
 */
class Controller extends WireData
{
    /**
     * @var \ProcessWire\TemplateEngineFactory
     */
    private $factory;

    /**
     * @var \ProcessWire\TemplateFile
     */
    private $controller;

    /**
     * @var string
     */
    private $templateFile;

    /**
     * @param \ProcessWire\TemplateEngineFactory $factory
     * @param string $controllerFile
     *   The absolute path to a controller file (ProcessWire template).
     * @param string $templateFile
     *   The relative path to a template of the active template engine.
     */
    public function __construct(TemplateEngineFactory $factory, $controllerFile, $templateFile)
    {
        parent::__construct();

        $this->factory = $factory;
        $this->controller = new TemplateFile($this->resolvePath($controllerFile));
        $this->templateFile = $templateFile;
    }

    /**
     * Execute the controller and returning the output rendered by the template engine.
     *
     * @return string
     */
    public function execute()
    {
        $viewApiVar = $this->factory->get('api_var');
        $templateVariables = $this->wire(new TemplateVariables());

        $this->controller->set($viewApiVar, $templateVariables);

        // Execute the "controller".
        $this->controller->render();

        return $this->factory->render($this->templateFile, $templateVariables->getArray());
    }

    /**
     * {@inheritdoc}
     */
    public function set($key, $value)
    {
        // Forward data to the controller template.
        $this->controller->set($key, $value);

        return parent::set($key, $value);
    }

    /**
     * @param string $controllerFile
     */
    private function resolvePath($controllerFile)
    {
        if (strpos($controllerFile, DIRECTORY_SEPARATOR) === 0) {
            return $controllerFile;
        }

        return $this->wire('config')->paths->templates . $controllerFile;
    }
}
