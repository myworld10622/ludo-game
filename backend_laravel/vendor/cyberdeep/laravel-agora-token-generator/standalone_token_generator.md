# Standalone Agora Token Generator

This script allows you to generate Agora tokens without requiring Laravel integration. It can be run directly from the project directory.

## Prerequisites

1. PHP 8.0 or higher
2. Composer dependencies installed (run `composer install` if you haven't already)
3. Your Agora App ID and App Certificate

## Usage

```bash
php standalone_token_generator.php <app_id> <app_certificate> <channel> <uid> [--join] [--audio-only] [--v2]
```

### Parameters

- `app_id`: Your Agora App ID
- `app_certificate`: Your Agora App Certificate
- `channel`: The channel name
- `uid`: The user ID

### Options

- `--join`: Generate a token for a subscriber (audience) instead of a publisher (host)
- `--audio-only`: Generate a token for audio-only mode
- `--v2`: Use RtcTokenBuilder2 instead of RtcTokenBuilder (v1)

## Examples

### Generate a token for a publisher (host) with video and audio

```bash
php standalone_token_generator.php your-app-id your-app-certificate test-channel test-user
```

### Generate a token for a subscriber (audience) with video and audio

```bash
php standalone_token_generator.php your-app-id your-app-certificate test-channel test-user --join
```

### Generate a token for a publisher with audio only

```bash
php standalone_token_generator.php your-app-id your-app-certificate test-channel test-user --audio-only
```

### Generate a token using RtcTokenBuilder2 (v2)

```bash
php standalone_token_generator.php your-app-id your-app-certificate test-channel test-user --v2
```

### Combine options as needed

```bash
php standalone_token_generator.php your-app-id your-app-certificate test-channel test-user --join --audio-only --v2
```

## Output

The script will display:
1. The configuration used to generate the token
2. The generated token
3. The token expiration time

Example output:
```
Generating Agora token...
App ID: your-app-id
Channel: test-channel
User ID: test-user
Join as subscriber: No
Audio only: No
Token builder version: v1

Token generated successfully:
006dfe1e44c36dabfff4a0a76e84939a38bIABuGveRSMnXB7WEkP/55jWZXxBLf8vdXPo9539cDggZ4AAAAAEAAQB7ImV4cCI6MTY0NjIzOTAyMiwiaWF0IjoxNjQ2MjM1NDIyLCJjaGFubmVsIjoiY2hhbm5lbC1uYW1lIiwidWlkIjoidXNlci1pZCJ9

Token generation complete.
This token will expire in 3600 seconds.
```

## How It Works

The script directly uses the token generation classes from the package without relying on Laravel's framework. It:

1. Parses the command-line arguments
2. Determines which token builder to use based on the options
3. Generates the token with the appropriate parameters
4. Displays the token and related information

This standalone approach allows you to generate tokens in any environment where PHP is available, without needing to set up a Laravel application.