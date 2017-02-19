<?php

/**
 * Created by PhpStorm.
 * User: kmadejski
 * Date: 26.11.16
 * Time: 17:36
 */
use \Neomerx\JsonApi\Schema\SchemaProvider;

class SourceListSchema extends SchemaProvider
{
    protected $resourceType = 'source_list';

    public function getId($list)
    {
        return 1;
    }
    public function getAttributes($list)
    {
        /** @var Statement $statement */
        return [
            'sources' => $list->list
        ];
    }
}