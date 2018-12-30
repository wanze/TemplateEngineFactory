<?php

namespace TemplateEngineFactory\Test;

use TemplateEngineFactory\TemplateEngineInterface;

class TemplateEngineDummy implements TemplateEngineInterface
{
    /**
     * {@inheritdoc}
     */
    public function render($template, $data = [])
    {
        if (count($data)) {
            $out = '';
            foreach ($data as $k => $v) {
                $out .= sprintf("%s => %s\n", $k, $v);
            }
            return rtrim($out, "\n");
        } else {
            return 'No data';
        }
    }
}
