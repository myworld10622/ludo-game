<?php

namespace CyberDeep\LaravelAgoraTokenGenerator\Commands;

use Illuminate\Console\Command;
use CyberDeep\LaravelAgoraTokenGenerator\Services\Agora;

class GenerateAgoraTokenCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'agora:generate-token
                            {channel : The channel name}
                            {uid : The user ID}
                            {--join : Whether the user is joining as a subscriber (default is publisher)}
                            {--audio-only : Whether the user is using audio only (default is audio+video)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate an Agora token for a specific channel and user ID';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $channel = $this->argument('channel');
        $uid = $this->argument('uid');
        $join = $this->option('join');
        $audioOnly = $this->option('audio-only');

        $this->info('Generating Agora token...');
        $this->info('Channel: ' . $channel);
        $this->info('User ID: ' . $uid);
        $this->info('Join as subscriber: ' . ($join ? 'Yes' : 'No'));
        $this->info('Audio only: ' . ($audioOnly ? 'Yes' : 'No'));

        try {
            $token = Agora::make(1) // The ID parameter is required but not used for token generation
                ->channel($channel)
                ->uId($uid)
                ->join($join)
                ->audioOnly($audioOnly)
                ->token();

            $this->info('Token generated successfully:');
            $this->line($token);

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Failed to generate token: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}