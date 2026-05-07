# Installing Laravel Agora Token Generator Locally

This guide provides instructions on how to install the `cyberdeep/laravel-agora-token-generator` package locally in your Laravel project.

## Method 1: Using Composer Path Repository

This is the recommended method for local development as it properly handles autoloading and dependencies.

1. **Navigate to your Laravel project's root directory**

2. **Add a path repository to your project's `composer.json` file:**

   ```
   "repositories": [
       {
           "type": "path",
           "url": "../laravel-agora-token-generator",
           "options": {
               "symlink": true
           }
       }
   ]
   ```

   Replace `../laravel-agora-token-generator` with the relative path from your Laravel project to the package directory.

3. **Require the package:**

   ```bash
   composer require cyberdeep/laravel-agora-token-generator:^1.0@dev
   ```

   The `^1.0` specifies the version constraint, and the `@dev` suffix tells Composer to use the development version of the package.

   If you encounter stability issues, you can try:

   ```bash
   composer require cyberdeep/laravel-agora-token-generator:^1.0@dev --with-all-dependencies
   ```

4. **Publish the configuration file:**

   ```bash
   php artisan vendor:publish --tag=laravel-agora-token-generator-config
   ```

5. **Add your Agora credentials to your `.env` file:**

   ```
   AGORA_APP_ID=your-app-id
   AGORA_APP_CERTIFICATE=your-app-certificate
   ```

## Method 2: Using Symbolic Links

This method is useful for quick testing but doesn't handle autoloading as cleanly as the path repository method.

1. **Create a symbolic link from your Laravel project to the package:**

   On Windows:
   ```cmd
   mklink /D C:\path\to\laravel-project\packages\cyberdeep\laravel-agora-token-generator C:\path\to\laravel-agora-token-generator
   ```

   On Linux/Mac:
   ```bash
   ln -s /path/to/laravel-agora-token-generator /path/to/laravel-project/packages/cyberdeep/laravel-agora-token-generator
   ```

2. **Add the package directory to your project's `composer.json` file:**

   ```
   "autoload-dev": {
       "psr-4": {
           "Tests\\": "tests/",
           "CyberDeep\\LaravelAgoraTokenGenerator\\": "packages/cyberdeep/laravel-agora-token-generator/src"
       }
   }
   ```

3. **Register the service provider in `config/app.php`:**

   ```php
   'providers' => [
       // Other service providers...
       CyberDeep\LaravelAgoraTokenGenerator\LaravelAgoraTokenGeneratorServiceProvider::class,
   ],
   ```

4. **Regenerate the autoloader:**

   ```bash
   composer dump-autoload
   ```

5. **Publish the configuration file:**

   ```bash
   php artisan vendor:publish --tag=laravel-agora-token-generator-config
   ```

6. **Add your Agora credentials to your `.env` file:**

   ```
   AGORA_APP_ID=your-app-id
   AGORA_APP_CERTIFICATE=your-app-certificate
   ```

## Method 3: Using Composer Local Repository

This method is useful if you want to test your package as if it were installed from Packagist.

1. **Create a `packages` directory in your Laravel project (if it doesn't exist):**

   ```bash
   mkdir -p packages
   ```

2. **Copy your package to the `packages` directory:**

   ```bash
   cp -r /path/to/laravel-agora-token-generator packages/
   ```

3. **Add a local repository to your project's `composer.json` file:**

   ```
   "repositories": [
       {
           "type": "path",
           "url": "./packages/laravel-agora-token-generator"
       }
   ]
   ```

4. **Require the package:**

   ```bash
   composer require cyberdeep/laravel-agora-token-generator:^1.0@dev
   ```

   If you encounter stability issues, you can try:

   ```bash
   composer require cyberdeep/laravel-agora-token-generator:^1.0@dev --with-all-dependencies
   ```

5. **Publish the configuration file:**

   ```bash
   php artisan vendor:publish --tag=laravel-agora-token-generator-config
   ```

6. **Add your Agora credentials to your `.env` file:**

   ```
   AGORA_APP_ID=your-app-id
   AGORA_APP_CERTIFICATE=your-app-certificate
   ```

## Troubleshooting

If you encounter any issues with the installation:

1. **Clear Composer cache:**
   ```bash
   composer clear-cache
   ```

2. **Regenerate the autoloader:**
   ```bash
   composer dump-autoload
   ```

3. **Clear Laravel cache:**
   ```bash
   php artisan cache:clear
   php artisan config:clear
   ```

4. **Check for namespace conflicts:**
   Ensure there are no conflicts between your package's namespace and other packages or your application code.

5. **Verify file permissions:**
   Ensure that the package files have the correct permissions for your web server to access them.
