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
        //conto il numero di oggetti good
        $client->request('GET','/goods?count=true');
        $count = $client->getResponse()->getContent();
        //eseguo la post
        $client -> request('POST','/goods',array(), array(), array("CONTENT_TYPE" => "application/json"),
	'{"description":"prova3", "quantity": 40, "price": 2.6}');
        $response = $client -> getResponse();
        
        $this->assertEquals(200, $response ->getStatusCode());
        
        //mi assicuro che il numero di goods sia incrementato di uno
        $client->request('GET','/goods?count=true');
        $count2 = $client->getResponse()->getContent();
        
        $this->assertFalse($count==$count2, "Inserimento andato a buon fine");
    }
    
    /**
     * This test test the deletion of a good
     */
    public function testDeleteGood() 
    {
        //This test is valid only once
        $client = static::createClient();
        $client -> request('DELETE', '/goods/6');
        
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        
        //se l'eliminazione è avvenuta con successo, allora avrò 404 not found
        $client->request('GET', '/goods/6');
        
        $this->assertFalse($client->getResponse()->getStatusCode() == 404, "Eliminazione perfetta");
    } 
    
   
    /**
     * This test tests that the validation actually works, sending a wrong json
     * object
     */
    public function testBadInsertGood()
    {
        $client = static::createClient();
        //conto il numero di goods
        $client->request('GET','/goods?count=true');
        $count = $client->getResponse()->getContent();
        
        
        $client -> request('POST','/goods',array(), array(), array("CONTENT_TYPE" => "application/json"),
	'{"description":"this description is much longer than 25 characters,'
                . ' yes i know, really really bad", "quantity": 45.5, "price": 2.6}');
        $response = $client -> getResponse();
        
        $this->assertEquals(400, $response ->getStatusCode());
        
        //controllo ancora una volta che tutto sia andato bene;
        //dato che niente deve essere inserito non devo avere alcuna variazione sul numero di oggetti
        $client->request('GET','/goods?count=true');
        $count2 = $client->getResponse()->getContent();
        
        $this->assertTrue($count==$count2, "Inserimento non andato a buon fine, tutto ok");
    }
    
    /**
     * This test test the "PATCH" request
     */
    public function testPatchGood() 
    {
        $client = static::createClient();
        //prendo l'oggetto da modificare
        $client->request('GET','/goods/1');
        $response1=$client->getResponse()->getContent();
        
        $client -> request('PATCH','/goods/1',
                array(), array(), array("CONTENT_TYPE" => "application/json"),
	'{"description":"modificato3", "quantity": 45, "price": 2.6}');
        $response = $client -> getResponse();
        
        $this->assertEquals(200, $response ->getStatusCode());
        
        //prendo l'oggetto modificato e verifico che sia andato tutto a buon fine, 
        //chiedendo se sono uguali
        $client->request('GET','/goods/1');
        $response2=$client->getResponse()->getContent();
        
        $this->assertTrue($response1==$response2, "Good modificato con successo");
    }
    
    /**
     * This test test the validation of the object also for the "PATCH"
     * request
     */
    public function testBadPatchGood() 
    {
        $client = static::createClient();
        //prendo l'oggetto da (non) modificare
        $client->request('GET','/goods/1');
        $response1=$client->getResponse()->getContent();
        
        $client -> request('PATCH','/goods/1',
                array(), array(), array("CONTENT_TYPE" => "application/json"),
	'{"description":"modificato2", "quantity": 45.8, "price": 2.6}');
        $response = $client -> getResponse();
      
        $this->assertEquals(400, $response ->getStatusCode());
        
        //prendo l'oggetto non modificato e verifico che sia uguale a prima
        $client->request('GET','/goods/1');
        $response2=$client->getResponse()->getContent();
        
        $this->assertFalse($response1==$response2, "Good non modificato, tutto ok");
    }
    
    
    
}
