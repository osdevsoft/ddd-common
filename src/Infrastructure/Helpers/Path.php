<?php

namespace Osds\DDDCommon\Infrastructure\Helpers;

abstract class Path
{

    #to define in implementation
    protected static $paths = [];

    public static function getPath($type, $id = null, $full = false, $create = false)
    {
        $paths = get_called_class()::$paths;

        if($full == true) {
            $path = $_SERVER['DOCUMENT_ROOT'] . '/../';
        } else {
            $path = '';
        }

        $path .= $paths[$type];

        if($id != null) {
            $path = sprintf($path, $id);
        }

        if($create) {
            if($full == false) {
                $path = $_SERVER['DOCUMENT_ROOT'] . $path;
            }
            @mkdir($path, 0777, true);
        }

        return $path;
    }

    public static function getFullUri($baseUri, $type, $id)
    {
        $paths = get_called_class()::$paths;
        $uri = $baseUri . sprintf($paths[$type], $id);
        return $uri;
    }
    
}