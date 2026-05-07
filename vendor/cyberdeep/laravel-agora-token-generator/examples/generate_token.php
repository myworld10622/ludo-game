<?php

/**
 * This is a simple script to demonstrate how to use the Agora token generator command.
 * 
 * Usage:
 * php examples/generate_token.php <channel> <uid> [--join] [--audio-only]
 * 
 * Example:
 * php examples/generate_token.php test-channel test-user --join --audio-only
 */

// Check if we have the required arguments
if ($argc < 3) {
    echo "Usage: php examples/generate_token.php <channel> <uid> [--join] [--audio-only]\n";
    exit(1);
}

// Parse arguments
$channel = $argv[1];
$uid = $argv[2];
$join = in_array('--join', $argv);
$audioOnly = in_array('--audio-only', $argv);

// Build the artisan command
$command = "php artisan agora:generate-token {$channel} {$uid}";
if ($join) {
    $command .= " --join";
}
if ($audioOnly) {
    $command .= " --audio-only";
}

// Display the command that will be executed
echo "Executing command: {$command}\n\n";

// Execute the command
passthru($command);

echo "\n\nToken generation complete.\n";
echo "For more information on how to use this token, see examples/generate_token.md\n";