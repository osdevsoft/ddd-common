<?php

namespace Osds\Api\Infrastructure\Helpers;

use Osds\Api\Domain\Exception\EntityNotFoundException;

class EntityFactory
{

    public static function getEntity($entity, $namespaces)
    {

        #already an object
        if(is_object($entity)) {
            return $entity;
        }

        #Full Qualified Name (with namespace) provided
        if(strstr($entity, '\\')) {
            return new $entity;
        }

        #just a string with no FQN (probably an entity)
        #search for it in all the namespaces
        if (count($namespaces) > 0) {
           foreach ($namespaces as $namespace) {
               $namespace = preg_replace('/\\\?$/','\\', $namespace);
               $entityFQName = $namespace . StringConversion::underscoreToCamelCase($entity);
               if(class_exists($entityFQName)) {
                   return new $entityFQName;
               }
           }
        }

        #no entity found
        throw new EntityNotFoundException('Entity ' . json_encode($entity) . ' not found');

    }

}