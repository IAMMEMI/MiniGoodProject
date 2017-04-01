<?php
namespace tests\AppBundle\Controller;

use AppBundle\Controller\GoodsController;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Normalizer\ArrayDenormalizer;
use Symfony\Component\Serializer\Normalizer\GetSetMethodNormalizer;
use Symfony\Component\Serializer\Encoder\JsonEncoder;


class GoodsControllerTest extends WebTestCase
{
    
     /**
     * This is an EntityManager that will be used to
      * query to the db.
     */
    private $em;
    private $serializer;

    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        self::bootKernel();

        $this->em = static::$kernel->getContainer()
            ->get('doctrine')
            ->getManager();
        
        $encoder = array(new JsonEncoder());
        $normalizer = array(new GetSetMethodNormalizer(),
            new ArrayDenormalizer());
        $this->serializer = new Serializer($normalizer, $encoder);
    }
    
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
        //Facciamo una richiesta di get ed estraiamo i goods dalla risposta.
        //Dobbiamo poi fare il parsing dal json.
        $client = static::createClient();
        $client->request('GET', '/goods');
        $responseTest = $client -> getResponse();
        $jsonGood = $responseTest->getContent();
        try {
            $testGoods = $this->serializer->deserialize(
                    $jsonGood, 'AppBundle\Entity\Good[]', "json");
        } catch (Symfony\Component\Serializer\Exception\UnexpectedValueException
        $ex) {
             $this->fail("Failed to parse json content!") ; 
        }
        //Estraiamo i goods effettuando una query dal db. Dobbiamo riparsarli 
        //in json e poi deserializzarli perchè altrimenti i due array sarebbero
        //leggermente diversi a causa del serializer
        $goods = $this->em->getRepository('AppBundle:Good')->findAll();
        $jsonGood2 = $this->serializer->serialize($goods, 'json');
         try {
            $goods = $this->serializer->deserialize(
                    $jsonGood2, 'AppBundle\Entity\Good[]', "json");
        } catch (Symfony\Component\Serializer\Exception\UnexpectedValueException
        $ex) {
             $this->fail("Failed to parse json content!") ; 
        }
        $this->assertTrue($testGoods==$goods,"Goods aren't the same!");
        
    }
    
    /**
     * This test assert that a request 'GET' on a single good specyifing 
     * the id actually sends back a response with a 200 status code
     */
    public function testGetGood()
    {   
        $client = static::createClient();
        $client -> request('GET', '/goods/1');
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
    }
    
    /**
     * This test tests that an insert is answered with a 200 status code, and 
     * that the good was inserted in the db making a select count() query, before
     * and after the client insert request.
     */
     public function testInsertGood()
    {
        $client = static::createClient();
        //conto il numero di oggetti good
        $query = $this ->em->createQuery(
                'SELECT COUNT(g.id) '
                . 'FROM AppBundle:Good g');
        $count = $query->getResult();
        //eseguo la post
        $client -> request('POST','/goods',array(), array(), array("CONTENT_TYPE" => "application/json"),
	'{"description":"prova3", "quantity": 40, "price": 2.6}');
        $response = $client -> getResponse();
        
        $this->assertEquals(200, $response ->getStatusCode());
        
        $query2 = $this ->em->createQuery(
                'SELECT COUNT(g.id) '
                . 'FROM AppBundle:Good g');
        $count2 = $query2->getResult();
        
        $this->assertTrue($count!=$count2, "The good was not inserted for real!");
    }
    
    /**
     * This test test the deletion of a good
     */
    public function testDeleteGood() 
    {
        //This test is valid only once
        $client = static::createClient();
        $query = $this->em-> createQuery("SELECT MAX(g.id) "
                . "from AppBundle:Good g"
                );
        $result = $query -> getResult();
        //dal debug è venuto fuori che il risultato della query
        //è un array con un intero.
        $maxid = $result[0][1];
        $client -> request('DELETE', '/goods/'.$maxid);
        
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        
        //se l'eliminazione è avvenuta con successo, allora avrò 404 not found
        $client->request('GET', '/goods/'.$maxid);
        
        $this->assertTrue($client->getResponse()->getStatusCode() == 404, 
                "The good wasn't deleted for real from the db!");
    } 
    
   
    /**
     * This test tests that the validation actually works, sending a wrong json
     * object
     */
    public function testBadInsertGood()
    {
        $client = static::createClient();
        
        //conto il numero di goods
        $query = $this ->em->createQuery(
                'SELECT COUNT(g.id) '
                . 'FROM AppBundle:Good g');
        $count = $query->getResult();
        
        
        $client -> request('POST','/goods',array(), array(), array("CONTENT_TYPE" => "application/json"),
	'{"description":"this description is much longer than 25 characters,'
                . ' yes i know, really really bad", "quantity": 45.5, "price": 2.6}');
        $response = $client -> getResponse();
        
        $this->assertEquals(400, $response ->getStatusCode());
        
        //controllo ancora una volta che tutto sia andato bene;
        //dato che niente deve essere inserito non devo avere alcuna variazione sul numero di oggetti
        $query2 = $this ->em->createQuery(
                'SELECT COUNT(g.id) '
                . 'FROM AppBundle:Good g');
        $count2 = $query2->getResult();
        
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
	'{"description":"modificato", "quantity": 45, "price": 2.6}');
        $response = $client -> getResponse();
        
        $this->assertEquals(200, $response ->getStatusCode());
        
        //prendo l'oggetto modificato e verifico che sia andato tutto a buon fine, 
        //chiedendo se sono uguali
        $client->request('GET','/goods/1');
        $response2=$client->getResponse()->getContent();
        
        $this->assertTrue($response1!=$response2, "Error! Good not modified!");
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
        
        $this->assertTrue($response1==$response2, 
                "Error! the good was modified anyway!");
    }
    
    
    
}
