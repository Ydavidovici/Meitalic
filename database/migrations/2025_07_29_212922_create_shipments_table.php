<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateShipmentsTable extends Migration
{
    public function up()
    {
        Schema::create('shipments', function (Blueprint $t) {
            $t->id();
            $t->foreignId('order_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();
            $t->string('label_id')->unique();
            $t->string('tracking_number')->nullable();  // if returned
            $t->string('carrier_code');
            $t->string('service_code');
            $t->decimal('shipment_cost', 8, 2)->nullable();
            $t->decimal('other_cost',    8, 2)->nullable();
            $t->string('label_url')->nullable();        // URL or Base64
            $t->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('shipments');
    }
}