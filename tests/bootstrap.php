<?php

require_once(dirname(dirname(dirname(dirname(__DIR__)))) . '/index.php');

// Install TemplateEngineFactory & TemplateEngineProcessWire
$wire->wire('modules')->install('TemplateEngineFactory');
$wire->wire('modules')->install('TemplateEngineProcesswire');
