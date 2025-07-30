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
     *   If envelope:
     *     ['type'=>'envelope', 'dims'=>[length,width,height,max_weight], 'maxWeight'=>…]
     *   If single box or fallback:
     *     ['type'=>'box',      'dims'=>[length,width,height,max_weight], 'maxWeight'=>…]
     *   If multi due to weight:
     *     ['type'=>'multi',    'count'=>int, 'dims'=>[length,width,height,max_weight], 'maxWeight'=>…]
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
            ], [0, 0, 0]);

        // 3) Envelope if exactly one unit, under weight, AND fits dims
        if ($totalUnits === 1
            && $totalWeight <= $env['max_weight']
            && static::fitsDims($maxItemDims, [$env['length'], $env['width'], $env['height']])
        ) {
            return [
                'type'      => 'envelope',
                'dims'      => $env + ['max_weight' => $env['max_weight']],
                'maxWeight' => $env['max_weight'],
            ];
        }

        // 4) Sort boxes by volume (smallest first)
        usort($boxes, fn($a, $b) =>
            ($a['length'] * $a['width'] * $a['height'])
            <=> ($b['length'] * $b['width'] * $b['height'])
        );

        // 5) First-fit boxes
        foreach ($boxes as $box) {
            $containerDims = [$box['length'], $box['width'], $box['height']];

            // 5a) Must fit single item dims
            if (! static::fitsDims($maxItemDims, $containerDims)) {
                continue;
            }

            // 5b) Multi due to weight only (after dims check)
            if ($totalWeight > $box['max_weight']) {
                $count = (int) ceil($totalWeight / $box['max_weight']);
                return [
                    'type'      => 'multi',
                    'count'     => $count,
                    'dims'      => $box,
                    'maxWeight' => $box['max_weight'],
                ];
            }

            // 5c) Handle multiple units for dim collisions
            if ($totalUnits > 1) {
                // Skip if container dims exactly equal item dims (can't pack more than one)
                $exactMatch = (
                    $maxItemDims[0] === $box['length']
                    && $maxItemDims[1] === $box['width']
                    && $maxItemDims[2] === $box['height']
                );

                // Skip if mixed item dimensions (cannot guarantee fit)
                $allSameDims = $items->every(fn($i) =>
                    $i->product->length === $maxItemDims[0]
                    && $i->product->width  === $maxItemDims[1]
                    && $i->product->height === $maxItemDims[2]
                );

                if ($exactMatch || ! $allSameDims) {
                    continue;
                }
            }

            // Fits this box
            return [
                'type'      => 'box',
                'dims'      => $box,
                'maxWeight' => $box['max_weight'],
            ];
        }

        // 6) Fallback to largest box
        $largest = end($boxes);
        return [
            'type'      => 'box',
            'dims'      => $largest,
            'maxWeight' => $largest['max_weight'],
        ];
    }

    /**
     * Check if a single item (by dims) fits inside a container (by dims).
     */
    protected static function fitsDims(array $item, array $container): bool
    {
        sort($item);
        sort($container);
        for ($i = 0; $i < 3; $i++) {
            if ($item[$i] > $container[$i]) {
                return false;
            }
        }
        return true;
    }
}