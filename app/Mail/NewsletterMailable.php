<?php

namespace App\Mail;

use App\Models\Newsletter;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class NewsletterMailable extends Mailable
{
    use Queueable, SerializesModels;

    /** @var Newsletter */
    public $newsletter;

    /** @var array */
    public $data;

    /**
     * Create a new message instance.
     *
     * @param  Newsletter  $newsletter
     */
    public function __construct(Newsletter $newsletter)
    {
        $this->newsletter = $newsletter;

        // Determine which Blade view to use based on the template_key
        $config = config('newsletters.templates.' . $newsletter->template_key);
        $this->view = $config['view'];

        // Extract only the configured fields (subject, header_text, body_text, etc.)
        $this->data = collect($config['fields'])
            ->mapWithKeys(fn($field) => [$field => $newsletter->$field])
            ->toArray();

        // Include promo_code if present
        if ($newsletter->promo_code) {
            $this->data['promo_code'] = $newsletter->promo_code;
        }
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this
            ->subject($this->newsletter->subject)
            ->view($this->view, $this->data);
    }
}
