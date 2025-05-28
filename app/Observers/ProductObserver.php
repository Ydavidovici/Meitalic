<?php

namespace App\Observers;

use App\Models\Product;
use Illuminate\Support\Facades\Log;

class ProductObserver
{
    public function created(Product $product)
    {
        Log::channel('audit')->info('Product created', [
            'model'        => 'Product',
            'action'       => 'created',
            'id'           => $product->id,
            'name'         => $product->name,
            'performed_by' => auth()->id(),
            'timestamp'    => now()->toIso8601String(),
        ]);
    }

    public function updated(Product $product)
    {
        $changes = $product->getChanges();
        Log::channel('audit')->info('Product updated', [
            'model'        => 'Product',
            'action'       => 'updated',
            'id'           => $product->id,
            'changes'      => $changes,
            'performed_by' => auth()->id(),
            'timestamp'    => now()->toIso8601String(),
        ]);
    }

    public function deleted(Product $product)
    {
        Log::channel('audit')->info('Product deleted', [
            'model'        => 'Product',
            'action'       => 'deleted',
            'id'           => $product->id,
            'name'         => $product->name,
            'performed_by' => auth()->id(),
            'timestamp'    => now()->toIso8601String(),
        ]);
    }
}
