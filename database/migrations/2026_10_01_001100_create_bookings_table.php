<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    // Overlap lookups filter by desk (or user) first, then range-scan the window.
    private const DESK_WINDOW_INDEX = 'bookings_desk_id_starts_at_ends_at_index';
    private const USER_WINDOW_INDEX = 'bookings_user_id_starts_at_ends_at_index';

    public function up(): void
    {
        if (Schema::hasTable('bookings')) {
            return;
        }

        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('desk_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            // Links the individual days of a multi-day booking.
            $table->uuid('booking_group_id')->nullable()->index();
            $table->string('type', 32);
            $table->string('status', 32)->default('confirmed');
            // Always UTC.
            $table->timestampTz('starts_at');
            $table->timestampTz('ends_at');
            $table->timestampTz('checked_in_at')->nullable();
            $table->timestampTz('checked_out_at')->nullable();
            $table->timestampTz('cancelled_at')->nullable();
            $table->unsignedInteger('price_cents')->default(0);
            $table->char('currency', 3)->nullable();
            $table->string('payment_status', 32)->default('not_required');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();

            $table->index(['desk_id', 'starts_at', 'ends_at'], self::DESK_WINDOW_INDEX);
            $table->index(['user_id', 'starts_at', 'ends_at'], self::USER_WINDOW_INDEX);
        });
    }
};
