<?php

namespace Osds\DDDCommon\Infrastructure\Persistence;

use League\Csv\Reader;

use Doctrine\Common\Annotations\Annotation;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\PersistentCollection;
use Doctrine\ORM\Query;


use League\Csv\Statement;
use League\Csv\Writer;
use Osds\Api\Domain\Exception\ItemNotFoundException;
use Osds\DDDCommon\Infrastructure\Helpers\EntityFactory;
use Osds\DDDCommon\Infrastructure\Helpers\Server;
use Osds\DDDCommon\Infrastructure\Helpers\StringConversion;

abstract class CsvRepository
{

    public function __construct()
    {
        $this->data_path = $_SERVER['DOCUMENT_ROOT'] . '/../data/persistence/csv/';
        if(!is_dir($this->data_path)) {
            mkdir($this->data_path, 0777, true);
        }
    }


    /**
     * @param string     $entity            name of the entity we want to retrieve items
     * @param array|null $searchFields     fields we are going to filter by
     * @param array|null $queryFilters     sorting, pagination
     * @return array
     */
    public function search($entity, Array $searchFields = null, Array $queryFilters = null)
    {
        $source_file = $this->checkSourceFile($entity)['source_file'];
        $reader = Reader::createFromPath($source_file, 'r');
        $reader->setHeaderOffset(0);
        $reader->setDelimiter(';');

        $items = Statement::create()->process($reader)->getRecords();

        if($searchFields != null) {
            $itemsParsed = [];
            foreach($items as $item) {

                foreach($searchFields as $field => $data) {
                    if(is_array($data)) {
                        $value = $data['value'];
                        $operand = isset($data['operand'])?$data['operand']:'';
                        switch($operand) {
                            case 'NOT_IN':
                                if($item[$field] != $value) {
                                    $itemsParsed[] = $item;
                                }
                                break;
                            default:
                                if(stristr($item[$field], $value)) {
                                    $itemsParsed[] = $item;
                                }
                        }

                    } else {
                        if($item[$field] == $data) {
                            $itemsParsed[] = $item;
                        }
                    }
                }
            }
            $items = $itemsParsed;
        } else {
            $items = iterator_to_array($items);
        }

        return [
            'total_items' => count($items),
            'items' => $items
        ];

    }

    public function find($entity, Array $searchFields = null, Array $queryFilters = null)
    {
        return $this->search($entity, $searchFields);
    }


    public function insert($entityUuid, $data): string
    {
        $data = array_values($data);
        array_unshift($data, $entityUuid);

        $source_file = $this->checkSourceFile()['source_file'];
        $writer = Writer::createFromPath($source_file, 'a+');
        $newline = $writer->getNewline();
        $writer->setNewline($newline);
        $writer->setDelimiter(';');

        return $writer->insertOne($data);
    }

    public function update($entityUuid, $data): string
    {
        #delete by uuid
        $this->delete($entityUuid);
        #insert again
        return $this->insert($entityUuid, $data);
    }


    public function delete($entityUuid)
    {
        $source_file_data = $this->checkSourceFile($this->getEntity());
        $source_file_path = $source_file_data['source_file'];
        #get all items but the one to delete
        $filter = [
            'uuid' => ['value' => $entityUuid, 'operand' => 'NOT_IN']
        ];
        $list_without_item = $this->search($this->getEntity(), $filter);

        #make a backup of the file
        $backup_file = $source_file_path . '_' . date('YmdHis') . '_backup';
        $backup_correct = copy($source_file_path, $backup_file);
        if(!$backup_correct) {
            throw new \Exception('not_deleted');
        } else {
            #generate the file from the headers and items without the one to delete
            $writer = Writer::createFromPath($source_file_path, 'w+');
            $newline = $writer->getNewline();
            $writer->setNewline($newline);
            $writer->setDelimiter(';');

            $writer->insertOne($source_file_data['headers']);
            $writer->insertAll($list_without_item['items']);

            #theorically okay, perform some checks
            #check that new file num elements is equal to the previous $list_without_item list
            $current_items = $this->search($this->getEntity());

            if(count($list_without_item['items']) == $current_items['total_items']) {
                #all ok
                #remove backup...?
            } else {
                #\|00|/#
                #restore previous file
                copy($backup_file, $source_file_path);
                throw new \Exception('not_deleted');
            }

        }

    }



    public function setEntity($entity)
    {
        $this->entity = $entity;
    }

    public function getEntity()
    {
        return $this->entity;
    }

    public function getEntityData()
    {
        return $this->checkSourceFile($this->entity)['headers'];
    }

    private function checkSourceFile($entity = null)
    {
        if($entity == null) {
            $entity = $this->getEntity();
        }
        $entity = strtolower($entity);
        $this->entity = $entity;

        $source_file = $this->data_path . $entity . ".csv";
        if(!file_exists($source_file)) {
            touch($source_file);
        }

        $reader = Reader::createFromPath($source_file, 'r');
        $reader->setHeaderOffset(0);
        $records = Statement::create()->process($reader);
        $headers = $records->getHeader();
        if(empty($headers)) {
            #get entity attributes
            #persist them in the csv
        }

        return [
            'source_file' => $source_file,
            'headers' => explode(";", $headers[0])
        ];

    }


}
