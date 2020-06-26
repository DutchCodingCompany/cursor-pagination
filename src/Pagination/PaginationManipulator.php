<?php

namespace DutchCodingCompany\CursorPagination\Pagination;

use GraphQL\Language\AST\FieldDefinitionNode;
use GraphQL\Language\AST\ObjectTypeDefinitionNode;
use Nuwave\Lighthouse\Pagination\PaginationType;
use Nuwave\Lighthouse\Schema\AST\ASTHelper;
use Nuwave\Lighthouse\Schema\AST\PartialParser;

class PaginationManipulator extends \Nuwave\Lighthouse\Pagination\PaginationManipulator
{

    /**
     * Transform the definition for a field to a field with pagination.
     *
     * This makes either an offset-based Paginator or a cursor-based Connection.
     * The types in between are automatically generated and applied to the schema.
     *
     * @param  \Nuwave\Lighthouse\Pagination\PaginationType  $paginationType
     */
    public function transformToPaginatedField(
        PaginationType $paginationType,
        FieldDefinitionNode &$fieldDefinition,
        ObjectTypeDefinitionNode &$parentType,
        ?int $defaultCount = null,
        ?int $maxCount = null,
        ?ObjectTypeDefinitionNode $edgeType = null
    ): void {
        if ($paginationType->isConnection()) {
            $this->registerConnection($fieldDefinition, $parentType, $defaultCount, $maxCount, $edgeType);
        } else {
            $this->registerPaginator($fieldDefinition, $parentType, $defaultCount, $maxCount);
        }
    }

    /**
     * Register connection with schema.
     */
    protected function registerConnection(
        FieldDefinitionNode &$fieldDefinition,
        ObjectTypeDefinitionNode &$parentType,
        ?int $defaultCount = null,
        ?int $maxCount = null,
        ?ObjectTypeDefinitionNode $edgeType = null
    ): void {
        // Register cursor specific pagination type
        $this->addCursorPaginationInfoType();

        $fieldTypeName = ASTHelper::getUnderlyingTypeName($fieldDefinition);

        if ($edgeType) {
            $connectionEdgeName = $edgeType->name->value;
            $connectionTypeName = "{$connectionEdgeName}Connection";
        } else {
            $connectionEdgeName = "{$fieldTypeName}Edge";
            $connectionTypeName = "{$fieldTypeName}Connection";
        }

        $connectionFieldName = addslashes(ConnectionField::class);
        $connectionType = PartialParser::objectTypeDefinition(/** @lang GraphQL */ <<<GRAPHQL
            "A paginated list of $fieldTypeName edges."
            type $connectionTypeName {
                "Pagination information about the list of edges."
                pageInfo: CursorPageInfo! @field(resolver: "{$connectionFieldName}@pageInfoResolver")

                "A list of $fieldTypeName edges."
                edges: [$connectionEdgeName] @field(resolver: "{$connectionFieldName}@edgeResolver")
            }
GRAPHQL
        );
        $this->addPaginationWrapperType($connectionType);

        $connectionEdge = $edgeType
            ?? $this->documentAST->types[$connectionEdgeName]
            ?? PartialParser::objectTypeDefinition(/** @lang GraphQL */ <<<GRAPHQL
                "An edge that contains a node of type $fieldTypeName and a cursor."
                type $connectionEdgeName {
                    "The $fieldTypeName node."
                    node: $fieldTypeName
                }
GRAPHQL
            );
        $this->documentAST->setTypeDefinition($connectionEdge);

        $inputValueDefinitions = [
            self::countArgument('first', $defaultCount, $maxCount),
            "\"A cursor after which elements are returned.\"\nafter: String",
        ];

        $connectionArguments = PartialParser::inputValueDefinitions($inputValueDefinitions);

        $fieldDefinition->arguments = ASTHelper::mergeNodeList($fieldDefinition->arguments, $connectionArguments);
        $fieldDefinition->type = PartialParser::namedType($connectionTypeName);
        $parentType->fields = ASTHelper::mergeNodeList($parentType->fields, [$fieldDefinition]);
    }

    /**
     * Add the types required for pagination.
     */
    protected function addCursorPaginationInfoType(): void
    {
        $this->documentAST->setTypeDefinition(
            PartialParser::objectTypeDefinition(/** @lang GraphQL */ '
                "Pagination information about the corresponding list of items."
                type CursorPageInfo {
                  "When paginating forwards, the cursor to continue."
                  after: String

                  "When paginating forwards, are there more items?"
                  hasAfter: Boolean!

                  "Total number of node in connection."
                  total: Int

                  "Count of nodes in current request."
                  count: Int

                }
            ')
        );
    }
}
