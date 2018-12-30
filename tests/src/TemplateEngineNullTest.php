<?php

namespace TemplateEngineFactory\Test;

use PHPUnit\Framework\TestCase;
use TemplateEngineFactory\TemplateEngineNull;

/**
 * Tests for the TemplateEngineNull class.
 *
 * @coversDefaultClass \ProcessWire\TemplateEngineFactory
 *
 * @group TemplateEngineFactory
 */
class TemplateEngineNullTest extends TestCase
{
    /**
     * @dataProvider renderDataProvider
     *
     * @covers ::render
     */
    public function testRender_PassDifferentTemplatesAndData_AlwaysReturnsEmptyString($template, array $data)
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
