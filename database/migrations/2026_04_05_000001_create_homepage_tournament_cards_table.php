<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('homepage_tournament_cards', function (Blueprint $table) {
            $table->id();

            // Display content
            $table->string('name');                          // "Grand Sunday Classic"
            $table->string('icon', 10)->default('🎲');       // "🔥"
            $table->text('description')->nullable();

            // Card appearance
            $table->enum('card_color', ['gold', 'blue', 'purple'])->default('gold');
            $table->enum('status_badge', ['live', 'open', 'soon'])->default('open');
            $table->string('status_text', 60)->default('Open Registration');

            // Meta stats shown on card (3 stats)
            $table->string('meta1_label', 40)->default('Prize Pool');
            $table->string('meta1_value', 40)->default('₹0');
            $table->string('meta2_label', 40)->default('Entry');
            $table->string('meta2_value', 40)->default('FREE');
            $table->string('meta3_label', 40)->default('Players');
            $table->string('meta3_value', 40)->default('0/0');

            // Optional: link to real tournament
            $table->foreignId('tournament_id')->nullable()->constrained('tournaments')->nullOnDelete();

            // Ordering & visibility
            $table->unsignedTinyInteger('sort_order')->default(0);
            $table->boolean('is_visible')->default(true);

            $table->timestamps();
        });

        // Seed 3 default cards
        DB::table('homepage_tournament_cards')->insert([
            [
                'name'          => 'Grand Sunday Classic',
                'icon'          => '🔥',
                'description'   => '128-player open tournament. Abhi sirf 23 seats baaki hain. Jaldi join karo!',
                'card_color'    => 'gold',
                'status_badge'  => 'live',
                'status_text'   => 'Live Now',
                'meta1_label'   => 'Prize Pool',
                'meta1_value'   => '₹25,000',
                'meta2_label'   => 'Entry',
                'meta2_value'   => '₹199',
                'meta3_label'   => 'Players',
                'meta3_value'   => '105/128',
                'sort_order'    => 1,
                'is_visible'    => true,
                'created_at'    => now(),
                'updated_at'    => now(),
            ],
            [
                'name'          => 'Rookie Rumble',
                'icon'          => '⚡',
                'description'   => 'New players ke liye special tournament. Max 2 months account age. Free entry!',
                'card_color'    => 'blue',
                'status_badge'  => 'open',
                'status_text'   => 'Open Registration',
                'meta1_label'   => 'Prize Pool',
                'meta1_value'   => '₹5,000',
                'meta2_label'   => 'Entry',
                'meta2_value'   => 'FREE',
                'meta3_label'   => 'Players',
                'meta3_value'   => '56/64',
                'sort_order'    => 2,
                'is_visible'    => true,
                'created_at'    => now(),
                'updated_at'    => now(),
            ],
            [
                'name'          => 'Champions League',
                'icon'          => '👑',
                'description'   => 'Premium tournament for ranked players only. Top 10 rating required. Monthly mega event.',
                'card_color'    => 'purple',
                'status_badge'  => 'soon',
                'status_text'   => 'Starting Soon',
                'meta1_label'   => 'Prize Pool',
                'meta1_value'   => '₹1,00,000',
                'meta2_label'   => 'Entry',
                'meta2_value'   => '₹999',
                'meta3_label'   => 'Starts In',
                'meta3_value'   => '2h 14m',
                'sort_order'    => 3,
                'is_visible'    => true,
                'created_at'    => now(),
                'updated_at'    => now(),
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('homepage_tournament_cards');
    }
};
