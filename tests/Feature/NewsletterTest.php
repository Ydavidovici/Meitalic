<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Mail;
use App\Models\User;
use App\Models\Newsletter;
use App\Jobs\SendNewsletter;
use App\Mail\NewsletterMailable;

class NewsletterTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Define a minimal newsletter template for testing
        config([
            'newsletters.templates' => [
                'standard' => [
                    'name'   => 'Standard',
                    'view'   => 'emails.newsletter.standard',
                    'fields' => ['subject','header_text','body_text'],
                ],
            ],
        ]);

        // Create an admin user and two subscribers
        $this->admin = User::factory()->create(['is_admin' => true]);
        $this->sub1  = User::factory()->create(['is_subscribed' => true]);
        $this->sub2  = User::factory()->create(['is_subscribed' => true]);
        $this->unsub = User::factory()->create(['is_subscribed' => false]);
    }

    /** @test */
    public function admin_can_schedule_and_dispatch_newsletter_job()
    {
        Bus::fake();

        $this->actingAs($this->admin)
            ->post(route('admin.newsletter.store'), [
                'template_key' => 'standard',
                'subject'      => 'Test Subject',
                'header_text'  => 'Hello Subscribers',
                'body_text'    => 'This is a test newsletter.',
                // no promo fields
            ])
            ->assertRedirect(route('admin.dashboard'))
            ->assertSessionHas('success');

        // Newsletter record created
        $newsletter = Newsletter::first();
        $this->assertNotNull($newsletter);
        $this->assertEquals('scheduled', $newsletter->status);
        $this->assertNull($newsletter->promo_code);

        // Job was dispatched with correct newsletter ID
        Bus::assertDispatched(SendNewsletter::class, function ($job) use ($newsletter) {
            return $job->newsletterId === $newsletter->id;
        });
    }

    /** @test */
    public function send_newsletter_job_handles_sending_and_marks_as_sent()
    {
        Mail::fake();

        // Create a scheduled newsletter
        $newsletter = Newsletter::create([
            'template_key' => 'standard',
            'subject'      => 'Hello!',
            'header_text'  => 'Greetings',
            'body_text'    => 'Newsletter body here.',
            'status'       => 'scheduled',
        ]);

        // Dispatch synchronously to run handle() immediately
        (new SendNewsletter($newsletter->id))->handle();

        // The newsletter status should update to 'sent'
        $this->assertEquals('sent', $newsletter->fresh()->status);

        // Two subscribers should each receive a queued NewsletterMailable
        Mail::assertQueued(NewsletterMailable::class, 2);

        // And none for the unsubscribed user
        Mail::assertNotQueued(NewsletterMailable::class, function ($mailable) {
            return $mailable->hasTo($this->unsub->email);
        });
    }

    /** @test */
    public function admin_can_schedule_newsletter_with_promo_code()
    {
        Bus::fake();

        $this->actingAs($this->admin)
            ->post(route('admin.newsletter.store'), [
                'template_key'   => 'standard',
                'subject'        => 'Promo Newsletter',
                'header_text'    => 'Special Offer',
                'body_text'      => 'Use the code below!',
                'promo_code'     => 'TESTPROMO',
                'promo_type'     => 'percent',
                'promo_discount' => '20',
            ])
            ->assertRedirect(route('admin.dashboard'))
            ->assertSessionHas('success');

        $newsletter = Newsletter::first();
        $this->assertEquals('TESTPROMO', $newsletter->promo_code);

        // Ensure job dispatches
        Bus::assertDispatched(SendNewsletter::class, fn($job) => $job->newsletterId === $newsletter->id);
    }
}
