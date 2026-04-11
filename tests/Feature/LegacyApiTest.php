<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class LegacyApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->createLegacyTables();
    }

    public function test_register_and_login_with_skip_otp(): void
    {
        $payload = [
            'mobile' => '9990011223',
            'password' => 'Secret123!',
            'name' => 'Test Player',
            'skip_otp' => '1',
        ];

        $this->postJson('/api/User/register', $payload)
            ->assertStatus(200)
            ->assertJson([
                'code' => 200,
                'message' => 'Registered Successfully',
            ])
            ->assertJsonStructure(['user_id', 'username', 'token']);

        $this->postJson('/api/User/login', [
            'mobile' => $payload['mobile'],
            'password' => $payload['password'],
        ])->assertStatus(200)
            ->assertJson([
                'code' => 200,
                'message' => 'Login successful.',
            ])
            ->assertJsonStructure([
                'user_data' => [
                    [
                        'token',
                    ],
                ],
            ]);
    }

    public function test_bonus_command_processes_deposit_bonus(): void
    {
        putenv('PAYMENTAPI_KEY=test-key');
        putenv('LEGACY_INCOME_DEPOSIT_BONUS=1');

        Http::fake([
            'https://api.nowpayments.io/v1/payment/*' => Http::response([
                'payment_status' => 'finished',
            ], 200),
        ]);

        DB::table('tbl_setting')->updateOrInsert(['id' => 1], [
            'id' => 1,
            'referral_amount' => 0,
            'level_1' => 0,
            'level_2' => 0,
            'level_3' => 0,
            'level_4' => 0,
            'level_5' => 0,
            'level_6' => 0,
            'level_7' => 0,
            'level_8' => 0,
            'level_9' => 0,
            'level_10' => 0,
            'min_redeem' => 0,
            'min_withdrawal' => 0,
            'admin_coin' => 0,
            'isDeleted' => 0,
        ]);

        DB::table('tbl_deposit_bonus_master')->insert([
            'id' => 1,
            'min' => 50,
            'max' => 150,
            'deposit_count' => 1,
            'self_bonus' => 10,
            'upline_bonus' => 5,
            'isDeleted' => 0,
        ]);

        DB::table('tbl_users')->insert([
            [
                'id' => 1,
                'mobile' => '9000000001',
                'email' => 'user1@example.com',
                'referred_by' => 2,
                'wallet' => 0,
                'bonus_wallet' => 0,
                'unutilized_wallet' => 0,
                'todays_recharge' => 0,
                'winning_wallet' => 0,
                'referral_precent' => 0,
                'isDeleted' => 0,
                'updated_date' => now()->format('Y-m-d H:i:s'),
            ],
            [
                'id' => 2,
                'mobile' => '9000000002',
                'email' => 'ref@example.com',
                'referred_by' => null,
                'wallet' => 0,
                'bonus_wallet' => 0,
                'unutilized_wallet' => 0,
                'todays_recharge' => 0,
                'winning_wallet' => 0,
                'referral_precent' => 0,
                'isDeleted' => 0,
                'updated_date' => now()->format('Y-m-d H:i:s'),
            ],
        ]);

        $user = User::query()->create([
            'uuid' => (string) Str::uuid(),
            'username' => 'test_user',
            'email' => 'user1@example.com',
            'mobile' => '9000000001',
            'password' => Hash::make('secret123'),
            'referral_code' => 'REF12345',
            'is_active' => true,
            'is_banned' => false,
        ]);

        $referrer = User::query()->create([
            'uuid' => (string) Str::uuid(),
            'username' => 'ref_user',
            'email' => 'ref@example.com',
            'mobile' => '9000000002',
            'password' => Hash::make('secret123'),
            'referral_code' => 'REF54321',
            'is_active' => true,
            'is_banned' => false,
        ]);

        DB::table('tbl_purchase')->insert([
            'id' => 10,
            'user_id' => 1,
            'coin' => 100,
            'extra' => 0,
            'payment' => 0,
            'isDeleted' => 0,
            'razor_payment_id' => 'pay_10',
            'transaction_type' => 0,
            'updated_date' => now()->format('Y-m-d H:i:s'),
        ]);

        Artisan::call('legacy:process-bonus-commission', [
            '--limit' => 1,
            '--provider' => 'nowpayments',
        ]);

        $this->assertSame(1, (int) DB::table('tbl_purchase')->where('id', 10)->value('payment'));
        $this->assertSame(110.0, (float) DB::table('tbl_users')->where('id', 1)->value('wallet'));
        $this->assertSame(5.0, (float) DB::table('tbl_users')->where('id', 2)->value('wallet'));

        $this->assertDatabaseHas('wallet_transactions', [
            'user_id' => $user->id,
            'reference_type' => 'legacy_purchase',
            'reference_id' => 10,
            'description' => 'Deposit credited (legacy)',
        ]);

        $this->assertDatabaseHas('wallet_transactions', [
            'user_id' => $user->id,
            'reference_type' => 'legacy_purchase',
            'reference_id' => 10,
            'description' => '1st Deposit Bonus (self)',
        ]);

        $this->assertDatabaseHas('wallet_transactions', [
            'user_id' => $referrer->id,
            'reference_type' => 'legacy_purchase',
            'reference_id' => 10,
            'description' => '1st Deposit Bonus (upline)',
        ]);

        $this->assertDatabaseHas('tbl_purcharse_ref', [
            'purchase_id' => 10,
            'level' => 0,
        ]);

        $this->assertDatabaseHas('tbl_purcharse_ref', [
            'purchase_id' => 10,
            'level' => 1,
        ]);
    }

    private function createLegacyTables(): void
    {
        if (! Schema::hasTable('tbl_users')) {
            Schema::create('tbl_users', function (Blueprint $table) {
                $table->integer('id')->primary();
                $table->string('mobile')->nullable();
                $table->string('email')->nullable();
                $table->integer('referred_by')->nullable();
                $table->decimal('wallet', 12, 2)->default(0);
                $table->decimal('bonus_wallet', 12, 2)->default(0);
                $table->decimal('unutilized_wallet', 12, 2)->default(0);
                $table->decimal('todays_recharge', 12, 2)->default(0);
                $table->decimal('winning_wallet', 12, 2)->default(0);
                $table->decimal('referral_precent', 8, 2)->default(0);
                $table->integer('isDeleted')->default(0);
                $table->timestamp('updated_date')->nullable();
            });
        }

        if (! Schema::hasTable('tbl_purchase')) {
            Schema::create('tbl_purchase', function (Blueprint $table) {
                $table->integer('id')->primary();
                $table->integer('user_id');
                $table->decimal('coin', 12, 2)->default(0);
                $table->decimal('extra', 8, 2)->default(0);
                $table->integer('payment')->default(0);
                $table->integer('isDeleted')->default(0);
                $table->string('razor_payment_id')->nullable();
                $table->integer('transaction_type')->default(0);
                $table->timestamp('updated_date')->nullable();
            });
        }

        if (! Schema::hasTable('tbl_deposit_bonus_master')) {
            Schema::create('tbl_deposit_bonus_master', function (Blueprint $table) {
                $table->integer('id')->primary();
                $table->decimal('min', 12, 2)->default(0);
                $table->decimal('max', 12, 2)->default(0);
                $table->integer('deposit_count')->default(1);
                $table->decimal('self_bonus', 12, 2)->default(0);
                $table->decimal('upline_bonus', 12, 2)->default(0);
                $table->integer('isDeleted')->default(0);
            });
        }

        if (! Schema::hasTable('tbl_setting')) {
            Schema::create('tbl_setting', function (Blueprint $table) {
                $table->increments('id');
                $table->decimal('referral_amount', 12, 2)->default(0);
                $table->decimal('level_1', 8, 2)->default(0);
                $table->decimal('level_2', 8, 2)->default(0);
                $table->decimal('level_3', 8, 2)->default(0);
                $table->decimal('level_4', 8, 2)->default(0);
                $table->decimal('level_5', 8, 2)->default(0);
                $table->decimal('level_6', 8, 2)->default(0);
                $table->decimal('level_7', 8, 2)->default(0);
                $table->decimal('level_8', 8, 2)->default(0);
                $table->decimal('level_9', 8, 2)->default(0);
                $table->decimal('level_10', 8, 2)->default(0);
                $table->decimal('min_redeem', 12, 2)->default(0);
                $table->decimal('min_withdrawal', 12, 2)->default(0);
                $table->decimal('admin_coin', 12, 2)->default(0);
                $table->integer('isDeleted')->default(0);
            });
        }

        if (! Schema::hasTable('tbl_statement')) {
            Schema::create('tbl_statement', function (Blueprint $table) {
                $table->increments('id');
                $table->integer('user_id');
                $table->string('source')->nullable();
                $table->integer('source_id')->default(0);
                $table->integer('user_type')->default(0);
                $table->decimal('amount', 12, 2)->default(0);
                $table->decimal('current_wallet', 12, 2)->default(0);
                $table->decimal('admin_commission', 12, 2)->default(0);
                $table->decimal('admin_coin', 12, 2)->default(0);
                $table->timestamp('added_date')->nullable();
            });
        }

        if (! Schema::hasTable('tbl_direct_admin_profit_statement')) {
            Schema::create('tbl_direct_admin_profit_statement', function (Blueprint $table) {
                $table->increments('id');
                $table->string('source')->nullable();
                $table->integer('source_id')->default(0);
                $table->decimal('admin_coin', 12, 2)->default(0);
                $table->decimal('admin_commission', 12, 2)->default(0);
                $table->timestamp('added_date')->nullable();
            });
        }

        if (! Schema::hasTable('tbl_purcharse_ref')) {
            Schema::create('tbl_purcharse_ref', function (Blueprint $table) {
                $table->increments('id');
                $table->integer('user_id');
                $table->integer('purchase_id');
                $table->integer('purchase_user_id');
                $table->decimal('coin', 12, 2)->default(0);
                $table->decimal('purchase_amount', 12, 2)->default(0);
                $table->integer('level')->default(0);
            });
        }

        if (! Schema::hasTable('tbl_referral_bonus_log')) {
            Schema::create('tbl_referral_bonus_log', function (Blueprint $table) {
                $table->increments('id');
                $table->integer('user_id');
                $table->integer('referred_user_id');
                $table->decimal('coin', 12, 2)->default(0);
                $table->timestamp('added_date')->nullable();
            });
        }

        if (! Schema::hasTable('tbl_extra_wallet_log')) {
            Schema::create('tbl_extra_wallet_log', function (Blueprint $table) {
                $table->increments('id');
                $table->integer('user_id');
                $table->decimal('coin', 12, 2)->default(0);
                $table->integer('type')->default(0);
                $table->timestamp('added_date')->nullable();
            });
        }
    }
}
