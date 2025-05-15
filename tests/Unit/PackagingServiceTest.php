<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Illuminate\Support\Collection;
use App\Services\PackagingService;

class PackagingServiceTest extends TestCase
{
    /**
     * @dataProvider packageSelectionProvider
     */
    public function test_selectPackage_picks_correct_container(
        array $items,
        ?array $envelope,
        array $boxes,
        array $expected
    ) {
        // Wrap each item in a stdClass with ->product and ->quantity
        $wrapped = new Collection(array_map(function($i) {
            return (object)[
                'product' => (object)[
                    'length' => $i['length'],
                    'width'  => $i['width'],
                    'height' => $i['height'],
                ],
                'quantity' => $i['quantity'],
            ];
        }, $items));

        $result = PackagingService::selectPackage($wrapped, $boxes, $envelope);

        $this->assertSame($expected['type'], $result['type'], "Expected type {$expected['type']}");
        $this->assertEquals($expected['dims'], $result['dims'], "Expected dims to match");
    }

    public static function packageSelectionProvider(): array
    {
        // define some boxes
        $boxes = [
            ['length'=>8,  'width'=>6,  'height'=>4],   // small (vol=192)
            ['length'=>12, 'width'=>9,  'height'=>6],   // medium (648)
            ['length'=>20, 'width'=>15, 'height'=>10],  // large (3000)
        ];

        // a thin envelope
        $envelope = ['length'=>9, 'width'=>6, 'height'=>0.5]; // vol=27

        return [
            'fits in envelope' => [
                'items'    => [
                    ['length'=>8, 'width'=>5, 'height'=>0.4, 'quantity'=>1],
                ],
                'envelope' => $envelope,
                'boxes'    => $boxes,
                'expected' => [
                    'type' => 'envelope',
                    'dims' => $envelope,
                ],
            ],

            'too tall for envelope → small box' => [
                'items'    => [
                    ['length'=>8, 'width'=>6, 'height'=>2, 'quantity'=>1],
                ],
                'envelope' => $envelope,
                'boxes'    => $boxes,
                'expected' => [
                    'type' => 'box',
                    'dims' => $boxes[0],
                ],
            ],

            'multiple small items exceed envelope vol → small box' => [
                'items'    => [
                    ['length'=>4, 'width'=>3, 'height'=>1, 'quantity'=>10], // total vol=120
                ],
                'envelope' => $envelope,
                'boxes'    => $boxes,
                'expected' => [
                    'type' => 'box',
                    'dims' => $boxes[0],
                ],
            ],

            'requires medium box' => [
                'items'    => [
                    ['length'=>10, 'width'=>8, 'height'=>5, 'quantity'=>1], // max dims
                    ['length'=>6,  'width'=>5, 'height'=>3, 'quantity'=>2],
                ],
                'envelope' => $envelope,
                'boxes'    => $boxes,
                'expected' => [
                    'type' => 'box',
                    'dims' => $boxes[1], // medium
                ],
            ],

            'nothing fits → fallback largest' => [
                'items'    => [
                    ['length'=>25, 'width'=>20, 'height'=>15, 'quantity'=>1],
                ],
                'envelope' => $envelope,
                'boxes'    => $boxes,
                'expected' => [
                    'type' => 'box',
                    'dims' => $boxes[2], // largest
                ],
            ],

            'no envelope provided → box selection' => [
                'items'    => [
                    ['length'=>7, 'width'=>5, 'height'=>3, 'quantity'=>1],
                ],
                'envelope' => null,
                'boxes'    => $boxes,
                'expected' => [
                    'type' => 'box',
                    'dims' => $boxes[0],
                ],
            ],
        ];
    }
}
