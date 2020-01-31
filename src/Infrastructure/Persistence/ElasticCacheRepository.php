<?php

namespace Osds\Api\Infrastructure\Persistence;

use Osds\Api\Domain\Exception\ItemNotFoundException;

abstract class ElasticCacheRepository
{
    private $client;
    private $entity;

    public function __construct(
        $client,
        array $configuration
    ) {
        $this->client = $client::create()->setHosts([$configuration['server']])->build();
    }

    public function setEntity($entity)
    {
        $this->entity = $entity;
    }

    public function getEntity()
    {
        return $this->entity;
    }

    public function insert($entity_uuid, $data)
    {
//        $params = [
//            'index' => 'my_index',
//            'body' => [
//                'settings' => [
//                    'number_of_shards' => 2,
//                    'number_of_replicas' => 0
//                ]
//            ]
//        ];
//
//        $this->client->indices()->create($params);

        #TODO: if item has referenced entities => recover them

        $params = [
            'index' => $this->entity,
            'type' => 'data',
            'id' => $entity_uuid,
            'body' => $data
        ];
        $response = $this->client->index($params);
        return $response;
    }

    public function search($entity, Array $searchFields = null, Array $queryFilters = null)
    {
        try {
            $params = [
                'index' => $entity,
                'type' => 'data',
            ];

            if (count($searchFields) > 0) {
                $params['body'] = [
                                    'query' => [
                                        'match' => $searchFields
                                    ]
                                ];
            }
            if (count($queryFilters) > 0) {
                #TODO: add mappings to index
                if (isset($queryFilters['sortby'])) {
//                    $params['sort'] = $queryFilters['sortby'];
//                    foreach ($queryFilters['sortby'] as $field => $direction) {
//                        $params['sort'][$field] = $direction;
//                    }
                }

                if (isset($queryFilters['page_items'])) {
                    $pageItems = $queryFilters['page_items'];
                } else {
                    $pageItems = 999;
                }

                if (isset($queryFilters['page'])) {
                    $pageNumber = $queryFilters['page'];
                } else {
                    $pageNumber = 1;
                }

                $start = ($pageNumber - 1) * $pageItems;

                $params['from'] = $start;
                $params['size'] = $pageItems;

            }

            $response = $this->client->search($params);

            $totalItems = @$response['hits']['total']['value'];
            $items = [];
            if ($totalItems > 0) {
                foreach ($response['hits']['hits'] as $hit) {
                       $item = $hit['_source'];
                       $item['uuid'] = $hit['_id'];
                       $items[] = $item;
                }
            }
            return [
                'total_items' => $totalItems,
                'items' => $items
            ];

        } catch (\Exception $e) {
            dd($e);
        }
    }

    public function find($entity, Array $searchFields = null, Array $queryFilters = null)
    {
        $result = $this->search($entity, $searchFields, $queryFilters);

        if (isset($result['items']) && count($result['items']) == 0) {
            throw new ItemNotFoundException();
        }


        return $result;
    }

    public function update($entity_uuid, $data)
    {
        $this->insert($entity_uuid, $data);
    }

    public function delete($entity_uuid)
    {
        $params = [
            'index' => $this->entity,
            'type' => 'data',
            'id' => $entity_uuid
        ];

        $response = $this->client->delete($params);
        return $response;
    }

    public function getEntityFields($entity)
    {
        $params = ['index' => $entity];
        $response = $this->client->indices()->getMapping($params);
        return array_keys($response[$entity]['mappings']['properties']);
    }
}
