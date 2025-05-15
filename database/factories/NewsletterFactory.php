<?php

namespace Database\Factories;

use App\Models\Newsletter;
use Illuminate\Database\Eloquent\Factories\Factory;

class NewsletterFactory extends Factory
{
    protected $model = Newsletter::class;

    public function definition()
    {
        // pick one of your configured templates
        $templates = array_keys(config('newsletters.templates'));
        $key       = $this->faker->randomElement($templates);
        $fields    = config("newsletters.templates.{$key}.fields");

        // Generate fake data for each template field
        $data = [];
        foreach ($fields as $field) {
            switch ($field) {
                case 'subject':
                    $data[$field] = $this->faker->sentence(6);
                    break;

                case 'header_text':
                    $data[$field] = $this->faker->words(3, true);
                    break;

                case 'body_text':
                    $data[$field] = $this->faker->paragraphs(3, true);
                    break;

                case 'image_url':
                    $data[$field] = $this->faker->imageUrl(600, 200);
                    break;

                case 'cta_url':
                    $data[$field] = $this->faker->url;
                    break;

                case 'cta_text':
                    $data[$field] = $this->faker->words(2, true);
                    break;
            }
        }

        return array_merge($data, [
            'template_key' => $key,
            'promo_code'   => $this->faker->boolean(50)
                ? strtoupper($this->faker->bothify('PROMO-####'))
                : null,
            'scheduled_at' => $this->faker->boolean(70)
                ? $this->faker->dateTimeBetween('-1 week', '+1 week')
                : null,
            'status'       => 'draft',
        ]);
    }
}
