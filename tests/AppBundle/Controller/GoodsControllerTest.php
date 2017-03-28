<?php
namespace tests\AppBundle\Controller;

use AppBundle\Controller\GoodsController;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;


class GoodsControllerTest extends WebTestCase
{
    
    /**
     * This test tests the content type of the data sent back by the server:
     * it must be application/json
     */
    public function testContentType()
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/goods');
        
        $this->assertTrue(
        $client->getResponse()->headers->contains(
            'Content-Type',
            'application/json'
        ),
        'non è "application/json"');
    }

    /**
     * This test assert that a request 'GET' on the root /goods 
     * actually sent a proper response with a 200 status code
     */
    public function testGetGoods()
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/goods');
        echo $client->getResponse() -> getContent();
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        
    }
    
    /**
     * This test assert that a request 'GET' on a single good specyifing 
     * the id actually sends back a response with a 200 status code
     */
    public function testGetGood()
    {   
        $client = static::createClient();
        $crawler = $client -> request('GET', '/goods/1');
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
    }
    
    /**
     * This test tests that an insert is answered with a 200 status code
     */
     public function testInsertGood()
    {
        $client = static::createClient();
        $crawler = $client -> request('POST','/goods',array(), array(), array("CONTENT_TYPE" => "application/json"),
	'{"description":"prova", "quantity": 40, "price": 2.6}');
        $response = $client -> getResponse();
        echo $response -> getContent();
        $this->assertEquals(200, $response ->getStatusCode());
    }
    
    /**
     * This test test the deletion of a good
     */
    public function testDeleteGood() 
    {
        //This test is valid only once
        $client = static::createClient();
        $crawler = $client -> request('DELETE', '/goods/2');
        echo $client ->getResponse() ->getContent();
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
    } 
    
   
    /**
     * This test tests that the validation actually works, sending a wrong json
     * object
     */
    public function testBadInsertGood()
    {
        $client = static::createClient();
        $crawler = $client -> request('POST','/goods',array(), array(), array("CONTENT_TYPE" => "application/json"),
	'{"description":"this description is much longer than 25 characters,'
                . ' yes i know, really really bad", "quantity": 45.5, "price": 2.6}');
        $response = $client -> getResponse();
        echo $response -> getContent();
        $this->assertEquals(400, $response ->getStatusCode());
    }
    
    /**
     * This test test the "PATCH" request
     */
    public function testPatchGood() 
    {
        $client = static::createClient();
        $crawler = $client -> request('PATCH','/goods/1',
                array(), array(), array("CONTENT_TYPE" => "application/json"),
	'{"description":"modificato2", "quantity": 45, "price": 2.6}');
        $response = $client -> getResponse();
        echo $response -> getContent();
        $this->assertEquals(200, $response ->getStatusCode());
    }
    
    /**
     * This test test the validation of the object also for the "PATCH"
     * request
     */
    public function testBadPatchGood() 
    {
        $client = static::createClient();
        $crawler = $client -> request('PATCH','/goods/1',
                array(), array(), array("CONTENT_TYPE" => "application/json"),
	'{"description":"modificato2", "quantity": 45.8, "price": 2.6}');
        $response = $client -> getResponse();
        echo $response -> getContent();
        $this->assertEquals(400, $response ->getStatusCode());
    }
    
    
    
}
