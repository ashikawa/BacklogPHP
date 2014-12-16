<?php
namespace Backlog;

use ReflectionClass;
use Zend\Http\Client as HttpClient;
use Zend\Http\Request;
use Zend\Http\Response as HttpResponse;
use Backlog\Exception\ApiErrorException;
use Backlog\Exception\HttpErrorException;

/**
 * Backlog Api V2 REST Client
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
    protected $baseUri = "https://%s.backlog.jp/api/v2/";

    /**
     * @var string
     */
    protected $space = null;

    /**
     * @var string
     */
    protected $token = null;

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
    protected $HttpClient  = null;

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
     * Set up HTTP Methods to use in __call()
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
     * @param  string $url
     * @return Client
     */
    public function setBaseUri($uri)
    {
        $this->baseUri = $uri;

        return $this;
    }

    /**
     * @param  string $space
     * @return Client
     */
    public function setSpace($space)
    {
        $this->space = $space;

        return $this;
    }

    /**
     * @param  string $token
     * @return Client
     */
    public function setToken($token)
    {
        $this->token = $token;

        return $this;
    }

    /**
     * @return HttpClient
     */
    public function getHttpClient()
    {
        if (!$this->HttpClient) {
            $this->HttpClient = new HttpClient();
        }

        return $this->HttpClient;
    }

    /**
     * @param  string $name
     * @return Client
     */
    public function __get($name)
    {
        $this->methodStack[] = $name;

        return $this;
    }

    /**
     * @param  string            $name
     * @param  array             $arguments
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
     * @param  string   $method
     * @param  array    $params
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

        $endpoint = sprintf($this->baseUri, $this->space).$path;

        $this->methodStack = array();

        return $endpoint;
    }

    /**
     * @param  Client $client
     * @param  array  $params
     * @return Client
     */
    protected function setupParameters($client, $params)
    {
        $method = $client->getMethod();

        $authParameter = array(
           'apiKey'  => $this->token,
        );

        if (Request::METHOD_GET === $method
                || Request::METHOD_DELETE === $method) {
            $params = array_merge($params, $authParameter);
            $client->setParameterGet($params);
        }

        $hasBody = array(
            Request::METHOD_POST,
            Request::METHOD_PUT,
            Request::METHOD_PATCH,
        );

        if (in_array($method, $hasBody)) {
            $client->setParameterGet($authParameter)
                ->setParameterPost($params);
        }

        return $client;
    }

    /**
     * @param  Response $response
     * @return boolean
     */
    protected function throwApiException(Response $response)
    {
        if (!isset($response->errors)) {
            throw new DomainException("Error Processing Request");
        }

        // $message = $response->getRawBody();

        $exception = new ApiErrorException('Backlog API Errors: more info `$e->getErrors()`');
        $exception->setErrors($response->errors);

        throw $exception;
    }
}
