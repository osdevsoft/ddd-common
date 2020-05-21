<?php

namespace Osds\DDDCommon\Infrastructure\Persistence;

use Doctrine\Common\Annotations\Annotation;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\PersistentCollection;
use Doctrine\ORM\Query;

use Osds\Api\Domain\Exception\ItemNotFoundException;
use Osds\DDDCommon\Infrastructure\Helpers\EntityFactory;
use Osds\DDDCommon\Infrastructure\Helpers\Server;
use Osds\DDDCommon\Infrastructure\Helpers\StringConversion;

abstract class DoctrineRepository
{

    private $entity;

    private $entityManager;

    public function __construct(
        $client
    ) {
        $this->entityManager = $client->getManager($_REQUEST['originSite']);
    }


    /**
     * @param string     $entity            name of the entity we want to retrieve items
     * @param array|null $searchFields     fields we are going to filter by
     * @param array|null $queryFilters     sorting, pagination
     * @return array
     */
    public function search($entity, Array $searchFields = null, Array $queryFilters = null)
    {
        $joinedEntities = [];

        $this->setEntity($entity);
        $tableName = $this->getEntityData('table');
        #get repository and query builder for the queries
        $queryBuilder = $this->getEntityData('repository')->createQueryBuilder($tableName);

        list($queryBuilder, $joinedEntities) =
            $this->addFieldsToSearchBy($tableName, $searchFields, $queryBuilder, $joinedEntities);
        list($queryBuilder, $joinedEntities) =
            $this->addFieldsToFilterBy($tableName, $queryFilters, $queryBuilder, $joinedEntities);
        list($queryBuilder, $total_items) =
            $this->addFieldsToPaginateBy($tableName, $queryFilters, $queryBuilder);

        $items = $queryBuilder->getQuery()->getResult(); #Query::HYDRATE_ARRAY

        return [
            'total_items' => !is_null($total_items)?$total_items:count($items),
            'items' => $items
        ];
    }

    public function find($entity, Array $searchFields = null, Array $queryFilters = null)
    {
        $result = $this->search($entity, $searchFields, $queryFilters);

        if (count($result['items']) == 0) {
            throw new ItemNotFoundException;
        }

        return $result;
    }


    public function insert($entityUuid, $data): string
    {
        #add it at the beginning in order to have it for the rest of requests
        $data = ['uuid' => $entityUuid] + $data;
        $entity = $this->entity;
        $entityData = new $entity();

        #treat fields before updating / inserting
        $entityData = $this->prepareEntityToPersist($data, $entityData, $entityUuid);

        $this->entityManager->persist($entityData);
        $result = $this->entityManager->flush();
        return $entityUuid;
    }

    public function update($entityUuid, $data): string
    {

        $entityData = $this->getEntityData('repository')->findBy(['uuid' => $entityUuid])[0];

        #treat fields before updating / inserting
        $entityData = $this->prepareEntityToPersist($data, $entityData, $entityUuid);

        $this->entityManager->persist($entityData);
        $result = $this->entityManager->flush();
        return $entityUuid;
    }


    public function delete($entityUuid)
    {

        $object = $this->getEntityData('repository')->findBy(['uuid' => $entityUuid]);
        // tell Doctrine you want to (eventually) save the Product (no queries yet)
        if(count($object) != 1) {
            throw new ItemNotFoundException('DeleteItem: Item not found');
        }
        $this->entityManager->remove($object[0]);

        // actually executes the queries (i.e. the INSERT query)
        $this->entityManager->flush();

        return $entityUuid;
    }



    /**
     * @param $data
     * @param $entityData
     * @param $entityId
     * @return mixed
     * @throws \Exception
     */
    private function prepareEntityToPersist($data, $entityData, $entityId)
    {
        foreach ($data as $field => $value) {

            if (strstr($field, 'many_reference_') !== false) {
                #this field is a reference of another entity with many options
                #this means we have to delete them in order to add them again with the ones provided
                $field = str_replace('many_reference_', '', $field);
                $entityData->{"unset" . StringConversion::underscoreToCamelCase($field)}();
                $this->entityManager->persist($entityData);
                $this->entityManager->flush();
                $entityData = $this->getEntityData('repository')->findBy(['uuid' => $entityId])[0];

                $referencedEntities = explode('%many%', $value);
                foreach ($referencedEntities as $referencedEntity) {
                    $value = $this->treatValuePrePersist($field, $referencedEntity);
                    if ($value === null) continue;
                    $entityData->{"set" . StringConversion::underscoreToCamelCase($field)}($value);
                    $this->entityManager->persist($entityData);
                    $this->entityManager->flush();
                    $entityData = $this->getEntityData('repository')->findBy(['uuid' => $entityId])[0];
                }

            } else {
                #set a "normal" value. It could also be a many2one2many
                $value = $this->treatValuePrePersist($field, $value);
                if ($value === null) continue;
                $entityData->{"set" . StringConversion::underscoreToCamelCase($field)}($value);
            }
        }
        return $entityData;
    }



    private function treatValuePrePersist($field, $value)
    {
        #if matches a yyyy-mm-dd, yyyy-mm-dd hh:ii, or yyyy-mm-dd hh:ii:ss
        if (is_string($value) &&
            preg_match('/^[0-9]{4}-[0-9]{2}-[0-9]{2}( [0-9]{2}:[0-9]{2}(:[0-9]{2})?)?$/', $value)) {
            #add seconds to allow this type of date (yyyy-mm-dd hh:ii)
            if (preg_match('/^[0-9]{4}-[0-9]{2}-[0-9]{2} [0-9]{2}:[0-9]{2}$/', $value)) {
                $value .= ':00';
            }
            $value = new \DateTime($value);
        }

        #if another entity uuid comes, search for it to reference it
        if ($field != 'uuid' && strstr($field, '_uuid')) {
            if(empty($value)) return null;
            $entity_name = str_replace('_uuid', '', $field);
            $original_value = $value;
            $value = $this->getEntityData('repository', EntityFactory::getEntity($entity_name, $this->getNamespaces()))->findBy(['uuid' => $original_value]);
            if (is_null($value)) {
                throw new \Exception("$entity_name with uuid '$original_value' not found");
            } else {
                $value = $value[0];
            }
        }

        return $value;
    }


    public function getNamespaces()
    {
        return array_keys($this->entityManager->getConfiguration()->getMetadataDriverImpl()->getDrivers());
    }

    /**
     * @param $entity
     * @return array
     *
     * Given an Entity, it will return its fields
     */
    public function getEntityData($type, $entity = null)
    {
        if($entity == null) {
            $entity = $this->entity;
        }
        switch($type) {
            case 'FQName':
                return get_class($entity);
                break;
            case 'table':
                return $this->entityManager->getClassMetadata(get_class($entity))->getTableName();
                break;
            case 'fields':
                return $this->entityManager->getClassMetadata(get_class($entity))->getColumnNames();
                break;
            case 'associations':
                return $this->entityManager->getClassMetadata(get_class($entity))->getAssociationMappings();
                break;
            case 'repository':
                return $this->entityManager->getRepository(get_class($entity));
                break;
        }
    }


   /**
     * @param string $entity entity name
     *
     * Sets the entity to use. If a string is received, will create it
     */
    public function setEntity($entity)
    {
        $this->entity = EntityFactory::getEntity($entity, $this->getNamespaces());
    }

    /**
     * @return mixed
     */
    public function getEntity()
    {
        return $this->entity;
    }

    public function getReferencedEntities($entity)
    {
        $references = [];
        $associations = $this->getEntityData('associations');
        if (count($associations) > 0) {
            foreach ($associations as $association) {
                $targetEntity = EntityFactory::getEntity($association['targetEntity'], $this->getNamespaces());
                $references[] = $this->getEntityData('table', $targetEntity);
            }
        }

        return $references;

    }

    /**
     * example of referenced_entities:
     *    post => in a json structure, "post" is the key of the array
     *        comments => we will treat it recursively
     *    author => in a json structure, "0" is the key of the array
     * @param $entityItems
     * @param $referencedEntities
     * @return array
     */
    public function getReferencedEntitiesContents(&$entityItems, $referencedEntities)
    {
        $parsed_items = [];

        #if we want to retrieve referenced entities contents, get them
        foreach ($referencedEntities as $referencedEntity => $referencedSubentities) {
            if (!is_integer($referencedEntity)) {
                #this entity has subentities to parse
                $entityToGather = $referencedEntity;
            } else {
                #this array element has an integer key, it means
                $entityToGather = $referencedSubentities;
            }

            foreach ($entityItems as $ei_key => $entityItem) {
                $subItems = [];
                if (!isset($parsed_items[$ei_key])) {
                    $parsed_items[$ei_key] = self::convertToArray($entityItem);
                }

                #call the method that recovers the subentity for this entity_item (from a staticPage, get its user entity)
                $function = "get" . StringConversion::underscoreToCamelCase($entityToGather);
                $subentity = $entityItem->{$function}();
                if(is_null($subentity)) continue;
                if (strstr(get_class($subentity), 'PersistentCollection')) {
                    #it's a One to Many relation type. Get all the items for this subentity
                    $subentity->initialize();
                    $subItems = $subentity->getSnapshot();

                } else {
                    #Many to One (load User from a StaticPage)
                    if(get_class($entityItem) != get_class($subentity)) {
                        #hhmmm... it doesn't work if we try to load the contents from the same entity
                        $subentity->__load();
                    }
                    $subItems[] = $subentity;
                }

                #we have subitems for this entity and more referenced entities to parse
                if (!is_integer($referencedEntity)) {
                    $subItems = $this->getReferencedEntitiesContents($subItems, $referencedSubentities);
                } else {
                    $subItemsNew = [];
                    #no subentities => convert this subitems to array
                    foreach ($subItems as $si) {
                        $subItemsNew[] = self::convertToArray($si);
                    }
                    $subItems = $subItemsNew;
                }
                $parsed_items[$ei_key]['references'][$entityToGather] = $subItems;

            }
        }

        return $parsed_items;
    }


    /************************/
    /*** Helper Functions **
     * @param $parentEntity
     * @param $searchFields
     * @param $queryBuilder
     * @param $joinedEntities
     * @return array
     */
    /************************/

    private function addFieldsToSearchBy($parentEntity, $searchFields, $queryBuilder, $joinedEntities)
    {
        $searchEntity = '';
        if ($searchFields != null) {
            foreach ($searchFields as $field_name => $props) {
                $currentEntity = null;
                #model to use on the where clause
                $filter_entity = $parentEntity;
                if (strstr($field_name, '.')) {
                    #this field to filter by is from another entity, not the main one
                    $entities_and_field = explode('.', $field_name);
                    $field_name = array_pop($entities_and_field);

                    $joined_entity = $parentEntity;
                    foreach ($entities_and_field as $searchEntity) {
                        if (!is_null($currentEntity)) {
                            #first entity to join by => the other side is parent one
                            $joined_entity = $currentEntity;
                        }
                        list($queryBuilder, $joinedEntities) =
                            $this->addEntityToQueryBuilder($joined_entity, $searchEntity, $queryBuilder, $joinedEntities);
                        $currentEntity = $searchEntity;
                    }
                    $filter_entity = $searchEntity;
                }

                #looking for an exact match of anything else
                if (is_array($props)) {
                    if (empty($props['value']) && !is_numeric($props['value'])) {
                        continue;
                    }
                    $value = $props['value'];
                    if (isset($props['operand'])) {
                        $operand = $props['operand'];
                        switch ($operand) {
                            case 'LIKE':
                                $value = "'%{$value}%'";
                                break;
                            case 'IN':
                                if (is_array($value)) {
                                    $values = "";
                                    $fields = function ($value) use (&$values) {
                                        $is_string = false;
                                        foreach ($value as $item) {
                                            $is_string = (is_string($item));
                                        }
                                        if ($is_string) {
                                            $values = '("' . implode('","', $value) . '")';
                                        } else {
                                            $values = '(' . implode(',', $value) . ')';
                                        }
                                    };
                                    $fields($value);
                                    $value = $values;
                                }
                                break;
                            case 'GT':
                                $operand = '>';
                                break;
                        }
                    } else {
                        $operand = 'LIKE';
                        $value = "'%{$value}%'";
                    }
                } else {
                    if (empty($props) && !is_numeric($props)) {
                        continue;
                    }
                    $value = (is_string($props)) ? "'{$props}'" : $props;
                    $operand = '=';
                }

                $queryBuilder->andWhere("{$filter_entity}.{$field_name} {$operand} {$value}");

            }
        }

        return [$queryBuilder, $joinedEntities];
    }

    /**
     * @param $parent_entity
     * @param $filter_entity
     * @param $query_builder
     * @param $joined_entities
     * @return array
     * @throws \ReflectionException
     */
    private function addEntityToQueryBuilder($parent_entity, $filter_entity, $query_builder, $joined_entities): array
    {
        if (#we don't want to merge ourselves
            $parent_entity != $filter_entity
            #we haven't merged this entity yet
            && !in_array($filter_entity, $joined_entities)
        ) {
            $referenced_entity = EntityFactory::getEntity($filter_entity, $this->getNamespaces());
            $referenced_entity_fqname = $this->getEntityData('FQName', $referenced_entity);
            $referenced_entity_fields = $this->getEntityData('fields', $referenced_entity);
            $joined_entities[] = $referenced_entity_fqname;

            #check in which way we have to make the join "ON"
            $entity_field = strtolower($parent_entity) . "_uuid";
            $remote_model_field = strtolower($filter_entity)."_uuid";
//            if ($referenced_entity->hasProperty($entity_field)) {
            if (!in_array($entity_field, $referenced_entity_fields)) {
                $origin_entity = $parent_entity;
                $origin_field = 'uuid';
                $joined_entity = $filter_entity;
                $joined_field = $entity_field;
            } else {
                $origin_entity = $filter_entity;
                $origin_field = 'uuid';
                $joined_entity = $parent_entity;
                $joined_field = $remote_model_field;
            }

            $query_builder->leftJoin(
                $referenced_entity_fqname,
                $filter_entity,
                \Doctrine\ORM\Query\Expr\Join::WITH,
                "{$origin_entity}.{$origin_field} = {$joined_entity}.{$joined_field}"
            );
        }

        return [$query_builder, $joined_entities];
    }

    private function addFieldsToFilterBy($entity, $query_filters, $queryBuilder, $joinedEntities)
    {
        if ($query_filters != null) {
            if (isset($query_filters['sortby'])) {
                for ($i=0; $i<count($query_filters['sortby']); $i++) {
                    $field_name = $query_filters['sortby'][$i]['field'];
                    $filter_entity = $entity;
                    if (strstr($field_name, '.')) {
                        #this field to filter by is from another entity, not the main one
                        [$filter_entity, $field_name] = explode('.', $field_name);
                        list($queryBuilder, $joinedEntities) =
                            $this->addEntityToQueryBuilder($entity, $filter_entity, $queryBuilder, $joinedEntities);
                    }

                    $queryBuilder->addOrderBy($filter_entity . '.' . $field_name, $query_filters['sortby'][$i]['dir']);
                }
            }
        }
        return [$queryBuilder, $joinedEntities];
    }

    private function addFieldsToPaginateBy($entity, $query_filters, $query_builder)
    {

        $total_items = null;

        if (isset($query_filters['page_items'])) {
            $page_items = $query_filters['page_items'];
        } else {
            $page_items = 999;
        }

        if (!isset($query_filters['page'])) {
            $query_filters['page'] = 1;
        }

        $start = ($query_filters['page'] - 1) * $page_items;

        $query_builder_total = clone $query_builder;
        $query_builder_total->select("count({$entity}.uuid)");
        $total_items = (int) $query_builder_total->getQuery()->getSingleScalarResult();

        $query_builder->setFirstResult($start)->setMaxResults($page_items);

        return [$query_builder, $total_items];
    }

    /**
     * converts doctrine entity to array
     *
     * @param $entity_item
     * @return array
     */
    public static function convertToArray($entity_item)
    {

        $entity_fqn = get_class($entity_item);
        $entity_fqn = str_replace('Proxies\__CG__\\', '', $entity_fqn);
        $array_entity_item = (array) $entity_item;
        foreach ($array_entity_item as $aei_key => $aei_prop) {
            unset($array_entity_item[$aei_key]);
            if (!is_object($aei_prop) && !strstr($aei_key, '__')) {
                #remove null characters when doing the conversion
                $aei_key = str_replace("\0", "", $aei_key);
                $aei_key = str_replace("*", "", $aei_key);
                $aei_key = str_replace($entity_fqn, '', $aei_key);
                #avoid binary id key
                if($aei_key === "id") continue;
                $array_entity_item[$aei_key] = $aei_prop;
            }
            #foreign object
            else if (is_object($aei_prop)) {
                $aei_key = str_replace("\0", "", $aei_key);
                $aei_key = str_replace("*", "", $aei_key);
                $aei_key = str_replace($entity_fqn, '', $aei_key);
                if(strtolower(get_class($aei_prop)) == 'datetime') {
                    $array_entity_item[$aei_key] = $aei_prop->format('Y-m-d H:i:s');
                }
                else if (strstr($aei_key, 'id')) {
                    #many to one
                    $aei_key = str_replace('_id', '_uuid', $aei_key);
                    $array_entity_item[$aei_key] = $aei_prop->getUuid();
                } else if(stristr(get_class($aei_prop), 'PersistentCollection')) {
                    #many to many
//                    $array_entity_item[$aei_key] = $aei_prop->getUuid();
                }
            }
        }
        return $array_entity_item;
    }

}
