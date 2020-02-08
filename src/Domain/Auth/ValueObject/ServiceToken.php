<?php

namespace Osds\DDDCommon\Domain\Auth\ValueObject;

class ServiceToken
{
    private $token = null;
    
    public function __construct(
        $token
    )
    {
        $this->token = $token;
    }
    
    public function get()
    {
        return $this->token;
    }
    
    public function __toString()
    {
        return $this->get();
    }

}