# Contributing to ComposerAutoload

First off, thank you for considering contributing to ComposerAutoload! It's people like you that make this package a great tool for the PHP community.

## Code of Conduct

This project and everyone participating in it is governed by our Code of Conduct. By participating, you are expected to uphold this code.

## How Can I Contribute?

### Reporting Bugs

Before creating bug reports, please check the existing issues as you might find out that you don't need to create one. When you are creating a bug report, please include as many details as possible:

- **Use a clear and descriptive title**
- **Describe the exact steps to reproduce the problem**
- **Provide specific examples to demonstrate the steps**
- **Describe the behavior you observed after following the steps**
- **Explain which behavior you expected to see instead and why**
- **Include PHP version, Laravel version, and package version**

### Suggesting Enhancements

Enhancement suggestions are tracked as GitHub issues. When creating an enhancement suggestion, please include:

- **Use a clear and descriptive title**
- **Provide a step-by-step description of the suggested enhancement**
- **Provide specific examples to demonstrate the steps**
- **Describe the current behavior and explain which behavior you expected to see instead**
- **Explain why this enhancement would be useful**

### Pull Requests

1. Fork the repo and create your branch from `main`
2. If you've added code that should be tested, add tests
3. If you've changed APIs, update the documentation
4. Ensure the test suite passes
5. Make sure your code follows the existing code style
6. Issue that pull request!

## Development Process

### Setting Up Development Environment

1. Fork the repository
2. Clone your fork: `git clone https://github.com/yourusername/composer-autoload.git`
3. Install dependencies: `composer install`
4. Run tests to ensure everything works: `vendor/bin/phpunit`

### Coding Standards

This project follows PSR-12 coding standards. Please ensure your contributions adhere to these standards:

- Use 4 spaces for indentation (no tabs)
- Keep line length under 120 characters when possible
- Use meaningful variable and method names
- Add type hints for all parameters and return types
- Add proper PHPDoc blocks for all methods

#### Running Code Style Checks

We use Laravel Pint for code formatting:

```bash
# Check code style
./vendor/bin/pint --test

# Fix code style issues
./vendor/bin/pint
```

### Testing

All contributions must include appropriate tests:

- Write unit tests for all new functionality
- Ensure existing tests continue to pass
- Aim for high test coverage
- Use descriptive test method names
- Group related tests using test classes and methods

#### Running Tests

```bash
# Run all tests
vendor/bin/phpunit

# Run specific test file
vendor/bin/phpunit tests/Unit/Services/AutoloaderManagerTest.php

# Run tests with coverage
vendor/bin/phpunit --coverage-html coverage
```

### Commit Messages

Please use clear and meaningful commit messages:

- Use the present tense ("Add feature" not "Added feature")
- Use the imperative mood ("Move cursor to..." not "Moves cursor to...")
- Limit the first line to 72 characters or less
- Reference issues and pull requests liberally after the first line

Example:
```
Add support for custom namespace prefixes

- Implement namespace prefix configuration
- Add tests for prefix functionality
- Update documentation

Fixes #123
```

### Documentation

- Update README.md if you're adding new features
- Add or update PHPDoc blocks for new/modified methods
- Include examples in docblocks where helpful
- Update CHANGELOG.md following Keep a Changelog format

## Project Structure

```
src/
├── Commands/          # Artisan commands
├── Config/           # Configuration classes
├── Facades/          # Laravel facades
├── Interfaces/       # Interface definitions
├── Providers/        # Service providers
└── Services/         # Core business logic

tests/
├── Unit/             # Unit tests
├── Feature/          # Integration tests
└── bootstrap.php     # Test bootstrap

config/
└── config.php        # Package configuration
```

## Release Process

1. Update version in `composer.json`
2. Update `CHANGELOG.md`
3. Create git tag
4. GitHub Actions will automatically publish to Packagist

## Questions?

Feel free to open an issue with the `question` label if you have any questions about contributing.
