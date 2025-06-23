<?php

namespace App\Http\Controllers;

use App\Models\Consultation;
use Illuminate\Http\Request;

// App\Http\Controllers\ConsultationController.php
class ConsultationController extends Controller
{
    public function create()
    {
        return view('pages.consultations.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'image' => 'required|image|max:20480',
            'notes' => 'nullable|string',
        ]);

        $path = $request->file('image')->store('consultations', 'public');

        Consultation::create([
            'user_id' => auth()->id(),
            'image_path' => $path,
            'notes' => $request->notes,
        ]);

        return redirect()->route('consultations.index')->with('success', 'Photo uploaded. We’ll get back to you shortly!');
    }

    public function index()
    {
        $consultations = Consultation::where('user_id', auth()->id())->latest()->get();
        return view('pages.consultations.index', compact('consultations'));
    }
}

