# Tasks: Dice Expression Parser and Roller

**Input**: Design documents from `/specs/001-dice-parser-roller/`
**Prerequisites**: plan.md (required), spec.md (required), research.md, data-model.md, contracts/

**Tests**: Tests are NOT explicitly requested in the spec - tasks focus on implementation only. The spec mandates TDD approach per Constitution Check, but test creation will be part of implementation workflow (Red-Green-Refactor).

**Organization**: Tasks are grouped by user story to enable independent implementation and testing of each story.

## Format: `- [ ] [ID] [P?] [Story?] Description`

- **Checkbox**: ALWAYS `- [ ]` (markdown checkbox)
- **[ID]**: Task ID (T001, T002, T003...)
- **[P]**: Can run in parallel (different files, no dependencies)
- **[Story]**: Which user story this task belongs to (US1, US2, etc.) - only for user story phases
- Include exact file paths in descriptions

## Path Conventions

Single library project structure: `src/`, `tests/` at repository root per plan.md

---

## Phase 1: Setup (Project Initialization)

**Purpose**: Create project structure, configure development environment, and set up all development tools

### Devcontainer Setup (First Priority)

- [ ] T001 Create .devcontainer/devcontainer.json for PHP 8.0+ development environment
- [ ] T002 Configure devcontainer with Composer, Git, and development extensions (PHP Intelephense, PHPUnit, etc.)
- [ ] T003 Add devcontainer features: PHP 8.0+, Composer, Xdebug for debugging and coverage
- [ ] T004 Reopen workspace in devcontainer to ensure consistent development environment

### Project Structure Setup

- [ ] T005 Create directory structure: src/, tests/, docs/ per plan.md
- [ ] T006 Create composer.json with package metadata, PSR-4 autoloading for PHPDice namespace, require PHP 8.0+
- [ ] T007 [P] Create phpunit.xml with 90% coverage threshold and test suite configuration
- [ ] T008 [P] Create .php-cs-fixer.php for PSR-12 enforcement with strict_types requirement
- [ ] T009 [P] Create phpstan.neon for static analysis at strict level
- [ ] T010 [P] Create README.md with installation instructions and basic usage example
- [ ] T011 [P] Create LICENSE file (MIT recommended per plan.md)
- [ ] T012 [P] Create CHANGELOG.md with initial version 0.1.0-dev
- [ ] T013 [P] Create .gitignore for vendor/, .phpunit.cache/, coverage reports
- [ ] T014 Run composer install to verify package configuration in devcontainer

---

## Phase 2: Foundational (Blocking Prerequisites)

**Purpose**: Core infrastructure that MUST be complete before ANY user story can be implemented

**⚠️ CRITICAL**: No user story work can begin until this phase is complete

- [ ] T015 [P] Create ParseException class in src/Exception/ParseException.php
- [ ] T016 [P] Create ValidationException class in src/Exception/ValidationException.php
- [ ] T017 [P] Create DiceType enum in src/Model/DiceType.php with STANDARD, FUDGE, PERCENTILE cases
- [ ] T018 [P] Create DiceSpecification entity in src/Model/DiceSpecification.php with count, sides, type fields
- [ ] T019 [P] Create RollModifiers entity in src/Model/RollModifiers.php with all modifier fields per data-model.md
- [ ] T020 [P] Create StatisticalData entity in src/Model/StatisticalData.php with min, max, expected fields
- [ ] T021 Create DiceExpression entity in src/Model/DiceExpression.php with specification, modifiers, statistics
- [ ] T022 Create RollResult entity in src/Model/RollResult.php with expression, total, diceValues, flags per data-model.md
- [ ] T023 [P] Create RandomNumberGenerator abstraction in src/Roller/RandomNumberGenerator.php using random_int()
- [ ] T024 Create base test case classes in tests/Unit/BaseTestCase.php and tests/Integration/BaseTestCase.php

**Checkpoint**: Foundation ready - user story implementation can now begin in parallel

---

## Phase 3: User Story 1 - Basic Dice Rolling (Priority: P1) 🎯 MVP

**Goal**: Parse and roll basic dice expressions (XdY format) with numeric results

**Independent Test**: Parse "3d6", "1d20", "2d10" and roll to get sums between valid ranges

### Implementation for User Story 1

- [ ] T025 [P] [US1] Create Lexer class in src/Parser/Lexer.php to tokenize basic XdY notation
- [ ] T026 [P] [US1] Create Token class in src/Parser/Token.php with type and value properties
- [ ] T027 [US1] Implement basic parser in src/Parser/DiceExpressionParser.php for XdY pattern parsing
- [ ] T028 [US1] Add validation for dice count >= 1 (FR-027) in src/Parser/Validator.php
- [ ] T029 [US1] Add validation for sides >= 1 (FR-028) in src/Parser/Validator.php
- [ ] T030 [US1] Add validation for max 100 dice total (FR-029) in src/Parser/Validator.php
- [ ] T031 [US1] Add validation for max 100 sides per die (FR-030) in src/Parser/Validator.php
- [ ] T032 [US1] Add validation to reject invalid syntax like "d6", "3d", "abc" (FR-026) in src/Parser/Validator.php
- [ ] T033 [US1] Implement StatisticalCalculator in src/Model/StatisticalCalculator.php for basic dice statistics
- [ ] T034 [US1] Implement basic DiceRoller in src/Roller/DiceRoller.php to roll standard dice and return RollResult
- [ ] T035 [US1] Create PHPDice facade in src/PHPDice.php with parse() and roll() methods
- [ ] T036 [US1] Write integration test in tests/Integration/BasicRollingTest.php covering all US1 acceptance scenarios
- [ ] T037 [US1] Write unit tests in tests/Unit/Parser/LexerTest.php for tokenization
- [ ] T038 [US1] Write unit tests in tests/Unit/Parser/ValidatorTest.php for all FR-026 through FR-030 validations
- [ ] T039 [US1] Write unit tests in tests/Unit/Roller/DiceRollerTest.php for basic rolling logic

**Checkpoint**: At this point, basic dice rolling (MVP) should be fully functional and testable independently

---

## Phase 4: User Story 2 - Modifiers and Arithmetic (Priority: P2)

**Goal**: Support arithmetic expressions with +, -, *, /, parentheses, and math functions

**Independent Test**: Parse "1d20+5", "(2d6+3)*2", "floor(1d20/2)" and verify arithmetic evaluation

### Implementation for User Story 2

- [ ] T040 [P] [US2] Extend Lexer in src/Parser/Lexer.php to recognize +, -, *, /, (, ), function names
- [ ] T041 [US2] Create ArithmeticNode classes in src/Parser/AST/ for expression tree representation
- [ ] T042 [US2] Implement arithmetic expression parser using recursive descent in src/Parser/DiceExpressionParser.php
- [ ] T043 [US2] Add operator precedence handling (*, / before +, -) in parser
- [ ] T044 [US2] Add parenthesis matching validation (FR-033) in src/Parser/Validator.php
- [ ] T045 [US2] Add division by zero detection (FR-031) in src/Parser/Validator.php
- [ ] T046 [US2] Implement mathematical functions: floor(), ceiling(), round() in src/Parser/Functions.php
- [ ] T047 [US2] Add function argument validation (FR-032) - must have exactly 1 argument in src/Parser/Validator.php
- [ ] T048 [US2] Update StatisticalCalculator to handle arithmetic expressions in src/Model/StatisticalCalculator.php
- [ ] T049 [US2] Update DiceRoller to evaluate arithmetic expressions in src/Roller/DiceRoller.php
- [ ] T050 [US2] Write integration test in tests/Integration/ModifiersTest.php covering all US2 acceptance scenarios
- [ ] T051 [US2] Write unit tests in tests/Unit/Parser/ArithmeticParserTest.php for expression parsing
- [ ] T052 [US2] Write unit tests in tests/Unit/Parser/ValidatorTest.php for FR-031, FR-032, FR-033

**Checkpoint**: At this point, basic rolling AND arithmetic modifiers should both work independently

---

## Phase 5: User Story 3 - Advantage and Disadvantage (Priority: P3)

**Goal**: Support advantage/disadvantage mechanics (roll multiple, keep highest/lowest)

**Independent Test**: Parse "1d20 advantage", "4d6 keep 3 highest" and verify correct die selection

### Implementation for User Story 3

- [ ] T053 [P] [US3] Extend Lexer to recognize keywords: advantage, disadvantage, keep, highest, lowest in src/Parser/Lexer.php
- [ ] T054 [US3] Add advantage/disadvantage parsing in src/Parser/DiceExpressionParser.php
- [ ] T055 [US3] Add keep count validation - must not exceed roll count (FR-003a, FR-004a) in src/Parser/Validator.php
- [ ] T056 [US3] Add conflicting modifier detection - cannot have both advantage AND disadvantage (FR-034) in src/Parser/Validator.php
- [ ] T057 [US3] Update StatisticalCalculator to compute advantage/disadvantage probabilities in src/Model/StatisticalCalculator.php
- [ ] T058 [US3] Update DiceRoller to implement keep-highest and keep-lowest logic in src/Roller/DiceRoller.php
- [ ] T059 [US3] Update RollResult to track keptDice and discardedDice indices in src/Roller/DiceRoller.php
- [ ] T060 [US3] Write integration test in tests/Integration/AdvantageTest.php covering all US3 acceptance scenarios
- [ ] T061 [US3] Write unit tests in tests/Unit/Parser/ValidatorTest.php for FR-003a, FR-004a, FR-034
- [ ] T062 [US3] Write unit tests in tests/Unit/Roller/DiceRollerTest.php for advantage/disadvantage logic

**Checkpoint**: At this point, basic rolling, arithmetic, AND advantage/disadvantage should all work independently

---

## Phase 6: User Story 4 - Success Counting (Priority: P4)

**Goal**: Count dice above threshold instead of summing (for dice pool systems)

**Independent Test**: Parse "5d6 >=4", "10d10 threshold 7" and verify result is count, not sum

### Implementation for User Story 4

- [ ] T063 [P] [US4] Extend Lexer to recognize keywords: threshold, comparison operators >=, >, <, <= in src/Parser/Lexer.php
- [ ] T064 [US4] Add success counting syntax parsing in src/Parser/DiceExpressionParser.php
- [ ] T065 [US4] Update StatisticalCalculator to compute expected success count in src/Model/StatisticalCalculator.php
- [ ] T066 [US4] Update DiceRoller to count successes instead of summing when success threshold set in src/Roller/DiceRoller.php
- [ ] T067 [US4] Update RollResult to set successCount field in success counting mode in src/Roller/DiceRoller.php
- [ ] T068 [US4] Write integration test in tests/Integration/SuccessCountingTest.php covering all US4 acceptance scenarios
- [ ] T069 [US4] Write unit tests in tests/Unit/Model/StatisticalCalculatorTest.php for success probability calculations
- [ ] T070 [US4] Write unit tests in tests/Unit/Roller/DiceRollerTest.php for success counting logic

**Checkpoint**: At this point, all previous stories PLUS success counting should work independently

---

## Phase 7: User Story 5 - Reroll Mechanics (Priority: P5)

**Goal**: Reroll dice meeting conditions exactly once (no infinite loops)

**Independent Test**: Parse "4d6 reroll <=2" and verify dice showing 1-2 are rerolled once only

### Implementation for User Story 5

- [ ] T071 [P] [US5] Extend Lexer to recognize keyword: reroll in src/Parser/Lexer.php
- [ ] T072 [US5] Add reroll syntax parsing with threshold operators in src/Parser/DiceExpressionParser.php
- [ ] T073 [US5] Update StatisticalCalculator to adjust statistics for reroll mechanics in src/Model/StatisticalCalculator.php
- [ ] T074 [US5] Implement reroll logic with single reroll limit (FR-005a) in src/Roller/DiceRoller.php
- [ ] T075 [US5] Update RollResult to track rerolledDice map (index => original value) in src/Roller/DiceRoller.php
- [ ] T076 [US5] Write integration test in tests/Integration/RerollTest.php covering all US5 acceptance scenarios
- [ ] T077 [US5] Write unit tests in tests/Unit/Roller/DiceRollerTest.php verifying single reroll per die (FR-005a)

**Checkpoint**: At this point, all previous stories PLUS reroll mechanics should work independently

---

## Phase 8: User Story 6 - Special Dice Types (Priority: P6)

**Goal**: Support fudge dice (dF: -1/0/+1) and percentile dice (d%: 1-100)

**Independent Test**: Parse "4dF", "d%", "1d100" and verify correct value ranges

### Implementation for User Story 6

- [ ] T078 [P] [US6] Extend Lexer to recognize special dice notation: dF, d% in src/Parser/Lexer.php
- [ ] T079 [US6] Add fudge dice parsing (XdF format) in src/Parser/DiceExpressionParser.php
- [ ] T080 [US6] Add percentile dice parsing (d% or Xd100 format) in src/Parser/DiceExpressionParser.php
- [ ] T081 [US6] Update StatisticalCalculator for fudge dice (-1, 0, +1 values) in src/Model/StatisticalCalculator.php
- [ ] T082 [US6] Update StatisticalCalculator for percentile dice (1-100 range) in src/Model/StatisticalCalculator.php
- [ ] T083 [US6] Update DiceRoller to generate fudge dice values (-1, 0, +1) in src/Roller/DiceRoller.php
- [ ] T084 [US6] Update DiceRoller to generate percentile values (1-100) in src/Roller/DiceRoller.php
- [ ] T085 [US6] Write integration test in tests/Integration/SpecialDiceTest.php covering all US6 acceptance scenarios
- [ ] T086 [US6] Write unit tests in tests/Unit/Roller/DiceRollerTest.php for fudge and percentile dice

**Checkpoint**: At this point, all previous stories PLUS special dice types should work independently

---

## Phase 9: User Story 7 - Placeholders and Variables (Priority: P7)

**Goal**: Support %name% placeholder syntax with variable binding at parse time

**Independent Test**: Parse "1d20+%str%+%proficiency%" with variables provided and verify resolution

### Implementation for User Story 7

- [ ] T087 [P] [US7] Extend Lexer to recognize placeholder pattern %name% in src/Parser/Lexer.php
- [ ] T088 [US7] Add placeholder parsing and variable substitution in src/Parser/DiceExpressionParser.php
- [ ] T089 [US7] Add validation to reject unbound placeholders (FR-009a) in src/Parser/Validator.php
- [ ] T090 [US7] Store resolved variables in RollModifiers.resolvedVariables in src/Parser/DiceExpressionParser.php
- [ ] T091 [US7] Update parse() method signature to accept variables parameter in src/PHPDice.php
- [ ] T092 [US7] Write integration test in tests/Integration/PlaceholdersTest.php covering all US7 acceptance scenarios
- [ ] T093 [US7] Write unit tests in tests/Unit/Parser/ValidatorTest.php for FR-009a (missing variables error)

**Checkpoint**: At this point, all previous stories PLUS placeholder variables should work independently

---

## Phase 10: User Story 8 - Success Rolls and Comparisons (Priority: P8)

**Goal**: Evaluate if roll meets target number (return boolean success flag)

**Independent Test**: Parse "1d20+3 >= 15" and verify result includes success/failure flag

### Implementation for User Story 8

- [ ] T094 [P] [US8] Extend Lexer to recognize comparison operators at expression level (for success rolls) in src/Parser/Lexer.php
- [ ] T095 [US8] Add comparison expression parsing (expression >= threshold) in src/Parser/DiceExpressionParser.php
- [ ] T096 [US8] Store comparisonOperator and comparisonThreshold in DiceExpression in src/Parser/DiceExpressionParser.php
- [ ] T097 [US8] Update DiceRoller to evaluate comparison and set isSuccess flag in src/Roller/DiceRoller.php
- [ ] T098 [US8] Write integration test in tests/Integration/ComparisonTest.php covering all US8 acceptance scenarios
- [ ] T099 [US8] Write unit tests in tests/Unit/Roller/DiceRollerTest.php for comparison evaluation logic

**Checkpoint**: At this point, all previous stories PLUS success roll comparisons should work independently

---

## Phase 11: User Story 9 - Critical Success and Critical Failure (Priority: P9)

**Goal**: Detect critical success (e.g., natural 20) and critical failure (e.g., natural 1)

**Independent Test**: Parse "1d20 crit 20 glitch 1" and verify flags set when thresholds met

### Implementation for User Story 9

- [ ] T100 [P] [US9] Extend Lexer to recognize keywords: crit, glitch in src/Parser/Lexer.php
- [ ] T101 [US9] Add critical threshold parsing (crit N, glitch N syntax) in src/Parser/DiceExpressionParser.php
- [ ] T102 [US9] Add validation that critical thresholds are within die range (FR-035, FR-036) in src/Parser/Validator.php
- [ ] T103 [US9] Store criticalSuccess and criticalFailure thresholds in RollModifiers in src/Parser/DiceExpressionParser.php
- [ ] T104 [US9] Update DiceRoller to check for critical success/failure and set flags in src/Roller/DiceRoller.php
- [ ] T105 [US9] Write integration test in tests/Integration/CriticalTest.php covering all US9 acceptance scenarios
- [ ] T106 [US9] Write unit tests in tests/Unit/Parser/ValidatorTest.php for FR-035, FR-036
- [ ] T107 [US9] Write unit tests in tests/Unit/Roller/DiceRollerTest.php for critical detection logic

**Checkpoint**: At this point, all previous stories PLUS critical detection should work independently

---

## Phase 12: User Story 10 - Statistical Analysis (Priority: P10)

**Goal**: Provide min, max, expected value for any expression without rolling

**Independent Test**: Parse "3d6+5" and query statistics for min=8, max=23, expected=15.5

### Implementation for User Story 10

- [ ] T108 [US10] Verify StatisticalCalculator handles all expression types from US1-US9 in src/Model/StatisticalCalculator.php
- [ ] T109 [US10] Add variance and standard deviation calculations (optional fields) in src/Model/StatisticalCalculator.php
- [ ] T110 [US10] Ensure 3 decimal precision for all statistical calculations (SC-004) in src/Model/StatisticalCalculator.php
- [ ] T111 [US10] Add getStatistics() method to DiceExpression for easy access in src/Model/DiceExpression.php
- [ ] T112 [US10] Write integration test in tests/Integration/StatisticsTest.php covering all US10 acceptance scenarios
- [ ] T113 [US10] Write unit tests in tests/Unit/Model/StatisticalCalculatorTest.php for all dice types and mechanics
- [ ] T114 [US10] Verify statistical accuracy against known probability distributions in tests

**Checkpoint**: All user stories (P1-P10) should now be independently functional with complete statistical analysis

---

## Phase 13: Polish & Cross-Cutting Concerns

**Purpose**: Improvements that affect multiple user stories and final quality checks

- [ ] T115 [P] Write comprehensive API documentation in docs/api.md covering all classes and methods
- [ ] T116 [P] Expand README.md with installation, usage examples, game system compatibility table
- [ ] T117 [P] Create examples/ directory with game system examples (D&D 5e, Shadowrun, FATE, etc.)
- [ ] T118 [P] Update quickstart.md examples to match actual implementation
- [ ] T119 Run PHPStan static analysis and fix all issues to achieve strict level compliance
- [ ] T120 Run PHP-CS-Fixer to ensure 100% PSR-12 compliance
- [ ] T121 Verify all files have declare(strict_types=1) as first statement
- [ ] T122 Run PHPUnit with coverage report and ensure 90%+ coverage threshold met
- [ ] T123 Add PHPDoc comments to all public methods and classes
- [ ] T124 [P] Create contract tests in tests/Contract/GameSystemContractTest.php for D&D 5e, Pathfinder, Shadowrun, World of Darkness, FATE
- [ ] T125 Performance profiling - verify parse <100ms, roll <50ms for all test cases
- [ ] T126 Memory profiling - verify <1MB per operation for all test cases
- [ ] T127 [P] Validate quickstart.md tutorial can be completed in 10 minutes (SC-006)
- [ ] T128 Update CHANGELOG.md with all features and prepare for v1.0.0 release
- [ ] T129 [P] Create CONTRIBUTING.md with development workflow and constitution compliance
- [ ] T130 Final constitution check against all 8 principles in plan.md

---

## Dependencies & Execution Order

### Phase Dependencies

- **Setup (Phase 1)**: No dependencies - can start immediately
- **Foundational (Phase 2)**: Depends on Setup completion - BLOCKS all user stories
- **User Stories (Phase 3-12)**: All depend on Foundational phase completion
  - User stories can proceed in parallel (if staffed) OR
  - Sequentially in priority order: P1 → P2 → P3 → ... → P10
- **Polish (Phase 13)**: Depends on all desired user stories being complete

### User Story Dependencies

All user stories are designed to be independently testable, but they build incrementally:

- **US1 (Basic Rolling)**: Foundation - no dependencies after Phase 2
- **US2 (Arithmetic)**: Extends US1 parser/roller - can work independently
- **US3 (Advantage)**: Extends US1 roller - can work independently
- **US4 (Success Counting)**: Extends US1 roller - can work independently
- **US5 (Reroll)**: Extends US1 roller - can work independently
- **US6 (Special Dice)**: Extends US1 parser/roller - can work independently
- **US7 (Placeholders)**: Extends US2 parser - can work independently
- **US8 (Comparisons)**: Extends US2 parser - can work independently
- **US9 (Criticals)**: Extends US1 roller - can work independently
- **US10 (Statistics)**: Uses calculator from US1 - validates all previous stories

### Within Each User Story

For user stories with explicit TDD workflow:
1. Write failing integration test first (Red)
2. Implement parser extensions (if needed)
3. Implement validation (if needed)
4. Implement roller extensions (if needed)
5. Implement statistical calculations (if needed)
6. Verify integration test passes (Green)
7. Refactor (Refactor)
8. Write unit tests for edge cases
9. Verify all tests pass
10. Mark story complete

### Parallel Opportunities

**Phase 1 (Setup)**:
- Devcontainer tasks (T001-T004) must run sequentially (T004 depends on T001-T003)
- After devcontainer ready: T007, T008, T009, T010, T011, T012, T013 can all run in parallel

**Phase 2 (Foundational)**: T015, T016, T017, T018, T019, T020, T023 can all run in parallel (different files)

**User Story Parallelization** (if team has capacity):
- After Phase 2: All user stories can start in parallel by different developers
- Within each story: Tasks marked [P] can run in parallel (different files)

**Examples**:
```bash
# Devcontainer setup (sequential):
T001 (devcontainer.json) → T002 (configure) → T003 (features) → T004 (reopen)

# Parallel in Setup (after devcontainer):
T007 (phpunit.xml) || T008 (.php-cs-fixer.php) || T009 (phpstan.neon)

# Parallel in Foundational:
T015 (ParseException) || T016 (ValidationException) || T017 (DiceType enum)

# Parallel User Stories (with 3 developers):
Dev A: Phase 3 (US1 Basic Rolling)
Dev B: Phase 4 (US2 Arithmetic) - after Phase 2 complete
Dev C: Phase 5 (US3 Advantage) - after Phase 2 complete

# Within US1:
T025 (Lexer) || T026 (Token) can run in parallel (different files)
```

---

## Implementation Strategy

### MVP First (Fastest Path to Value)

**Minimum Viable Product = User Story 1 Only**

1. Complete Phase 1: Setup (T001-T014) ≈ 1 day (includes devcontainer setup)
2. Complete Phase 2: Foundational (T015-T024) ≈ 1 day
3. Complete Phase 3: User Story 1 (T025-T039) ≈ 3 days
4. **STOP and VALIDATE**: Run integration tests, verify basic dice rolling works
5. **Deploy/Demo**: You now have a working dice roller library!

**Total MVP Time**: ~5 days

### Incremental Delivery (Recommended)

Each phase adds value without breaking previous functionality:

1. **Setup + Foundational** (T001-T024) → Foundation ready ≈ 2 days
2. **+ US1 Basic Rolling** (T025-T039) → MVP deployable ≈ 3 days total (5 days)
3. **+ US2 Arithmetic** (T040-T052) → Complex expressions ≈ 2 days (7 days)
4. **+ US3 Advantage** (T053-T062) → D&D 5e support ≈ 2 days (9 days)
5. **+ US4 Success Counting** (T063-T070) → Dice pool systems ≈ 1 day (10 days)
6. **+ US5 Reroll** (T071-T077) → Additional mechanics ≈ 1 day (11 days)
7. **+ US6 Special Dice** (T078-T086) → FATE/percentile ≈ 1 day (12 days)
8. **+ US7 Placeholders** (T087-T093) → Character integration ≈ 1 day (13 days)
9. **+ US8 Comparisons** (T094-T099) → Target numbers ≈ 1 day (14 days)
10. **+ US9 Criticals** (T100-T107) → Exceptional outcomes ≈ 1 day (15 days)
11. **+ US10 Statistics** (T108-T114) → Probability analysis ≈ 1 day (16 days)
12. **+ Polish** (T115-T130) → Production ready ≈ 2 days (18 days)

**Total Full Implementation**: ~18 days (matches plan.md estimate of 16 days)

### Parallel Team Strategy (3 Developers)

With 3 developers available:

**Week 1**:
- All devs: Setup + Foundational (T001-T024) together - 2 days
- Dev A: US1 Basic Rolling (T025-T039) - 3 days
- Dev B: US2 Arithmetic (T040-T052) - starts day 3 - 2 days
- Dev C: US3 Advantage (T053-T062) - starts day 3 - 2 days

**Week 2**:
- Dev A: US4 Success Counting (T063-T070) - 1 day
- Dev B: US5 Reroll (T071-T077) - 1 day
- Dev C: US6 Special Dice (T078-T086) - 1 day
- Dev A: US7 Placeholders (T087-T093) - 1 day
- Dev B: US8 Comparisons (T094-T099) - 1 day
- Dev C: US9 Criticals (T100-T107) - 1 day

**Week 3**:
- Dev A: US10 Statistics (T108-T114) - 1 day
- All devs: Polish (T115-T130) together - 2 days

**Total with 3 Devs**: ~12 days (significant speedup)

---

## Validation Checkpoints

### After Each User Story

1. Run integration tests for that story
2. Verify story works independently
3. Run full test suite to ensure no regressions
4. Check code coverage still >= 90%
5. Run PHPStan and PHP-CS-Fixer
6. Commit with message: "feat: Implement [User Story N] - [Title]"

### Before Phase 13 (Polish)

1. All 10 user stories must have passing integration tests
2. Code coverage must be >= 90%
3. PHPStan must pass at strict level
4. PSR-12 compliance must be 100%
5. All acceptance scenarios from spec.md must be verified

### Before Release (v1.0.0)

1. All 130 tasks completed and checked off
2. Constitution check passed (all 8 principles verified)
3. Success criteria SC-001 through SC-007 all met
4. Quickstart.md validated (10 minute completion)
5. Game system contract tests passing (D&D, Shadowrun, FATE, etc.)
6. Performance benchmarks met (parse <100ms, roll <50ms)
7. Documentation complete and accurate

---

## Notes

- **Devcontainer First**: All development happens in consistent PHP 8.0+ environment
- **TDD Workflow**: Per constitution, follow Red-Green-Refactor for each task
- **[P] markers**: Tasks in different files with no dependencies can run in parallel
- **[Story] labels**: Enable traceability from tasks back to user requirements
- **Edge Case Coverage**: All FR-026 through FR-037 validation requirements are included
- **Independent Stories**: Each user story delivers value and can be tested standalone
- **Stop Points**: Use checkpoints to validate before proceeding
- **Commit Frequency**: Commit after each task or logical group of [P] tasks
- **Quality Gates**: Run PHPStan + PHP-CS-Fixer + PHPUnit before each commit

---

## Error Handling Requirements (from Edge Cases)

All validation tasks enforce these error handling requirements:

- Invalid syntax (FR-026): Tasks T032, T038
- Dice constraints (FR-027, FR-028, FR-029, FR-030): Tasks T028, T029, T030, T031, T038
- Arithmetic validation (FR-031, FR-032, FR-033): Tasks T044, T045, T047, T052
- Modifier conflicts (FR-034): Tasks T056, T061
- Critical thresholds (FR-035, FR-036): Tasks T102, T106
- Unbound placeholders (FR-009a): Tasks T089, T093

All error messages must follow FR-037: identify problem, indicate location, specify expectations
