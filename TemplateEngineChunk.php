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
    protected $chunkFile;

    /**
     * @var
     */
    protected $templateFile;


    /**
     * @param string $chunkFile Path to the chunk file to load, relative to site/templates/ without suffix
     * @param string $templateFile Path to the corresponding template file (view) relative to the path where the engine stores its templates
     * @throws WireException
     */
    public function __construct($chunkFile, $templateFile = '')
    {
        $this->setChunkFile($chunkFile);
        $this->setTemplateFile($templateFile);
    }


    /**
     * @param string $key
     * @param mixed $value
     * @throws WireException
     * @return $this
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
     * @param string $chunkFile The chunk file to load, relative to site/templates/ without suffix
     * @throws WireException
     * @return $this
     */
    public function setChunkFile($chunkFile)
    {
        if (!is_file($this->getChunkPath($chunkFile))) {
            throw new WireException("Chunk file does not exist: '{$chunkFile}'");
        }
        $this->chunkFile = $chunkFile;

        return $this;
    }


    /**
     * @param string $templateFile The template file (view) that should be used to render this chunk
     * @throws WireException
     * @return $this
     */
    public function setTemplateFile($templateFile)
    {
        $templateFile = ($templateFile) ? $templateFile : $this->chunkFile;
        $this->templateFile = $templateFile;
        $this->view = $this->wire('factory')->load($templateFile);
        if ($this->view === null) {
            throw new WireException("View for chunk {$this->chunkFile} does not exist");
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
        $chunk = new TemplateFile($this->getChunkPath($this->chunkFile));
        $chunk->setArray($this->getArray());

        return $chunk;
    }


    /**
     * @param string $chunkFile
     * @return string
     */
    protected function getChunkPath($chunkFile)
    {
        return $this->wire('config')->paths->templates . $chunkFile . '.' . $this->wire('config')->templateExtension;
    }

}