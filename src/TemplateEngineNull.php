<?php

namespace TemplateEngineFactory;

/**
 * A template engine implementing the "null object" pattern.
 *
 * This engine is used by the factory if there is no other engine available.
 */
class TemplateEngineNull implements TemplateEngineInterface
{
    /**
     * {@inheritdoc}
     */
    public function render($template, $data = [])
    {
        return '';
    }
}
