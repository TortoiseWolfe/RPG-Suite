# Contributing to RPG Suite

Thank you for considering contributing to RPG Suite! This document provides guidelines and instructions for contributing to the project.

## Development Environment

### Requirements

- PHP 8.2+
- WordPress 6.8+
- Composer
- Node.js & npm (for JavaScript/CSS assets)
- Docker (optional, for local development)

### Setup

1. **Clone the repository**

```bash
git clone https://github.com/TortoiseWolfe/RPG-Suite.git
cd RPG-Suite
```

2. **Install dependencies**

```bash
composer install
npm install
```

3. **Set up WordPress test environment** (optional)

```bash
bin/install-wp-tests.sh wordpress_test root password localhost latest
```

## Code Standards

RPG Suite follows the WordPress Coding Standards with some modern PHP practices:

- WordPress Coding Standards for PHP
- PSR-12 for areas not covered by WPCS
- ESLint for JavaScript
- Stylelint for CSS

### Code Style Validation

```bash
# PHP
composer phpcs

# Fix automatically fixable PHP issues
composer phpcbf

# JavaScript/CSS
npm run lint
```

## Testing

We use PHPUnit for testing PHP code and Jest for JavaScript:

```bash
# Run PHP tests
composer test

# Run JavaScript tests
npm test
```

Please ensure all tests pass before submitting a PR.

## Branching Strategy

- `main` - production-ready code
- `develop` - main development branch
- Feature branches - `feature/your-feature-name`
- Bug fixes - `fix/bug-description`

## Pull Request Process

1. Create a feature branch from `develop`
2. Implement your changes with tests
3. Ensure all tests pass
4. Update documentation as needed
5. Submit a pull request to the `develop` branch

### PR Requirements

- Clear description of changes
- Tests for new functionality
- Documentation updates if applicable
- No linting errors
- All tests passing

## Subsystem Development

When developing a new subsystem or enhancing an existing one:

1. Follow the established architecture patterns
2. Create appropriate hooks for extensibility
3. Implement proper capability checks
4. Add comprehensive tests
5. Document public APIs

## Commit Message Guidelines

We follow conventional commits:

- `feat:` - A new feature
- `fix:` - A bug fix
- `docs:` - Documentation changes
- `style:` - Code style changes (formatting, etc.)
- `refactor:` - Code changes that neither fix bugs nor add features
- `test:` - Adding or modifying tests
- `chore:` - Changes to the build process or auxiliary tools

Example: `feat(health): add status effects system`

## License

By contributing to RPG Suite, you agree that your contributions will be licensed under the project's GPL-2.0+ license.

## Questions?

If you have questions about contributing, please open an issue on GitHub or contact the maintainers directly.