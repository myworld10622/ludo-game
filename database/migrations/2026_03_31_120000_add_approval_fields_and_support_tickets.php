<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tournaments', function (Blueprint $table) {
            $table->timestamp('approved_at')->nullable()->after('is_approved');
            $table->text('approval_note')->nullable()->after('approved_at');
            $table->timestamp('rejected_at')->nullable()->after('approval_note');
            $table->text('rejection_reason')->nullable()->after('rejected_at');
        });

        Schema::create('support_tickets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tournament_id')->nullable()->constrained()->nullOnDelete();
            $table->string('subject', 180);
            $table->string('category', 50)->default('general');
            $table->string('status', 30)->default('open');
            $table->string('priority', 30)->default('normal');
            $table->timestamp('last_message_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('support_ticket_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('support_ticket_id')->constrained('support_tickets')->cascadeOnDelete();
            $table->string('sender_type', 20);
            $table->foreignId('sender_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('sender_admin_user_id')->nullable()->constrained('admin_users')->nullOnDelete();
            $table->text('message');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('support_ticket_messages');
        Schema::dropIfExists('support_tickets');

        Schema::table('tournaments', function (Blueprint $table) {
            $table->dropColumn([
                'approved_at',
                'approval_note',
                'rejected_at',
                'rejection_reason',
            ]);
        });
    }
};
