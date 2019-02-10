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

        $this->fakePath($this->wire, 'site', 'site/modules/TemplateEngineFactory/tests/');

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

        // The series are rendered comma separated, @see templates/views/test-data.php
        $expected = implode(',', $series);

        $this->assertEquals($expected, $this->engine->render('test-data', ['series' => $series]));
    }
}
