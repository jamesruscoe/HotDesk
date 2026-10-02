<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        if (Schema::hasColumn('users', 'timezone')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            // IANA name, detected from the browser on first sign-in.
            $table->string('timezone', 64)->default('UTC')->after('email');
        });
    }
};
