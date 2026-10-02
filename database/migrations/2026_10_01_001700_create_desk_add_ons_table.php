<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Which of its office's add-ons a desk or room comes with. A booking can
 * only pick extras attached to the desk being booked. Null overrides fall
 * back to the office's offer.
 */
return new class () extends Migration {
    private const UNIQUE_ATTACHMENT = 'desk_add_ons_desk_id_location_add_on_id_unique';

    public function up(): void
    {
        if (Schema::hasTable('desk_add_ons')) {
            return;
        }

        Schema::create('desk_add_ons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('desk_id')->constrained()->cascadeOnDelete();
            $table->foreignId('location_add_on_id')->constrained()->cascadeOnDelete();
            $table->string('availability', 32)->nullable();
            $table->unsignedInteger('price_cents')->nullable();
            $table->timestamps();

            $table->unique(['desk_id', 'location_add_on_id'], self::UNIQUE_ATTACHMENT);
        });
    }
};
