<?php

/**
 * tests/Unit/PhoneNormalizeTest.php
 * Normalisasi nomor HP: 08… → 628…, 628… tetap, +62/62 → 62, buang non-digit.
 */

namespace Tests\Unit;

use App\Support\Phone;
use PHPUnit\Framework\TestCase;

class PhoneNormalizeTest extends TestCase
{
    public function test_leading_zero_becomes_62(): void
    {
        $this->assertSame('6281234567890', Phone::normalize('081234567890'));
    }

    public function test_already_62_is_unchanged(): void
    {
        $this->assertSame('6281234567890', Phone::normalize('6281234567890'));
    }

    public function test_plus_62_and_separators_are_cleaned(): void
    {
        $this->assertSame('6281234567890', Phone::normalize('+62 812-3456-7890'));
    }

    public function test_bare_number_gets_62_prefix(): void
    {
        $this->assertSame('6281234567890', Phone::normalize('81234567890'));
    }

    public function test_empty_returns_null(): void
    {
        $this->assertNull(Phone::normalize(''));
        $this->assertNull(Phone::normalize(null));
    }
}
