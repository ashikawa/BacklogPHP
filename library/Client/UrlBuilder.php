<?php
namespace Backlog\Client;

class UrlBuilder
{
    /**
     * @var array
     */
    protected $methodStack = array();

    /**
     * @var string
     */
    protected $baseUri = null;

    /**
     * @param string $url
     *
     * @return UrlBuilder
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
     * @param string $name
     *
     * @return UrlBuilder
     */
    public function __get($name)
    {
        $this->methodStack[] = $name;

        return $this;
    }

    /**
     * @param string $name
     * @param mixed  $arguments
     *
     * @return UrlBuilder
     */
    public function __call($name, $arguments)
    {
        $this->methodStack = array_merge(
            $this->methodStack,
            array($name),
            $arguments
        );

        return $this;
    }

    /**
     * @return string
     */
    public function build()
    {
        $methodStack = $this->methodStack;
        $path        = implode('/', $methodStack);

        $endpoint = trim($this->getBaseUrl(), '/').'/api/v2/'.$path;

        $this->methodStack = array();

        return $endpoint;
    }
}
