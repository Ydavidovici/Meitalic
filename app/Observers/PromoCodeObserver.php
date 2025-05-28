<?php

namespace App\Observers;

use App\Models\PromoCode;
use Illuminate\Support\Facades\Log;

class PromoCodeObserver
{
    public function created(PromoCode $promo)
    {
        Log::channel('audit')->info('PromoCode created', [
            'model'           => 'PromoCode',
            'action'          => 'created',
            'id'              => $promo->id,
            'code'            => $promo->code,
            'discount'        => $promo->discount,
            'expires_at'      => $promo->expires_at,
            'performed_by'    => auth()->id(),
            'timestamp'       => now()->toIso8601String(),
        ]);
    }

    public function updated(PromoCode $promo)
    {
        $changes = $promo->getChanges();
        Log::channel('audit')->info('PromoCode updated', [
            'model'           => 'PromoCode',
            'action'          => 'updated',
            'id'              => $promo->id,
            'changes'         => $changes,
            'performed_by'    => auth()->id(),
            'timestamp'       => now()->toIso8601String(),
        ]);
    }

    public function deleted(PromoCode $promo)
    {
        Log::channel('audit')->info('PromoCode deleted', [
            'model'           => 'PromoCode',
            'action'          => 'deleted',
            'id'              => $promo->id,
            'code'            => $promo->code,
            'performed_by'    => auth()->id(),
            'timestamp'       => now()->toIso8601String(),
        ]);
    }
}
