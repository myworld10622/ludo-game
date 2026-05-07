<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('tbl_setting')) {
            return;
        }

        Schema::table('tbl_setting', function (Blueprint $table) {
            if (! Schema::hasColumn('tbl_setting', 'app_popup_title')) {
                $table->string('app_popup_title')->nullable();
            }
            if (! Schema::hasColumn('tbl_setting', 'app_popup_message')) {
                $table->text('app_popup_message')->nullable();
            }
            if (! Schema::hasColumn('tbl_setting', 'app_popup_button_text')) {
                $table->string('app_popup_button_text')->nullable();
            }
            if (! Schema::hasColumn('tbl_setting', 'app_popup_url')) {
                $table->string('app_popup_url')->nullable();
            }
            if (! Schema::hasColumn('tbl_setting', 'app_popup_image')) {
                $table->string('app_popup_image')->nullable();
            }
        });

        DB::table('tbl_setting')->update([
            'daily_bonus_status' => DB::raw("COALESCE(daily_bonus_status, '1')"),
            'app_popop_status' => DB::raw("COALESCE(app_popop_status, '0')"),
        ]);
    }

    public function down(): void
    {
        if (! Schema::hasTable('tbl_setting')) {
            return;
        }

        Schema::table('tbl_setting', function (Blueprint $table) {
            foreach (['app_popup_title', 'app_popup_message', 'app_popup_button_text', 'app_popup_url', 'app_popup_image'] as $column) {
                if (Schema::hasColumn('tbl_setting', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
