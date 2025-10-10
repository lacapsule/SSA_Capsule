<?php

declare(strict_types=1);

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;

#[CoversNothing]
final class BasicTest extends TestCase
{
    public function testSanity(): void
    {
        $this->assertTrue(true);
    }
}
