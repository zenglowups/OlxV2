<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class TranslationCatalogTest extends TestCase
{
    public function test_both_languages_cover_the_same_non_empty_messages(): void
    {
        $root = dirname(__DIR__, 2);
        foreach (['ui', 'auth', 'profile', 'catalog', 'listing'] as $file) {
            $ro = require $root.'/lang/ro/'.$file.'.php';
            $en = require $root.'/lang/en/'.$file.'.php';
            $this->assertSame(array_keys($ro), array_keys($en));
            foreach ([$ro, $en] as $catalog) {
                foreach ($catalog as $key => $value) {
                    $this->assertIsString($value, $file.':'.$key);
                    $this->assertNotSame('', trim($value), $file.':'.$key);
                }
            }
        }
    }
}
