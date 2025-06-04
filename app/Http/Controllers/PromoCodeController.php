<?php

namespace App\Http\Controllers;

use App\Models\PromoCode;
use Illuminate\Http\Request;

class PromoCodeController extends Controller
{
    /** POST /admin/promo */
    public function store(Request $request)
    {
        $data = $request->validate([
            'code'       => 'required|string|unique:promo_codes,code',
            'type'       => 'required|in:fixed,percent',
            'discount'   => 'required|numeric|min:0',
            'max_uses'   => 'nullable|integer|min:1',
            'expires_at' => 'nullable|date',
            'active'     => 'boolean',
        ]);

        PromoCode::create($data);

        return redirect()->route('admin.dashboard')
            ->with('success','Promo code created!');
    }

    /** PUT  /admin/promo/{promo} */
    public function update(Request $request, PromoCode $promo)
    {
        /*
        $data = $request->validate([
            'code'        => 'required|string|unique:promo_codes,code,' . $promo->id,
            'type'        => 'required|in:fixed,percent',
            'discount'    => 'required|numeric|min:0',
            'max_uses'    => 'nullable|integer|min:1',
            'used_count'  => 'required|integer|min:0',
            'expires_at'  => 'nullable|date',
            'active'      => 'boolean',
        ]);

        $promo->update($data);
        */

        $validated = $request->validate([
            'code'      => 'required|string',
            'type'      => 'required|in:fixed,percent',
            'discount'  => 'required|numeric',
            'max_uses'  => 'nullable|integer',
            'expires_at'=> 'nullable|date',
            'active'    => 'nullable|boolean',
        ]);

        $promo->update($validated);


        return redirect()->route('admin.dashboard')
            ->with('success','Promo code updated!');
    }

    /** DELETE /admin/promo/{promo} */
    public function destroy(PromoCode $promo)
    {
        $promo->delete();

        return redirect()->route('admin.dashboard')
            ->with('success','Promo code deleted.');
    }
}
