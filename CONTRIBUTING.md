# Contributing to Daijo Industrial Support System (DISS)

First off, thank you for taking the time to contribute!

Whether you are fixing a typo, updating documentation, resolving a bug, or building a brand-new feature, your help makes DISS better and more reliable for everyone at PT Daijo Industrial.

This document serves as a human-friendly guide to help you get up and running, understand our development conventions, and submit clean, review-ready pull requests.

---

## Table of Contents

- [Core Principles & Golden Rules](#core-principles--golden-rules)
- [How to Get Started](#how-to-get-started)
- [Branch Naming Conventions](#branch-naming-conventions)
- [Coding Standards & Tooling](#coding-standards--tooling)
  - [Backend (PHP & Laravel)](#backend-php--laravel)
  - [Frontend (Blade & Livewire)](#frontend-blade--livewire)
  - [Database & Migrations](#database--migrations)
  - [Automated Testing](#automated-testing)
- [Commit Guidelines](#commit-guidelines)
- [Pull Request (PR) Workflow](#pull-request-pr-workflow)
  - [Pre-PR Checklist](#pre-pr-checklist)
- [Reporting Bugs & Requesting Features](#reporting-bugs--requesting-features)
- [Questions or Need Help?](#questions-or-need-help)

---

## Core Principles & Golden Rules

1. **Safety First (No Secrets)**: Never commit passwords, API keys, database credentials, or real customer/vendor private data. Always check your `.env` and git diffs before staging.
2. **Respect Architecture**: Maintain separation of concerns. Keep controllers and Livewire components lean, placing complex business logic in dedicated Domain Services or Repositories.
3. **Test Before You Push**: If you fix a bug, write a test to prove it is fixed and prevent regressions. If you add a new feature, cover its happy and unhappy paths.
4. **Be Collaborative**: Write readable code with helpful explanations for non-obvious business rules. Leave the codebase a little better than you found it.

---

## How to Get Started

1. **Clone the repository**:
   ```bash
   git clone https://github.com/your-org/DISS.git
   cd DISS
   ```

2. **Set up your local environment**:
   Follow the setup guide in [README.md](README.md#getting-started):
   ```bash
   composer install
   npm install
   cp .env.example .env
   php artisan key:generate
   php artisan migrate
   npm run build
   ```

3. **Verify the test suite passes on your machine**:
   ```bash
   composer test
   ```

4. **Create a new branch for your work**:
   ```bash
   git checkout -b feature/your-feature-name
   ```

---

## Branch Naming Conventions

Use clear, lowercase branch names with hyphens and standard prefixes:

| Prefix | Description | Example |
| :--- | :--- | :--- |
| `feature/` | A new capability, screen, or user feature | `feature/vehicle-p2h-export` |
| `fix/` | A bug fix or patch | `fix/stnk-expiration-calculation` |
| `refactor/` | Code refactoring without behavior change | `refactor/sap-sync-service` |
| `docs/` | Documentation additions or edits | `docs/update-contributing-guide` |
| `test/` | Adding or fixing test cases | `test/add-user-telemetry-tests` |
| `chore/` | Dependency upgrades, build scripts, config | `chore/update-vite-config` |

---

## Coding Standards & Tooling

We provide automated tools to ensure consistent quality across the team.

### Backend (PHP & Laravel)

- **Style Guide**: We follow PSR-12 and standard Laravel conventions.
- **Code Formatter (Laravel Pint)**:
  Run Pint before committing to automatically format your code according to our `.pint.json` configuration:
  ```bash
  # Check code style without changing files
  composer pint:test

  # Automatically fix code styling issues
  composer pint
  ```
- **Static Analysis (PHPStan / Larastan)**:
  We maintain static analysis at Level 4+. Run PHPStan before opening a PR:
  ```bash
  composer stan
  ```
  Ensure zero errors are reported on your changed files.
- **Strict Types**: Add `declare(strict_types=1);` at the top of new service, DTO, or repository files where appropriate.

### Frontend (Blade & Livewire)

- **Formatting**:
  We format Blade views, JavaScript, and SCSS using Blade Formatter and Prettier:
  ```bash
  npm run format
  ```
- **Livewire Components**:
  - Keep state clearly typed in component properties.
  - Utilize Livewire 3 validation attributes (`#[Validate(...)]`) where appropriate.
  - Use URL persistence (`#[Url]`) for searchable or filterable table states.
- **Asset Compilation**:
  Always verify that the Vite bundle compiles cleanly:
  ```bash
  npm run build
  ```

### Database & Migrations

- **Never modify existing migrations** that have already been merged into the main branch. Create a new migration instead:
  ```bash
  php artisan make:migration add_column_to_table_name
  ```
- Always define foreign keys with appropriate cascade or null-on-delete behavior.
- Add index keys on columns frequently used in `WHERE`, `ORDER BY`, or `JOIN` clauses.

### Automated Testing

- We use [Pest PHP](https://pestphp.com) and [PHPUnit](https://phpunit.de).
- Feature tests live in `tests/Feature/` and unit tests in `tests/Unit/`.
- Run your tests before submitting your code:
  ```bash
  # Run all tests
  composer test

  # Run a specific test file
  php artisan test tests/Feature/VehicleTest.php

  # Filter by test name
  php artisan test --filter=UserIndexTest
  ```
- You can run the entire automated quality pipeline at once:
  ```bash
  composer quality
  ```
  *(This runs Pint style checks, PHPStan static analysis, and the Pest/PHPUnit test suite in sequence).*

---

## Commit Guidelines

Write concise, descriptive commit messages. We recommend the [Conventional Commits](https://www.conventionalcommits.org/) convention:

```
<type>: <short summary in imperative mood>

[optional body explaining context or rationale]
```

### Common Types:
- `feat:` A new feature or user-facing capability
- `fix:` A bug fix
- `docs:` Documentation changes only
- `style:` Formatting, missing semicolons, whitespace (no code change)
- `refactor:` Code restructuring that does not alter feature behavior
- `test:` Adding missing tests or correcting existing tests
- `chore:` Updating build tasks, dependencies, package configs

### Examples:
```text
feat: add STNK five-year renewal alert notification
fix: resolve division by zero in forecast material explosion
docs: clarify SAP UTF-16LE streaming parser requirements
refactor: extract EloquentUserRepository methods
```

---

## Pull Request (PR) Workflow

1. **Rebase or merge latest main**:
   Before submitting, ensure your branch is up-to-date with `main` to avoid merge conflicts:
   ```bash
   git checkout main
   git pull origin main
   git checkout your-branch
   git merge main
   ```
2. **Push your branch**:
   ```bash
   git push origin feature/your-feature-name
   ```
3. **Open a Pull Request**:
   - Provide a clear, descriptive title.
   - Summarize the **Why** (problem statement or feature request) and the **What** (summary of code changes).
   - If UI changes were made, attach screenshots or a brief recording showing the new behavior.

### Pre-PR Checklist

Before marking your PR as ready for review, check off these items:

- [ ] `composer pint` was run and `composer pint:test` passes.
- [ ] `composer stan` passes with no errors.
- [ ] `composer test` passes all feature and unit tests.
- [ ] `npm run format` was run for Blade and frontend assets.
- [ ] `npm run build` compiles with 0 errors.
- [ ] No temporary `dd()`, `dump()`, or debug logs left in the code.
- [ ] No sensitive credentials, private keys, or `.env` files are tracked.
- [ ] New database migrations (if any) run cleanly up and down.

---

## Reporting Bugs & Requesting Features

### Reporting a Bug
If you encounter a bug or unexpected behavior:
1. Check if an issue or PR already addresses it.
2. If not, open a new issue with:
   - **Summary**: Clear description of the issue.
   - **Steps to Reproduce**: Detailed reproduction steps.
   - **Expected vs Actual Behavior**: What you expected to happen vs what actually happened.
   - **Environment Details**: PHP version, browser, operating system.
   - **Logs & Screenshots**: Relevant excerpts from `storage/logs/laravel.log` or console errors.

### Requesting a Feature
When proposing new features:
1. Describe the manufacturing or operational use case.
2. Outline the proposed workflow or UI/UX experience.
3. Discuss with the team/maintainers before starting massive architecture overhauls.

---

## Questions or Need Help?

If you have questions about the codebase, architecture patterns, or need guidance on implementing a feature:
- Reach out to the internal engineering team or project maintainer.
- Review existing documentation skills in `.agents/skills/` for domain-specific knowledge (Fleet Management, SAP Sync, Supplier Evaluation, User Management).

Thank you for helping build a better, stronger system for PT Daijo Industrial!
