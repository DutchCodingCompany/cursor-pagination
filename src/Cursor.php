<?php

namespace DutchCodingCompany\CursorPagination;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;
use Illuminate\Support\Arr;

/**
 * Encode and decode pagination cursors.
 */
class Cursor implements Arrayable
{
    protected $after = null;

    public function __construct($after = null)
    {
        $this->after = $after;
    }

    /**
     * @return bool
     */
    public function isPresent()
    {
        return $this->hasAfter();
    }

    /**
     * @return bool
     */
    public function hasAfter()
    {
        return !is_null($this->after);
    }

    /**
     * @return mixed
     */
    public function getAfterCursor()
    {
        return $this->after;
    }

    /**
     * Decode cursor from query arguments.
     *
     * If no 'after' argument is provided or the contents are not a valid base64 string,
     * this will return empty array. That will effectively reset pagination, so the user gets the
     * first slice.
     *
     * @param  array<string, mixed>  $args
     */
    public static function decode(?array $args): Cursor
    {
        $after = null;
        if ($cursor = Arr::get($args, 'after')) {
            $after = json_decode(base64_decode($cursor), true);
        }

        return new static($after);
    }

    /**
     * Encode the given offset to make the implementation opaque.
     */
    public static function encode(array $data): string
    {
        return base64_encode(json_encode($data));
    }

    public function toArray()
    {
        return [
            'after' => static::encode($this->after),
        ];
    }
}
