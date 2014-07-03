<?php

/**
 * TemplateEngine
 *
 * Base class for all implemented template engines.
 *
 * @author Stefan Wanzenried <stefan.wanzenried@gmail.com>
 *
 * ProcessWire 2.x
 * Copyright (C) 2014 by Ryan Cramer
 * Licensed under GNU/GPL v2, see LICENSE.TXT
 *
 * http://processwire.com
 *
 */

abstract class TemplateEngine extends Wire
{

    /**
     * @var array
     */
    protected $loaded_config = array();

    /**
     * Filename of template file
     * @var string
     */
    protected $filename = '';

    /**
     * Instance to the TemplateEngineFactory module
     * @var TemplateEngineFactory
     */
    protected $factory;


    /**
     * @param string $filename Filename of template file for this instance
     */
    public function __construct($filename = '')
    {
        $this->initConfig(); // Want to have config available as early as possible
        $modules = wire('modules');
        $this->setFilename($filename);
        $this->factory = $modules->get('TemplateEngineFactory'); // Module is singular === singleton
    }


    /**
     * Register the template engine when installing
     */
    public function install() {
        $this->factory->registerEngine($this);
    }


    /**
     * Unregister template engine when uninstalling
     */
    public function uninstall() {
        $this->factory->unregisterEngine($this);
    }


    /**
     * Init engine, derived classes should use this method to bootstrap the engines
     */
    public function initEngine() {}


    /**
     * @param $key
     * @param $value
     */
    public function __set($key, $value)
    {
        $this->set($key, $value);
    }


    /**
     * Set a key/value pair to the template engine
     *
     * @param $key
     * @param $value
     */
    abstract public function set($key, $value);


    /**
     * Render markup from template engine
     *
     * @return mixed
     */
    abstract public function render();


    /**
     * @return array
     */
    public static function getDefaultConfig()
    {
        return array(
            'templates_path' => 'templates/views/',         // Relative to /site/ directory
        );
    }


    /**
     * ProcessWire does call this method and set config values from database
     *
     */
    public function setConfigData(array $data=array()) {
        $this->loaded_config = array_merge($this->getDefaultConfig(), $data);
    }


    /**
     * Return config all implemented engines share in common
     *
     * @param array $data
     * @return InputfieldWrapper
     */
    public static function getModuleConfigInputfields(array $data)
    {
        $wrapper = new InputfieldWrapper();
        $modules = wire('modules');
        $f = $modules->get('InputfieldText');
        $f->name = 'templates_path';
        $f->label = 'Path to templates';
        $f->description = __('Relative path from the site directory where template files are stored. E.g. "templates/views/" resolves to "/site/templates/views/"');
        $f->value = $data['templates_path'];
        $f->required = 1;
        $wrapper->append($f);
        return $wrapper;
    }


    /**
     * Get a config value
     *
     * @param $key
     * @return string|null
     */
    public function getConfig($key)
    {
        return (isset($this->loaded_config[$key])) ? $this->loaded_config[$key] : null;
    }


    /**
     * Set a config value (runtime only)
     * @param $key
     * @param $value
     */
    public function setConfig($key, $value)
    {
        $this->loaded_config[$key] = $value;
    }


    /**
     * @return string
     */
    public function getFilename()
    {
        return $this->filename;
    }


    /**
     * @param string $filename
     */
    public function setFilename($filename)
    {
        $this->filename = $filename;
    }


    /**
     * Get the path where templates are stored
     *
     * @return string
     */
    public function getTemplatesPath()
    {
        $path = ltrim($this->getConfig('templates_path'), '/');
        return $this->config->paths->root . 'site/' . rtrim($path, '/') . '/';
    }


    /**
     * Load configuration
     */
    protected function initConfig() {
        /** @var Modules $modules */
        $modules = wire('modules');
        $configs = $modules->getModuleConfigData($this);
        $this->loaded_config = array_merge($this->getDefaultConfig(), $configs);
    }
}