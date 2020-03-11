<?php

namespace Osds\DDDCommon\Infrastructure\Export;

class ExportUrlToPDF implements ExportInterface
{

    public function store($destinyPath, $content)
    {
        $staticPageContent = file_get_contents($content);
        $file_name = $destinyPath . '.html';
        return file_put_contents($file_name, $staticPageContent);

    }

}