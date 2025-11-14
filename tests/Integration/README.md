# Integration Tests

This directory contains integration tests for the webtrees-jsonld module using testcontainers-php.

## Prerequisites

- Docker must be installed and running
- PHP 8.1 or higher
- Composer dependencies installed

## Custom Docker Image

The integration tests require a custom PHP image with mysqli and pdo_mysql extensions enabled. Since testcontainers-php doesn't support building from Dockerfiles, we use a pre-test build script approach.

### Architecture

1. **Containerfile** (`php-apache/Containerfile`): Defines the custom image based on `php:8-apache` with mysqli and pdo_mysql extensions
2. **Preparation Script** (`scripts/prepare_integration_tests.php`): Builds the custom image before running tests
3. **Bootstrap** (`tests/bootstrap-integration.php`): Automatically builds the image when running tests from IDE
4. **WebtreesContainer** (`WebtreesContainer.php`): Uses the pre-built custom image `webtrees-php:8-apache-mysqli`

### Running Integration Tests

```bash
# Using Composer (recommended)
composer test:integration

# Using Make
make integrationtest-testcontainers

# Manual steps
php scripts/prepare_integration_tests.php
php scripts/run_integration_tests.php
```

The preparation script will:
1. Check Docker availability
2. Build the custom PHP image with mysqli and pdo_mysql extensions
3. Tag it as `webtrees-php:8-apache-mysqli`

This image is then used by `WebtreesContainer` when running the integration tests.

### Running from IDE (PhpStorm, etc.)

When running integration tests directly from your IDE, the bootstrap file (`tests/bootstrap-integration.php`) automatically detects integration tests and builds the Docker image if needed. No manual preparation is required!

## Why This Approach?

- **testcontainers-php limitation**: The library doesn't support building from Dockerfiles
- **exec() issues**: Attempting to install mysqli at runtime via container exec() fails
- **Maintainability**: Separate build script is clear, maintainable, and runs everywhere
- **Performance**: Image is built once and reused across test runs
- **Simplicity**: No need for custom testcontainers fork or complex workarounds
