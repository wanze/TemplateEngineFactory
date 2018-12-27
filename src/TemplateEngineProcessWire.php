<?php

namespace TemplateEngineFactory;

use ProcessWire\TemplateFile;

/**
 * A template engine using ProcessWire's "TemplateFile" class to render the output.
 */
class TemplateEngineProcessWire extends TemplateEngineBase
{
    /**
     * {@inheritdoc}
     */
    public function render($template, $data = [])
    {
        return (new TemplateFile($this->getTemplatePath($template)))
            ->setArray($data)
            ->render();
    }

    /**
     * Return the full absolute path to the given relative template.
     *
     * @param string $template
     *
     * @throws \ProcessWire\WireException
     *
     * @return string
     */
    private function getTemplatePath($template)
    {
        $normalizedTemplate = ltrim($template, '/');

        if (!preg_match('/\.php$/', $template)) {
            $normalizedTemplate .= '.php';
        }

        return $this->getTemplatesRootPath() . $normalizedTemplate;
    }
}
