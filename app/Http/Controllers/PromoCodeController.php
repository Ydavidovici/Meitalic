<?php

namespace App\Http\Controllers;

use App\Models\PromoCode;
use Illuminate\Http\Request;

class PromoCodeController extends Controller
{
    // Store new promo code
    public function store(Request $request)
    {
        $validated = $request->validate([
            'code'       => 'required|string|unique:promo_codes,code',
            'type'       => 'required|in:fixed,percent',
            'discount'   => 'required|numeric|min:0',
            'max_uses'   => 'nullable|integer|min:1',
            'expires_at' => 'nullable|date',
            'active'     => 'boolean',
        ]);

        PromoCode::create($validated);

        return redirect()->route('admin.dashboard')
            ->with('success', 'Promo code created successfully.');
    }

    // Update promo code
    public function update(Request $request, PromoCode $promo)
    {
        $validated = $request->validate([
            'code'       => 'required|string|unique:promo_codes,code,' . $promo->id,
            'type'       => 'required|in:fixed,percent',
            'discount'   => 'required|numeric|min:0',
            'max_uses'   => 'nullable|integer|min:1',
            'expires_at' => 'nullable|date',
            'active'     => 'boolean',
        ]);

        $promo->update($validated);

        return redirect()->route('admin.dashboard')
            ->with('success', 'Promo code updated successfully.');
    }

    // Delete promo code
    public function destroy(PromoCode $promo)
    {
        $promo->delete();

        return redirect()->route('admin.dashboard')
            ->with('success', 'Promo code deleted.');
    }
}
