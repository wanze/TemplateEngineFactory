<?php

namespace TemplateEngineFactory;

use ProcessWire\TemplateEngineFactory;
use ProcessWire\Wire;

/**
 * Base class for all implemented template engines.
 */
abstract class TemplateEngineBase extends Wire implements TemplateEngineInterface
{
    /**
     * @var \ProcessWire\TemplateEngineFactory
     */
    protected $factory;

    /**
     * @param \ProcessWire\TemplateEngineFactory $factory
     */
    public function __construct(TemplateEngineFactory $factory)
    {
        parent::__construct();

        $this->factory = $factory;
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
        $path = ltrim($this->factory->get('templates_path'), '/');

        return sprintf('%s%s/',
            $this->wire('config')->paths->site,
            rtrim($path, '/')
        );
    }
}
