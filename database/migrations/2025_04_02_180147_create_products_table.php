<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProductsTable extends Migration
{
    public function up()
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('brand');
            $table->string('category');
            $table->text('description');
            $table->decimal('weight', 8, 2)->default(0);
            $table->decimal('length', 8, 2)->default(0);
            $table->decimal('width',  8, 2)->default(0);
            $table->decimal('height', 8, 2)->default(0);
            $table->decimal('price', 8, 2);
            $table->string('image')->nullable();
            $table->string('sku')->unique();
            $table->json('options')->nullable();
            $table->boolean('active')->default(true);
            $table->boolean('is_featured')->default(false);
            $table->integer('inventory');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('products');
    }
}
