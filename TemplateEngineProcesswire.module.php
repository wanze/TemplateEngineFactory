<?php

namespace ProcessWire;

use TemplateEngineFactory\TemplateEngineProcessWire as ProcessWireEngine;

/**
 * Provides a template engine using ProcessWire's internal template files.
 */
class TemplateEngineProcesswire extends WireData implements Module
{
    /**
     * @return array
     */
    public static function getModuleInfo()
    {
        return [
            'title' => 'Template Engine ProcessWire',
            'version' => 200,
            'author' => 'Stefan Wanzenried',
            'summary' => 'ProcessWire templates for the TemplateEngineFactory.',
            'singular' => true,
            'autoload' => true,
            'requires' => [
                'TemplateEngineFactory>=2.0.0',
                'PHP>=7.0',
                'ProcessWire>=3.0',
            ],
        ];
    }

    public function init()
    {
        /** @var \ProcessWire\TemplateEngineFactory $factory */
        $factory = $this->wire('modules')->get('TemplateEngineFactory');

        $factory->registerEngine('ProcessWire', new ProcessWireEngine($factory->getArray()));
    }
}
