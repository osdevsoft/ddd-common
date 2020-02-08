<?php

namespace Osds\DDDCommon\Infrastructure\Communication\Input;

interface InputRequestInterface
{
    public function getParameters();

    public function getParameter($param);


}