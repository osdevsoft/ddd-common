<?php

namespace Osds\DDDCommon\Infrastructure\Helpers;

class Server
{

    public static function folderSize($path)
    {
        $total_size = 0;
        $files = scandir($path);
        $cleanPath = rtrim($path, '/') . '/';

        foreach ($files as $t) {
            if ($t <> "." && $t <> "..") {
                $currentFile = $cleanPath . $t;
                if (is_dir($currentFile)) {
                    $size = foldersize($currentFile);
                    $total_size += $size;
                } else {
                    $size = filesize($currentFile);
                    $total_size += $size;
                }
            }
        }
        return $total_size;
    }

    public static function getDomainInfo()
    {
        $requestOrigin = str_replace($_SERVER['REQUEST_SCHEME'] . '://', '', $_SERVER['HTTP_HOST']);

        $domainData = [
            'protocol' => $_SERVER['REQUEST_SCHEME'],
            'requestOrigin' => $requestOrigin,
            'mainDomain' => '',
            'snakedId' => ''
        ];
        $domainData['mainDomain'] = preg_replace('/^backoffice./','', $requestOrigin);
        $domainData['snakedId'] = str_replace('www.','', $domainData['mainDomain']);
        $domainData['snakedId'] = preg_replace('/.sandbox$/','', $domainData['mainDomain']);
        $domainData['snakedId'] = preg_replace('/[^a-zA-Z0-9-]/', '_', $domainData['snakedId']);
        $domainData['camelCaseId'] = self::underscoreToCamelCase($domainData['snakedId']);

        return $domainData;
    }

    public static function underscoreToCamelCase($input)
    {
        return str_replace(' ', '', ucwords(str_replace('_', ' ', $input)));
    }

}