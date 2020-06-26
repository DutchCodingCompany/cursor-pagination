<?php

namespace DutchCodingCompany\CursorPagination\Pagination;

use GraphQL\Type\Definition\ResolveInfo;
use Illuminate\Support\Collection;
use DutchCodingCompany\CursorPagination\CursorPaginator;
use Nuwave\Lighthouse\Pagination\Cursor;
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
        // We know this must be a list, as it is constructed this way during schema manipulation
        /** @var \GraphQL\Type\Definition\ListOfType $listOfType */
        $listOfType = $resolveInfo->returnType;

        // We also know this is one of those two return types
        /** @var \GraphQL\Type\Definition\ObjectType|\GraphQL\Type\Definition\InterfaceType $objectLikeType */
        $objectLikeType = $listOfType->ofType;
        $returnTypeFields = $objectLikeType->getFields();

        // @phpstan-ignore-next-line static refers to the wrong class because it is a proxied method call
        return $paginator->values()
            ->map(function ($item, $index) use ($returnTypeFields): array {
                $data = [];

                foreach ($returnTypeFields as $field) {
                    switch ($field->name) {
                        case 'node':
                            $data['node'] = $item;
                            break;

                        default:
                            // All other fields on the return type are assumed to be part
                            // of the edge, so we try to locate them in the pivot attribute
                            if (isset($item->pivot->{$field->name})) {
                                $data[$field->name] = $item->pivot->{$field->name};
                            }
                    }
                }

                return $data;
            });
    }
}
