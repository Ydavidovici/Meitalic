<?php

namespace App\Services;

class PackagingService
{
    /**
     * Pick the smallest container (box or envelope) that will fit these items.
     *
     * @param  \Illuminate\Support\Collection  $items    // each has product->length/width/height
     * @param  array                          $boxes    // from config('shipping.boxes')
     * @param  array|null                     $envelope // from config('shipping.envelope') or null
     * @return array ['type'=>'box'|'envelope', 'dims'=>['length','width','height']]
     */
    public static function selectPackage($items, array $boxes, ?array $envelope = null): array
    {
        // 1) Compute total volume, and item max dimensions
        $maxL = $maxW = $maxH = 0;
        $totalVol = 0;
        foreach ($items as $i) {
            $p = $i->product;
            $maxL = max($maxL, $p->length);
            $maxW = max($maxW, $p->width);
            $maxH = max($maxH, $p->height);
            $totalVol += ($p->length * $p->width * $p->height) * $i->quantity;
        }

        // 2) Try envelope first (if provided)
        if ($envelope) {
            $envVol = $envelope['length'] * $envelope['width'] * $envelope['height'];
            if (
                $envelope['length'] >= $maxL &&
                $envelope['width']  >= $maxW &&
                $envelope['height'] >= $maxH &&
                $envVol >= $totalVol
            ) {
                return [
                    'type' => 'envelope',
                    'dims' => $envelope,
                ];
            }
        }

        // 3) Otherwise pick the smallest box
        usort($boxes, fn($a,$b) =>
            ($a['length'] * $a['width'] * $a['height'])
            <=> ($b['length'] * $b['width'] * $b['height'])
        );

        foreach ($boxes as $b) {
            $boxVol = $b['length'] * $b['width'] * $b['height'];
            if (
                $b['length'] >= $maxL &&
                $b['width']  >= $maxW &&
                $b['height'] >= $maxH &&
                $boxVol >= $totalVol
            ) {
                return [
                    'type' => 'box',
                    'dims' => $b,
                ];
            }
        }

        // 4) Fallback: use the largest box
        $biggest = end($boxes);
        return [
            'type' => 'box',
            'dims' => $biggest,
        ];
    }
}
