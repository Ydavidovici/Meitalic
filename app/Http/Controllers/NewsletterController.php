<?php

namespace App\Http\Controllers;

use App\Models\Newsletter;
use App\Models\PromoCode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Jobs\SendNewsletter;

class NewsletterController extends Controller
{
    /**
     * Display a list of past newsletters.
     */
    public function index()
    {
        $newsletters = Newsletter::latest()->paginate(3);
        $templates   = config('newsletters.templates');
        return view('admin.dashboard', compact('newsletters','templates'));
    }

    /**
     * Show the form to compose a new newsletter.
     */
    public function create()
    {
        $templates = config('newsletters.templates');
        return view('admin.dashboard', compact('templates'));
    }

    /**
     * Store the newsletter in the database and dispatch the send job.
     */
    public function store(Request $request)
    {
        $templates = config('newsletters.templates');
        $keys      = implode(',', array_keys($templates));

        // Base validation rules
        $rules = [
            'template_key'    => "required|string|in:{$keys}",
            'scheduled_at'    => 'nullable|date',
            // promo fields
            'promo_code'      => 'nullable|string|unique:promo_codes,code',
            'promo_type'      => 'nullable|required_with:promo_code|in:fixed,percent',
            'promo_discount'  => 'nullable|required_with:promo_code|numeric|min:0',
            'promo_max_uses'  => 'nullable|integer|min:1',
            'promo_expires_at'=> 'nullable|date',
            'promo_active'    => 'boolean',
        ];

        // Add rules for each template field
        $fields = $templates[$request->input('template_key')]['fields'];
        foreach ($fields as $field) {
            $rules[$field] = 'required|string';
        }

        $data = $request->validate($rules);

        // 1) Create promo code if provided
        $code = null;
        if (!empty($data['promo_code'])) {
            $promo = PromoCode::create([
                'code'       => $data['promo_code'],
                'type'       => $data['promo_type'],
                'discount'   => $data['promo_discount'],
                'max_uses'   => $data['promo_max_uses'] ?? null,
                'expires_at' => $data['promo_expires_at'] ?? null,
                'active'     => $data['promo_active'] ?? true,
            ]);
            $code = $promo->code;
        }

        // 2) Persist the newsletter record, including the promo_code
        $newsletter = Newsletter::create(array_merge(
            ['status' => 'scheduled', 'promo_code' => $code],
            $request->only(array_merge(['template_key','scheduled_at'], $fields))
        ));

        // 3) Dispatch the background job to send this newsletter
        SendNewsletter::dispatch($newsletter->id);

        return redirect()
            ->route('admin.dashboard')
            ->with('success', 'Newsletter scheduled and promo code created successfully.');
    }
}
