# Laravel Agora Token Generator

Agora Token Generator With easy to use Service Class for Laravel applications. This package supports both Agora V1 and V2 token generation, including V2 tokens that start with "007".

## Requirements

- PHP 8.0.2 or higher
- Laravel 8.x, 9.x, 10.x, 11.x, or 12.x

## Installation

### From Packagist

```bash
composer require cyberdeep/laravel-agora-token-generator:^1.0
```

If you encounter stability issues, you can explicitly allow dev stability:

```bash
composer require cyberdeep/laravel-agora-token-generator:^1.0 --with-all-dependencies
```

### Local Installation

For instructions on how to install this package locally during development, please
see [LOCAL_INSTALLATION.md](LOCAL_INSTALLATION.md).

## Configuration

After installing the package, publish the configuration file:

```bash
php artisan vendor:publish --tag=laravel-agora-token-generator-config
```

Then, add your Agora credentials to your `.env` file:

```
AGORA_APP_ID=your-app-id
AGORA_APP_CERTIFICATE=your-app-certificate
AGORA_TOKEN_BUILDER=v1  # Use 'v1' for RtcTokenBuilder or 'v2' for RtcTokenBuilder2 (generates tokens starting with 007)
```

## Usage

### Programmatic Usage

```php
use CyberDeep\LaravelAgoraTokenGenerator\Services\Agora;

// Generate a token for a publisher
$token = Agora::make($userId)
    ->channel('channel-name')
    ->uId('user-id')
    ->join(false) // false for publisher, true for subscriber
    ->audioOnly(false) // false for video+audio, true for audio only
    ->token();
```

The token generation method will use either V1 or V2 based on your configuration setting (`AGORA_TOKEN_BUILDER`).

### Console Command

You can also generate tokens directly from the command line:

```bash
php artisan agora:generate-token channel-name user-id
```

Options:
- `--join`: Generate a token for a subscriber (audience) instead of a publisher (host)
- `--audio-only`: Generate a token for audio-only mode

For more detailed examples and usage instructions, see [examples/generate_token.md](examples/generate_token.md).

### Standalone Token Generator

If you need to generate tokens without Laravel integration, you can use the standalone token generator:

```bash
php standalone_token_generator.php <app_id> <app_certificate> <channel> <uid> [--join] [--audio-only] [--v2]
```

The `--v2` flag enables Agora V2 token generation, which produces tokens that start with "007".

For more information about the standalone token generator, see [standalone_token_generator.md](standalone_token_generator.md).

## Token Versions

This package supports two versions of Agora tokens:

### V1 Tokens
- Generated using the `RtcTokenBuilder` class
- Default token version if not specified
- Set `AGORA_TOKEN_BUILDER=v1` in your `.env` file

### V2 Tokens
- Generated using the `RtcTokenBuilder2` class
- Tokens start with "007"
- Provides enhanced security and features
- Set `AGORA_TOKEN_BUILDER=v2` in your `.env` file

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Security

Please see [SECURITY](SECURITY.md) for more information about security.

## Credits

- [Fady Mondy](mailto:info@3x1.io)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
