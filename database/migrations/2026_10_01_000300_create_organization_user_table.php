<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    private const UNIQUE_MEMBERSHIP = 'organization_user_organization_id_user_id_unique';

    public function up(): void
    {
        if (Schema::hasTable('organization_user')) {
            return;
        }

        Schema::create('organization_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('role', 32)->default('member');
            $table->timestamps();

            $table->unique(['organization_id', 'user_id'], self::UNIQUE_MEMBERSHIP);
        });
    }
};
