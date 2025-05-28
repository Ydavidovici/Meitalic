<?php

namespace App\Observers;

use App\Models\Order;
use Illuminate\Support\Facades\Log;

class OrderObserver
{
    public function created(Order $order)
    {
        Log::channel('audit')->info('Order created', [
            'model'           => 'Order',
            'action'          => 'created',
            'id'              => $order->id,
            'user_id'         => $order->user_id,
            'total'           => $order->total,
            'status'          => $order->status,
            'performed_by'    => auth()->id(),
            'timestamp'       => now()->toIso8601String(),
        ]);
    }

    public function updated(Order $order)
    {
        $changes = $order->getChanges();
        Log::channel('audit')->info('Order updated', [
            'model'           => 'Order',
            'action'          => 'updated',
            'id'              => $order->id,
            'changes'         => $changes,
            'performed_by'    => auth()->id(),
            'timestamp'       => now()->toIso8601String(),
        ]);
    }

    public function deleted(Order $order)
    {
        Log::channel('audit')->info('Order deleted', [
            'model'           => 'Order',
            'action'          => 'deleted',
            'id'              => $order->id,
            'user_id'         => $order->user_id,
            'total'           => $order->total,
            'performed_by'    => auth()->id(),
            'timestamp'       => now()->toIso8601String(),
        ]);
    }
}
