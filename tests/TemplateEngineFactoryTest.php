<?php

namespace TemplateEngineFactory\Test;

use PHPUnit\Framework\TestCase;
use ProcessWire\HookEvent;
use ProcessWire\Page;
use ProcessWire\ProcessWire;
use ProcessWire\Template;
use ProcessWire\TemplateEngineFactory;
use TemplateEngineFactory\TemplateEngineInterface;
use TemplateEngineFactory\TemplateEngineNull;
use TemplateEngineFactory\TemplateVariables;

/**
 * Tests for the TemplateEngineFactory module.
 *
 * @coversDefaultClass \ProcessWire\TemplateEngineFactory
 *
 * @group TemplateEngineFactory
 */
class TemplateEngineFactoryTest extends TestCase
{
    /**
     * @var TemplateEngineFactory
     */
    private $factory;

    /**
     * @var \ProcessWire\ProcessWire
     */
    private $wire;

    protected function setUp()
    {
        $this->bootstrapProcessWire();
        $this->factory = new TemplateEngineFactory();
    }

    protected function tearDown()
    {
        ProcessWire::removeInstance($this->wire);
    }

    public function test_default_config()
    {
        $expected = [
            'engine' => '',
            'api_var' => 'view',
            'api_var_factory' => 'factory',
            'active' => true,
            'enabled_templates' => [],
            'disabled_templates' => [],
            'templates_path' => 'templates/views/',
        ];

        $this->assertEquals($expected, $this->factory->getArray());
    }

    /**
     * @covers ::getEngine
     */
    public function test_get_engine()
    {
        $this->assertInstanceOf(TemplateEngineNull::class, $this->factory->getEngine());

        $this->registerDummyEngine();

        $this->assertInstanceOf(TemplateEngineDummy::class, $this->factory->getEngine());
    }

    /**
     * @covers ::getEngines
     */
    public function test_get_engines()
    {
        $this->assertEmpty($this->factory->getEngines());

        $this->registerDummyEngine();

        $engines = $this->factory->getEngines();
        $this->assertCount(1, $engines);
        $this->assertInstanceOf(TemplateEngineInterface::class, array_pop($engines));
    }

    /**
     * @covers ::render
     */
    public function test_render()
    {
        $this->assertEquals('', $this->factory->render('some/template'));

        $this->registerDummyEngine();

        $this->assertEquals('No data', $this->factory->render('some/template'));
        $this->assertEquals('foo => bar', $this->factory->render('some/template', ['foo' => 'bar']));
    }

    /**
     * @covers ::ready
     */
    public function test_hooks_attached_if_active()
    {
        $this->factory->ready();

        $this->assertTrue($this->hookExists('Page::render', TemplateEngineFactory::class, 'before'));
        $this->assertTrue($this->hookExists('Page::render', TemplateEngineFactory::class, 'after'));
    }

    /**
     * @covers ::ready
     */
    public function test_hooks_not_attached_if_inactive()
    {
        $this->factory->set('active', false);
        $this->factory->ready();

        $this->assertFalse($this->hookExists('Page::render', TemplateEngineFactory::class, 'before'));
        $this->assertFalse($this->hookExists('Page::render', TemplateEngineFactory::class, 'after'));
    }

    public function test_hook_resolveTemplate()
    {
        $this->factory->ready();

        $engine = $this->registerMockedEngine();

        $page = $this->getPage('my-template');

        $engine
            ->expects($this->at(0))
            ->method('render')
            ->with('my-template');

        $engine
            ->expects($this->at(1))
            ->method('render')
            ->with('my-custom-template');

        $page->render();

        $this->wire->addHookAfter('TemplateEngineFactory::resolveTemplate', function (HookEvent $event) {
            $event->return = 'my-custom-template';
        });

        $page->render();
    }

    public function test_enabled_templates_rendered_if_enabled()
    {
        $this->factory->ready();
        $this->factory->set('enabled_templates', ['my-template']);

        $engine = $this->registerMockedEngine();

        $page = $this->getPage('my-template');

        $engine
            ->expects($this->once())
            ->method('render')
            ->with('my-template');

        $page->render();
    }

    public function test_enabled_templates_not_rendered_if_not_enabled()
    {
        $this->factory->ready();
        $this->factory->set('enabled_templates', ['home']);

        $engine = $this->registerMockedEngine();

        $page = $this->getPage('not-home');

        $engine
            ->expects($this->never())
            ->method('render');

        $page->render();
    }

    public function test_disabled_templates_not_rendered_if_disabled()
    {
        $this->factory->ready();
        $this->factory->set('disabled_templates', ['disabled-template']);

        $engine = $this->registerMockedEngine();

        $page = $this->getPage('disabled-template');

        $engine
            ->expects($this->never())
            ->method('render');

        $page->render();
    }

    public function test_disabled_templates_rendered_if_not_disabled()
    {
        $this->factory->ready();
        $this->factory->set('disabled_templates', ['disabled-template']);

        $engine = $this->registerMockedEngine();

        $page = $this->getPage('enabled-template');

        $engine
            ->expects($this->once())
            ->method('render')
            ->with('enabled-template');

        $page->render();
    }

    public function test_template_variables_passed_to_engine()
    {
        $variables = [
            'foo' => 'bar',
            'this' => 'that',
        ];

        // Hook after Page::render but before the factory renders the template
        // in order to manipulate the template variables.
        $this->wire->addHookAfter('Page::render', function () use ($variables) {
            $this->wire->wire($this->factory->get('api_var'), new TemplateVariables($variables));
        }, null, ['priority' => 50]);

        $this->factory->ready();

        $engine = $this->registerMockedEngine();

        $engine
            ->expects($this->once())
            ->method('render')
            ->with('foo', $variables);

        $page = $this->getPage('foo');
        $page->render();
    }

    public function test_hook_getTemplateVariables()
    {
        $variables = [
            'foo' => 'bar',
            'this' => 'that',
        ];

        $this->wire->addHookAfter(
            'TemplateEngineFactory::getTemplateVariables',
            function (HookEvent $event) use ($variables) {
                $event->return = $variables;
            });

        $result = [];

        $this->wire->addHookBefore('Page::render', function() use (&$result) {
            /** @var TemplateVariables $templateVariables */
            $templateVariables = $this->wire->wire($this->factory->get('api_var'));
            $result = $templateVariables->getArray();
        }, null, ['priority' => 150]);

        $this->registerDummyEngine();

        $this->factory->ready();

        $page = $this->getPage('foo');
        $page->render();

        $this->assertEquals($variables, $result);
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject
     */
    private function registerMockedEngine()
    {
        $engine = $this->getMockBuilder(TemplateEngineInterface::class)->getMock();

        $this->factory->registerEngine('MockedEngine', $engine);
        $this->factory->set('engine', 'MockedEngine');

        return $engine;
    }

    private function registerDummyEngine()
    {
        $this->factory->registerEngine('Dummy', new TemplateEngineDummy());
        $this->factory->set('engine', 'Dummy');
    }

    /**
     * @param string $templateName
     *
     * @return \ProcessWire\Page
     */
    private function getPage($templateName)
    {
        $template = new Template();
        $template->name = $templateName;

        return new Page($template);
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
    private function hookExists($hookedMethod, $fromObject, $type)
    {
        list($class, $method) = explode('::', $hookedMethod);

        $regexHookId = "/^${class}:.*:{$method}$/";

        $hooks = array_filter($this->wire->getHooks('*'),
            function ($hook) use ($regexHookId, $fromObject, $type) {
                return preg_match($regexHookId, $hook['id'])
                    && $hook['toObject'] instanceof $fromObject
                    && $hook['options'][$type] === true;
            });

        return count($hooks) > 0;
    }

    private function bootstrapProcessWire()
    {
        $rootPath = __DIR__ . '../../../../../';
        $config = ProcessWire::buildConfig($rootPath);
        $this->wire = new ProcessWire($config);

        // Make sure that the module's ready() method is not called by ProcessPageView::execute().
        // We call this method in our tests.
        $this->wire->addHookBefore('ProcessWire::ready', function (HookEvent $event) {
            $event->replace = true;
        });

        $process = $this->wire->modules->get('ProcessPageView');
        $this->wire->wire('process', $process);
        $process->execute(false);
    }
}
