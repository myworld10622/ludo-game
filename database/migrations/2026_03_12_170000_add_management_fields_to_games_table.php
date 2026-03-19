<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('games', function (Blueprint $table) {
            $table->boolean('tournaments_enabled')->default(false)->after('is_visible')->index();
            $table->string('client_route', 150)->nullable()->after('launch_type');
            $table->string('socket_namespace', 150)->nullable()->after('client_route');

            $table->index(['is_visible', 'is_active', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::table('games', function (Blueprint $table) {
            $table->dropIndex(['is_visible', 'is_active', 'sort_order']);
            $table->dropColumn([
                'tournaments_enabled',
                'client_route',
                'socket_namespace',
            ]);
        });
    }
};
