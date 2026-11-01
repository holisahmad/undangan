<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::unprepared(<<<'SQL'
            CREATE OR REPLACE FUNCTION uuid_max(uuid, uuid)
            RETURNS uuid AS $$
            BEGIN
                IF $1 IS NULL THEN RETURN $2; END IF;
                IF $2 IS NULL THEN RETURN $1; END IF;
                IF $1 > $2 THEN RETURN $1; ELSE RETURN $2; END IF;
            END;
            $$ LANGUAGE plpgsql IMMUTABLE;

            DROP AGGREGATE IF EXISTS max(uuid);
            CREATE AGGREGATE max(uuid) (
                sfunc = uuid_max,
                stype = uuid
            );

            CREATE OR REPLACE FUNCTION uuid_min(uuid, uuid)
            RETURNS uuid AS $$
            BEGIN
                IF $1 IS NULL THEN RETURN $2; END IF;
                IF $2 IS NULL THEN RETURN $1; END IF;
                IF $1 < $2 THEN RETURN $1; ELSE RETURN $2; END IF;
            END;
            $$ LANGUAGE plpgsql IMMUTABLE;

            DROP AGGREGATE IF EXISTS min(uuid);
            CREATE AGGREGATE min(uuid) (
                sfunc = uuid_min,
                stype = uuid
            );
        SQL);
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::unprepared(<<<'SQL'
            DROP AGGREGATE IF EXISTS max(uuid);
            DROP FUNCTION IF EXISTS uuid_max(uuid, uuid);
            DROP AGGREGATE IF EXISTS min(uuid);
            DROP FUNCTION IF EXISTS uuid_min(uuid, uuid);
        SQL);
    }
};
