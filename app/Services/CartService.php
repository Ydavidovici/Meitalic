<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Session;

class CartService
{
    protected string $sessionKey = 'cart';

    public function all(): Collection
    {
        return collect(Session::get($this->sessionKey, []));
    }

    public function add(int $productId, int $quantity = 1): void
    {
        $cart = $this->all();

        $existing = $cart->firstWhere('product_id', $productId);

        if ($existing) {
            $existing['quantity'] += $quantity;
            $cart = $cart->map(function ($item) use ($productId, $existing) {
                return $item['product_id'] === $productId ? $existing : $item;
            });
        } else {
            $product = Product::findOrFail($productId);
            $cart->push([
                'product_id' => $product->id,
                'name'       => $product->name,
                'price'      => $product->price,
                'quantity'   => $quantity,
            ]);
        }

        Session::put($this->sessionKey, $cart);
    }

    public function update(int $productId, int $quantity): void
    {
        $cart = $this->all()->map(function ($item) use ($productId, $quantity) {
            if ($item['product_id'] === $productId) {
                $item['quantity'] = $quantity;
            }
            return $item;
        });

        Session::put($this->sessionKey, $cart);
    }

    public function remove(int $productId): void
    {
        $cart = $this->all()->reject(fn($item) => $item['product_id'] === $productId);
        Session::put($this->sessionKey, $cart);
    }

    public function clear(): void
    {
        Session::forget($this->sessionKey);
    }

    public function total(): float
    {
        return $this->all()->sum(fn($item) => $item['price'] * $item['quantity']);
    }

    public function count(): int
    {
        return $this->all()->sum('quantity');
    }
}
