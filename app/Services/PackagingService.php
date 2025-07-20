<?php

namespace App\Services;

use Illuminate\Support\Collection;

class PackagingService
{
    /**
     * Decide whether to ship via envelope, single box, or multiple boxes.
     *
     * @param  Collection $items  each has ->product->{length,width,height,weight} and ->quantity
     * @return array
     *   If envelope or single box:
     *     ['type'=>'envelope'|'box', 'dims'=>[length, width, height], 'maxWeight'=>…]
     *   If multi:
     *     ['type'=>'multi', 'count'=>int, 'dims'=>[length,width,height], 'maxWeight'=>…]
     */
    public static function selectPackage(Collection $items): array
    {
        $env   = config('shipping.envelope');
        $boxes = config('shipping.boxes');

        // 1) Summaries
        $totalWeight = $items->sum(fn($i) => $i->product->weight * $i->quantity);
        $totalUnits  = $items->sum(fn($i) => $i->quantity);

        // 2) Max dims of any single item
        $maxItemDims = $items
            ->map(fn($i) => [
                $i->product->length,
                $i->product->width,
                $i->product->height,
            ])
            ->reduce(fn($carry, $dims) => [
                max($carry[0], $dims[0]),
                max($carry[1], $dims[1]),
                max($carry[2], $dims[2]),
            ], [0,0,0]);

        // 3) Envelope if exactly one unit, under weight, AND fits dims
        if ($totalUnits === 1
            && $totalWeight <= $env['max_weight']
            && static::fitsDims($maxItemDims, [$env['length'],$env['width'],$env['height']])
        ) {
            return [
                'type'      => 'envelope',
                'dims'      => $env,
                'maxWeight' => $env['max_weight'],
            ];
        }

        // 4) Sort boxes by volume
        usort($boxes, fn($a,$b) =>
            ($a['length']*$a['width']*$a['height'])
            <=> ($b['length']*$b['width']*$b['height'])
        );

        // 5) First-fit (and possibly multi-box)
        foreach ($boxes as $box) {
            $containerDims = [$box['length'],$box['width'],$box['height']];

            if ($totalWeight <= $box['max_weight']
                && static::fitsDims($maxItemDims, $containerDims)
            ) {
                // fits into this box—but may need multiple
                if ($totalWeight > $box['max_weight']) {
                    $count = (int) ceil($totalWeight / $box['max_weight']);
                    return [
                        'type'      => 'multi',
                        'count'     => $count,
                        'dims'      => $box,
                        'maxWeight' => $box['max_weight'],
                    ];
                }

                // single box
                return [
                    'type'      => 'box',
                    'dims'      => $box,
                    'maxWeight' => $box['max_weight'],
                ];
            }
        }

        // 6) Fallback to the largest box
        $largest = end($boxes);
        return [
            'type'      => 'box',
            'dims'      => $largest,
            'maxWeight' => $largest['max_weight'],
        ];
    }

    protected static function fitsDims(array $item, array $container): bool
    {
        sort($item); sort($container);
        for ($i = 0; $i < 3; $i++) {
            if ($item[$i] > $container[$i]) {
                return false;
            }
        }
        return true;
    }
}