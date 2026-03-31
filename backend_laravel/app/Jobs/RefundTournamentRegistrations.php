<?php

namespace App\Jobs;

use App\Models\Tournament;
use App\Models\TournamentRegistration;
use App\Models\TournamentWalletTransaction;
use App\Models\Wallet;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RefundTournamentRegistrations implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(public readonly Tournament $tournament) {}

    public function handle(): void
    {
        $registrations = $this->tournament->registrations()
            ->where('is_bot', false)
            ->whereIn('status', [
                TournamentRegistration::STATUS_REGISTERED,
                TournamentRegistration::STATUS_CHECKED_IN,
            ])
            ->where('entry_fee_paid', '>', 0)
            ->get();

        foreach ($registrations as $registration) {
            try {
                DB::transaction(function () use ($registration) {
                    $wallet = Wallet::where('user_id', $registration->user_id)
                                    ->lockForUpdate()
                                    ->first();

                    if (! $wallet) {
                        return;
                    }

                    $wallet->balance += $registration->entry_fee_paid;
                    $wallet->save();

                    TournamentWalletTransaction::create([
                        'tournament_id'   => $this->tournament->id,
                        'user_id'         => $registration->user_id,
                        'type'            => TournamentWalletTransaction::TYPE_REFUND,
                        'amount'          => $registration->entry_fee_paid,
                        'status'          => 'completed',
                        'registration_id' => $registration->id,
                        'description'     => "Refund: tournament cancelled — {$this->tournament->name}",
                    ]);

                    $registration->update(['status' => TournamentRegistration::STATUS_REFUNDED]);
                });
            } catch (\Throwable $e) {
                Log::error("Refund failed for registration {$registration->id}", [
                    'tournament_id' => $this->tournament->id,
                    'user_id'       => $registration->user_id,
                    'error'         => $e->getMessage(),
                ]);
            }
        }

        Log::info("Refunds processed for tournament {$this->tournament->id}. Total: {$registrations->count()}");
    }
}
