<?php
namespace BacklogTest;

use Backlog\Client as Client;

trait mockRequest
{
    protected $adapter = null;

    protected $client = null;

    protected function setupClient()
    {
        $adapter = new \Zend\Http\Client\Adapter\Test();

        $this->adapter = $adapter;

        $client =  new Client(array(
            'adapter' => $adapter,
        ));
        $client->setBaseUri('http://dummyspace.example.com/');

        $this->client = $client;
    }

    protected function buildMockResponse($file = '200ok')
    {
        $responseString = file_get_contents(__DIR__.'/httpresponse/'.$file.'.txt');

        return \Zend\Http\Response::fromString($responseString);
    }

    protected function setMockResponse($file = '200ok')
    {
        $adapter  = $this->adapter;
        $response = $this->buildMockResponse($file);

        $adapter->setResponse($response);
    }

    protected function getHttpClient()
    {
        return $this->client->getHttpClient();
    }

    protected function getHttpRequest()
    {
        return $this->getHttpClient()->getRequest();
    }

    protected function assertRequestMethod($expected)
    {
        $request = $this->getHttpRequest();
        $this->assertEquals($expected, $request->getMethod());
    }

    protected function assertRequestPath($expected)
    {
        $path = $this->getHttpClient()->getUri()->getPath();
        $this->assertEquals($expected, $path);
    }
}
