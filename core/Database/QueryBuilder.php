<?php

namespace Core\Database;

/**
 * Builder SQL
 */
interface QueryBuilder
{
    public function select(array $selects): QueryBuilder;

    public function where(string $field, string $operator = '=', string $value): QueryBuilder;

    public function limit(int $start, int $offset): QueryBuilder;



    public function get();
}