<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Config;
use App\Services\PackagingService;

class PackagingServiceTest extends TestCase
{
    /**
     * @dataProvider packageSelectionProvider
     */
    public function test_selectPackage_picks_correct_container(
        array $items,
        array $envelope,
        array $boxes,
        array $expected
    )
    {
        // 1) Stub config
        Config::set('shipping.envelope', $envelope);
        Config::set('shipping.boxes', $boxes);

        // 2) Wrap each item into stdClass with ->product and ->quantity
        $wrapped = new Collection(array_map(function ($i) {
            return (object)[
                'product' => (object)[
                    'length' => $i['length'],
                    'width' => $i['width'],
                    'height' => $i['height'],
                    'weight' => $i['weight'] ?? 0,  // some tests add weight
                ],
                'quantity' => $i['quantity'],
            ];
        }, $items));

        // 3) Run
        $result = PackagingService::selectPackage($wrapped);

        $this->assertSame($expected['type'], $result['type'], "Expected container type");
        $this->assertEquals($expected['dims'], $result['dims'], "Expected dims");
    }

    public static function packageSelectionProvider(): array
    {
        $env = ['length' => 7.0, 'width' => 4.0, 'height' => 1.0, 'max_weight' => 1.0];
        $boxes = [
            ['length' => 8.5, 'width' => 3.5, 'height' => 3.5, 'max_weight' => 50],
            ['length' => 9.0, 'width' => 6.5, 'height' => 3.5, 'max_weight' => 50],
        ];

        return [
            'single fits envelope' => [
                [['length' => 6, 'width' => 3, 'height' => 0.8, 'quantity' => 1, 'weight' => 0.5]],
                $env, $boxes,
                ['type' => 'envelope', 'dims' => $env],
            ],

            'single overweight envelope' => [
                [['length' => 6, 'width' => 3, 'height' => 0.8, 'quantity' => 1, 'weight' => 2.0]],
                $env, $boxes,
                ['type' => 'box', 'dims' => $boxes[0]],   // <-- corrected here
            ],

            'single fits exactly small box' => [
                [['length' => 8.5, 'width' => 3.5, 'height' => 3.5, 'quantity' => 1, 'weight' => 10]],
                $env, $boxes,
                ['type' => 'box', 'dims' => $boxes[0]],
            ],

            'two small boxes fallback large' => [
                [['length' => 8.5, 'width' => 3.5, 'height' => 3.5, 'quantity' => 2, 'weight' => 1]],
                $env, $boxes,
                ['type' => 'box', 'dims' => $boxes[1]],
            ],

            'many postcards fit small box' => [
                [['length' => 4, 'width' => 3, 'height' => 1, 'quantity' => 10, 'weight' => 0.1]],
                $env, $boxes,
                ['type' => 'box', 'dims' => $boxes[0]],
            ],

            'one item needs large box' => [
                [['length' => 8, 'width' => 4, 'height' => 2, 'quantity' => 1, 'weight' => 5]],
                $env, $boxes,
                ['type' => 'box', 'dims' => $boxes[1]],
            ],

            'mixed dims push to large' => [
                [
                    ['length' => 8, 'width' => 3, 'height' => 3, 'quantity' => 1, 'weight' => 1],
                    ['length' => 7, 'width' => 3, 'height' => 2, 'quantity' => 1, 'weight' => 1],
                ],
                $env, $boxes,
                ['type' => 'box', 'dims' => $boxes[1]],
            ],

            'nothing fits → fallback large' => [
                [['length' => 20, 'width' => 20, 'height' => 20, 'quantity' => 1, 'weight' => 100]],
                $env, $boxes,
                ['type' => 'box', 'dims' => $boxes[1]],
            ],
        ];
    }
}
