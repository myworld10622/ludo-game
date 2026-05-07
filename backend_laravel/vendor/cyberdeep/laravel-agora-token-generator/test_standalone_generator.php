<?php

/**
 * Test script for the standalone token generator
 * 
 * This script tests the standalone token generator with sample parameters.
 * It's a simple wrapper that calls the standalone_token_generator.php script
 * with predefined parameters and displays the output.
 */

echo "Testing standalone token generator...\n\n";

// Test case 1: Basic token generation (publisher, video+audio, v1)
echo "Test Case 1: Basic token generation (publisher, video+audio, v1)\n";
echo "=============================================================\n";
$command = "php standalone_token_generator.php test-app-id test-app-certificate test-channel test-user";
echo "Command: $command\n\n";
passthru($command);
echo "\n\n";

// Test case 2: Subscriber token generation
echo "Test Case 2: Subscriber token generation\n";
echo "======================================\n";
$command = "php standalone_token_generator.php test-app-id test-app-certificate test-channel test-user --join";
echo "Command: $command\n\n";
passthru($command);
echo "\n\n";

// Test case 3: Audio-only token generation
echo "Test Case 3: Audio-only token generation\n";
echo "======================================\n";
$command = "php standalone_token_generator.php test-app-id test-app-certificate test-channel test-user --audio-only";
echo "Command: $command\n\n";
passthru($command);
echo "\n\n";

// Test case 4: V2 token generation
echo "Test Case 4: V2 token generation\n";
echo "==============================\n";
$command = "php standalone_token_generator.php test-app-id test-app-certificate test-channel test-user --v2";
echo "Command: $command\n\n";
passthru($command);
echo "\n\n";

// Test case 5: Combined options
echo "Test Case 5: Combined options (subscriber, audio-only, v2)\n";
echo "======================================================\n";
$command = "php standalone_token_generator.php test-app-id test-app-certificate test-channel test-user --join --audio-only --v2";
echo "Command: $command\n\n";
passthru($command);
echo "\n\n";

echo "All tests completed.\n";