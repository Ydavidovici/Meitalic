<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use App\Models\User;

class NewsletterController extends Controller
{
    /**
     * Show the newsletter compose form.
     */
    public function form()
    {
        return view('admin.newsletter.form');
    }

    /**
     * Send a one‑off newsletter to all subscribed users.
     */
    public function send(Request $request)
    {
        $data = $request->validate([
            'subject' => 'required|string|max:255',
            'body'    => 'required|string',
        ]);

        $emails = User::where('is_subscribed', true)
            ->pluck('email')
            ->toArray();

        foreach ($emails as $email) {
            Mail::raw($data['body'], function ($m) use ($email, $data) {
                $m->to($email)
                    ->subject($data['subject']);
            });
        }

        return back()->with('success', 'Newsletter sent to '.count($emails).' subscribers.');
    }
}
