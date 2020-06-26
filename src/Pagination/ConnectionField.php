<?php

namespace DutchCodingCompany\CursorPagination\Pagination;

use GraphQL\Type\Definition\ResolveInfo;
use Illuminate\Support\Collection;
use DutchCodingCompany\CursorPagination\CursorPaginator;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;

class ConnectionField
{
    /**
     * Resolve page info for connection.
     *
     * @return array<string, mixed>
     */
    public function pageInfoResolver(CursorPaginator $paginator): array
    {
        return $paginator->toArray();
    }

    /**
     * Resolve edges for connection.
     *
     * @param  \Illuminate\Pagination\LengthAwarePaginator<mixed>  $paginator
     * @param  array<string, mixed>  $args
     */
    public function edgeResolver(CursorPaginator $paginator, array $args, GraphQLContext $context, ResolveInfo $resolveInfo): Collection
    {
        // @phpstan-ignore-next-line static refers to the wrong class because it is a proxied method call
        return $paginator->values();
    }
}
