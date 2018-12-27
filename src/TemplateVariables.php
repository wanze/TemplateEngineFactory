<?php

namespace TemplateEngineFactory;

use ProcessWire\WireData;

/**
 * Collect data for the template engine via the $view API variable.
 */
class TemplateVariables extends WireData
{
    /**
     * @param array $data
     */
    public function __construct(array $data = [])
    {
        parent::__construct();

        $this->setArray($data);
    }
}
