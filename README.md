# PHPDice

A comprehensive PHP library for parsing and rolling dice expressions for tabletop RPG systems.

## Features

- **Universal Dice Notation**: Support for all major RPG systems (D&D 5e, Pathfinder, Shadowrun, World of Darkness, FATE, Savage Worlds)
- **Advanced Mechanics**: Advantage/disadvantage, success counting, rerolls, exploding dice, critical detection
- **Statistical Analysis**: Pre-calculated min/max/expected values for any expression
- **Error Handling**: Clear, specific error messages with location information
- **High Performance**: Parse <100ms, Roll <50ms for expressions up to 50 characters
- **Type Safe**: Full PHP 8.0+ type declarations and strict mode

## Requirements

- PHP 8.0 or higher

## Installation

```bash
composer require phpdice/phpdice
```

## Quick Start

```php
<?php

require 'vendor/autoload.php';

use PHPDice\PHPDice;

// Basic dice rolling
$dice = new PHPDice();
$result = $dice->roll('3d6+5');
echo $result->total; // e.g., 18

// Advantage (D&D 5e)
$result = $dice->roll('1d20 advantage');
echo $result->total; // Higher of two d20 rolls

// Success counting (Shadowrun)
$result = $dice->roll('5d6 >=5');
echo $result->successCount; // Number of dice >= 5

// Statistical analysis
$expression = $dice->parse('3d6+5');
echo $expression->statistics->minimum;  // 8
echo $expression->statistics->maximum;  // 23
echo $expression->statistics->expected; // 15.5
```

## Documentation

- [Quick Start Guide](specs/001-dice-parser-roller/quickstart.md)
- [API Documentation](docs/api.md)
- [Examples](examples/)

## Development

### Running Tests

```bash
composer test
```

### Code Quality

```bash
# PSR-12 compliance
composer cs-fix

# Static analysis
composer stan
```

## License

MIT License - see [LICENSE](LICENSE) file for details

## Contributing

See [CONTRIBUTING.md](CONTRIBUTING.md) for development workflow and coding standards.
