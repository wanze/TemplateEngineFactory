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

        $this->fakeSitePath($this->wire, 'site/modules/TemplateEngineFactory/tests/');

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
    public function testExecute_dataPassedToController_dataRenderedCorrectlyViaTemplateEngine()
    {
        $controllerFile = dirname(__DIR__) . '/templates/controller-test.php';
        $templateFile = 'controller-test.php';

        $controller = new Controller($this->factory, $controllerFile, $templateFile);
        $controller->set('foo', 'foo');
        $controller->set('bar', 'bar');

        $this->assertEquals('foo => bar', $controller->execute());
    }
}
