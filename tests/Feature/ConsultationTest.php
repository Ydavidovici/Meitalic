<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use App\Models\User;
use App\Models\Consultation;

class ConsultationTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function guest_is_redirected_from_consultation_form()
    {
        $response = $this->get(route('consultations.create'));
        $response->assertRedirect(route('login'));
    }

    /** @test */
    public function user_can_view_consultation_form()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->get(route('consultations.create'));
        $response->assertStatus(200)
            ->assertViewIs('pages.consultations.create');
    }

    /** @test */
    public function user_can_submit_consultation_request()
    {
        Storage::fake('public');
        $user = User::factory()->create();
        $this->actingAs($user);

        $file = UploadedFile::fake()->image('face.jpg');
        $response = $this->post(route('consultations.store'), [
            'image' => $file,
            'notes' => 'Need advice',
        ]);

        $response->assertRedirect(route('consultations.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('consultations', [
            'user_id'    => $user->id,
            'notes'      => 'Need advice',
        ]);
        Storage::disk('public')->assertExists('consultations/'.$file->hashName());
    }

    /** @test */
    public function user_can_view_their_consultations()
    {
        $user = User::factory()->create();
        Consultation::factory()->count(2)->create(['user_id' => $user->id]);
        $this->actingAs($user);

        $response = $this->get(route('consultations.index'));
        $response->assertStatus(200)
            ->assertViewHas('consultations', function ($consultations) {
                return $consultations->count() === 2;
            });
    }
}
