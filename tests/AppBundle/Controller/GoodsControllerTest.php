<?php
namespace tests\AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
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
     * @dataProvider orderedGoodsInputProvider
     */
    public function testOrderedGoods($field, $order = null) {
        //Facciamo una richiesta di get ed estraiamo i goods dalla risposta.
        //Dobbiamo poi fare il parsing dal json.
        $client = static::createClient();
        $queryString = '/goods?field='.$field;
        if(!is_null($order)) {
            $queryString .="&&order=".$order;
        }
        $client->request('GET', $queryString);
        
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
        $sameOrder = true;
        //break when all the goods are checked or 
        //when the order is not respected
        for($i=0; $i < count($goods) && $sameOrder; $i++) {
            $sameOrder = $goods[$i]->getDescription() === $testGoods[$i] -> getDescription();
        }
        $this->assertTrue($sameOrder,"Wrong ordination!");
    }
    
    /**
     * This test asserts the correctness of the order function
     * @dataProvider badQueriesOrderedGoodsInputProvider
     */
    public function testBadQueriesOrderedGoods($field, $order = null) {
        //Facciamo una richiesta di get ed estraiamo i goods dalla risposta.
        //Dobbiamo poi fare il parsing dal json.
        $client = static::createClient();
        $queryString = '/goods?field='.$field;
        if(!is_null($order)) {
            $queryString .="&&order=".$order;
        }
        $client->request('GET', $queryString);
        
        $responseTest = $client -> getResponse();
        $jsonGood = $responseTest->getContent();
        try {
            $testError = $this->serializer->deserialize(
                    $jsonGood, 'AppBundle\Entity\Error', "json");
        } catch (Symfony\Component\Serializer\Exception\UnexpectedValueException
        $ex) {
             $this->fail("Failed to parse json content!") ; 
        }
        $this -> assertTrue($testError -> getType() 
                == \AppBundle\Utility::BAD_QUERY);
        
    }
    
     /**
     * This function is used as a dataProvider for
     * the testSearchGoods
     * @return array $data
     */
    public function badQueriesorderedGoodsInputProvider() {
        
         return array(
            array("beer","asc"),
            array("margarida"),
            array("no more tests","descx"),
            array("quantity", "show"),
        );
    }
    
     /**
     * This function is used as a dataProvider for
     * the testSearchGoods
     * @return array $data
     */
    public function orderedGoodsInputProvider() {
        
         return array(
            array("description","asc"),
            array("description"),
            array("description","desc"),
            array("quantity", "asc"),
            array("quantity","desc"),
            array("quantity"),
            array("id", "asc"),
            array("id", "desc"),
            array("id"),
            array("price","asc"),
            array("price", "desc"),
            array("price"),
        );
    }
    
    /**
     * This test asserts the correctness of the search function
     * @dataProvider searchForGoodsInputProvider
     */
    public function testSearchGoods($field, $value, $order = null) {
        
        //We need to do a get request in order to extract
        //the response from the controller,
        //then we parse the json inside.
        $client = static::createClient();
        $requestURL = '/goods?field='.$field.'&&value='.$value;
        if($order != null) {
            $requestURL .= '&&order='.$order;
        }
        $client->request('GET', $requestURL);
        $responseTest = $client -> getResponse();
        $jsonGood = $responseTest->getContent();
        try {
            $testGoods = $this->serializer->deserialize(
                    $jsonGood, 'AppBundle\Entity\Good[]', "json");
        } catch (Symfony\Component\Serializer\Exception\UnexpectedValueException
        $ex) {
             $this->fail("Failed to parse json content!") ; 
        }
        $queryBuilder = $this -> em -> createQueryBuilder();
        $queryBuilder -> select (array('p'))
                          -> from('AppBundle:Good', 'p');
        //Preparing the field value for the query
        if($field == "description") {
            $value = "'%".$value."%'";
        } else {
            $value = "'".$value."'";
        }
        $queryBuilder -> where(
                            $queryBuilder -> expr() -> like('p.'.$field, $value)
                              );
        if(!is_null($order)) {
            $queryBuilder-> orderBy('p.'.$field, $order);
        }
        $query = $queryBuilder -> getQuery();
        $goods = $query -> getResult();

        $this->assertTrue($testGoods==$goods,"Goods aren't the same!");
        
    }
    
    /**
     * This function is used as a dataProvider for
     * the testSearchGoods
     * @return array $data
     */
    public function searchForGoodsInputProvider() {
        
         return array(
            array("description","prova"),
            array("price",2.6),
            array("quantity", 40),
            array("id",5),
            array("description","prova","asc"),
            array("description","prova","desc"),
        );
    }
    
    
    /**
     * This test asserts the correctness of the search function
     * @dataProvider badQueriesSearchForGoodsInputProvider
     */
    public function testBadQueriesSearchForGoods($field, $value, $order = null) {
        
        //We need to do a get request in order to extract
        //the response from the controller,
        //then we parse the json inside.
        $client = static::createClient();
        $requestURL = '/goods?field='.$field.'&&value='.$value;
        if($order != null) {
            $requestURL .= '&&order='.$order;
        }
        $client->request('GET', $requestURL);
        $responseTest = $client -> getResponse();
        $jsonGood = $responseTest->getContent();
        try {
            $testError = $this->serializer->deserialize(
                    $jsonGood, 'AppBundle\Entity\Error', "json");
        } catch (Symfony\Component\Serializer\Exception\UnexpectedValueException
        $ex) {
             $this->fail("Failed to parse json content!") ; 
        }
        $this -> assertTrue($testError -> getType() 
                == \AppBundle\Utility::BAD_QUERY);
    }
    
    /**
     * This function is used as a dataProvider for
     * the testSearchGoods
     * @return array $data
     */
    public function badQueriesSearchForGoodsInputProvider() {
        
         return array(
            array("descrizio","prova"),
            array("prizzo",2.6),
            array("quantita", 40),
            array("identificativo",5),
            array("description","prova","ascella"),
            array("description","prova","desk"),
            array("descrizio", "prova", "desk"),
            array("id", "prova"),
            array("quantity", "prova"),
        );
    }
    
    
    

    /**
     * This test assert that a request 'GET' on a single good specyifing 
     * the id actually sends back a response with a 200 status code, and that
     * the good sent with it is actually the one we have requested
     */
    public function testGetGood()
    {   
        //We create a client and then we get the maximum id on the db
        //in order to get the last good inserted.
        $client = static::createClient();
        $query = $this->em-> createQuery("SELECT MAX(g.id) "
                . "from AppBundle:Good g"
                );
        $result = $query -> getResult();
        $maxid = $result[0][1];
        $client->request('GET', '/goods/'.$maxid);
        $responseTest = $client -> getResponse();
        $jsonGood = $responseTest->getContent();
        //we try to deserialize the content 
        try {
            $testGood = $this->serializer->deserialize(
                    $jsonGood, 'AppBundle\Entity\Good', "json");
        } catch (Symfony\Component\Serializer\Exception\UnexpectedValueException
        $ex) {
             $this->fail("Failed to parse json content!") ; 
        }
        //We assert the correctness of the response status code and that the
        //good returned is the right one, making a direct query to the db.
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
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
        $count = \AppBundle\Utility::countGoods($this -> em);
        $client -> request('POST','/goods',array(), array(), array("CONTENT_TYPE" => "application/json"),
	'{"description":"prova3", "quantity": 40, "price": 2.6}');
        $response = $client -> getResponse();
        $this->assertEquals(200, $response ->getStatusCode());
        $count2 = \AppBundle\Utility::countGoods($this -> em);
        $this->assertTrue($count!=$count2, "The good was not inserted for real!");
    }
    
    /**
     * This test tests the deletion of a good
     */
    public function testDeleteGood() 
    {
        //This test is valid only once
        $client = static::createClient();
        $query = $this->em-> createQuery("SELECT MAX(g.id) "
                . "from AppBundle:Good g"
                );
        $result = $query -> getResult();
        $maxid = $result[0][1];
        $client -> request('DELETE', '/goods/'.$maxid);        
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        //We make a get for the same good just deleted, and the response
        //status code must be a 404 because there isn't that good anymore.
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
        $count = \AppBundle\Utility::countGoods($this->em);
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
        $count2 = \AppBundle\Utility::countGoods($this->em);
        $this->assertTrue($count==$count2, "The insert is successful!");
    }
    
    /**
     * This test tests the "PUT" request
     */
    public function testPutGood() 
    {
        $client = static::createClient();
        //I take the object to modify
        $client->request('GET','/goods/1');
        $response1=$client->getResponse()->getContent();
        //We need a random function, because otherwise the test will fail
        //the second time we modify the same object.
        $randomModify = rand();
        $client -> request('PUT','/goods/1',
                array(), array(), array("CONTENT_TYPE" => "application/json"),
	'{"description":"modificato'.$randomModify.'", "quantity": 45, "price": 2.6}');
        $response = $client -> getResponse();
        $this->assertEquals(200, $response ->getStatusCode());
        $client->request('GET','/goods/1');
        $response2=$client->getResponse()->getContent();
        $this->assertTrue($response1!=$response2, "Error! Good not modified!");
    }
    
    /**
     * This test tests the validation of the object also for the "PATCH"
     * request, and tests that the error response is correct.
     */
    public function testBadPutGood() 
    {
        $client = static::createClient();
        $client->request('GET','/goods/1');
        $response1=$client->getResponse()->getContent();
        //the quantity value is not correct, the validation will fail.
        $client -> request('PUT','/goods/1',
                array(), array(), array("CONTENT_TYPE" => "application/json"),
	'{"description":"modificato2", "quantity": 45.8, "price": 2.6}');
        $response = $client -> getResponse();
      
        //Here we check the response error.
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
        //We check that the good is the same as before
        $client->request('GET','/goods/1');
        $response2=$client->getResponse()->getContent();
        $this->assertTrue($response1==$response2, 
                "Error! the good was modified anyway!");
    }  
    
}
