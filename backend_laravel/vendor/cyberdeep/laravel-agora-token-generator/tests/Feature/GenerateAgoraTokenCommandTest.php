<?php

namespace CyberDeep\LaravelAgoraTokenGenerator\Tests\Feature;

use Illuminate\Support\Facades\Config;
use Orchestra\Testbench\TestCase;
use CyberDeep\LaravelAgoraTokenGenerator\LaravelAgoraServiceProvider;

class GenerateAgoraTokenCommandTest extends TestCase
{
    protected function getPackageProviders($app)
    {
        return [
            LaravelAgoraServiceProvider::class,
        ];
    }

    protected function setUp(): void
    {
        parent::setUp();

        // Mock the Agora configuration
        Config::set('laravel-agora-token-generator.agora.app_id', 'test-app-id');
        Config::set('laravel-agora-token-generator.agora.app_certificate', 'test-app-certificate');
        Config::set('laravel-agora-token-generator.agora.token_builder', 'v1');
    }

    /** @test */
    public function it_can_generate_a_token_with_required_parameters()
    {
        $this->artisan('agora:generate-token', [
            'channel' => 'test-channel',
            'uid' => 'test-user',
        ])
            ->expectsOutput('Generating Agora token...')
            ->expectsOutput('Channel: test-channel')
            ->expectsOutput('User ID: test-user')
            ->expectsOutput('Join as subscriber: No')
            ->expectsOutput('Audio only: No')
            ->expectsOutput('Token generated successfully:')
            ->assertExitCode(0);
    }

    /** @test */
    public function it_can_generate_a_token_with_join_option()
    {
        $this->artisan('agora:generate-token', [
            'channel' => 'test-channel',
            'uid' => 'test-user',
            '--join' => true,
        ])
            ->expectsOutput('Generating Agora token...')
            ->expectsOutput('Channel: test-channel')
            ->expectsOutput('User ID: test-user')
            ->expectsOutput('Join as subscriber: Yes')
            ->expectsOutput('Audio only: No')
            ->expectsOutput('Token generated successfully:')
            ->assertExitCode(0);
    }

    /** @test */
    public function it_can_generate_a_token_with_audio_only_option()
    {
        $this->artisan('agora:generate-token', [
            'channel' => 'test-channel',
            'uid' => 'test-user',
            '--audio-only' => true,
        ])
            ->expectsOutput('Generating Agora token...')
            ->expectsOutput('Channel: test-channel')
            ->expectsOutput('User ID: test-user')
            ->expectsOutput('Join as subscriber: No')
            ->expectsOutput('Audio only: Yes')
            ->expectsOutput('Token generated successfully:')
            ->assertExitCode(0);
    }

    /** @test */
    public function it_can_generate_a_token_with_all_options()
    {
        $this->artisan('agora:generate-token', [
            'channel' => 'test-channel',
            'uid' => 'test-user',
            '--join' => true,
            '--audio-only' => true,
        ])
            ->expectsOutput('Generating Agora token...')
            ->expectsOutput('Channel: test-channel')
            ->expectsOutput('User ID: test-user')
            ->expectsOutput('Join as subscriber: Yes')
            ->expectsOutput('Audio only: Yes')
            ->expectsOutput('Token generated successfully:')
            ->assertExitCode(0);
    }
}