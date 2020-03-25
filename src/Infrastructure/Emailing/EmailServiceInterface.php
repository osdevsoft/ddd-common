<?php

namespace Osds\DDDCommon\Infrastructure\Emailing;

interface EmailServiceInterface
{

    public function send($from, $to, $subject, $body, $headers = []);

}