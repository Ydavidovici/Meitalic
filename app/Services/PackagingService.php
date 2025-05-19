<?php
// app/Services/PackagingService.php

namespace App\Services;

use Illuminate\Support\Collection;

class PackagingService
{
    /**
     * @param  Collection $items  each has ->product->{length,width,height,weight} and ->quantity
     * @return array{type:string, dims:array{length:float, width:float, height:float}}
     */
    public static function selectPackage(Collection $items): array
    {
        $env   = config('shipping.envelope');
        $boxes = config('shipping.boxes');

        // 1) Summaries
        $totalWeight  = $items->sum(fn($i) => $i->product->weight * $i->quantity);
        $totalUnits   = $items->sum(fn($i) => $i->quantity);
        $uniqueTypes  = $items->count();

        // 1.a) Max single-item edge in each dimension
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

        // 2) Envelope: only if exactly one *unit* and it fits
        // app/Services/PackagingService.php

        if ($totalUnits === 1
            && $totalWeight <= $env['max_weight']
        ) {
            return [
                'type' => 'envelope',
                'dims' => $env,   // <-- return full envelope array, including max_weight
            ];
        }



        // 3) Sort boxes by ascending volume
        usort($boxes, fn($a,$b) =>
            ($a['length']*$a['width']*$a['height'])
            <=> ($b['length']*$b['width']*$b['height'])
        );

        // 4) First-fit box
        foreach ($boxes as $box) {
            $dims = [$box['length'],$box['width'],$box['height']];

            // must individually fit
            if ($totalWeight <= $box['max_weight']
                && static::fitsDims($maxItemDims, $dims)
            ) {
                // if more than one unit, apply stacking logic
                if ($totalUnits > 1) {
                    if ($uniqueTypes === 1) {
                        // identical items → simple “at least one axis strictly bigger”
                        if (! static::canStackIdentical($maxItemDims, $dims)) {
                            continue;
                        }
                    } else {
                        // mixed items → must be able to line them up along some axis
                        $sumL = $items->sum(fn($i)=> $i->product->length * $i->quantity);
                        $sumW = $items->sum(fn($i)=> $i->product->width  * $i->quantity);
                        $sumH = $items->sum(fn($i)=> $i->product->height * $i->quantity);

                        if (! (
                            $sumL <= $box['length']
                            || $sumW <= $box['width']
                            || $sumH <= $box['height']
                        )) {
                            continue;
                        }
                    }
                }

                // passed all checks!
                return ['type'=>'box','dims'=>$box];
            }
        }

        // 5) Fallback to the largest
        return ['type'=>'box','dims'=>end($boxes)];
    }

    /**
     * Can we stack multiple *identical* items in this box?
     * (i.e. is there at least one axis where the box is strictly
     * larger than the single‐item max dimension)
     */
    protected static function canStackIdentical(array $item, array $container): bool
    {
        sort($item);            // small→large
        sort($container);       // small→large
        for ($i = 0; $i < 3; $i++) {
            if ($container[$i] > $item[$i]) {
                return true;
            }
        }
        return false;
    }

    /**
     * Can a single item of dims `$item` (l,w,h) fit into
     * `$container` (L,W,H) under some orientation?
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
