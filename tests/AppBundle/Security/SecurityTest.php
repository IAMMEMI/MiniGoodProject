<?php

namespace tests\AppBundle\Security;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Normalizer\ArrayDenormalizer;
use Symfony\Component\Serializer\Normalizer\GetSetMethodNormalizer;
use Symfony\Component\Serializer\Encoder\JsonEncoder;

/**
 * Description of SecurityTest
 *
 * @author dezio
 */
class SecurityTest extends WebTestCase {
    
    private $validUsername;
    private $validPassword;
    private $invalidPassword;
    
    /**
     * {@inheritDoc}
     */
    protected function setUp() {
        $this -> validUsername = "testUser1";
        $this -> validPassword = "testPass1";
        $this -> invalidPassword = "WRONG";
    }

    /**
     * 
     * @return \Symfony\Bundle\FrameworkBundle\Client
     */
    private function createAuthenticatedClient() {

        //We send our first request, 
        //in order to take the token for the first time
        $client = static::createClient();
        $client->request(
                'POST', '/login_check', array(
            '_username' => $this -> validUsername,
            '_password' => $this -> validPassword,
                )
        );
        $data = json_decode($client->getResponse()->getContent(), true);
        //We re-create the client with the proper token
        $client = static::createClient();
        $client->setServerParameter('HTTP_Authorization', sprintf('Bearer %s', 
                $data['token']));
        $client ->setServerParameter('TEST_NEED', $data['refresh_token']);
       
        return $client;
    }
    
    private function createRefreshedAuthenticatedClient
            (\Symfony\Bundle\FrameworkBundle\Client $client) {
        $httpAuthorizationParam = $client -> getServerParameter('HTTP_Authorization');
        $refreshToken = $client -> getServerParameter('TEST_NEED');
        if($httpAuthorizationParam != null) {
            $client->request(
                    'POST', '/token/refresh', array(
                        "refresh_token" => $refreshToken
                    )
            );
            $data = json_decode($client->getResponse()->getContent(), true);
            //We re-create the client with the refreshed token
            $client = static::createClient();
            $client->setServerParameter('HTTP_Authorization', 
                    sprintf('Bearer %s', $data['token']));
            return $client;
        } else { throw new \InvalidArgumentException("The client must already be"
        . "authenticated with a token!"); }
    }

    /**
     * Test for the proper response to an authenticated client
     */
    public function testAuthenticatedGet() {

        $client = $this->createAuthenticatedClient();
        $client->request('GET', '/goods');
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
    }
    
    /**
     * Test for the refreshed token. 
     */
    public function testRefreshTokenUse() {
        $authClient = $this->createAuthenticatedClient();
        $authRefreshClient = $this-> createRefreshedAuthenticatedClient($authClient);
        $authRefreshClient->request('GET', '/goods');
        $this->assertEquals(200, $authRefreshClient->getResponse()->getStatusCode());
    }

    /**
     * Test for the proper response to a not authenticated client
     */
    public function testNotAuthenticatedGet() {
        $client = static::createClient();
        $client->request('GET', '/goods');
        $this->assertEquals(401, $client->getResponse()->getStatusCode());
    }
    
    public function testBlockUserAfter3LoginAttempts() {
        $client = static::createClient();
        for($i =0; $i < 3; $i++) {
            $client->request(
                    'POST', '/login_check', array(
                '_username' => $this->validUsername,
                '_password' => $this->invalidPassword,
                    )
            );
        }
        $client->request(
                'POST', '/login_check', array(
            '_username' => $this->validUsername,
            '_password' => $this->validPassword,
                )
        );
        $this->assertEquals(401, $client->getResponse()->getStatusCode());
    }
}

