<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Stays empty until a payment provider is plugged in behind PaymentGateway.
 */
return new class () extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('payments')) {
            return;
        }

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('amount_cents');
            $table->char('currency', 3);
            $table->string('provider', 32);
            $table->string('provider_reference')->nullable()->index();
            $table->string('status', 32);
            $table->timestampTz('paid_at')->nullable();
            $table->timestampTz('refunded_at')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }
};
