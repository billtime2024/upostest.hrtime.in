<?php

namespace Modules\Scheme\Entities\Relations;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\Relation;

class JsonBelongsToMany extends Relation
{
    protected $variationIds;

    public function __construct($parent, $variationIds)
    {
        $this->variationIds = $variationIds;
        parent::__construct(\App\Variation::query(), $parent);
    }

    public function addConstraints()
    {
        // No constraints needed
    }

    public function addEagerConstraints(array $models)
    {
        // Not implemented for this use case
    }

    public function initRelation(array $models, $relation)
    {
        // Not implemented for this use case
    }

    public function match(array $models, Collection $results, $relation)
    {
        // Not implemented for this use case
    }

    public function getResults()
    {
        if (empty($this->variationIds)) {
            return collect();
        }
        return $this->query->whereIn('id', $this->variationIds)->get();
    }
}