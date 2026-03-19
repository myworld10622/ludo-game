<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class EnrichTournamentLoadFixtureTokensCommand extends Command
{
    protected $signature = 'tournaments:enrich-load-fixture-tokens
        {fixture : Fixture JSON path relative to backend root or absolute}
        {--token-name-prefix=load-test : Prefix used when minting tokens}
        {--delete-existing-prefixed-tokens : Delete existing personal access tokens for the same prefix before minting}';

    protected $description = 'Populate access tokens in an exported tournament load fixture JSON file.';

    public function handle(): int
    {
        $fixturePath = (string) $this->argument('fixture');
        $absolutePath = str_starts_with($fixturePath, DIRECTORY_SEPARATOR) || preg_match('/^[A-Za-z]:\\\\/', $fixturePath)
            ? $fixturePath
            : base_path($fixturePath);

        if (! File::exists($absolutePath)) {
            $this->error('Fixture file not found: ' . $absolutePath);
            return self::FAILURE;
        }

        $payload = json_decode(File::get($absolutePath), true);
        if (! is_array($payload) || ! isset($payload['entries']) || ! is_array($payload['entries'])) {
            $this->error('Invalid fixture payload.');
            return self::FAILURE;
        }

        $tokenPrefix = trim((string) $this->option('token-name-prefix')) ?: 'load-test';
        $deleteExisting = (bool) $this->option('delete-existing-prefixed-tokens');
        $minted = 0;

        foreach ($payload['entries'] as $index => $entry) {
            $userId = (int) ($entry['user_id'] ?? 0);
            if ($userId <= 0) {
                continue;
            }

            $user = User::query()->find($userId);
            if (! $user) {
                continue;
            }

            if ($deleteExisting && method_exists($user, 'tokens')) {
                $user->tokens()
                    ->where('name', 'like', $tokenPrefix . '%')
                    ->delete();
            }

            $tokenName = sprintf('%s-user-%d-entry-%d', $tokenPrefix, $userId, $index + 1);
            $token = $user->createToken($tokenName)->plainTextToken;

            $payload['entries'][$index]['access_token'] = $token;
            $minted++;
        }

        File::put($absolutePath, json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        $this->info('Load fixture tokens populated successfully.');
        $this->line('Fixture: ' . $absolutePath);
        $this->line('Tokens minted: ' . $minted);

        return self::SUCCESS;
    }
}
