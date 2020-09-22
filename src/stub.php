<?php

declare(strict_types = 1);


interface MysqliQueryBuilder {
    public function getQuery(): string;
}

// This is to prevent PHPStorm complaining about code.
function is_literal(string|int|float|bool $value)
{
    return true;
}

class SecurityException extends \Exception {}