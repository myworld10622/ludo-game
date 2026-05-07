<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use CyberDeep\LaravelAgoraTokenGenerator\Services\Agora;

class AgoraTest extends TestCase
{
    /**
     * Test that the Agora service can generate a token using RtcTokenBuilder (v1).
     *
     * @return void
     */
    public function testTokenGenerationWithV1()
    {
        // Mock the config values
        $appId = 'test-app-id';
        $appCertificate = 'test-app-certificate';
        $tokenBuilder = 'v1';

        // Create a mock of the config function
        $this->setUpConfigMock($appId, $appCertificate, $tokenBuilder);

        // Create an instance of the Agora service
        $agora = Agora::make(1)
            ->channel('test-channel')
            ->uId('test-user')
            ->join(false)
            ->audioOnly(false);

        // Generate a token
        $token = $agora->token();

        // Assert that the token is a string and not empty
        $this->assertIsString($token);
        $this->assertNotEmpty($token);
    }

    /**
     * Test that the Agora service can generate a token using RtcTokenBuilder2 (v2).
     *
     * @return void
     */
    public function testTokenGenerationWithV2()
    {
        // Mock the config values
        $appId = 'test-app-id';
        $appCertificate = 'test-app-certificate';
        $tokenBuilder = 'v2';

        // Create a mock of the config function
        $this->setUpConfigMock($appId, $appCertificate, $tokenBuilder);

        // Create an instance of the Agora service
        $agora = Agora::make(1)
            ->channel('test-channel')
            ->uId('test-user')
            ->join(false)
            ->audioOnly(false);

        // Generate a token
        $token = $agora->token();

        // Assert that the token is a string and not empty
        $this->assertIsString($token);
        $this->assertNotEmpty($token);
    }

    /**
     * Set up a mock for the config function.
     *
     * @param string $appId
     * @param string $appCertificate
     * @param string $tokenBuilder
     * @return void
     */
    private function setUpConfigMock(string $appId, string $appCertificate, string $tokenBuilder = 'v1')
    {
        // Define a function to replace the global config function
        // This is a simplified approach for demonstration purposes
        // In a real Laravel application, you would use Laravel's testing utilities
        if (!function_exists('config')) {
            // Use a static variable to store the token builder value
            static $tokenBuilderValue = 'v1';
            $tokenBuilderValue = $tokenBuilder;

            function config($key)
            {
                // Access the static variable
                global $tokenBuilderValue;

                if ($key === 'laravel-agora-token-generator.agora.app_id') {
                    return 'test-app-id';
                }
                if ($key === 'laravel-agora-token-generator.agora.app_certificate') {
                    return 'test-app-certificate';
                }
                if ($key === 'laravel-agora-token-generator.agora.token_builder') {
                    return $tokenBuilderValue ?? 'v1';
                }
                return null;
            }
        } else {
            // If the function already exists, we need to update the token builder value
            // This is a bit hacky, but it's just for testing purposes
            global $tokenBuilderValue;
            $tokenBuilderValue = $tokenBuilder;
        }
    }
}
