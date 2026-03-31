<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Fake registration padding for tournaments
        Schema::table('tournaments', function (Blueprint $table) {
            $table->integer('fake_registrations_count')->default(0)->after('current_players');
        });

        // 2. 8-digit user code for login + display
        Schema::table('users', function (Blueprint $table) {
            $table->string('user_code', 8)->nullable()->unique()->after('uuid');
        });

        // Populate user_code for existing users
        $users = DB::table('users')->whereNull('user_code')->get(['id']);
        foreach ($users as $user) {
            do {
                $code = str_pad(random_int(10000000, 99999999), 8, '0', STR_PAD_LEFT);
            } while (DB::table('users')->where('user_code', $code)->exists());
            DB::table('users')->where('id', $user->id)->update(['user_code' => $code]);
        }

        // Make user_code non-nullable after population
        Schema::table('users', function (Blueprint $table) {
            $table->string('user_code', 8)->nullable(false)->change();
        });

        // 3. Forced winner override on tournament matches (admin can pre-set who will win)
        Schema::table('tournament_matches', function (Blueprint $table) {
            $table->unsignedBigInteger('forced_winner_registration_id')->nullable()->after('winner_registration_id');
            $table->foreign('forced_winner_registration_id')
                  ->references('id')->on('tournament_registrations')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('tournament_matches', function (Blueprint $table) {
            $table->dropForeign(['forced_winner_registration_id']);
            $table->dropColumn('forced_winner_registration_id');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['user_code']);
            $table->dropColumn('user_code');
        });

        Schema::table('tournaments', function (Blueprint $table) {
            $table->dropColumn('fake_registrations_count');
        });
    }
};
