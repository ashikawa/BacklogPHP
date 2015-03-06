<?php
namespace BacklogTest;

use PHPUnit_Framework_TestCase as TestCase;
use Backlog\Response as Response;

class ResponseTest extends TestCase
{
    use mockRequest;

    public function setUp()
    {
    }

    public function tearDown()
    {
    }

    public function testResponseMethods()
    {
        $httpresponse = $this->buildMockResponse();
        $response = new Response($httpresponse);

        $this->assertTrue(isset($response->content));
        $this->assertEquals('dummy', $response->content);
        $this->assertEquals('dummy', $response->getBody()->content);
        $this->assertInternalType('string', $response->getRawBody());
    }

    /**
     * @expectedException DomainException
     */
    public function testErrorJsonParseError()
    {
        $httpresponse = $this->buildMockResponse('200ok_wrongformat');

        new Response($httpresponse);
    }
}
