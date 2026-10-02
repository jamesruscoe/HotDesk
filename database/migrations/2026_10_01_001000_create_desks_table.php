<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('desks')) {
            return;
        }

        Schema::create('desks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('floor_id')->constrained()->cascadeOnDelete();
            $table->string('label');
            $table->string('type', 32)->default('desk');
            $table->decimal('x', 10, 2)->default(0);
            $table->decimal('y', 10, 2)->default(0);
            $table->decimal('width', 10, 2)->default(60);
            $table->decimal('height', 10, 2)->default(40);
            $table->decimal('rotation', 6, 2)->default(0);
            $table->json('amenities')->nullable();
            $table->boolean('is_bookable')->default(true);
            // Permanent assignment. Only this user may book the desk while set.
            $table->foreignId('assigned_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('qr_token', 64)->unique();
            // Payment-ready pricing. Null falls back to the location's rate.
            $table->unsignedInteger('hourly_rate_cents')->nullable();
            $table->unsignedInteger('daily_rate_cents')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();
        });
    }
};
