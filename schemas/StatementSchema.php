<?php

/**
 * Created by PhpStorm.
 * User: kmadejski
 * Date: 26.11.16
 * Time: 17:36
 */
use \Neomerx\JsonApi\Schema\SchemaProvider;

class StatementSchema extends SchemaProvider
{
    protected $resourceType = 'statements';

    public function getId($statement)
    {
        /** @var Statement $statement */
        return $statement->id;
    }
    public function getAttributes($statement)
    {
        /** @var Statement $statement */
        return [
            'text' => $statement->text,
            'person_name'  => $statement->person_name,
            'rating'  => $statement->rating,
            'rating_text' => $statement->rating_text,
            'rating_img' => $statement->rating_img,
            'explanation'  => $statement->explanation,
            'factchecker_uri'  => $statement->factchecker_uri,
            'sources'  => $statement->sources,
        ];
    }
}