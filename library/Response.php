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
     * @var HttpResponse $httpResponse
     */
    public function __construct(HttpResponse $httpResponse)
    {
        $this->httpResponse = $httpResponse;

        $headers   = $httpResponse->getHeaders();

        $dispotion   = $headers->get('Content-Disposition');
        $contentType = $headers->get('Content-Type');

        if ($dispotion && strpos($dispotion->getFieldValue(), 'attachment') === 0) {
            return;
        }

        if ($contentType->match('application/json')) {
            try {
                $jsonBody = Json::decode($httpResponse->getBody(), Json::TYPE_OBJECT);
                $this->jsonBody = $jsonBody;

                return;
            } catch (JsonException $e) {
                throw new DomainException(sprintf(
                    'Unable to decode response : %s',
                    $e->getMessage()
                ), 0, $e);
            }
        }

        throw new DomainException('Unsupported Response');
    }

    public function getHttpResponse()
    {
        return $this->httpResponse;
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
    public function getRawBody()
    {
        return $this->httpResponse->getBody();
    }

    /**
     * @return int
     */
    public function save($name)
    {
        return file_put_contents($name, $this->getRawBody());
    }
}
