<?php

declare(strict_types=1);

/**
 * Savage Worlds Examples
 *
 * Demonstrates exploding dice (Ace mechanic) and raise system
 */

require_once __DIR__ . '/../vendor/autoload.php';

use PHPDice\PHPDice;

$phpdice = new PHPDice();

echo "=== Savage Worlds Dice Rolling Examples ===\n\n";

// 1. Basic Trait Test with Wild Die
echo "1. Fighting Skill Test (d8 + Wild Die d6):\n";
$traitResult = $phpdice->roll('1d8 explode');

$wildResult = $phpdice->roll('1d6 explode');

$finalResult = max($traitResult->total, $wildResult->total);

echo "   Trait die (d8): {$traitResult->total}";
if ($traitResult->explosionHistory !== null && isset($traitResult->explosionHistory[0])) {
    $explosions = $traitResult->explosionHistory[0];
    echo " (Exploded! Rolls: " . implode(' + ', $explosions['rolls']) . ")";
}
echo "\n";

echo "   Wild die (d6): {$wildResult->total}";
if ($wildResult->explosionHistory !== null && isset($wildResult->explosionHistory[0])) {
    $explosions = $wildResult->explosionHistory[0];
    echo " (Exploded! Rolls: " . implode(' + ', $explosions['rolls']) . ")";
}
echo "\n";

echo "   Taking higher result: {$finalResult}\n";
echo "   Target Number (TN): 4\n";
if ($finalResult >= 4) {
    $raises = floor(($finalResult - 4) / 4);
    echo "   SUCCESS! " . ($raises > 0 ? "With {$raises} raise(s)!" : "") . "\n";
} else {
    echo "   FAILURE\n";
}
echo "\n";

// 2. Damage Roll (Longsword + d6)
echo "2. Damage Roll (Longsword d8 + d6 Strength):\n";
$result = $phpdice->roll('1d8 explode + 1d6 explode');
echo "   Total damage: {$result->total}\n";

if ($result->explosionHistory !== null) {
    foreach ($result->explosionHistory as $dieIndex => $history) {
        echo "   Die {$dieIndex} exploded: " . implode(' + ', $history['rolls']) .
             " = {$history['cumulativeTotal']}\n";
    }
}
echo "\n";

// 3. Soak Roll (Vigor d6)
echo "3. Soak Roll (Vigor d6 + Wild Die d6):\n";
$damage = 12;
echo "   Taking {$damage} damage\n";

$vigorResult = $phpdice->roll('1d6 explode');

$wildResult = $phpdice->roll('1d6 explode');

$soakRoll = max($vigorResult->total, $wildResult->total);
echo "   Vigor: {$vigorResult->total}, Wild: {$wildResult->total}\n";
echo "   Best roll: {$soakRoll}\n";

$soaked = floor($soakRoll / 4);
echo "   Wounds soaked: {$soaked}\n\n";

// 4. Multiple Attacks (Multi-Action Penalty)
echo "4. Multiple Attacks (3 attacks, -2 penalty each):\n";
for ($i = 1; $i <= 3; $i++) {
    $result = $phpdice->roll('1d8 explode + 1d6 explode - 2');
    $success = $result->total >= 4;
    echo "   Attack {$i}: {$result->total} " .
         ($success ? "HIT" : "MISS") . "\n";
}
echo "\n";

// 5. Shooting Attack (d8 Shooting)
echo "5. Shooting Attack (d8 Shooting + Wild Die, at Medium Range -2):\n";
$shootingResult = $phpdice->roll('1d8 explode - 2');

$wildResult = $phpdice->roll('1d6 explode - 2');

$attackRoll = max($shootingResult->total, $wildResult->total);
echo "   Shooting: {$shootingResult->total}, Wild: {$wildResult->total}\n";
echo "   Best result: {$attackRoll}\n";

if ($attackRoll >= 4) {
    $raises = floor(($attackRoll - 4) / 4);
    echo "   HIT! ";
    if ($raises > 0) {
        echo "With {$raises} raise(s)! (+1d6 damage per raise)\n";
$damageExpr = $phpdice->parse('2d6 explode + ' . $raises . 'd6 explode');
    } else {
        echo "\n";
$damageExpr = $phpdice->parse('2d6 explode');
    }
    $damageResult = $phpdice->roll($damageExpr);
    echo "   Damage: {$damageResult->total}\n";
} else {
    echo "   MISS\n";
}
echo "\n";

// 6. Dramatic Task (Disarming a Bomb)
echo "6. Dramatic Task (5 rounds to disarm bomb, need 5 successes):\n";
$successes = 0;
$round = 1;

while ($round <= 5 && $successes < 5) {
    echo "   Round {$round}: ";
$skillResult = $phpdice->roll('1d8 explode');

$wildResult = $phpdice->roll('1d6 explode');

    $roll = max($skillResult->total, $wildResult->total);
    echo "Roll {$roll} ";

    if ($roll >= 4) {
        $raises = floor(($roll - 4) / 4);
        $taskSuccess = 1 + $raises;
        $successes += $taskSuccess;
        echo "SUCCESS (+{$taskSuccess}) Total: {$successes}/5\n";
    } else {
        echo "FAILURE Total: {$successes}/5\n";
    }

    $round++;
}

if ($successes >= 5) {
    echo "   BOMB DISARMED!\n";
} else {
    echo "   BOMB EXPLODES!\n";
}
echo "\n";

// 7. Bennies - Reroll
echo "7. Using a Benny to Reroll:\n";
echo "   First roll:\n";
$firstResult = $phpdice->roll('1d6 explode + 1d6 explode');
echo "     Result: {$firstResult->total}\n";

if ($firstResult->total < 4) {
    echo "   That's bad! Spending a Benny to reroll...\n";
$rerollResult = $phpdice->roll('1d6 explode + 1d6 explode');
    echo "     Reroll: {$rerollResult->total}\n";
    $final = max($firstResult->total, $rerollResult->total);
    echo "   Taking better result: {$final}\n";
}
echo "\n";

// 8. Vehicle Chase (Opposed Driving Rolls)
echo "8. Vehicle Chase (3 rounds):\n";
for ($round = 1; $round <= 3; $round++) {
    echo "   Round {$round}:\n";

$driver1Result = $phpdice->roll('1d8 explode + 1d6 explode');

$driver2Result = $phpdice->roll('1d6 explode + 1d6 explode');

    echo "     Driver 1: {$driver1Result->total}\n";
    echo "     Driver 2: {$driver2Result->total}\n";

    if ($driver1Result->total > $driver2Result->total) {
        echo "     Driver 1 gains ground!\n";
    } elseif ($driver2Result->total > $driver1Result->total) {
        echo "     Driver 2 gains ground!\n";
    } else {
        echo "     Dead heat!\n";
    }
}
echo "\n";

// 9. Spell Casting (Powers)
echo "9. Casting Bolt (d10 Spellcasting):\n";
$spellResult = $phpdice->roll('1d10 explode');

$wildResult = $phpdice->roll('1d6 explode');

$castRoll = max($spellResult->total, $wildResult->total);
echo "   Spellcasting: {$spellResult->total}, Wild: {$wildResult->total}\n";
echo "   Best roll: {$castRoll}\n";

if ($castRoll >= 4) {
    $raises = floor(($castRoll - 4) / 4);
    echo "   SUCCESS! Spell cast.\n";
    if ($raises > 0) {
        echo "   {$raises} raise(s)! +1d6 damage per raise.\n";
    }
$damageResult = $phpdice->roll('2d6 explode' . ($raises > 0 ? " + {$raises}d6 explode" : ""));
    echo "   Bolt damage: {$damageResult->total}\n";
} else {
    echo "   FAILURE - Spell fizzles\n";
}
echo "\n";

// 10. Probability Analysis
echo "10. Probability Analysis:\n";
echo "   d6 exploding:\n";
$result = $phpdice->roll('1d6 explode');
$stats = $expression->getStatistics();
echo "     Min: {$stats->minimum}, Max: theoretically unlimited (capped at limit)\n";
echo "     Expected: {$stats->expected}\n";
echo "     (Explosions increase average from 3.5 to ~4.2)\n\n";

echo "=== Examples Complete ===\n";
