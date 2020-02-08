<?php

namespace Osds\DDDCommon\Infrastructure\View;

interface ViewInterface
{

    public function setVariables($variable);

    public function setVariable($key, $value);

    public function setTemplate($template);

    public function getTemplate();
    
    public function render();

}