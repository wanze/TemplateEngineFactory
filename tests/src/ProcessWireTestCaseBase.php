<?php

namespace TemplateEngineFactory\Test;

use PHPUnit\Framework\TestCase;
use ProcessWire\Field;
use ProcessWire\Fieldgroup;
use ProcessWire\ProcessWire;
use ProcessWire\Template;

/**
 * Base class for all tests.
 */
abstract class ProcessWireTestCaseBase extends TestCase
{
    /**
     * @var array
     */
    protected $pages = [];

    /**
     * @var array
     */
    protected $templates = [];

    /**
     * @var array
     */
    protected $fields = [];

    /**
     * @var array
     */
    protected $hookIds = [];

    /**
     * @var \ProcessWire\ProcessWire
     */
    protected $wire;

    public function __construct(string $name = null, array $data = [], string $dataName = '')
    {
        parent::__construct($name, $data, $dataName);

        $this->wire = ProcessWire::getCurrentInstance();
    }

    protected function wire($name = '', $value = null)
    {
        return $this->wire->wire($name, $value);
    }

    protected function addHookAfter($method, $toObject, $toMethod = null, $options = [])
    {
        $hookId = $this->wire->addHookAfter($method, $toObject, $toMethod, $options);
        $this->hookIds[] = $hookId;

        return $hookId;
    }

    protected function addHookBefore($method, $toObject, $toMethod = null, $options = [])
    {
        $hookId = $this->wire->addHookBefore($method, $toObject, $toMethod, $options);
        $this->hookIds[] = $hookId;

        return $hookId;
    }

    /**
     * @param $template
     * @param $parent
     * @param string $name
     * @param array $data
     *
     * @return \ProcessWire\Page
     */
    protected function createPage($template, $parent, $name = '', $data = [])
    {
        $page = $this->wire->wire('pages')->add($template, $parent, $name, $data);
        $this->pages[] = $page;

        return $page;
    }

    /**
     * @param string $name
     * @param string $filename
     *
     * @return \ProcessWire\Template
     */
    protected function createTemplate($name, $filename = '')
    {
        $fieldgroup = (new Fieldgroup())
            ->set('name', $name)
            ->save();

        $template = (new Template())
            ->set('name', $name)
            ->set('filename', $filename)
            ->set('fieldgroup', $fieldgroup);
        $template->save();

        $this->templates[] = $template;

        return $template;
    }

    /**
     * @param $type
     * @param string $name
     *
     * @return \ProcessWire\Field
     */
    protected function createField($type, $name)
    {
        $field = (new Field())
            ->setFieldtype($type)
            ->setName($name);
        $field->save();

        $this->fields[] = $field;

        return $field;
    }

    /**
     * Let a path in $config->pahts point to the given directory.
     *
     * @param \ProcessWire\ProcessWire $wire
     * @param string $which
     * @param string $path
     */
    protected function fakePath($which, $path)
    {
        $paths = $this->wire('config')->paths;
        $paths->set($which, $path);
    }

    /**
     * @param string $hookedMethod
     *   The method being hooked, e.g. Page::render.
     * @param $fromObject
     *   Class name of the object attaching the hook.
     * @param $type
     *   after or before
     *
     * @return bool
     */
    protected function assertHookExists($hookedMethod, $fromObject, $type)
    {
        list($class, $method) = explode('::', $hookedMethod);

        $regexHookId = "/^${class}:.*:{$method}$/";

        $hooks = array_filter($this->wire->getHooks('*'),
            function ($hook) use ($regexHookId, $fromObject, $type) {
                return preg_match($regexHookId, $hook['id'])
                    && $hook['toObject'] instanceof $fromObject
                    && $hook['options'][$type] === true;
            });

        $exists = count($hooks) > 0;

        $this->assertTrue($exists);
    }

    protected function tearDown()
    {
        foreach ($this->pages as $page) {
            $this->wire->wire('pages')->delete($page);
        }

        foreach ($this->templates as $template) {
            $this->wire->wire('templates')->delete($template);
            $this->wire->wire('fieldgroups')->delete($template->fieldgroup);
        }

        foreach ($this->fields as $field) {
            $this->wire->wire('fields')->delete($field);
        }

        foreach ($this->hookIds as $hookId) {
            $this->wire->removeHook($hookId);
        }
    }
}
