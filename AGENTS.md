# Coding standards and best practices for the Padiush project

This document outlines the coding standards and best practices for the Padiush project. It is intended to ensure consistency, readability, and maintainability of the codebase.

## General Guidelines

1. **Linting and Formatting**: Use ESLint and Prettier for JavaScript/TypeScript files. Ensure that all code is linted and formatted before committing.
    - Run `npm run lint` to check for linting errors.
    - Run `npm run format` to format the codebase.
2. **Commit Messages**: Use conventional commit messages. This helps in understanding the history of changes and automating versioning.
    - Use the format: `type: subject`
    - Types can include `feat` (feature), `fix` (bug fix), `docs` (documentation), `style` (formatting, missing semi-colons, etc.), `refactor` (code change that neither fixes a bug nor adds a feature), `test` (adding missing tests), and `chore` (maintenance).

## Branching Strategy

- Use `main` for production-ready code.
- Feature branches should be named `feature/your-feature-name`.
- Bugfix branches should be named `bugfix/your-bugfix-name`.

## Code Structure

- Organize code into modules and components.
- Use clear and descriptive names for files and directories.
- Keep components small and focused on a single responsibility.

## Documentation

- Document all public APIs and components.
- Use JSDoc comments for functions and classes.
- Maintain a `README.md` file in the root directory with project setup instructions, usage, and contribution guidelines.
