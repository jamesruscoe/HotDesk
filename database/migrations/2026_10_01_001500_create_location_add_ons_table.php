<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    private const UNIQUE_OFFER = 'location_add_ons_location_id_add_on_id_unique';

    public function up(): void
    {
        if (Schema::hasTable('location_add_ons')) {
            return;
        }

        Schema::create('location_add_ons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('location_id')->constrained()->cascadeOnDelete();
            $table->foreignId('add_on_id')->constrained()->cascadeOnDelete();
            $table->string('availability', 32)->default('included');
            // Only meaningful for optional add-ons. Null or 0 is a free opt-in.
            $table->unsignedInteger('price_cents')->nullable();
            $table->string('pricing_unit', 32)->default('per_booking');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['location_id', 'add_on_id'], self::UNIQUE_OFFER);
        });
    }
};
