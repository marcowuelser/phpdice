<!--
SYNC IMPACT REPORT
===================
Version: 0.0.0 → 1.0.0
Rationale: Initial constitution establishment for phpdice PHP library project

Modified Principles:
  - NEW: I. Composer Package Standards
  - NEW: II. PSR-12 Coding Standards
  - NEW: III. Test-Driven Development (NON-NEGOTIABLE)
  - NEW: IV. PHPUnit Testing Coverage
  - NEW: V. Complete Documentation

Added Sections:
  - Core Principles (5 principles established)
  - Quality Assurance Standards
  - Package Distribution Requirements
  - Governance

Removed Sections:
  - None (initial establishment)

Templates Status:
  ✅ plan-template.md - Constitution Check section compatible with new principles
  ✅ spec-template.md - User scenarios and requirements align with TDD approach
  ✅ tasks-template.md - Test-first task organization supports TDD workflow

Follow-up TODOs:
  - None - all placeholders resolved

Generated: 2025-12-02
-->

# phpdice Constitution

## Core Principles

### I. Composer Package Standards

phpdice MUST be developed and distributed as a professionally maintained Composer package:

- Package MUST be published and consumable via Composer (packagist.org registration required)
- MUST follow Composer package best practices including proper autoloading (PSR-4)
- MUST include properly configured `composer.json` with complete metadata (name, description, license, authors, keywords)
- MUST declare all dependencies with appropriate version constraints
- MUST support semantic versioning for dependency management
- Package structure MUST enable easy installation via `composer require marcowuelser/phpdice`

**Rationale**: As a public library, phpdice must integrate seamlessly into PHP projects using the standard package manager. Professional package standards ensure reliability, discoverability, and ease of adoption.

### II. PSR-12 Coding Standards

All code MUST strictly adhere to PSR-12 Extended Coding Style:

- Code style MUST comply with PSR-12 specification (which extends PSR-1)
- Automated linting MUST enforce PSR-12 compliance (php-cs-fixer or phpcs required)
- NO code may be merged that violates PSR-12 standards
- Consistent formatting across entire codebase is NON-NEGOTIABLE
- Include `.php-cs-fixer.php` or `phpcs.xml` configuration in repository

**Rationale**: PSR-12 compliance ensures code consistency, readability, and interoperability with the broader PHP ecosystem. It reduces cognitive load and establishes professional quality standards.

### III. Test-Driven Development (NON-NEGOTIABLE)

TDD is mandatory for all feature development:

- Tests MUST be written BEFORE implementation code
- Red-Green-Refactor cycle MUST be strictly followed:
  1. Write failing test (RED)
  2. Implement minimal code to pass (GREEN)
  3. Refactor while maintaining passing tests (REFACTOR)
- NO implementation code without corresponding failing tests first
- User/stakeholder approval of test scenarios MUST occur before implementation begins
- Tests define the contract and specification for all functionality

**Rationale**: TDD ensures code is testable by design, requirements are clearly understood before coding begins, and regression protection is built-in from day one. This is fundamental to library reliability.

### IV. PHPUnit Testing Coverage

Comprehensive PHPUnit test coverage is required:

- ALL production code MUST be covered by PHPUnit tests
- Test suite MUST include:
  - Unit tests for all classes and methods
  - Integration tests for component interactions
  - Edge case and error condition coverage
- Tests MUST be executable via `composer test` or `vendor/bin/phpunit`
- Minimum code coverage target: 90% (enforced via phpunit.xml configuration)
- PHPUnit configuration (`phpunit.xml`) MUST be included in repository
- Tests MUST run in CI/CD pipeline before any merge

**Rationale**: High test coverage ensures library stability, catches regressions early, and provides confidence for refactoring. PHPUnit is the PHP community standard and enables professional testing practices.

### V. Complete Documentation

User-facing documentation MUST be comprehensive and maintained:

- MUST provide complete user documentation including:
  - README.md with quick start guide and installation instructions
  - API documentation covering all public interfaces
  - Usage examples demonstrating common use cases
  - Code examples that are tested and verified
- Documentation MUST be kept in sync with code changes
- Public API MUST include docblock comments (PHPDoc format)
- SHOULD include contribution guidelines and changelog
- Documentation updates MUST accompany feature implementations

**Rationale**: As a public library, phpdice's success depends on developer adoption. Complete, accurate documentation reduces support burden and accelerates user onboarding.

## Quality Assurance Standards

### Static Analysis

- MUST use static analysis tools (PHPStan or Psalm) at strict level
- Type declarations MUST be used wherever possible (strict_types=1)
- Static analysis MUST pass before merge

### Code Review

- All changes MUST undergo code review
- Reviewer MUST verify PSR-12 compliance, test coverage, and TDD adherence
- Constitution compliance MUST be explicitly checked during review

### Continuous Integration

- MUST have automated CI pipeline that runs:
  - PHPUnit test suite
  - PSR-12 style checks
  - Static analysis
  - Code coverage reporting
- All checks MUST pass before merge is allowed

## Package Distribution Requirements

### Public Repository

- Source code MUST be hosted on GitHub (github.com/marcowuelser/phpdice)
- MUST include proper LICENSE file (open source license required)
- MUST maintain semantic versioning (MAJOR.MINOR.PATCH)
- Tagged releases MUST be created for all versions

### Composer Registry

- MUST be registered on packagist.org
- Package metadata MUST be accurate and complete
- Version updates MUST be automatically synchronized

### Dependencies

- MUST minimize external dependencies
- All dependencies MUST be justified and documented
- MUST specify compatible PHP versions (minimum version requirement)

## Governance

This constitution supersedes all other development practices and standards for the phpdice project.

### Amendment Process

- Amendments require documented rationale and impact analysis
- Constitution version MUST be incremented using semantic versioning:
  - **MAJOR**: Principle removal or incompatible governance changes
  - **MINOR**: New principle additions or material expansions
  - **PATCH**: Clarifications, wording improvements, typo fixes
- All template and documentation dependencies MUST be updated to reflect amendments

### Compliance

- ALL pull requests and code reviews MUST verify compliance with this constitution
- Violations MUST be documented and justified or rejected
- Constitution check MUST be included in implementation plans (see plan-template.md)

### Versioning

Constitution follows semantic versioning aligned with governance impact.

**Version**: 1.0.0 | **Ratified**: 2025-12-02 | **Last Amended**: 2025-12-02
