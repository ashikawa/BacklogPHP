<?php
namespace BacklogTest;

trait mockResponse
{
    protected $adapter = null;

    protected function setupAdapter()
    {
        $adapter = new \Zend\Http\Client\Adapter\Test();
        $this->adapter = $adapter;

        return $adapter;
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
}
