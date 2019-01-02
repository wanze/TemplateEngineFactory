<?php

namespace TemplateEngineFactory\Test;

use PHPUnit\Framework\TestCase;
use ProcessWire\HookEvent;
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
    use TestHelperTrait;

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
        $this->wire = $this->bootstrapProcessWire();
        $this->factory = new TemplateEngineFactory();
    }

    protected function tearDown()
    {
        ProcessWire::removeInstance($this->wire);
    }

    public function testConstruct_UsingDefaultConfiguration_ReturnsCorrectConfiguration()
    {
        $expected = [
            'engine' => '',
            'api_var' => 'view',
            'auto_page_render' => true,
            'enabled_templates' => [],
            'disabled_templates' => [],
            'templates_path' => 'templates/views/',
        ];

        $this->assertEquals($expected, $this->factory->getArray());
    }

    /**
     * @covers ::getEngine
     */
    public function testGetEngine_NoEngineOrDummyEngineRegistered_ReturnsCorrectEngine()
    {
        $this->assertInstanceOf(TemplateEngineNull::class, $this->factory->getEngine());

        $this->registerDummyEngine();

        $this->assertInstanceOf(TemplateEngineDummy::class, $this->factory->getEngine());
    }

    /**
     * @covers ::getEngines
     */
    public function testGetEngines_NoOrMultipleEnginesRegistered_ReturnsRegisteredEngines()
    {
        $this->assertEmpty($this->factory->getEngines());

        $this->registerDummyEngine();
        $this->registerMockedEngine();

        $engines = $this->factory->getEngines();

        $this->assertCount(2, $engines);
        $this->assertInstanceOf(TemplateEngineInterface::class, array_pop($engines));
        $this->assertInstanceOf(TemplateEngineDummy::class, array_pop($engines));
    }

    /**
     * @covers ::render
     */
    public function testRender_NoEngineOrDummyEngineRegistered_EngineRendersCorrectOutput()
    {
        $this->assertEquals('', $this->factory->render('some/template'));

        $this->registerDummyEngine();

        $this->assertEquals('No data', $this->factory->render('some/template'));
        $this->assertEquals('foo => bar', $this->factory->render('some/template', ['foo' => 'bar']));
    }

    /**
     * @covers ::ready
     */
    public function testReady_AutomaticPageRenderingEnabled_HooksToPageRenderRegistered()
    {
        $this->factory->ready();

        $this->assertTrue($this->hookExists($this->wire, 'Page::render', TemplateEngineFactory::class, 'before'));
        $this->assertTrue($this->hookExists($this->wire, 'Page::render', TemplateEngineFactory::class, 'after'));
    }

    /**
     * @covers ::ready
     */
    public function testReady_AutomaticPageRenderingDisabled_HooksToPageRenderNotRegistered()
    {
        $this->factory->set('auto_page_render', false);
        $this->factory->ready();

        $this->assertFalse($this->hookExists($this->wire, 'Page::render', TemplateEngineFactory::class, 'before'));
        $this->assertFalse($this->hookExists($this->wire, 'Page::render', TemplateEngineFactory::class, 'after'));
    }

    public function testHookResolveTemplate_RegisterHookReturningCustomTemplate_TemplateEngineUsesCustomTemplate()
    {
        $this->factory->ready();

        $engine = $this->registerMockedEngine();

        $page = $this->getPageWithTemplate('my-template', dirname(__DIR__) . '/templates/foo.php');

        $engine
            ->expects($this->at(0))
            ->method('render')
            ->with('my-template');

        $engine
            ->expects($this->at(1))
            ->method('render')
            ->with('my-custom-template');

        // First call without hook: Should use 'my-template'.
        $page->render();

        $this->wire->addHookAfter('TemplateEngineFactory::resolveTemplate', function (HookEvent $event) {
            $event->return = 'my-custom-template';
        });

        // Second call, hook active: Should use 'my-custom-template'.
        $page->render();
    }

    public function testEnabledTemplates_TemplateOfRenderedPageEnabled_PageRenderedByTemplateEngine()
    {
        $this->factory->ready();
        $this->factory->set('enabled_templates', ['my-template']);

        $engine = $this->registerMockedEngine();

        $page = $this->getPageWithTemplate('my-template', dirname(__DIR__) . '/templates/foo.php');

        $engine
            ->expects($this->once())
            ->method('render')
            ->with('my-template');

        $page->render();
    }

    public function testEnabledTemplates_TemplateOfRenderedPageNotEnabled_PageNotRenderedByTemplateEngine()
    {
        $this->factory->ready();
        $this->factory->set('enabled_templates', ['home']);

        $engine = $this->registerMockedEngine();

        $page = $this->getPageWithTemplate('not-home', dirname(__DIR__) . '/templates/foo.php');

        $engine
            ->expects($this->never())
            ->method('render');

        $page->render();
    }

    public function testDisabledTemplates_TemplateOfRenderedPageDisabled_PageNotRenderedByTemplateEngine()
    {
        $this->factory->ready();
        $this->factory->set('disabled_templates', ['disabled-template']);

        $engine = $this->registerMockedEngine();

        $page = $this->getPageWithTemplate('disabled-template', dirname(__DIR__) . '/templates/foo.php');

        $engine
            ->expects($this->never())
            ->method('render');

        $page->render();
    }

    public function testDisabledTemplates_TemplateOfRenderedPageNotDisabled_PageRenderedByTemplateEngine()
    {
        $this->factory->ready();
        $this->factory->set('disabled_templates', ['disabled-template']);

        $engine = $this->registerMockedEngine();

        $page = $this->getPageWithTemplate('enabled-template', dirname(__DIR__) . '/templates/foo.php');

        $engine
            ->expects($this->once())
            ->method('render')
            ->with('enabled-template');

        $page->render();
    }

    public function testPageRender_RenderedPageDoesNotHaveTemplateFile_PageNotRenderedByTemplateEngine()
    {
        $this->factory->ready();

        $engine = $this->registerMockedEngine();

        $page = $this->getPageWithTemplate('a-template-with-no-file');

        $engine
            ->expects($this->never())
            ->method('render');

        $page->render();
    }

    public function testPageRender_RenderedPageProvidesTemplateVariables_TemplateVariablesPassedToTemplateEngine()
    {
        // These variables are passed to the engine via $view->set().
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

        $page = $this->getPageWithTemplate('foo', dirname(__DIR__) . '/templates/foo.php');
        $page->render();
    }

    public function testHookGetTemplateVariables_RegisterHookWithCustomVariables_VariablesArePassedToTemplateEngine()
    {
        $variables = [
            'foo' => 'bar',
            'this' => 'that',
        ];

        // Make the above variables available for the template engine.
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

        $page = $this->getPageWithTemplate('foo', dirname(__DIR__) . '/templates/foo.php');
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
}
