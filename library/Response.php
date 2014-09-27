<?php
namespace Backlog;

use DomainException;
use Zend\Http\Response as HttpResponse;
use Zend\Json\Exception\ExceptionInterface as JsonException;
use Zend\Json\Json;

/**
 * Wrapper of Zend\Http\Response
 * comvert json to Object
 */
class Response
{
    /**
     * @var HttpResponse
     */
    protected $httpResponse;

    /**
     * @var stdClass
     */
    protected $jsonBody;

    /**
     * @var string
     */
    protected $rawBody;

    /**
     * @var HttpResponse $httpResponse
     */
    public function __construct(HttpResponse $httpResponse)
    {
        $this->httpResponse = $httpResponse;
        $this->rawBody      = $httpResponse->getBody();

        try {
            $jsonBody = Json::decode($this->rawBody, Json::TYPE_OBJECT);
            $this->jsonBody = $jsonBody;
        } catch (JsonException $e) {
            throw new DomainException(sprintf(
                'Unable to decode response : %s',
                $e->getMessage()
            ), 0, $e);
        }
    }

    /**
     * @return stdClass
     */
    public function getBody()
    {
        return $this->jsonBody;
    }

    /**
     * @param string @name
     * @return boolean
     */
    public function __isset($name)
    {
        if (null === $this->jsonBody) {
            return false;
        }

        return isset($this->jsonBody->{$name});
    }

    /**
     * @param string @name
     * @return mixied
     */
    public function __get($name)
    {
        if (null === $this->jsonBody) {
            return null;
        }
        if (!isset($this->jsonBody->{$name})) {
            return null;
        }

        return $this->jsonBody->{$name};
    }

    /**
     * @return string
     */
    public function getRawResponse()
    {
        return $this->rawBody;
    }
}
