<?php

namespace TemplateEngineFactory;

use ProcessWire\Wire;

/**
 * Base class for all implemented template engines.
 */
abstract class TemplateEngineBase extends Wire implements TemplateEngineInterface
{
    /**
     * The TemplateEngineFactory module configuration
     *
     * @var array
     */
    protected $factoryConfig;

    /**
     * Configuration from the module providing this engine.
     *
     * @var array
     */
    protected $moduleConfig;

    /**
     * @param array $factoryConfig
     * @param array $moduleConfig
     */
    public function __construct(array $factoryConfig, array $moduleConfig = [])
    {
        parent::__construct();

        $this->factoryConfig = $factoryConfig;
        $this->moduleConfig = $moduleConfig;
    }

    /**
     * Get the root path where all templates are stored.
     *
     * @throws \ProcessWire\WireException
     *
     * @return string
     */
    protected function getTemplatesRootPath()
    {
        $path = ltrim($this->factoryConfig['templates_path'], DIRECTORY_SEPARATOR);

        return sprintf('%s%s',
            $this->wire('config')->paths->site,
            rtrim($path, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR
        );
    }
}
