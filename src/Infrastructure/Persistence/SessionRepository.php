<?php

namespace Osds\DDDCommon\Infrastructure\Persistence;

class SessionRepository
{

    public function __construct()
    {
        @session_start();
    }

    public function insert($key, $value)
    {
        $_SESSION[$key] = $value;
    }

    public function find($key)
    {
        if(!isset($_SESSION[$key])) {
            return null;
        }
        return $_SESSION[$key];
    }

    public function delete($key)
    {
        unset($_SESSION[$key]);
    }

}
