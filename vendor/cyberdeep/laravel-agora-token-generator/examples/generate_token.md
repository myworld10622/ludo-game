# Generating Agora Tokens from the Console

This package provides a convenient command-line interface for generating Agora tokens. This is useful for testing or for generating tokens in a development environment.

## Prerequisites

1. Make sure you have installed the package:
   ```bash
   composer require cyberdeep/laravel-agora-token-generator
   ```

2. Publish the configuration file:
   ```bash
   php artisan vendor:publish --tag=laravel-agora-token-generator-config
   ```

3. Configure your Agora credentials in your `.env` file:
   ```
   AGORA_APP_ID=your-app-id
   AGORA_APP_CERTIFICATE=your-app-certificate
   ```

## Basic Usage

To generate a token for a specific channel and user ID:

```bash
php artisan agora:generate-token channel-name user-id
```

This will generate a token for a publisher (host) with both audio and video capabilities.

## Advanced Usage

### Generating a Token for a Subscriber

To generate a token for a subscriber (audience):

```bash
php artisan agora:generate-token channel-name user-id --join
```

### Generating a Token for Audio-Only Mode

To generate a token for audio-only mode:

```bash
php artisan agora:generate-token channel-name user-id --audio-only
```

### Combining Options

You can combine options as needed:

```bash
php artisan agora:generate-token channel-name user-id --join --audio-only
```

This will generate a token for a subscriber with audio-only capabilities.

## Example Output

```
Generating Agora token...
Channel: channel-name
User ID: user-id
Join as subscriber: No
Audio only: No
Token generated successfully:
006dfe1e44c36dabfff4a0a76e84939a38bIABuGveRSMnXB7WEkP/55jWZXxBLf8vdXPo9539cDggZ4AAAAAEAAQB7ImV4cCI6MTY0NjIzOTAyMiwiaWF0IjoxNjQ2MjM1NDIyLCJjaGFubmVsIjoiY2hhbm5lbC1uYW1lIiwidWlkIjoidXNlci1pZCJ9
```

## Using the Token

The generated token can be used with the Agora SDK to join a channel. For example, in a JavaScript application:

```javascript
// Initialize the Agora client
const client = AgoraRTC.createClient({ mode: 'rtc', codec: 'vp8' });

// Join the channel with the generated token
client.join('your-app-id', 'channel-name', 'token-from-command', 'user-id');
```

## Programmatic Usage

If you need to generate tokens programmatically in your application, you can use the Agora service directly:

```php
use CyberDeep\LaravelAgoraTokenGenerator\Services\Agora;

$token = Agora::make(1)
    ->channel('channel-name')
    ->uId('user-id')
    ->join(false) // false for publisher, true for subscriber
    ->audioOnly(false) // false for video+audio, true for audio only
    ->token();
```