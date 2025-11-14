# CONTRIBUTING

## Development tools

On Ubuntu, install php and docker:
```
sudo snap install docker
sudo apt install php-cli php-curl php-gd php-json php-intl php-mbstring php-xml php-xdebug php-zip
```

As an IDE, you can choose vscode from snap or phpstorm:
```
sudo snap install phpstorm --classic
sudo snap install code --classic
```

## Testing

Before committing, please run tests to ensure code quality:

```bash
# Run all tests
composer test

# Run only unit tests
composer test:unit

# Run integration tests (requires Docker)
composer test:integration
```

## Building

To build the distribution package:

```bash
# Build complete distribution
composer dist

# Or step by step:
composer compile-po      # Compile translations
composer build-package   # Build package
```