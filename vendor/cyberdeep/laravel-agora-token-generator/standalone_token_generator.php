<?php

/**
 * Standalone Agora Token Generator
 * 
 * This script generates Agora tokens without requiring Laravel integration.
 * It can be run directly from the project directory.
 * 
 * Usage:
 * php standalone_token_generator.php <app_id> <app_certificate> <channel> <uid> [--join] [--audio-only] [--v2]
 * 
 * Example:
 * php standalone_token_generator.php your-app-id your-app-certificate test-channel test-user --join --audio-only
 */

// Check if we have the required arguments
if ($argc < 5) {
    echo "Usage: php standalone_token_generator.php <app_id> <app_certificate> <channel> <uid> [--join] [--audio-only] [--v2]\n";
    echo "Options:\n";
    echo "  --join       Generate a token for a subscriber (audience) instead of a publisher (host)\n";
    echo "  --audio-only Generate a token for audio-only mode\n";
    echo "  --v2         Use RtcTokenBuilder2 instead of RtcTokenBuilder\n";
    exit(1);
}

// Include the necessary files
require_once __DIR__ . '/vendor/autoload.php';

// Import the necessary classes
use CyberDeep\LaravelAgoraTokenGenerator\Services\Token\AccessToken;
use CyberDeep\LaravelAgoraTokenGenerator\Services\Token\AccessToken2;
use CyberDeep\LaravelAgoraTokenGenerator\Services\Token\RtcTokenBuilder;
use CyberDeep\LaravelAgoraTokenGenerator\Services\Token\RtcTokenBuilder2;
use CyberDeep\LaravelAgoraTokenGenerator\Services\Token\Services\ServiceRtc;

// Parse arguments
$appId = $argv[1];
$appCertificate = $argv[2];
$channel = $argv[3];
$uid = $argv[4];
$join = in_array('--join', $argv);
$audioOnly = in_array('--audio-only', $argv);
$useV2 = in_array('--v2', $argv);

// Display the configuration
echo "Generating Agora token...\n";
echo "App ID: " . $appId . "\n";
echo "Channel: " . $channel . "\n";
echo "User ID: " . $uid . "\n";
echo "Join as subscriber: " . ($join ? 'Yes' : 'No') . "\n";
echo "Audio only: " . ($audioOnly ? 'Yes' : 'No') . "\n";
echo "Token builder version: " . ($useV2 ? 'v2' : 'v1') . "\n\n";

// Set up token parameters
$expireTimeInSeconds = 3600;
$currentTimestamp = time();
$privilegeExpiredTs = $currentTimestamp + $expireTimeInSeconds;

try {
    // Generate the token based on the version
    if ($useV2) {
        // Use RtcTokenBuilder2
        $role = $join ? RtcTokenBuilder2::ROLE_SUBSCRIBER : RtcTokenBuilder2::ROLE_PUBLISHER;
        
        $token = RtcTokenBuilder2::buildTokenWithUid(
            $appId,
            $appCertificate,
            $channel,
            $uid,
            $role,
            $privilegeExpiredTs,
            $privilegeExpiredTs
        );
    } else {
        // Use RtcTokenBuilder (v1)
        $role = $join ? RtcTokenBuilder::$roles['RoleSubscriber'] : RtcTokenBuilder::$roles['RolePublisher'];
        
        $token = RtcTokenBuilder::build(
            $appId,
            $appCertificate,
            $channel,
            $uid,
            $role,
            $privilegeExpiredTs,
            $audioOnly ? 'audio' : 'video'
        );
    }

    // Output the token
    echo "Token generated successfully:\n";
    echo $token . "\n";
} catch (Exception $e) {
    echo "Error generating token: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\nToken generation complete.\n";
echo "This token will expire in " . $expireTimeInSeconds . " seconds.\n";