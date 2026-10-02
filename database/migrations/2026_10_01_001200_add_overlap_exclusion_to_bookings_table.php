<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Database-level guarantee that two live bookings never overlap on one desk.
 * BookingService checks first and returns a friendly error; this catches the
 * race where two requests pass that check at the same moment.
 *
 * Postgres only. Other drivers rely on the service's locked check.
 */
return new class () extends Migration {
    private const CONSTRAINT = 'bookings_desk_no_overlap';

    public function up(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        if (DB::table('pg_constraint')->where('conname', self::CONSTRAINT)->exists()) {
            return;
        }

        DB::statement('CREATE EXTENSION IF NOT EXISTS btree_gist');

        // '[)' makes ranges half-open, so 09:00-10:00 and 10:00-11:00 on the
        // same desk do not collide.
        DB::statement(sprintf(
            "ALTER TABLE bookings ADD CONSTRAINT %s EXCLUDE USING gist (
                desk_id WITH =,
                tstzrange(starts_at, ends_at, '[)') WITH &&
            ) WHERE (status NOT IN ('cancelled', 'no_show') AND deleted_at IS NULL)",
            self::CONSTRAINT,
        ));
    }
};
