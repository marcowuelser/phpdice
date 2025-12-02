# Quick Start Guide: PHPDice Library

**Goal**: Get up and running with dice parsing and rolling in 10 minutes

## Installation

### Via Composer (Recommended)

```bash
composer require marcowuelser/phpdice
```

### Manual Installation

```bash
git clone https://github.com/marcowuelser/phpdice.git
cd phpdice
composer install
```

## Basic Usage

### 1. Simple Dice Roll (30 seconds)

```php
<?php
require 'vendor/autoload.php';

use PHPDice\PHPDice;

// Create instance
$dice = new PHPDice();

// Parse and roll
$expression = $dice->parse("3d6");
$result = $dice->roll($expression);

echo "Rolled: " . $result->total . "\n";
echo "Dice: " . implode(", ", $result->diceValues) . "\n";

// Example output:
// Rolled: 14
// Dice: 5, 6, 3
```

### 2. Roll with Modifiers (1 minute)

```php
// Attack roll with +5 bonus
$expression = $dice->parse("1d20+5");
$result = $dice->roll($expression);

echo "Attack roll: {$result->total}\n";
echo "Natural die: {$result->diceValues[0]}\n";

// Example output:
// Attack roll: 18
// Natural die: 13

// Complex arithmetic with parentheses
$expression = $dice->parse("(2d6+3)*2");
$result = $dice->roll($expression);

echo "Damage: {$result->total}\n";
echo "Dice: " . implode(", ", $result->diceValues) . "\n";

// Example output:
// Damage: 22  // (2d6=8 + 3) * 2
// Dice: 5, 3

// Mathematical function
$expression = $dice->parse("floor(1d20/2)");
$result = $dice->roll($expression);

echo "Result: {$result->total}\n";

// Example output:
// Result: 7  // floor(15/2) = floor(7.5) = 7
```

### 3. Advantage / Disadvantage (2 minutes)

```php
// D&D 5e advantage: roll 2d20, keep highest
$expression = $dice->parse("1d20 advantage");
$result = $dice->roll($expression);

echo "Advantage roll: {$result->total}\n";
echo "Both dice: " . implode(", ", $result->diceValues) . "\n";
echo "Kept die index: " . $result->keptDice[0] . "\n";

// Example output:
// Advantage roll: 17
// Both dice: 17, 8
// Kept die index: 0
```

### 4. Character Stats with Variables (3 minutes)

```php
// Character has Strength 3, Dexterity 2
$expression = $dice->parse("1d20+%str%+%dex%", [
    "str" => 3,
    "dex" => 2
]);

$result = $dice->roll($expression);

echo "Ability check: {$result->total}\n";
echo "Die roll: {$result->diceValues[0]}\n";
echo "Modified by: +5 (str +3, dex +2)\n";

// Example output:
// Ability check: 18
// Die roll: 13
// Modified by: +5 (str +3, dex +2)
```

### 5. Success Counting for Dice Pools (4 minutes)

```php
// Shadowrun-style: roll 5d6, count 4+ as successes
$expression = $dice->parse("5d6 >=4");
$result = $dice->roll($expression);

echo "Successes: {$result->successCount}\n";
echo "Dice rolled: " . implode(", ", $result->diceValues) . "\n";

// Mark successes
foreach ($result->diceValues as $index => $value) {
    echo ($value >= 4 ? "✓" : "✗") . " ";
}
echo "\n";

// Example output:
// Successes: 3
// Dice rolled: 6, 5, 4, 2, 1
// ✓ ✓ ✓ ✗ ✗
```

### 6. Critical Success Detection (5 minutes)

```php
// D&D attack with critical on natural 20
$expression = $dice->parse("1d20+5 crit 20");
$result = $dice->roll($expression);

echo "Attack: {$result->total}\n";

if ($result->isCriticalSuccess) {
    echo "🎯 CRITICAL HIT!\n";
} else {
    echo "Regular hit\n";
}

// Example output (if rolled 20):
// Attack: 25
// 🎯 CRITICAL HIT!
```

### 7. Statistical Analysis (7 minutes)

```php
// Get probabilities without rolling
$expression = $dice->parse("3d6+5");

$stats = $expression->statistics;

echo "Expression: {$expression->originalExpression}\n";
echo "Minimum: {$stats->minimum}\n";
echo "Maximum: {$stats->maximum}\n";
echo "Expected: {$stats->expected}\n";

// Output:
// Expression: 3d6+5
// Minimum: 8
// Maximum: 23
// Expected: 15.5
```

## Common Game System Examples

### D&D 5e Attack Roll

```php
// Attack with weapon (+5 bonus), advantage, critical on 20
$expression = $dice->parse("1d20+5 advantage crit 20");
$result = $dice->roll($expression);

echo "Attack: {$result->total}\n";
echo "Dice: " . implode(", ", $result->diceValues) . "\n";

if ($result->isCriticalSuccess) {
    echo "Critical hit! Roll damage twice.\n";
}
```

### Pathfinder Ability Check

```php
// Skill check with +7 modifier against DC 15
$expression = $dice->parse("1d20+7 >=15");
$result = $dice->roll($expression);

echo "Roll: {$result->total}\n";
echo $result->isSuccess ? "✓ Success!" : "✗ Failure";
echo "\n";
```

### Shadowrun Dice Pool

```php
// Roll 8 dice, count 5+ as successes
$expression = $dice->parse("8d6 >=5");
$result = $dice->roll($expression);

echo "Hits: {$result->successCount}\n";

if ($result->successCount >= 4) {
    echo "Overwhelming success!\n";
} elseif ($result->successCount >= 2) {
    echo "Success\n";
} else {
    echo "Failure\n";
}
```

### FATE / Fudge Dice

```php
// 4 FATE dice for skill check
$expression = $dice->parse("4dF+3"); // +3 skill bonus
$result = $dice->roll($expression);

echo "Result: {$result->total}\n";
echo "Dice: " . implode(", ", $result->diceValues) . "\n";

// Interpret FATE ladder
$ladder = [
    -2 => "Terrible", -1 => "Poor", 0 => "Mediocre",
    1 => "Average", 2 => "Fair", 3 => "Good",
    4 => "Great", 5 => "Superb", 6 => "Fantastic"
];

echo "Level: " . ($ladder[$result->total] ?? "Legendary") . "\n";
```

### D&D Character Stat Rolling

```php
// Roll 4d6, drop lowest (standard stat generation)
$expression = $dice->parse("4d6 keep 3 highest");
$result = $dice->roll($expression);

echo "Stat: {$result->total}\n";
echo "Dice: " . implode(", ", $result->diceValues) . "\n";
echo "Dropped: {$result->diceValues[$result->discardedDice[0]]}\n";

// Example output:
// Stat: 15
// Dice: 5, 6, 4, 2
// Dropped: 2
```

## Error Handling

### Invalid Syntax

```php
try {
    $expression = $dice->parse("3d"); // Invalid
} catch (\PHPDice\Exception\ParseException $e) {
    echo "Parse error: {$e->getMessage()}\n";
    // Output: Parse error: Invalid dice notation
}
```

### Missing Variables

```php
try {
    $expression = $dice->parse("1d20+%str%"); // Missing variable
} catch (\PHPDice\Exception\ValidationException $e) {
    echo "Validation error: {$e->getMessage()}\n";
    // Output: Validation error: Missing variable: %str%
}
```

### Invalid Parameters

```php
try {
    // Can't keep 5 dice from a 3d6 roll
    $expression = $dice->parse("3d6 keep 5 highest");
} catch (\PHPDice\Exception\ValidationException $e) {
    echo "Validation error: {$e->getMessage()}\n";
    // Output: Validation error: Keep count exceeds rolls
}
```

## Advanced Features

### Reroll Mechanics

```php
// Reroll 1s and 2s once (Great Weapon Fighting in D&D 5e)
$expression = $dice->parse("2d6 reroll <=2");
$result = $dice->roll($expression);

echo "Damage: {$result->total}\n";
echo "Final dice: " . implode(", ", $result->diceValues) . "\n";

if ($result->rerolledDice) {
    echo "Rerolled:\n";
    foreach ($result->rerolledDice as $index => $original) {
        echo "  Position $index: $original → {$result->diceValues[$index]}\n";
    }
}
```

### Comparison Operators

```php
// Save against DC 15 (must roll 15 or higher)
$expression = $dice->parse("1d20+3 >=15");
$result = $dice->roll($expression);

echo "Saving throw: {$result->total}\n";
echo ($result->isSuccess ? "✓ Saved!" : "✗ Failed") . "\n";
```

### Multiple Modifiers Combined

```php
// Complex roll: advantage, modifier, critical, comparison
$expression = $dice->parse("1d20+5 advantage crit 20 >=15");
$result = $dice->roll($expression);

echo "Roll: {$result->total}\n";
echo "Success: " . ($result->isSuccess ? "Yes" : "No") . "\n";
echo "Critical: " . ($result->isCriticalSuccess ? "Yes" : "No") . "\n";
```

## Next Steps

- **Full Documentation**: See [API.md](API.md) for complete API reference
- **Examples**: Browse [EXAMPLES.md](EXAMPLES.md) for more game system examples
- **Testing**: Learn how to test dice expressions in your application
- **Contributing**: Check [CONTRIBUTING.md](CONTRIBUTING.md) to contribute

## Performance Tips

1. **Reuse expressions**: Parse once, roll many times
   ```php
   $expr = $dice->parse("3d6+5");
   for ($i = 0; $i < 100; $i++) {
       $result = $dice->roll($expr);
   }
   ```

2. **Statistics are free**: Get probabilities without rolling
   ```php
   $expr = $dice->parse("1d20+5");
   $min = $expr->statistics->minimum;  // No roll needed
   ```

3. **Validate early**: Let parser catch errors before rolling
   ```php
   // This validates immediately at parse time
   $expr = $dice->parse("1d20+%str%", ["str" => 3]);
   ```

## Troubleshooting

**Q: "Missing variable" error**
- Ensure all placeholders have values in the `variables` array
- Use `%name%` syntax for all placeholders (e.g., `1d20+%str%` not `1d20+str`)
- Variable names are case-sensitive

**Q: Expression parses but rolls incorrectly**
- Check operator precedence (see documentation)
- Verify whitespace doesn't split numbers

**Q: Statistics seem wrong**
- Remember success counting returns count, not sum
- Advantage/disadvantage affect expected values

**Q: Need to support custom dice?**
- Standard dice support any XdY notation
- For truly custom mechanics, extend the library

## Support

- **Issues**: [GitHub Issues](https://github.com/marcowuelser/phpdice/issues)
- **Discussions**: [GitHub Discussions](https://github.com/marcowuelser/phpdice/discussions)
- **Documentation**: [Full API Reference](API.md)
