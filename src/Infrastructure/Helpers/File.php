<?php

namespace Osds\DDDCommon\Infrastructure\Helpers;

use Symfony\Component\Yaml\Yaml;

class File
{

    public static function parseYaml($yamlContent, $isFile = false)
    {
        if($isFile) {
            return Yaml::parseFile($yamlContent);
        }
        return Yaml::parse($yamlContent);
    }

}