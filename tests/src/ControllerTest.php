<?php
namespace TemplateEngineFactory\Test;

use PHPUnit\Framework\TestCase;
use ProcessWire\ProcessWire;
use ProcessWire\TemplateEngineFactory;
use TemplateEngineFactory\TemplateEngineProcesswire;
use TemplateEngineFactory\Controller;

/**
 * Tests for the Controller class.
 *
 * @coversDefaultClass \TemplateEngineFactory\Controller
 */
class ControllerTest extends TestCase
{
    use TestHelperTrait;

    /**
     * @var \ProcessWire\ProcessWire
     */
    private $wire;

    /**
     * @var \ProcessWire\TemplateEngineFactory
     */
    private $factory;

    protected function setUp()
    {
        $this->wire = $this->bootstrapProcessWire();

        $this->fakePath($this->wire, 'site', 'site/modules/TemplateEngineFactory/tests/');
        $this->fakePath($this->wire, 'templates', 'site/modules/TemplateEngineFactory/tests/templates/');

        $this->factory = new TemplateEngineFactory();
        $this->factory->registerEngine('ProcessWire', new TemplateEngineProcesswire($this->factory->getArray()));
        $this->factory->set('engine', 'ProcessWire');
    }

    protected function tearDown()
    {
        ProcessWire::removeInstance($this->wire);
    }

    /**
     * @covers ::execute
     */
    public function testExecute_passDataToController_dataRenderedCorrectlyViaTemplateEngine()
    {
        $controllerFile = dirname(__DIR__) . '/templates/controller-test.php';
        $templateFile = 'controller-test.php';

        $controller = new Controller($this->factory, $controllerFile, $templateFile);
        $controller->set('foo', 'foo');
        $controller->set('bar', 'bar');

        $this->assertEquals('foo => bar', $controller->execute());
    }

    public function testConstructor_initializeWithRelativeOrAbsoluteController_SameFileIsUsed()
    {
        $controllerAbsolute = dirname(__DIR__) . '/templates/controller-test.php';
        $controllerRelative = 'controller-test.php';
        $templateFile = 'controller-test.php';

        $controller1 = new Controller($this->factory, $controllerAbsolute, $templateFile);
        $controller2 = new Controller($this->factory, $controllerRelative, $templateFile);

        $data = ['foo' => 'foo', 'bar' => 'bar'];

        $controller1->setArray($data);
        $controller2->setArray($data);

        $this->assertEquals($controller1->execute(), $controller2->execute());
    }
}
