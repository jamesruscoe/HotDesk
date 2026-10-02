<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('floors')) {
            return;
        }

        Schema::create('floors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('location_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->integer('level')->default(0);
            // Canvas size in plan units; desk coordinates are relative to it.
            $table->unsignedInteger('width')->default(1200);
            $table->unsignedInteger('height')->default(800);
            // Drawn structure only (walls, rooms, labels). Desks are real rows.
            $table->json('layout')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();
        });
    }
};
