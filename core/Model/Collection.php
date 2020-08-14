<?php

namespace Core\Model;

use Exception;
use Traversable;

class Collection implements \IteratorAggregate
{
    /**
     * @var array
     */
    protected array $items = [];

    /**
     * Collection constructor.
     * @param array $items
     */
    public function __construct(array $items)
    {
        $this->items = $items;
    }

    /**
     * @return Traversable
     */
    public function getIterator(): Traversable
    {
        return new \ArrayIterator($this->items);
    }
}