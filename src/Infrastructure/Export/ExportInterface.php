<?php

namespace Osds\DDDCommon\Infrastructure\Export;

interface ExportInterface
{
    public function store($destinyPath, $content);

}