# Contributing to Sparkle

We welcome and appreciate contributions from the community! Whether it's bug fixes, feature enhancements, or documentation improvements, your help makes this project better.  

## How to Contribute  

1. **Fork this repository** to your own GitHub account.  
2. Clone your fork locally and create a new branch for your changes.  
3. Make your modifications following the project's standards.  

## Before Submitting a Pull Request  

To ensure code quality and consistency, please run the following commands before opening a PR from your fork:  

```bash
# Run tests
vendor/bin/phpunit

# Static analysis check
vendor/bin/phpstan analyse src tests

# Apply code style fixes
vendor/bin/php-cs-fixer fix src
vendor/bin/php-cs-fixer fix tests

# Automated refactoring (if applicable)
vendor/bin/rector
```

### Guidelines:  
- Follow the project’s coding standards.  
- Write clear, descriptive commit messages.  
- Ensure tests pass and include coverage for new features.  
- Update documentation if needed.  

Once everything passes:  
- Push your changes to your fork.  
- **Open a pull request** from your fork to this repository with a detailed description.  

Thank you for contributing! 🚀  
