<?php

namespace TemplateEngineFactory\Test;

use ProcessWire\WireException;
use TemplateEngineFactory\TemplateEngineProcessWire;

/**
 * Tests for the TemplateEngineProcessWire template engine.
 *
 * @coversDefaultClass \TemplateEngineFactory\TemplateEngineProcessWire
 *
 * @group TemplateEngineFactory
 */
class TemplateEngineProcessWireTest extends ProcessWireTestCaseBase
{
    /**
     * @var TemplateEngineProcessWire
     */
    private $engine;

    protected function setUp()
    {
        $this->fakePath('site', 'site/modules/TemplateEngineFactory/tests/');

        $factoryConfig = $this->wire->wire('modules')
            ->get('TemplateEngineFactory')
            ->getArray();

        $this->engine = new TemplateEngineProcessWire($factoryConfig);
    }

    /**
     * @test
     * @covers ::render
     */
    public function it_should_throw_an_exception_if_the_template_file_does_not_exist()
    {
        $this->expectException(WireException::class);

        $this->engine->render('this/template/does/not/exist');
    }

    /**
     * @test
     * @covers ::render
     */
    public function it_should_find_the_template_with_or_without_suffix_and_render_same_output()
    {
        $this->assertEquals('Dummy', $this->engine->render('dummy'));
        $this->assertEquals('Dummy', $this->engine->render('dummy.php'));
    }

    /**
     * @test
     * @covers ::render
     */
    public function it_should_pass_data_to_the_template()
    {
        $series = [
            'Breaking Bad',
            'Sons of Anarchy',
            'Big Bang Theory',
        ];

        // The series are rendered comma separated, @see templates/views/series.php
        $expected = implode(',', $series);

        $this->assertEquals($expected, $this->engine->render('series', ['series' => $series]));
    }
}
