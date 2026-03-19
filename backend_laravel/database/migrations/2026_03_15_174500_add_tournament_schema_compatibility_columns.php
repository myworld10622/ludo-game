<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('tournaments', function (Blueprint $table) {
            if (! Schema::hasColumn('tournaments', 'uuid')) {
                $table->uuid('uuid')->nullable()->after('id');
            }
            if (! Schema::hasColumn('tournaments', 'type')) {
                $table->string('type')->nullable()->after('code');
            }
            if (! Schema::hasColumn('tournaments', 'allow_multiple_entries')) {
                $table->boolean('allow_multiple_entries')->default(false)->after('entry_fee');
            }
            if (! Schema::hasColumn('tournaments', 'min_total_entries')) {
                $table->unsignedInteger('min_total_entries')->nullable()->after('max_entries_per_user');
            }
            if (! Schema::hasColumn('tournaments', 'ticket_prefix')) {
                $table->string('ticket_prefix', 32)->nullable()->after('max_total_entries');
            }
            if (! Schema::hasColumn('tournaments', 'next_entry_no')) {
                $table->unsignedBigInteger('next_entry_no')->default(1)->after('ticket_prefix');
            }
            if (! Schema::hasColumn('tournaments', 'current_total_entries')) {
                $table->unsignedBigInteger('current_total_entries')->default(0)->after('next_entry_no');
            }
            if (! Schema::hasColumn('tournaments', 'current_active_entries')) {
                $table->unsignedBigInteger('current_active_entries')->default(0)->after('current_total_entries');
            }
            if (! Schema::hasColumn('tournaments', 'entry_open_at')) {
                $table->timestamp('entry_open_at')->nullable()->after('current_active_entries');
            }
            if (! Schema::hasColumn('tournaments', 'entry_close_at')) {
                $table->timestamp('entry_close_at')->nullable()->after('entry_open_at');
            }
            if (! Schema::hasColumn('tournaments', 'start_at')) {
                $table->timestamp('start_at')->nullable()->after('entry_close_at');
            }
            if (! Schema::hasColumn('tournaments', 'end_at')) {
                $table->timestamp('end_at')->nullable()->after('start_at');
            }
            if (! Schema::hasColumn('tournaments', 'completed_at')) {
                $table->timestamp('completed_at')->nullable()->after('end_at');
            }
            if (! Schema::hasColumn('tournaments', 'cancelled_at')) {
                $table->timestamp('cancelled_at')->nullable()->after('completed_at');
            }
            if (! Schema::hasColumn('tournaments', 'rules')) {
                $table->json('rules')->nullable()->after('cancelled_at');
            }
            if (! Schema::hasColumn('tournaments', 'meta')) {
                $table->json('meta')->nullable()->after('rules');
            }
            if (! Schema::hasColumn('tournaments', 'created_by_admin_id')) {
                $table->unsignedBigInteger('created_by_admin_id')->nullable()->after('meta');
            }
            if (! Schema::hasColumn('tournaments', 'updated_by_admin_id')) {
                $table->unsignedBigInteger('updated_by_admin_id')->nullable()->after('created_by_admin_id');
            }
        });

        Schema::table('tournament_entries', function (Blueprint $table) {
            if (! Schema::hasColumn('tournament_entries', 'uuid')) {
                $table->uuid('uuid')->nullable()->after('id');
            }
            if (! Schema::hasColumn('tournament_entries', 'ticket_no')) {
                $table->string('ticket_no')->nullable()->after('entry_no');
            }
            if (! Schema::hasColumn('tournament_entries', 'entry_index_for_user')) {
                $table->unsignedInteger('entry_index_for_user')->nullable()->after('ticket_no');
            }
            if (! Schema::hasColumn('tournament_entries', 'wallet_hold_transaction_id')) {
                $table->unsignedBigInteger('wallet_hold_transaction_id')->nullable()->after('entry_fee');
            }
            if (! Schema::hasColumn('tournament_entries', 'wallet_capture_transaction_id')) {
                $table->unsignedBigInteger('wallet_capture_transaction_id')->nullable()->after('wallet_hold_transaction_id');
            }
            if (! Schema::hasColumn('tournament_entries', 'wallet_refund_transaction_id')) {
                $table->unsignedBigInteger('wallet_refund_transaction_id')->nullable()->after('wallet_capture_transaction_id');
            }
            if (! Schema::hasColumn('tournament_entries', 'joined_at')) {
                $table->timestamp('joined_at')->nullable()->after('wallet_refund_transaction_id');
            }
            if (! Schema::hasColumn('tournament_entries', 'completed_at')) {
                $table->timestamp('completed_at')->nullable()->after('eliminated_at');
            }
        });

        DB::table('tournaments')
            ->select('id', 'code', 'tournament_type', 'allow_re_entry', 'min_players', 'registration_starts_at', 'registration_ends_at', 'starts_at', 'ends_at', 'metadata')
            ->orderBy('id')
            ->chunkById(100, function ($rows) {
                foreach ($rows as $row) {
                    DB::table('tournaments')
                        ->where('id', $row->id)
                        ->update([
                            'uuid' => DB::raw('COALESCE(uuid, UUID())'),
                            'type' => DB::raw("COALESCE(type, " . DB::getPdo()->quote($row->tournament_type ?? 'standard') . ")"),
                            'allow_multiple_entries' => DB::raw('COALESCE(allow_multiple_entries, ' . ((int) ($row->allow_re_entry ?? 0)) . ')'),
                            'min_total_entries' => DB::raw('COALESCE(min_total_entries, ' . ((int) ($row->min_players ?? 2)) . ')'),
                            'ticket_prefix' => DB::raw("COALESCE(ticket_prefix, " . DB::getPdo()->quote(strtoupper(substr((string) ($row->code ?? 'TRN'), 0, 8))) . ")"),
                            'entry_open_at' => DB::raw('COALESCE(entry_open_at, registration_starts_at)'),
                            'entry_close_at' => DB::raw('COALESCE(entry_close_at, registration_ends_at)'),
                            'start_at' => DB::raw('COALESCE(start_at, starts_at)'),
                            'end_at' => DB::raw('COALESCE(end_at, ends_at)'),
                            'meta' => DB::raw('COALESCE(meta, metadata)'),
                        ]);
                }
            });

        DB::table('tournament_entries')
            ->select('id', 'entry_uuid', 'entry_no', 'created_at')
            ->orderBy('id')
            ->chunkById(100, function ($rows) {
                foreach ($rows as $row) {
                    DB::table('tournament_entries')
                        ->where('id', $row->id)
                        ->update([
                            'uuid' => $row->entry_uuid ?: (string) Str::uuid(),
                            'ticket_no' => DB::raw("COALESCE(ticket_no, " . DB::getPdo()->quote('TKT-' . str_pad((string) $row->entry_no, 6, '0', STR_PAD_LEFT)) . ")"),
                            'entry_index_for_user' => DB::raw('COALESCE(entry_index_for_user, 1)'),
                            'joined_at' => DB::raw('COALESCE(joined_at, created_at)'),
                        ]);
                }
            });
    }

    public function down(): void
    {
    }
};
