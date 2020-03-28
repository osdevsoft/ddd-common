<?php

namespace Osds\DDDCommon\Infrastructure\View;

interface ViewInterface
{

    public function setVariables($variable);

    public function setVariable($key, $value);

    public function getVariables();

    public function getVariable($key);

    public function setTemplate($template);

    public function getTemplate();
    
    public function render($return);

}