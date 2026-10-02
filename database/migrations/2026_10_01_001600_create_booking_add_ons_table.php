<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Optional add-ons chosen on a booking. Name and price are snapshotted so a
 * later catalogue change never rewrites what someone booked.
 */
return new class () extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('booking_add_ons')) {
            return;
        }

        Schema::create('booking_add_ons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained()->cascadeOnDelete();
            $table->foreignId('add_on_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('pricing_unit', 32);
            $table->unsignedInteger('unit_price_cents')->default(0);
            $table->unsignedInteger('quantity')->default(1);
            $table->unsignedInteger('total_cents')->default(0);
            $table->timestamps();
        });
    }
};
