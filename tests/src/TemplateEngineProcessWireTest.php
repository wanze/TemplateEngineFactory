<?php

namespace TemplateEngineFactory\Test;

use PHPUnit\Framework\TestCase;
use ProcessWire\ProcessWire;
use ProcessWire\WireException;
use TemplateEngineFactory\TemplateEngineProcessWire;

/**
 * Tests for the TemplateEngineProcessWire template engine.
 *
 * @coversDefaultClass \TemplateEngineFactory\TemplateEngineProcessWire
 *
 * @group TemplateEngineFactory
 */
class TemplateEngineProcessWireTest extends TestCase
{
    use TestHelperTrait;

    /**
     * @var \ProcessWire\ProcessWire
     */
    private $wire;

    /**
     * @var TemplateEngineProcessWire
     */
    private $engine;

    protected function setUp()
    {
        $this->wire = $this->bootstrapProcessWire();

        $this->fakeSitePath();

        $factoryConfig = $this->wire->wire('modules')
            ->get('TemplateEngineFactory')
            ->getArray();

        $this->engine = new TemplateEngineProcessWire($factoryConfig);
    }

    protected function tearDown()
    {
        ProcessWire::removeInstance($this->wire);
    }

    /**
     * @covers ::render
     */
    public function testRender_MissingTemplate_ThrowsException()
    {
        $this->expectException(WireException::class);

        $this->engine->render('this/template/does/not/exist');
    }

    /**
     * @covers ::render
     */
    public function testRender_TemplateWithOrWithoutSuffix_TemplatesFoundAndSameOutput()
    {
        $this->assertEquals('Dummy', $this->engine->render('dummy'));
        $this->assertEquals('Dummy', $this->engine->render('dummy.php'));
    }

    /**
     * @covers ::render
     */
    public function testRender_PassingDataToTemplate_DataAvailableInTemplateAndRenderedCorrectly()
    {
        $series = [
            'Breaking Bad',
            'Sons of Anarchy',
            'Big Bang Theory',
        ];

        // The series are rendered comma separated, @see templates/views/test_data.php
        $expected = implode(',', $series);

        $this->assertEquals($expected, $this->engine->render('test_data', ['series' => $series]));
    }

    /**
     * Let $config->paths->site point to the test directory.
     *
     * This allows to render test templates under /templates/views.
     */
    private function fakeSitePath()
    {
        $paths = $this->wire->wire('config')->paths;
        $paths->set('site', 'site/modules/TemplateEngineFactory/tests/');
    }
}
