<?php

namespace TemplateEngineFactory\Test;

use ProcessWire\HookEvent;
use ProcessWire\TemplateEngineFactory;
use TemplateEngineFactory\TemplateEngineInterface;
use TemplateEngineFactory\TemplateEngineNull;
use TemplateEngineFactory\TemplateEngineProcessWire;

/**
 * Tests for the TemplateEngineFactory module.
 *
 * @coversDefaultClass \ProcessWire\TemplateEngineFactory
 *
 * @group TemplateEngineFactory
 */
class TemplateEngineFactoryTest extends ProcessWireTestCaseBase
{
    /**
     * @var TemplateEngineFactory
     */
    private $factory;

    protected function setUp()
    {
        parent::setUp();

        $this->factory = $this->wire('modules')->get('TemplateEngineFactory');
    }

    /**
     * @test
     * @covers ::getEngine
     */
    public function it_should_return_the_correct_active_engine()
    {
        $this->factory->set('engine', '');
        $this->assertInstanceOf(TemplateEngineNull::class, $this->factory->getEngine());

        $this->registerDummyEngine();
        $this->assertInstanceOf(TemplateEngineDummy::class, $this->factory->getEngine());
    }

    /**
     * @test
     * @covers ::getEngines
     */
    public function it_should_return_the_registered_engines()
    {
        $this->registerProcesswireEngine();
        $this->registerDummyEngine();

        $engines = $this->factory->getEngines();

        $this->assertCount(2, $engines);
        $this->assertInstanceOf(TemplateEngineDummy::class, array_pop($engines));
        $this->assertInstanceOf(TemplateEngineProcessWire::class, array_pop($engines));
    }

    /**
     * @test
     * @covers ::render
     */
    public function it_should_render_empty_string_if_no_engine_is_active()
    {
        $this->factory->set('engine', '');

        $this->assertEquals('', $this->factory->render('some/template'));
    }

    /**
     * @test
     * @covers ::ready
     */
    public function it_should_register_page_render_hooks_if_automatic_page_rendering_is_enabled()
    {
        $this->assertHookExists('Page::render', TemplateEngineFactory::class, 'before');
        $this->assertHookExists('Page::render', TemplateEngineFactory::class, 'after');
    }

    /**
     * @test
     */
    public function it_should_use_a_custom_template_resolved_with_hook()
    {
        $engine = $this->registerMockedEngine();

        $template = $this->createTemplate('controller1', dirname(__DIR__) . '/templates/controller1.php');
        $page = $this->createPage($template, '/');

        $engine
            ->expects($this->at(0))
            ->method('render')
            ->with('controller1');

        $engine
            ->expects($this->at(1))
            ->method('render')
            ->with('my-custom-template');

        // First call without hook: Should use 'my-template'.
        $page->render();

        $this->addHookAfter('TemplateEngineFactory::resolveTemplate', function (HookEvent $event) {
            $event->return = 'my-custom-template';
        });

        // Second call, hook active: Should use 'my-custom-template'.
        $page->render();
    }

    /**
     * @test
     * @covers ::render
     */
    public function it_should_not_render_a_page_without_controller()
    {
        $engine = $this->registerMockedEngine();

        $template = $this->createTemplate('template-with-no-file');
        $page = $this->createPage($template, '/');

        $engine
            ->expects($this->never())
            ->method('render');

        $page->render();
    }

    /**
     * @test
     * @covers ::render
     */
    public function it_should_not_render_a_page_not_viewable()
    {
        $engine = $this->registerMockedEngine();

        $template = $this->createTemplate('controller1', dirname(__DIR__) . '/templates/controller1.php');
        $page = $this->createPage($template, '/');

        // Make Page::viewable return false for the created page.
        $this->addHookAfter('Page::viewable', function (HookEvent $event) use ($page) {
            $hookedPage = $event->object;
            if ($hookedPage->id === $page->id) {
                $event->return = false;
            }
        });

        $engine
            ->expects($this->never())
            ->method('render');

        $page->render();
    }

    /**
     * @test
     */
    public function it_should_not_render_a_page_if_prevented_via_hook()
    {
        $engine = $this->registerMockedEngine();

        $template = $this->createTemplate('controller1', dirname(__DIR__) . '/templates/controller1.php');
        $page = $this->createPage($template, '/');

        // Prevent the rendering via template engine
        $this->addHookAfter('TemplateEngineFactory::shouldRenderPage', function (HookEvent $event) use ($page) {
            $renderedPage = $event->arguments('page');
            if ($renderedPage->id === $page->id) {
                $event->return = false;
            }
        });

        $engine
            ->expects($this->never())
            ->method('render');

        $page->render();
    }

    /**
     * @test
     * @covers ::render
     */
    public function it_should_pass_template_variables_from_the_controller_to_the_view()
    {
        $template = $this->createTemplate('controller1', dirname(__DIR__) . '/templates/controller1.php');
        $page = $this->createPage($template, '/');

        // Let the site and template path point to the test directory to pick up our test controllers & views.
        $this->fakePath('site', 'site/modules/TemplateEngineFactory/tests/');
        $this->fakePath('templates', 'site/modules/TemplateEngineFactory/tests/templates/');

        $this->registerProcesswireEngine();

        $this->assertEquals('foo => bar', $page->render());
    }

    /**
     * @test
     */
    public function it_should_only_render_enabled_templates()
    {
        $this->fakePath('site', 'site/modules/TemplateEngineFactory/tests/');
        $this->fakePath('templates', 'site/modules/TemplateEngineFactory/tests/templates/');

        $template1 = $this->createTemplate('controller1', dirname(__DIR__) . '/templates/controller1.php');
        $template2 = $this->createTemplate('controller2', dirname(__DIR__) . '/templates/controller2.php');

        $page1 = $this->createPage($template1, '/');
        $page2 = $this->createPage($template2, '/');

        $this->factory->set('enabled_templates', [$template1->id]);
        $this->factory->set('disabled_templates', []);

        $this->registerProcesswireEngine();

        $this->assertEquals('foo => bar', $page1->render());
        $this->assertEquals('', $page2->render());
    }

    /**
     * @test
     */
    public function it_should_not_render_disabled_templates()
    {
        $this->fakePath('site', 'site/modules/TemplateEngineFactory/tests/');
        $this->fakePath('templates', 'site/modules/TemplateEngineFactory/tests/templates/');

        $template1 = $this->createTemplate('controller1', dirname(__DIR__) . '/templates/controller1.php');
        $template2 = $this->createTemplate('controller2', dirname(__DIR__) . '/templates/controller2.php');

        $page1 = $this->createPage($template1, '/');
        $page2 = $this->createPage($template2, '/');

        $this->factory->set('enabled_templates', []);
        $this->factory->set('disabled_templates', [$template2->id]);

        $this->registerProcesswireEngine();

        $this->assertEquals('foo => bar', $page1->render());
        $this->assertEquals('', $page2->render());
    }

    /**
     * @test
     */
    public function it_should_render_the_404_page_if_the_controller_throws_a_404_exception()
    {
        $template = $this->createTemplate('throw-404', dirname(__DIR__) . '/templates/throw-404.php');
        $page = $this->createPage($template, '/');

        $this->fakePath('site', 'site/modules/TemplateEngineFactory/tests/');
        $this->fakePath('templates', 'site/modules/TemplateEngineFactory/tests/templates/');

        $this->registerProcesswireEngine();

        // Prevent ProcessWire from sending a header during ProcessPageView::execute.
        $this->wire('config')->usePoweredBy = null;

        // If a 404 is caught, simply return the string "404!".
        $this->addHookBefore('ProcessPageView::pageNotFound', function (HookEvent $event) {
            // Clean output buffering from the template throwing the 404, this should be done by ProcessWire!
            ob_end_clean();
            $event->replace = true;
            $event->return = '404!';
        });

        // Fake a request from our page and let ProcessPageView process it.
        $_GET['it'] = $page->url;
        $processPageView = $this->wire('modules')->get('ProcessPageView');
        $this->wire('process', $processPageView);
        $out = $processPageView->execute();

        $this->assertEquals('404!', $out);
    }

    /**
     * @test
     */
    public function it_should_pass_custom_template_variables_resolved_with_a_hook_to_the_view()
    {
        // Make the above variables available for the template engine.
        $this->addHookAfter('TemplateEngineFactory::resolveTemplateVariables',
            function (HookEvent $event) {
                $event->return = array_merge($event->return, [
                    'customVariable' => 'custom',
                ]);
            });

        $this->fakePath('templates', 'site/modules/TemplateEngineFactory/tests/templates/');

        $engine = $this->registerMockedEngine();

        $template = $this->createTemplate('controller1', dirname(__DIR__) . '/templates/controller1.php');
        $page = $this->createPage($template, '/');

        // These are the variables passed to the template. foo & bar are set in the controller,
        // the "customVariable" has been added via hook above. "_page" is the page being rendered,
        // this key is added by default by the module.
        $expectedVariables = [
            '_page' => $page,
            'foo' => 'foo',
            'bar' => 'bar',
            'customVariable' => 'custom',
        ];

        $engine
            ->expects($this->once())
            ->method('render')
            ->with('controller1', $expectedVariables);

        $page->render();
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

    private function registerProcesswireEngine()
    {
        $this->factory->registerEngine('ProcessWire', new TemplateEngineProcessWire($this->factory->getArray()));
        $this->factory->set('engine', 'ProcessWire');
    }
}
