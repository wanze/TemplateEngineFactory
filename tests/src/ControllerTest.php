<?php
namespace TemplateEngineFactory\Test;

/**
 * Tests for the Controller class.
 *
 * @coversDefaultClass \TemplateEngineFactory\Controller
 *
 * @group TemplateEngineFactory
 */
class ControllerTest extends ProcessWireTestCaseBase
{
    /**
     * @var \ProcessWire\TemplateEngineFactory
     */
    private $factory;

    protected function setUp()
    {
        $this->fakePath('site', 'site/modules/TemplateEngineFactory/tests/');
        $this->fakePath('templates', 'site/modules/TemplateEngineFactory/tests/templates/');

        $this->factory = $this->wire->wire('modules')->get('TemplateEngineFactory');
        $this->factory->set('engine', 'ProcessWire');
    }

    /**
     * @test
     * @covers ::execute
     */
    public function it_should_pass_data_to_the_controller_and_render_the_associated_template()
    {
        $controllerFile = dirname(__DIR__) . '/templates/controller3.php';
        $templateFile = 'controller1.php';

        $controller = $this->factory->controller($controllerFile, $templateFile);
        $controller->set('foo', 'foo');
        $controller->set('bar', 'bar');

        $this->assertEquals('foo => bar', $controller->execute());
    }

    /**
     * @test
     * @covers ::execute
     */
    public function it_should_resolve_the_controller_file_with_absolute_or_relative_path()
    {
        $controllerAbsolute = dirname(__DIR__) . '/templates/controller3.php';
        $controllerRelative = 'controller3.php';
        $templateFile = 'controller1.php';

        $controller1 = $this->factory->controller($controllerAbsolute, $templateFile);
        $controller2 = $this->factory->controller($controllerRelative, $templateFile);

        $data = ['foo' => 'foo', 'bar' => 'bar'];

        $controller1->setArray($data);
        $controller2->setArray($data);

        $this->assertEquals($controller1->execute(), $controller2->execute());
    }
}
