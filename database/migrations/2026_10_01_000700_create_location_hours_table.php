<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    private const UNIQUE_DAY = 'location_hours_location_id_day_of_week_unique';

    public function up(): void
    {
        if (Schema::hasTable('location_hours')) {
            return;
        }

        Schema::create('location_hours', function (Blueprint $table) {
            $table->id();
            $table->foreignId('location_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('day_of_week');
            // Local wall-clock "HH:MM" in the location's timezone. Stored as
            // strings rather than TIME so a 24-hour office can close at "24:00".
            $table->string('opens_at', 5)->default('09:00');
            $table->string('closes_at', 5)->default('17:00');
            $table->boolean('is_closed')->default(false);
            $table->timestamps();

            $table->unique(['location_id', 'day_of_week'], self::UNIQUE_DAY);
        });
    }
};
