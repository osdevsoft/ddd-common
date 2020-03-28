<?php

namespace Osds\DDDCommon\Infrastructure\Emailing;

use Osds\DDDCommon\Infrastructure\View\ViewInterface;

class MailFunction extends AbstractMailer
{

    public function send($from, $to, $subject, $body = '', $headers = [])
    {
        $headers['From'] = $from['name'] . "<{$from['email']}>";
        $sHeaders = '';
        foreach($headers as $headerk => $headerv) {
            $sHeaders .= "{$headerk}: {$headerv} \r\n";
        }
        $sHeaders .= "MIME-Version: 1.0\r\n";
        $sHeaders .= "Content-Type: text/html; charset=ISO-8859-1\r\n";

        if(empty($body)) {
            $body = $this->view->render(true);
        }

        return mail($to, $subject, $body, $sHeaders);
    }

}