<?php

namespace Osds\DDDCommon\Infrastructure\Persistence;

abstract class InMemoryRepository
{

    private $entity;

    public function setEntity($entity)
    {
        $this->entity = $entity;
    }

    public function getEntity()
    {
        return $this->entity;
    }

    public function getEntityFields()
    {
        return [];
    }

    public function insert($entity_uuid, $data)
    {
        // TODO: Implement insert() method.
    }

    public function search($entity, Array $searchFields = null, Array $queryFilters = null)
    {
        $items = [];
        $totalItems = 0;

        if (count($searchFields) > 0) {
            if (isset($searchFields['uuid']) && $searchFields['uuid'] == '31415-926535-897932') {
                $totalItems = 1;
                $items[] = [
                   'uuid' => '31415-926535-897932',
                   'field' => 'value'
                ];
            }

            if (isset($searchFields['uuid']) && $searchFields['uuid'] == 'XXXXX-XXXXXX-XXXXXX') {
                // do nothing (not found)
            }

            if (isset($searchFields['profile']) && $searchFields['profile'] == 'admin') {
                $totalItems = 2;
                $items[] = [
                   'uuid' => '31415-926535-897932',
                   'field' => 'value'
                ];
                $items[] = [
                   'uuid' => '31415-926535-897932',
                   'field' => 'value'
                ];
            }
        }

        if (count($queryFilters) > 0) {
            if (isset($queryFilters['page']) && isset($queryFilters['page_items'])) {
                $totalItems = 100;
                for ($i=0; $i<$queryFilters['page_items']; $i++) {
                    $items[] = [];
                }
            }

        }

        return [
            'total_items' => $totalItems,
            'items' => $items
        ];
    }

    public function find($entity, Array $searchFields = null, Array $queryFilters = null)
    {
        // TODO: Implement find() method.
    }

    public function update($entityId, $data)
    {
        // TODO: Implement update() method.
    }

    public function delete($entityId)
    {
        // TODO: Implement delete() method.
    }
}
