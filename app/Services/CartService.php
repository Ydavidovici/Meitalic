<?php

namespace App\Services;

use App\Models\Product;
use App\Models\PromoCode;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Session;

class CartService
{
    protected string $sessionKey = 'cart';
    protected string $promoKey = 'promo_code';

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
        Session::forget($this->promoKey);
    }

    public function total(): float
    {
        $subtotal = $this->all()->sum(fn($item) => $item['price'] * $item['quantity']);
        return max(0, $subtotal - $this->getDiscount());
    }

    public function count(): int
    {
        return $this->all()->sum('quantity');
    }

    public function applyPromoCode(string $code): PromoCode
    {
        $promo = PromoCode::where('code', $code)->firstOrFail();

        if (!$promo->isValid()) {
            throw new \Exception("Invalid or expired promo code.");
        }

        Session::put($this->promoKey, $promo->code);
        return $promo;
    }

    public function getDiscount(): float
    {
        $code = Session::get($this->promoKey);
        if (!$code) return 0;

        $promo = PromoCode::where('code', $code)->first();
        $total = $this->all()->sum(fn($item) => $item['price'] * $item['quantity']);

        if (!$promo || !$promo->isValid()) return 0;

        return $promo->discount_percent
            ? $total * ($promo->discount_percent / 100)
            : $promo->discount_amount;
    }

    public function promoCode(): ?string
    {
        return Session::get($this->promoKey);
    }
}
