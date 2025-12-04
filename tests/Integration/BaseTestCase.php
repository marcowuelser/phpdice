<?php

declare(strict_types=1);

namespace PHPDice\Tests\Integration;

use PHPDice\PHPDice;
use PHPDice\Roller\RandomNumberGenerator;
use PHPUnit\Framework\TestCase;

/**
 * 
 * Base test case for integration tests.
 */
abstract class BaseTestCase extends TestCase
{
    protected PHPDice $phpdice;
    protected object $mockRng;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mockRng = $this->createMock(RandomNumberGenerator::class);
        $this->phpdice = new PHPDice($this->mockRng);
    }
}
