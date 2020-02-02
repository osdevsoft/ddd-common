<?php

namespace Osds\DDDCommon\Infrastructure\Communication\Input;

class InputRequestHttpFoundation implements InputRequestInterface
{
    
    public function __construct($request)
    {
        $this->handler = $request;
        #TODO: use HttpFoundation instead of this
        $this->parameters = $_REQUEST;
    }

    public function getParameter($param)
    {
//        return $this->handler->get($param);
        #TODO: use HttpFoundation instead of this
        if(isset($this->parameters[$param])) {
            return $this->parameters[$param];
        } else {
            return null;
        }
    }

    public function getParameters()
    {
//        return $this->getAll();
        #TODO: use HttpFoundation instead of this
        return $this->parameters;
    }

}