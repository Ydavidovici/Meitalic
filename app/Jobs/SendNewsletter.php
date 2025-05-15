<?php

namespace App\Jobs;

use App\Models\Newsletter;
use App\Models\User;
use App\Mail\NewsletterMailable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Mail;

class SendNewsletter implements ShouldQueue
{
    use Dispatchable, Queueable;

    public int $newsletterId;

    /**
     * Create a new job instance.
     *
     * @param  int  $newsletterId
     */
    public function __construct(int $newsletterId)
    {
        $this->newsletterId = $newsletterId;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $nl = Newsletter::findOrFail($this->newsletterId);

        // Only send to active subscribers
        User::where('is_subscribed', true)
            ->chunk(100, function($users) use ($nl) {
                foreach ($users as $user) {
                    Mail::to($user->email)
                        ->queue(new NewsletterMailable($nl));
                }
            });

        // Mark as sent
        $nl->update(['status' => 'sent']);
    }
}
