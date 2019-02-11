<?php

require_once(dirname(dirname(dirname(dirname(__DIR__)))) . '/index.php');

// Install TemplateEngineFactory & TemplateEngineProcessWire.
// Note: We use get() instead of install() which will install the modules and call the init() method!
$wire->wire('modules')->get('TemplateEngineFactory');
$wire->wire('modules')->get('TemplateEngineProcesswire');
