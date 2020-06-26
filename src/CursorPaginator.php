<?php

namespace DutchCodingCompany\CursorPagination;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Pagination\AbstractPaginator;
use Illuminate\Support\Collection;

class CursorPaginator extends AbstractPaginator
{
    protected $items;
    protected Cursor $cursor;
    protected bool $hasAfter;

    public function __construct($items, $cursor = null, bool $hasAfter = false)
    {
        $this->items = $items instanceof Collection ? $items : Collection::make($items);
        $this->cursor = $cursor instanceof Cursor ? $cursor : new Cursor($cursor);
        $this->hasAfter = $hasAfter;
    }

    public function items()
    {
        return $this->items;
    }

    public function toArray()
    {
        return [
            'after'     => Cursor::encode($this->cursor->getAfterCursor()),
            'hasAfter'  => $this->hasAfter,
        ];
    }
}
