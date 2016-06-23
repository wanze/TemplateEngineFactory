<?php

/**
 * Class TemplateEngineChunk
 *
 * Wrapper around ProcessWire's TemplateFile class to create "chunks". A chunk represents a small chunk of PHP logic
 * associated with a template (view) to render its output. The view is rendered with the current active template engine.
 *
 * Example:
 *
 * Chunk-File: /site/templates/chunks/my-chunk.php
 * Template-File (view): /site/templates/views/chunks/my-chunk.tpl
 *
 * Note that the path to the template file depends on the storage location of the active template engine.
 *
 *
 * @author Stefan Wanzenried <stefan.wanzenried@gmail.com>
 * @license http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License, version 2
 */
class TemplateEngineChunk extends WireData
{

    /**
     * @var TemplateEngine
     */
    protected $view;

    /**
     * @var string
     */
    protected $chunk_file;

    /**
     * @var
     */
    protected $template_file;


    /**
     * @param string $chunk_file Path to the chunk file to load, relative to site/templates/ without suffix
     * @param string $template_file Path to the corresponding template file (view) relative to the path where the engine stores its templates
     * @throws WireException
     */
    public function __construct($chunk_file, $template_file = '')
    {
        $this->setChunkFile($chunk_file);
        $this->setTemplateFile($template_file);
    }


    /**
     * Set a value
     *
     * @param string $key
     * @param mixed $value
     * @return $this
     *
     */
    public function set($key, $value)
    {
        switch ($key) {
            case 'chunkFile':
                return $this->setChunkFile($value);
            case 'templateFile':
                return $this->setTemplateFile($value);
        }

        return parent::set($key, $value);
    }


    /**
     * @return mixed|string
     */
    public function render()
    {
        if (!$this->view) {
            return '';
        }
        $factory = $this->modules->get('TemplateEngineFactory');
        $api_var = $factory->get('api_var');
        // Temporarily store old $view global
        $_view = $this->wire($api_var);
        // Assign the template for this chunk to the global $view variable
        $this->wire($api_var, $this->view);
        $chunk = $this->getChunk();
        // Process logic of chunk
        $chunk->render();
        $out = $this->view->render();
        // Restore previous global
        $this->wire($factory->get('api_var'), $_view);

        return $out;
    }


    /**
     * @param string $chunk_file The chunk file to load, relative to site/templates/ without suffix
     * @throws WireException
     * @return $this
     */
    public function setChunkFile($chunk_file)
    {
        if (!is_file($this->getChunkPath($chunk_file))) {
            throw new WireException("Chunk file does not exist: '{$chunk_file}'");
        }
        $this->chunk_file = $chunk_file;

        return $this;
    }


    /**
     * @param string $template_file The template file (view) that should be used to render this chunk
     * @throws WireException
     * @return $this
     */
    public function setTemplateFile($template_file)
    {
        $template_file = ($template_file) ? $template_file : $this->chunk_file;
        $this->template_file = $template_file;
        $this->view = $this->wire('factory')->load($template_file);
        if ($this->view === null) {
            throw new WireException("View for chunk {$this->chunk_file} does not exist");
        }

        return $this;
    }


    /**
     * Create the chunk file aka TemplateFile object
     *
     * @return TemplateFile
     */
    protected function ___getChunk()
    {
        $chunk = new TemplateFile($this->getChunkPath($this->chunk_file));
        $chunk->setArray($this->getArray());

        return $chunk;
    }


    /**
     * @param string $chunk_file
     * @return string
     */
    protected function getChunkPath($chunk_file)
    {
        return $this->wire('config')->paths->templates . $chunk_file . '.' . $this->wire('config')->templateExtension;
    }

}