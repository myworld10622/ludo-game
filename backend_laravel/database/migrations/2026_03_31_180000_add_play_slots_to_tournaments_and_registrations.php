<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tournaments', function (Blueprint $table) {
            $table->json('play_slots')->nullable()->after('terms_conditions');
        });

        Schema::table('tournament_registrations', function (Blueprint $table) {
            $table->timestamp('last_checked_in_at')->nullable()->after('registered_at');
            $table->unsignedTinyInteger('last_checked_in_slot_index')->nullable()->after('last_checked_in_at');
        });
    }

    public function down(): void
    {
        Schema::table('tournament_registrations', function (Blueprint $table) {
            $table->dropColumn(['last_checked_in_at', 'last_checked_in_slot_index']);
        });

        Schema::table('tournaments', function (Blueprint $table) {
            $table->dropColumn('play_slots');
        });
    }
};
