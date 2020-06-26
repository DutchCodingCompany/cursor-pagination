<?php

namespace DutchCodingCompany\CursorPagination\Pagination;

use GraphQL\Error\Error;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;
use Laravel\Scout\Builder as ScoutBuilder;
use DutchCodingCompany\CursorPagination\Cursor;
use DutchCodingCompany\CursorPagination\CursorPaginator;
use Nuwave\Lighthouse\Pagination\PaginationArgs;
use Nuwave\Lighthouse\Pagination\PaginationType;

class CursorArgs
{
    /**
     * @var Cursor
     */
    public $cursor;

    /**
     * @var int
     */
    public $first;

    /**
     * Create a new instance from user given args.
     *
     * @param  mixed[]  $args
     * @param  \Nuwave\Lighthouse\Pagination\PaginationType|null  $paginationType
     * @return static
     *
     * @throws \GraphQL\Error\Error
     */
    public static function extractArgs(array $args, ?PaginationType $paginationType, ?int $paginateMaxCount): self
    {
        $instance = new static();

        if ($paginationType->isConnection()) {
            $instance->first = $args['first'];
            $instance->cursor = Cursor::decode($args);
        } else {
            return PaginationArgs::extractArgs($args, $paginationType, $paginateMaxCount);
        }

        if ($instance->first <= 0) {
            throw new Error(
                self::requestedZeroOrLessItems($instance->first)
            );
        }

        // Make sure the maximum pagination count is not exceeded
        if (
            $paginateMaxCount !== null
            && $instance->first > $paginateMaxCount
        ) {
            throw new Error(
                self::requestedTooManyItems($paginateMaxCount, $instance->first)
            );
        }

        return $instance;
    }

    public static function requestedZeroOrLessItems(int $amount): string
    {
        return "Requested pagination amount must be more than 0, got {$amount}.";
    }

    public static function requestedTooManyItems(int $maxCount, int $actualCount): string
    {
        return "Maximum number of {$maxCount} requested items exceeded, got {$actualCount}. Fetch smaller chunks.";
    }

    public static function scoutBuilderNotSupported(): string
    {
        return "Laravel Scout Builder is not supported for connection based pagination";
    }

    /**
     * Apply the args to a builder, constructing a paginator.
     *
     * @param \Illuminate\Database\Query\Builder|\Laravel\Scout\Builder|\Illuminate\Database\Eloquent\Relations\Relation $builder
     */
    public function applyToBuilder($builder): CursorPaginator
    {
        if ($builder instanceof ScoutBuilder) {
            throw new Error(
                self::scoutBuilderNotSupported()
            );
        }

        return $builder->cursorPaginate($this->first, $this->cursor);
    }
}
