<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasTable('rox_gateway_transactions')) {
            return;
        }

        Schema::table('rox_gateway_transactions', function (Blueprint $table) {
            if (! Schema::hasColumn('rox_gateway_transactions', 'legacy_order_id')) {
                $table->unsignedBigInteger('legacy_order_id')->nullable()->after('user_id');
            }
            if (! Schema::hasColumn('rox_gateway_transactions', 'legacy_user_id')) {
                $table->unsignedBigInteger('legacy_user_id')->nullable()->after('legacy_order_id');
            }
            if (! Schema::hasColumn('rox_gateway_transactions', 'gateway_transaction_id')) {
                $table->string('gateway_transaction_id')->nullable()->after('gateway_status');
            }
            if (! Schema::hasColumn('rox_gateway_transactions', 'tra_id')) {
                $table->string('tra_id')->nullable()->after('gateway_transaction_id');
            }
            if (! Schema::hasColumn('rox_gateway_transactions', 'utr_id')) {
                $table->string('utr_id')->nullable()->after('tra_id');
            }
            if (! Schema::hasColumn('rox_gateway_transactions', 'payment_url')) {
                $table->text('payment_url')->nullable()->after('utr_id');
            }
            if (! Schema::hasColumn('rox_gateway_transactions', 'request_payload')) {
                $table->longText('request_payload')->nullable()->after('payment_url');
            }
            if (! Schema::hasColumn('rox_gateway_transactions', 'response_payload')) {
                $table->longText('response_payload')->nullable()->after('request_payload');
            }
            if (! Schema::hasColumn('rox_gateway_transactions', 'callback_payload')) {
                $table->longText('callback_payload')->nullable()->after('response_payload');
            }
            if (! Schema::hasColumn('rox_gateway_transactions', 'manual_status_by')) {
                $table->unsignedBigInteger('manual_status_by')->nullable()->after('refund_wallet_transaction_id');
            }
            if (! Schema::hasColumn('rox_gateway_transactions', 'manual_status_note')) {
                $table->text('manual_status_note')->nullable()->after('manual_status_by');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('rox_gateway_transactions')) {
            return;
        }

        Schema::table('rox_gateway_transactions', function (Blueprint $table) {
            $columns = [
                'legacy_order_id',
                'legacy_user_id',
                'gateway_transaction_id',
                'tra_id',
                'utr_id',
                'payment_url',
                'request_payload',
                'response_payload',
                'callback_payload',
                'manual_status_by',
                'manual_status_note',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('rox_gateway_transactions', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
