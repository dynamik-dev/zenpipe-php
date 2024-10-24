# Contributing to ZenPipe PHP

We're excited that you're interested in contributing to ZenPipe PHP, a simple, fluent pipeline implementation for PHP. This document outlines the process for contributing to our project. By participating in this project, you agree to abide by our [Code of Conduct](CODE_OF_CONDUCT.md).

## Getting Started

1. Fork the repository on GitHub.
2. Clone your fork locally: `git clone https://github.com/dynamik-dev/zenpipe-php.git`
3. Install dependencies: `composer install`
4. Create a new branch for your feature or bug fix: `git checkout -b feature/your-feature-name`

## Making Changes

1. Make your changes in your feature branch.
2. Add or update tests as necessary.
3. Ensure all tests pass by running: `composer test`
4. Update documentation if required.
5. Commit your changes using a descriptive commit message.

## Code Style and Quality

We use several tools to maintain code quality and consistency:

1. **Laravel Pint**: For code style formatting
   - To fix code style issues: `composer lint:fix`
   - To check code style without making changes: `composer lint:check`

2. **PHPStan**: For static analysis
   - To run PHPStan: `composer phpstan`
   - For CI environments: `composer phpstan:ci`

3. **Pest PHP**: For testing
   - To run tests: `composer test`

Please ensure your code passes all these checks before submitting a pull request.

## Submitting a Pull Request

1. Push your changes to your fork on GitHub.
2. Open a pull request from your feature branch to the main repository's `main` branch.
3. Provide a clear description of the changes in your pull request.
4. Link any relevant issues in the pull request description.

## Code Style

- Follow the existing code style in the project, which is enforced by Laravel Pint.
- Use meaningful variable and function names.
- Comment your code when necessary.
- Ensure your code is compatible with the project's PHP version requirements.

## Reporting Bugs

- Use the GitHub Issues page to report bugs.
- Describe the bug in detail, including steps to reproduce.
- Include information about your environment (PHP version, OS, etc.) if relevant.

## Requesting Features

- Use the GitHub Issues page to suggest new features.
- Clearly describe the feature and its potential benefits.
- Be open to discussion and feedback from maintainers and other contributors.

## Project Structure

The project uses PSR-4 autoloading:

- Main code should be placed in the `src/` directory.
- Tests should be placed in the `tests/` directory.
- Helper functions are located in `src/helpers.php`.

## Questions

If you have any questions about contributing, feel free to open an issue for discussion or contact the project maintainer, Chris Arter, at chris@arter.dev.

Thank you for contributing to ZenPipe PHP!

