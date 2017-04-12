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
use AppBundle\Error;


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
            'Content-type',
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
        //Facciamo poi una query diretta al database e assicuriamo che
        //siano gli stessi che ci ritornano la risposta
        $goods = $this->em->getRepository('AppBundle:Good')->findAll();
        $this->assertTrue($testGoods==$goods,"Goods aren't the same!");
        
    }
    
    /**
     * This test asserts the correctness of the order function
     */
    public function testOrderedGoods() {
        //Facciamo una richiesta di get ed estraiamo i goods dalla risposta.
        //Dobbiamo poi fare il parsing dal json.
        $client = static::createClient();
        $field = "description";
        $order = "asc";
        $client->request('GET', '/goods?field='.$field.'&&order='.$order);
        $responseTest = $client -> getResponse();
        $jsonGood = $responseTest->getContent();
        try {
            $testGoods = $this->serializer->deserialize(
                    $jsonGood, 'AppBundle\Entity\Good[]', "json");
        } catch (Symfony\Component\Serializer\Exception\UnexpectedValueException
        $ex) {
             $this->fail("Failed to parse json content!") ; 
        }
        //Facciamo poi una query diretta al database e assicuriamo che
        //siano gli stessi che ci ritornano la risposta, nello stesso ordine
       $query = $this->em
                    ->getRepository('AppBundle:Good')
                    ->createQueryBuilder('g')
                    ->orderBy('g.'.$field, $order)
                    ->getQuery();
        $goods = $query->getResult();
        

        //Dobbiamo fare un foreach per verificare l'ordine, in quanto
        //una singola uguaglianza non funzionerebbe se ci sono valori uguali
        //in più di un oggetto, il loro ordine potrebbe essere diverso
                    
        $sameOrder = true;
        //going out of the loop when the equality is not respected 
        //or go forward until all the rows are checked
        for($i=0; $i < count($goods) && $sameOrder; $i++) {
            $sameOrder = $goods[$i]->getDescription() === $testGoods[$i] -> getDescription();
        }
        $this->assertTrue($sameOrder,"Wrong ordination!");
    }
    
    /**
     * This test asserts the correctness of the search function
     */
    public function testSearchGoods() {
        
        //Facciamo una richiesta di get ed estraiamo i goods dalla risposta.
        //Dobbiamo poi fare il parsing dal json.
        $client = static::createClient();
        $client->request('GET', '/goods?field=description&&value=prova');
        $responseTest = $client -> getResponse();
        $jsonGood = $responseTest->getContent();
        try {
            $testGoods = $this->serializer->deserialize(
                    $jsonGood, 'AppBundle\Entity\Good[]', "json");
        } catch (Symfony\Component\Serializer\Exception\UnexpectedValueException
        $ex) {
             $this->fail("Failed to parse json content!") ; 
        }
        //Facciamo poi una query diretta al database e assicuriamo che
        //siano gli stessi che ci ritornano la risposta
        $goods = $this->em->getRepository('AppBundle:Good')
                ->findByDescription("prova");
        $this->assertTrue($testGoods==$goods,"Goods aren't the same!");
        
    }
    
    /**
     * This test asserts the correctness of the count function
     */
    public function testCountGoods() {
        
        //sending a get request, with the count queryparam
        $client = static::createClient();
        $client->request('GET', '/goods?count==true');
        $responseTest = $client -> getResponse();
        $jsonResult = $responseTest->getContent();
        try {
            $testJsonResult = $this->serializer->deserialize(
                    $jsonResult, 'AppBundle\Entity\Good[]', "json");
        } catch (Symfony\Component\Serializer\Exception\UnexpectedValueException
        $ex) {
             $this->fail("Failed to parse json content!") ; 
        }
        //Facciamo poi una query diretta al database e assicuriamo che
        //siano gli stessi che ci ritornano la risposta
        $query = $this->em->createQuery(
                'SELECT COUNT(g.id) '
                . 'FROM AppBundle:Good g');
        return $count = $query->getResult();
        $this->assertTrue($testJsonResult==$count,"The number of goods aren't the same!");
        
    }
    
    /**
     * This test assert that a request 'GET' on a single good specyifing 
     * the id actually sends back a response with a 200 status code, and that
     * the good sent with it is actually the one we have requested
     */
    public function testGetGood()
    {   
        //Creiamo un client, prendiamo per comodità il maxid e facciamo la get.
        $client = static::createClient();
        $query = $this->em-> createQuery("SELECT MAX(g.id) "
                . "from AppBundle:Good g"
                );
        $result = $query -> getResult();
        //dal debug è venuto fuori che il risultato della query
        //è un array con un intero.
        $maxid = $result[0][1];
        $client->request('GET', '/goods/'.$maxid);
        $responseTest = $client -> getResponse();
        $jsonGood = $responseTest->getContent();
        //proviamo a deserializare il contenuto 
        try {
            $testGood = $this->serializer->deserialize(
                    $jsonGood, 'AppBundle\Entity\Good', "json");
        } catch (Symfony\Component\Serializer\Exception\UnexpectedValueException
        $ex) {
             $this->fail("Failed to parse json content!") ; 
        }
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        
        //Estraiamo il good con maxid con una query dal db. 
        $good = $this->em->getRepository('AppBundle:Good')->findOneById($maxid);
        $this->assertTrue($testGood==$good,"Goods aren't the same!");
        
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
        $this->assertTrue(
        $response->headers->contains(
            'Content-type',
            'application/json'
        ),
        'Error response is not "application/json"');
        try {
            $testError = $this->serializer->deserialize(
                 $response->getContent(), 'AppBundle\Entity\Error', "json");
        } catch (Symfony\Component\Serializer\Exception\UnexpectedValueException
        $ex) {
             $this->fail("Failed to parse json content!") ; 
        }
        $this->assertEquals($testError -> getType(), 
                \AppBundle\Utility::BAD_JSON);
        
        //controllo ancora una volta che tutto sia andato bene;
        //dato che niente deve essere inserito non devo avere alcuna variazione sul numero di oggetti
        $query2 = $this ->em->createQuery(
                'SELECT COUNT(g.id) '
                . 'FROM AppBundle:Good g');
        $count2 = $query2->getResult();
        
        $this->assertTrue($count==$count2, "The insert is successful!");
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
        $randomModify = rand();
        $client -> request('PATCH','/goods/1',
                array(), array(), array("CONTENT_TYPE" => "application/json"),
	'{"description":"modificato'.$randomModify.'", "quantity": 45, "price": 2.6}');
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
        $this->assertTrue(
        $response->headers->contains(
            'Content-type',
            'application/json'
        ),
        'Error response is not "application/json"');
        try {
            $testError = $this->serializer->deserialize(
                 $response->getContent(), 'AppBundle\Entity\Error', "json");
        } catch (Symfony\Component\Serializer\Exception\UnexpectedValueException
        $ex) {
             $this->fail("Failed to parse json content!") ; 
        }
        $this->assertEquals($testError -> getType(), 
                \AppBundle\Utility::BAD_JSON);
        
        //prendo l'oggetto non modificato e verifico che sia uguale a prima
        $client->request('GET','/goods/1');
        $response2=$client->getResponse()->getContent();
        
        $this->assertTrue($response1==$response2, 
                "Error! the good was modified anyway!");
    }
    
    /**
     * 
     */
    public function testQueryError() {
        
        $client = static::createClient();
        //prendo l'oggetto da (non) modificare
        $client->request('GET','/goods?field=beer');
        $response=$client->getResponse();
        $this->assertEquals(400, $response ->getStatusCode());
        $this->assertTrue(
        $response->headers->contains(
            'Content-type',
            'application/json'
        ),
        'Error response is not "application/json"');
        try {
            $testError = $this->serializer->deserialize(
                 $response->getContent(), 'AppBundle\Entity\Error', "json");
        } catch (Symfony\Component\Serializer\Exception\UnexpectedValueException
        $ex) {
             $this->fail("Failed to parse json content!") ; 
        }
        $this->assertEquals($testError -> getType(), 
                \AppBundle\Utility::BAD_QUERY);
        $client->request('GET','/goods?field=decription&&value=beer');
        $response=$client->getResponse();
        $this->assertEquals(400, $response -> getStatusCode());
        $this->assertTrue(
        $response->headers->contains(
            'Content-type',
            'application/json'
        ),
        'Error response is not "application/json"');
        try {
            $testError = $this->serializer->deserialize(
                 $response->getContent(), 'AppBundle\Entity\Error', "json");
        } catch (Symfony\Component\Serializer\Exception\UnexpectedValueException
        $ex) {
             $this->fail("Failed to parse json content!") ; 
        }
        $this->assertEquals($testError -> getType(), 
                \AppBundle\Utility::BAD_QUERY);
        
        $client->request('GET',
                '/goods?field=decription&&value=prova&&order=beer');
        $response=$client->getResponse();
        $this->assertEquals(400, $response -> getStatusCode());
        $this->assertTrue(
        $response->headers->contains(
            'Content-type',
            'application/json'
        ),
        'Error response is not "application/json"');
        try {
            $testError = $this->serializer->deserialize(
                 $response->getContent(), 'AppBundle\Entity\Error', "json");
        } catch (Symfony\Component\Serializer\Exception\UnexpectedValueException
        $ex) {
             $this->fail("Failed to parse json content!") ; 
        }
        $this->assertEquals($testError -> getType(), 
                \AppBundle\Utility::BAD_QUERY);
    }
    
    /**
     * 
     */
    public function testJsonError() {
        
         
        
    }
    
    
    
    
    
}
