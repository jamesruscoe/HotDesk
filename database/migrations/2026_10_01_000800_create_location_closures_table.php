<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    private const UNIQUE_DATE = 'location_closures_location_id_date_unique';

    public function up(): void
    {
        if (Schema::hasTable('location_closures')) {
            return;
        }

        Schema::create('location_closures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('location_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->string('reason')->nullable();
            $table->timestamps();

            $table->unique(['location_id', 'date'], self::UNIQUE_DATE);
        });
    }
};
