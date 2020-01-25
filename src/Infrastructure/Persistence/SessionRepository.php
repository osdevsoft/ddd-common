<?php

namespace Osds\Api\Infrastructure\Persistence;

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
        return $_SESSION[$key];
    }

    public function delete($key)
    {
        unset($_SESSION[$key]);
    }

}
