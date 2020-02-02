<?php

namespace Osds\DDDCommon\Infrastructure\Communication\Input;

class InputRequestHttpFoundation implements InputRequestInterface
{
    
    public function __construct()
    {
        //add httpfoundation handler to construct
        #TODO: use HttpFoundation instead of this
        $this->parameters = $_REQUEST;
    }

    public function getParameter($param)
    {
        #TODO: use HttpFoundation instead of this
        if(isset($this->parameters[$param])) {
            return $this->parameters[$param];
        } else {
            return null;
        }
    }

    public function getParameters()
    {
        #TODO: use HttpFoundation instead of this
        return $this->parameters;
    }

}