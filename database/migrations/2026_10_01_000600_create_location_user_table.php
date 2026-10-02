<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Optional per-location access. A member with no rows here can see every
 * location in their organization; a member with rows sees only those.
 */
return new class () extends Migration {
    private const UNIQUE_ACCESS = 'location_user_location_id_user_id_unique';

    public function up(): void
    {
        if (Schema::hasTable('location_user')) {
            return;
        }

        Schema::create('location_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('location_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['location_id', 'user_id'], self::UNIQUE_ACCESS);
        });
    }
};
