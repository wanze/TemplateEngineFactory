<?php

namespace TemplateEngineFactory\Test;

use PHPUnit\Framework\TestCase;
use TemplateEngineFactory\TemplateEngineNull;

/**
 * Tests for the TemplateEngineNull class.
 *
 * @coversDefaultClass \TemplateEngineFactory\TemplateEngineNull
 *
 * @group TemplateEngineFactory
 */
class TemplateEngineNullTest extends TestCase
{
    /**
     * @test
     * @dataProvider renderDataProvider
     * @covers ::render
     */
    public function it_should_always_render_an_empty_string($template, array $data)
    {
        $engine = new TemplateEngineNull();
        $this->assertEquals('', $engine->render($template, $data));
    }

    /**
     * @return array
     */
    public function renderDataProvider()
    {
        return [
            [
                '',
                [],
            ],
            [
                'template',
                [],
            ],
            [
                'template',
                ['foo' => 'bar'],
            ],
            [
                '',
                ['foo' => 'bar'],
            ]
        ];
    }
}
