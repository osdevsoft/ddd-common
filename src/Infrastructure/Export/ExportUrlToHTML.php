<?php

namespace Osds\DDDCommon\Infrastructure\Export;

class ExportUrlToHTML implements ExportInterface
{

    public function store($content, $destinyPath)
    {
        $staticPageContent = file_get_contents($content);
        $file_name = $destinyPath . '.html';
        return file_put_contents($file_name, $staticPageContent);

    }

}