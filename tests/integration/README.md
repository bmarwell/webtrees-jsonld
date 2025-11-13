# Integration Tests

This directory contains integration tests for the webtrees JsonLD module using Testcontainers.

## Overview

The integration tests use [Testcontainers for PHP](https://github.com/testcontainers/testcontainers-php) to spin up Docker containers for testing the JsonLD module in a real webtrees environment.

## Requirements

- **Docker**: Docker must be installed and running on your system
- **PHP 8.1+**: As specified in composer.json
- **Composer dependencies**: Run `composer install` to install testcontainers and other dependencies

## Running Tests

### Run all tests (unit + integration):
```bash
composer test
# or
php vendor/bin/phpunit
```

### Run only unit tests:
```bash
php vendor/bin/phpunit --testsuite="Unit Tests"
```

### Run only integration tests:
```bash
php vendor/bin/phpunit --testsuite="Integration Tests"
```

## Test Structure

### Testcontainers-based Tests

The testcontainers-based integration tests (`WebtreesJsonLdIntegrationTest.php`) demonstrate how to:

1. **Spin up a MySQL container** - Using testcontainers' MySQL module
2. **Spin up a webtrees container** - With PHP and webserver
3. **Mount the JsonLD module** - Into the webtrees modules directory
4. **Test JSON-LD functionality**:
   - JSON-LD embedded in HTML (via `<script type="application/ld+json">` tag)
   - JSON-LD via Accept header (content negotiation)
   - Link header advertising JSON-LD availability

**Note**: The current testcontainers tests are marked as `incomplete` and serve as a skeleton/template. To make them fully functional, you need to:

1. Use or build a working webtrees Docker image (compatible with version ^2.2 from composer.json)
2. Configure the webtrees installation headlessly
3. Import a test GEDCOM file
4. Enable the JsonLD module
5. Implement the actual test assertions

### Docker Compose-based Tests (Legacy)

The existing docker-compose based integration tests are still available:

```bash
make integrationtest
```

These tests use docker-compose to:
- Start MySQL, PHP-FPM, and Nginx containers
- Load webtrees from GitHub releases
- Mount the JsonLD module
- Run test scripts to verify JSON-LD output

## Webtrees Version

The tests are configured to use webtrees version compatible with the `composer.json` requirement (`^2.2`).

## Test Scenarios

The integration tests verify:

1. **JSON-LD in HTML**: When requesting an individual page normally, the response includes a `<script type="application/ld+json" id="json-ld-data">` tag with valid JSON-LD data

2. **JSON-LD via Accept Header**: When requesting with `Accept: application/ld+json`, the server returns:
   - `Content-Type: application/ld+json; charset=utf-8`
   - Pure JSON-LD data (no HTML wrapper)
   - Valid schema.org Person structure

3. **Link Header**: Normal HTML requests include a `Link` header advertising JSON-LD availability:
   ```
   Link: <URL>; rel="alternate"; type="application/ld+json"
   ```

## Docker Requirements

If Docker is not available, the integration tests will be skipped automatically with an appropriate message.

To check if Docker is available:
```bash
docker info
```

## Extending the Tests

To complete the testcontainers integration tests:

1. **Choose a webtrees Docker image**:
   - Option A: Use an existing image (e.g., `nathanvaughn/webtrees`, `dtjs48jkt/webtrees`)
   - Option B: Build a custom image from the Dockerfiles in this directory

2. **Configure webtrees installation**:
   - Set environment variables for database connection
   - Perform initial setup via API or command line
   - Create a default tree

3. **Import test data**:
   - Use a test GEDCOM file (e.g., `vendor/fisharebest/webtrees/tests/data/demo.ged`)
   - Import via webtrees API or CLI

4. **Enable the module**:
   - Enable the JsonLD module via API or configuration

5. **Implement test assertions**:
   - Remove the `markTestIncomplete()` calls
   - Add actual HTTP requests and assertions
   - Validate JSON-LD structure and content

## Example: Making HTTP Requests in Tests

```php
// Test JSON-LD embedded in HTML
$ch = curl_init($webtreesUrl . '/index.php?route=/individual/I1/tree');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
curl_close($ch);

$this->assertStringContainsString('application/ld+json', $response);
$this->assertStringContainsString('"@context":"http://schema.org"', $response);
$this->assertStringContainsString('"@type":"Person"', $response);

// Test JSON-LD via Accept header
$ch = curl_init($webtreesUrl . '/index.php?route=/individual/I1/tree');
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Accept: application/ld+json']);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HEADER, true);
$response = curl_exec($ch);
curl_close($ch);

$this->assertStringContainsString('Content-Type: application/ld+json; charset=utf-8', $response);
```

## Troubleshooting

### Tests are skipped
- Check that Docker is installed and running: `docker info`
- Ensure you have permissions to run Docker commands
- On Linux, you may need to add your user to the `docker` group

### Container fails to start
- Check Docker logs: `docker logs <container-id>`
- Ensure you have enough disk space and memory
- Try pulling the image manually: `docker pull <image-name>`

### Module not loaded
- Verify the module path is correctly mounted
- Check that `module.php` is in the mounted directory
- Ensure the module structure matches webtrees expectations

## Resources

- [Testcontainers PHP Documentation](https://github.com/testcontainers/testcontainers-php)
- [Webtrees Documentation](https://www.webtrees.net/)
- [Schema.org Person](https://schema.org/Person)
- [JSON-LD](https://json-ld.org/)
