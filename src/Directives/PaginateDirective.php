<?php

namespace DutchCodingCompany\CursorPagination\Directives;

use GraphQL\Language\AST\FieldDefinitionNode;
use GraphQL\Language\AST\ObjectTypeDefinitionNode;
use GraphQL\Type\Definition\ResolveInfo;
use DutchCodingCompany\CursorPagination\Pagination\CursorArgs;
use DutchCodingCompany\CursorPagination\Pagination\PaginationManipulator;
use Nuwave\Lighthouse\Pagination\PaginationArgs;
use Nuwave\Lighthouse\Schema\AST\DocumentAST;
use Nuwave\Lighthouse\Schema\Values\FieldValue;
use Nuwave\Lighthouse\Support\Contracts\DefinedDirective;
use Nuwave\Lighthouse\Support\Contracts\FieldManipulator;
use Nuwave\Lighthouse\Support\Contracts\FieldResolver;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;

class PaginateDirective extends \Nuwave\Lighthouse\Schema\Directives\PaginateDirective implements FieldResolver, FieldManipulator, DefinedDirective
{
    public static function definition(): string
    {
        return /** @lang GraphQL */ <<<'SDL'
"""
Query multiple model entries as a paginated list.
"""
directive @paginate(
  """
  Which pagination style to use.
  Allowed values: `paginator`, `connection`.
  """
  type: String = "paginator"

  """
  Specify the class name of the model to use.
  This is only needed when the default model detection does not work.
  """
  model: String

  """
  Point to a function that provides a Query Builder instance.
  This replaces the use of a model.
  """
  builder: String

  """
  Apply scopes to the underlying query.
  """
  scopes: [String!]

  """
  Allow clients to query paginated lists without specifying the amount of items.
  Overrules the `pagination.default_count` setting from `lighthouse.php`.
  """
  defaultCount: Int

  """
  Limit the maximum amount of items that clients can request from paginated lists.
  Overrules the `pagination.max_count` setting from `lighthouse.php`.
  """
  maxCount: Int
) on FIELD_DEFINITION
SDL;
    }

    public function manipulateFieldDefinition(DocumentAST &$documentAST, FieldDefinitionNode &$fieldDefinition, ObjectTypeDefinitionNode &$parentType): void
    {
        $paginationManipulator = new PaginationManipulator($documentAST);

        if ($this->directiveHasArgument('builder')) {
            // This is done only for validation
            $this->getResolverFromArgument('builder');
        } else {
            $paginationManipulator->setModelClass(
                $this->getModelClass()
            );
        }

        $paginationManipulator
            ->transformToPaginatedField(
                $this->paginationType(),
                $fieldDefinition,
                $parentType,
                $this->directiveArgValue('defaultCount')
                ?? config('lighthouse.pagination.default_count'),
                $this->paginateMaxCount()
            );
    }

    /**
     * Resolve the field directive.
     */
    public function resolveField(FieldValue $fieldValue): FieldValue
    {
        return $fieldValue->setResolver(
            function ($root, array $args, GraphQLContext $context, ResolveInfo $resolveInfo) {
                if ($this->directiveHasArgument('builder')) {
                    $query = call_user_func(
                        $this->getResolverFromArgument('builder'),
                        $root,
                        $args,
                        $context,
                        $resolveInfo
                    );
                } else {
                    $query = $this->getModelClass()::query();
                }

                $query = $resolveInfo
                    ->argumentSet
                    ->enhanceBuilder(
                        $query,
                        $this->directiveArgValue('scopes', [])
                    );

                if ($this->paginationType()->isPaginator()) {
                    return PaginationArgs
                        ::extractArgs($args, $this->paginationType(), $this->paginateMaxCount())
                        ->applyToBuilder($query);
                }
                if ($this->paginationType()->isConnection()) {
                    return CursorArgs
                        ::extractArgs($args, $this->paginationType(), $this->paginateMaxCount())
                        ->applyToBuilder($query);
                }
            }
        );
    }
}
