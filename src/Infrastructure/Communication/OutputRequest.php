<?php

namespace Osds\DDDCommon\Infrastructure\Communication;

use GuzzleHttp\Client as HttpClient;
use Osds\Auth\Infrastructure\UI\ServiceAuth;
use Osds\DDDCommon\Infrastructure\Helpers\Server;

/**
 * Class used to make HTTP requests
 *
 * Class Request
 * @package Osds\DDDCommon
 */

class OutputRequest
{
    protected $serviceAuth;
    protected $apiUrl;
    
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

    protected $userAuth = false;

    public function __construct(
        ServiceAuth $serviceAuth,
        $apiUrl
    )
    {
        $this->serviceAuth = $serviceAuth;
        $this->apiUrl = $apiUrl;
    }

    /**
     *
     * @param string $requestUrl The API url
     * @param string $method The HTTP method
     * @param array $data The parameters
     * @param array $headers The HTTP headers
     */
    public function setQuery($requestUrl = null, $method = null, $data = null, array $headers = array(), $userAuth = false)
    {
        $this->setRequestUrl($requestUrl);
        $this->setMethod($method);
        $this->setData($data);
        $this->setHeaders($headers);
        $this->setUserAuth($userAuth);
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
    
    public function setUserAuth($userAuth)
    {
        $this->userAuth = $userAuth;
    }
    
    public function addAuthToken($token)
    {
        $this->setHeaders(
            array_merge($this->getHeaders(), ['Authorization' => "Bearer $token"])
        );
    }


    public function sendRequest($service)
    {
        $originSite = Server::getDomainInfo()['snakedId'];
        $bearer = $this->serviceAuth->getServiceAuthToken($service, $originSite);
        if(!strstr($this->requestUrl, 'originSite')) {
            $this->data['get']['originSite'] = $originSite;
        }
        $this->addAuthToken($bearer);

        $serviceUrl = '';
        if(strpos($this->apiUrl, 'http') === false)
        {
            $serviceUrl = $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'];
        }
        $serviceUrl .= $this->apiUrl;

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
                $this->requestUrl .= '/?' . http_build_query($this->data['get']);
            }

            $response = $client->request(
                $this->method,
                $this->requestUrl,
                [
                    'form_params' => $post_data,
                    'allow_redirects' => false
                ]
            );
            unset($this->data['get']);
        } catch (\Throwable $throwable) {
            throw new \Exception($throwable);
        }
        try {
            $data = $response->getBody();
            $data = json_decode($data);

            if (is_null($data)) {
                $data = $response->getBody();
            }
        } catch (\Exception $e) {
            $data = $response->getBody();
        }

        $data = json_decode(json_encode($data), true);

        $this->treatResponse($data, $service);

        return $data;

    }

    private function treatResponse($data, $service)
    {
        if(isset($data['error_code'])) {
            switch($data['error_code']) {
                case 401:
                    $this->serviceAuth->removeServiceAuthToken($service);
                    $this->sendRequest($service);
            }
        }

        if(isset($data['renewedServiceToken'])) {
            $this->serviceAuth->storeServiceAuthToken($service, $data['renewedServiceToken']);
        }
    }

}