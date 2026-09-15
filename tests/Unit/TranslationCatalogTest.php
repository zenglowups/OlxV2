<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class TranslationCatalogTest extends TestCase
{
    public function test_both_languages_cover_the_same_non_empty_messages(): void
    {
        $root = dirname(__DIR__, 2);
        $ro = require $root.'/lang/ro/ui.php';
        $en = require $root.'/lang/en/ui.php';
        $this->assertSame(array_keys($ro), array_keys($en));
        foreach ([$ro, $en] as $catalog) {
            foreach ($catalog as $key => $value) {
                $this->assertIsString($value, $key);
                $this->assertNotSame('', trim($value), $key);
            }
        }
    }
}
