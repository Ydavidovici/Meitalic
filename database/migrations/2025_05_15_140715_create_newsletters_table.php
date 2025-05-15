<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('newsletters', function (Blueprint $table) {
            $table->id();
            $table->string('template_key');
            $table->string('subject');
            $table->string('header_text');
            $table->text('body_text');
            $table->string('image_url')->nullable();
            $table->string('cta_url')->nullable();
            $table->string('cta_text')->nullable();
            $table->string('promo_code')->nullable();
            $table->timestamp('scheduled_at')->nullable();
            $table->enum('status', ['draft','scheduled','sent'])->default('draft');
            $table->timestamps();
        });
    }


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('newsletters');
    }
};
