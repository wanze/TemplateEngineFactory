<?php

namespace TemplateEngineFactory;

/**
 * Contract for all template engines.
 */
interface TemplateEngineInterface
{
    /**
     * Render a template with given data.
     *
     * @param string $template
     *   A relative path to the template file.
     * @param array $data
     *   Data passed to the template file.
     *
     * @return string
     */
    public function render($template, $data = []);
}
