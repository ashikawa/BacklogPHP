<?php
namespace Backlog;

use ReflectionClass;
use Zend\Http\Client as HttpClient;
use Zend\Http\Request;
use Zend\Http\Response as HttpResponse;
use Backlog\Exception\ApiErrorException;
use Backlog\Exception\HttpErrorException;

/**
 * Backlog Api V2 REST Client.
 */
class Client
{
    /**
     * @var array
     */
    protected $config  = null;

    /**
     * @var string
     */
    protected $baseUri = null;

    /**
     * @var string
     */
    protected $apiKey = null;

    /**
     * @var OAuth2\AccessToken
     */
    protected $accessToken = null;

    /**
     * @var array
     */
    protected $methodStack = array();

    /**
     * @var array
     */
    protected $httpMethods = array();

    /**
     * @var HttpClient
     */
    protected $httpClient  = null;

    /**
     * @param array $config
     */
    public function __construct($config = null)
    {
        if (!is_null($config)) {
            $this->config = $config;
        }

        $this->setupCallMethod();
    }

    /**
     * Set up HTTP Methods to use in __call().
     */
    protected function setupCallMethod()
    {
        $ref = new ReflectionClass('\Zend\Http\Request');
        $constants = $ref->getConstants();

        $prefix = 'METHOD_';

        foreach ($constants as $name => $value) {
            if (substr($name, 0, strlen($prefix)) == $prefix) {
                $this->httpMethods[] = $value;
            }
        }
    }

    /**
     * @param string $url
     *
     * @return Client
     */
    public function setBaseUri($uri)
    {
        $this->baseUri = $uri;

        return $this;
    }

    /**
     * @return string
     */
    public function getBaseUrl()
    {
        return $this->baseUri;
    }

    /**
     * @param string $apiKey
     *
     * @return Client
     */
    public function setApiKey($apiKey)
    {
        $this->apiKey = $apiKey;

        return $this;
    }

    /**
     * @param string $apiKey
     *
     * @return Client
     */
    public function setAccessToken($accessToken)
    {
        $this->accessToken = $accessToken;

        return $this;
    }

    /**
     * @return HttpClient
     */
    public function getHttpClient()
    {
        if (!$this->httpClient) {
            $this->httpClient = new HttpClient();
        }

        return $this->httpClient;
    }

    /**
     * @param string $name
     *
     * @return Client
     */
    public function __get($name)
    {
        $this->methodStack[] = $name;

        return $this;
    }

    /**
     * @param string $name
     * @param array  $arguments
     *
     * @return HttpClient|Client
     */
    public function __call($name, $arguments)
    {
        if (in_array(strtoupper($name), $this->httpMethods)) {
            array_unshift($arguments, strtoupper($name));

            return call_user_func_array(array($this, 'request'), $arguments);
        }

        $this->methodStack = array_merge(
            $this->methodStack,
            array($name),
            $arguments
        );

        return $this;
    }

    /**
     * @param string $method
     * @param array  $params
     *
     * @return Response
     */
    public function request($method, $params = array())
    {
        $httpClient = $this->getHttpClient();

        $endpoint = $this->buildEndPointUri();

        $httpClient->setUri($endpoint)
            ->setMethod($method);

        $config = $this->config;

        if (!is_null($config)) {
            $httpClient->setOptions($config);
        }

        $this->setupParameters($httpClient, $params);

        $httpResponse = $httpClient->send();

        if ($httpResponse->isSuccess()) {
            $response = new Response($httpResponse);

            return $response;
        }

        try {
            $response = new Response($httpResponse);
        } catch (\Exception $e) {
            $message = $httpResponse->getReasonPhrase();
            $code    = $httpResponse->getStatusCode();

            throw new HttpErrorException($message, $code);
        }

        $this->throwApiException($response);
    }

    /**
     * @return string
     */
    protected function buildEndPointUri()
    {
        $methodStack = $this->methodStack;
        $path        = implode('/', $methodStack);

        $endpoint = trim($this->getBaseUrl(), '/').'/api/v2/'.$path;

        $this->methodStack = array();

        return $endpoint;
    }

    /**
     * @param HttpClient $client
     * @param array      $params
     *
     * @return HttpClient
     */
    protected function setupParameters($client, $params)
    {
        $method = $client->getMethod();

        $apiKey = array();
        if ($this->apiKey) {
            $apiKey = array(
                'apiKey' => $this->apiKey,
            );
        }

        $hasBody = array(
            Request::METHOD_POST,
            Request::METHOD_PUT,
            Request::METHOD_PATCH,
        );

        if (in_array($method, $hasBody)) {
            $client->setParameterPost($params);
            $client->setParameterGet($apiKey);
        } else {
            $params = array_merge($params, $apiKey);
            $client->setParameterGet($params);
        }

        if ($this->accessToken) {
            $client->setHeaders(array(
                'Authorization' => 'Bearer '.$this->accessToken,
            ));
        }

        return $client;
    }

    /**
     * @param Response $response
     *
     * @return boolean
     */
    protected function throwApiException(Response $response)
    {
        if (!isset($response->errors)) {
            throw new \DomainException("Error Processing Request");
        }

        $exception = new ApiErrorException('Backlog API Errors: more info `$e->getErrors()`');
        $exception->setErrors($response->errors);

        throw $exception;
    }
}
