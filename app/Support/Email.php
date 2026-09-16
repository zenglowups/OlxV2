<?php

namespace App\Support;

class Email
{
    public static function normalize(mixed $value): mixed
    {
        return is_string($value) ? mb_strtolower(trim($value)) : $value;
    }
}
