<?php

declare(strict_types = 1);


class SafeMysqliQueryBuilder implements MysqliQueryBuilder
{
    private $fragments = [];



    public function __construct(string $query = '')
    {
        if (is_literal($query) !== true) {
            throw new SecurityException("Only literal strings accepted.");
        }

        $this->query = $query;
    }


    public function append(string $sqlfragment)
    {
        if (is_literal($sqlfragment) !== true) {
            throw new SecurityException("Only literal strings accepted.");
        }

        $this->fragments[] = $sqlfragment;
    }

    public function setParameter(string $parameter, mixed $value)
    {
        $this->parameters[$parameter] = $value;
    }

    public function getQuery(): string
    {
        $query = 'not implemented.';
        // Use appropriate escaping here to generate query
        return $query;
    }
}
