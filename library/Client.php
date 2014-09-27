<?php
namespace Backlog;

use DomainException;
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
        $this->config = $config;

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
        $endpoint = $this->buildEndPointUri();
        $config   = $this->config;

        $httpClient   = new HttpClient($endpoint, $config);
        $httpClient->setMethod($method);

        $this->HttpClient = $httpClient;

        $this->setupParameters($httpClient, $params);

        $httpResponse = $httpClient->send();

        $this->httpErrorCheck($httpResponse);

        $jsonResponse = new Response($httpResponse);

        $this->apiErrorCheck($jsonResponse);

        return $jsonResponse;
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

        if ($method === Request::METHOD_GET
                || $method === Request::METHOD_DELETE) {
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
     * @param  HttpResponse $response
     * @return boolean
     */
    protected function httpErrorCheck(HttpResponse $response)
    {
        $contentType = $response->getHeaders()->get('Content-Type');

        if (!($contentType instanceof \Zend\Http\Header\ContentType)) {
            throw new DomainException('Not found response header `Content-Type`');
        }

        if (!$contentType->match('application/json')) {
            if ($response->isSuccess()) {
                throw new DomainException('Error Processing Request');
            }

            $message = $response->getReasonPhrase();
            $code    = $response->getStatusCode();

            throw new HttpErrorException($message, $code);
        }

        return true;
    }

    /**
     * @param  Response $response
     * @return boolean
     */
    protected function apiErrorCheck(Response $response)
    {
        if (!isset($response->errors)) {
            return true;
        }

        $message = $response->getRawResponse();

        $exception = new ApiErrorException('Backlog API Errors: more info `$e->getErrors()`');
        $exception->setErrors($response->errors);

        throw $exception;
    }
}
