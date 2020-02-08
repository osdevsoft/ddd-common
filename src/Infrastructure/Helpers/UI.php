<?php

namespace Osds\DDDCommon\Infrastructure\Helpers;

class UI
{

    public static function getAlertMessages($request_data)
    {
        $message = null;

        if (isset($request_data['get']['action_message'])) {
            $message = ['message' => $request_data['get']['action_message'] ];
            if (isset($request_data['get']['action_result'])) {
                $message['type'] = $request_data['get']['action_result'];
            } else {
                $message['type'] = 'info';
            }
        }

        return $message;
    }

    public static function redirect($url, $result = null, $message = null, $error = null)
    {
        $locale = [];

        if ($message != null) {
            if (isset($locale[strtoupper($message)])) {
                $message = $locale[strtoupper($message)];
            }
            $url .= '?action_message=' . $message;

            if ($error != null) {
                $url .= '<br>';
                if (is_string($error)) {
                    $url .= $error;
                } else {
                    $url .= $error->getMessage() . ' @ ' . basename($error->getFile()) . '::' . $error->getLine();
                }
            }

            if ($result != null) {
                $url .= '&action_result=' . $result;
            }
        }
        header('Location: ' . $url);
        exit;
    }
}