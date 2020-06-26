<?php

namespace DutchCodingCompany\CursorPagination;

use Illuminate\Support\Facades\Facade;

/**
 * @see \DutchCodingCompany\CursorPagination\Skeleton\SkeletonClass
 */
class CursorPaginationFacade extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'cursor-pagination';
    }
}
