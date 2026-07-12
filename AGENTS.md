# Coding standards and best practices for the Padiush project

This document outlines the coding standards and best practices for the Padiush project. It is intended to ensure consistency, readability, and maintainability of the codebase.

## General Guidelines

1. **Linting and Formatting**: Use ESLint and Prettier for JavaScript/TypeScript files. Ensure that all code is linted and formatted before committing.
    - Run `npm run lint` to check for linting errors.
    - Run `npm run format` to format the codebase.
2. **Commit Messages**: Use conventional commit messages. This helps in understanding the history of changes and automating versioning.
    - Use the format: `type: subject`, **without a scope** (e.g. `feat: Add species search`, not `feat(catalog): ...`).
    - Types can include `feat` (feature), `fix` (bug fix), `docs` (documentation), `style` (formatting, missing semi-colons, etc.), `refactor` (code change that neither fixes a bug nor adds a feature), `test` (adding missing tests), and `chore` (maintenance).
    - Commit in logical batches: each commit should represent one coherent change, rather than lumping unrelated work together.
    - A longer description below the subject is welcome when it adds useful context.

## Testing

- New features and bug fixes must be backed by regression tests. A fix should
  include a test that fails without it; a feature should cover its meaningful
  behaviors and edge cases.
- Run the relevant suites before committing (`docker compose exec -T app php artisan test`
  for PHP, `npx vitest run` for the frontend).

## Frontend Verification

- Every addition, change, or removal that affects the frontend must be visually
  verified in-browser before it is considered done.
- Verify at all three breakpoints: **desktop, tablet, and mobile**. Confirm the
  change renders and behaves correctly at each, not only at desktop width.

## Localization

- Keep the app properly localized across all supported languages (Spanish,
  English, and Portuguese). Spanish is the fallback and should be written first.
- Any user-facing string must be added to every locale file under
  `public/locales/` — never hard-code display text in components.

## Confidentiality

- If given read access to other projects or repositories (for reference or
  inspiration), do not acknowledge or reference them in commit messages, pull
  request descriptions, or code comments.

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
