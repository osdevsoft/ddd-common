<?php

namespace Osds\DDDCommon\Infrastructure\Communication;

use GuzzleHttp\Client as HttpClient;

/**
 * Class used to make HTTP requests
 *
 * Class Request
 * @package Osds\DDDCommon
 */

class OutputRequest
{
    protected $serviceUrl;
    
    /**
     * @var string
     */
    protected $requestUrl;

    /**
     * @var string
     */
    protected $method;

    /**
     * @var array
     */
    protected $data = array();

    /**
     * @var array
     */
    protected $headers = array();

    public function __construct($serviceUrl)
    {
        $this->serviceUrl = $serviceUrl;
    }

    /**
     *
     * @param string $requestUrl The API url
     * @param string $method The HTTP method
     * @param array $data The parameters
     * @param array $headers The HTTP headers
     */
    public function setQuery($requestUrl = null, $method = null, $data = null, array $headers = array())
    {
        $this->setRequestUrl($requestUrl);
        $this->setMethod($method);
        $this->setData($data);
        $this->setHeaders($headers);
    }

    /**
     * @return string
     */
    public function getRequestUrl()
    {
        return $this->requestUrl;
    }

    /**
     * @param string $requestUrl
     */
    public function setRequestUrl($requestUrl)
    {
        $this->requestUrl = $requestUrl;
    }

    /**
     * @return string
     */
    public function getMethod()
    {
        return $this->method;
    }

    /**
     * @param string $method
     */
    public function setMethod($method)
    {
        $this->method = $method;
    }

    /**
     * @return array
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @param array $data
     */
    public function setData($data)
    {
        $this->data = $data;
    }

    /**
     * @return array
     */
    public function getHeaders()
    {
        return $this->headers;
    }

    /**
     * @param array $headers
     */
    public function setHeaders($headers)
    {
        $this->headers = $headers;
    }

    public function appendHeaders($headers)
    {
        $this->setHeaders(array_merge($this->getHeaders(), $headers));
    }
    
    public function addAuthToken($token)
    {
        $this->setHeaders(
            array_merge($this->getHeaders(), ['Authorization' => "Bearer $token"])
        );
    }


    public function sendRequest()
    {
        $this->data['get']['originSite'] = 'NexinEs';
        $serviceUrl = '';
        if(strpos($this->serviceUrl, 'http') === false)
        {
            $serviceUrl = $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'];
        }
        $serviceUrl .= $this->serviceUrl;

        $this->appendHeaders(['Accept' => 'application/json']);

        $client = new HttpClient([
            'base_uri' => $serviceUrl,
            'headers' => $this->getHeaders()
        ]);

        try {
                if(isset($this->data['post']))
                {
                    $post_data = $this->data['post'];
                } else
                {
                    $post_data = [];
                }

            if(isset($this->data['uri']) && count($this->data['uri']) > 0) {
                $this->requestUrl .= '/' . implode('/', $this->data['uri']);
            }
            if(isset($this->data['get']) && count($this->data['get']) > 0) {
                $this->requestUrl .= '?' . http_build_query($this->data['get']);
            }

            $response = $client->request(
                $this->method,
                $this->requestUrl,
//                $post_data
                ['form_params' => $post_data]
            );
            unset($this->data);
        } catch (\Throwable $throwable) {
            throw new \Exception($throwable);
        }
        try {
            $data = json_decode($response->getBody());

            if (is_null($data)) {
                $data = $response->getBody();
            }
        } catch (\Exception $e) {
            $data = $response->getBody();
        }

        return json_decode(json_encode($data), true);

    }

}