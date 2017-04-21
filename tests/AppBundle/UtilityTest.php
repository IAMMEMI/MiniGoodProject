<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace AppBundle;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;


/**
 * This testcase tests validation methods of the Utility class
 *
 * @author dezio
 */
class UtilityTest extends WebTestCase {
    
    
    /**
     * This test asserts the correctness of the search function
     * @dataProvider searchForGoodsInputProvider
     */
    public function testSearchGoods($field, $value, $order) {
        
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
        //We than do a query to make sure the objects of the response
        //are the same from the database.
        if($field == "description") {
            //fare la query con % trovare il modoDEBUG
        }
        if(!is_null($order)) {
            $goods = $this->em->getRepository('AppBundle:Good')
                ->findBy(array($field => $value),array($field => $order));
        } else {
            $goods = $this->em->getRepository('AppBundle:Good')
                ->findBy(array($field => $value));
        }
        $this->assertTrue($testGoods==$goods,"Goods aren't the same!");
        
    }
    
    /**
     * This function is used as a dataProvider for
     * the testBadValidatePrice
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
     * This test asserts the correctness of the validation function
     */
    public function testCorrectValidateDescription() {
        
        $validInputString = "fantasticGood";
        $result = Utility::validateDescription($validInputString);
        $this -> assertTrue($result, true);
        
    }
    
    /**
     * @dataProvider badDescrInputProvider
     * This test asserts that the only valid input of validateDescription is 
     * a string shorter than 25 characters
     */
    public function testBadValidateDescription($invalidInput) {
       
        $result = Utility::validateDescription($invalidInput);
        $this -> assertFalse($result);
    }
    
    /**
     * This function is used as a dataProvider for
     * the testBadValidateDescription
     * @return array $data
     */
    public function badDescrInputProvider() {
        
        return array(
            array("this is a really really really good good"),
            array(5),
            array(4.76),
            array(null),
            array(true, false),
            array((object)array(1 => "randomValue"))
        );
        
    }
    
    /**
     * This test asserts the correctness of the validateId function
     */
    public function testValidateId() {
         
         $validIdInteger = 45;
         $validIdString = "45";
         $result = Utility::validateId($validIdInteger);
         $this -> assertTrue($result);
         $result = Utility::validateId($validIdString);
         $this -> assertTrue($result);
    }
    
    /**
     * This test asserts that the only input valid for this function is 
     * a string or an integer of max 11 digits.
     * @dataProvider badIdInputProvider
     */
    public function badTestValidateId($invalidId) {
        
        $result = Utility::validateId($invalidId);
        $this -> assertFalse($result);
        
    }
    
    
    
    /**
     * This function is used as a dataProvider for
     * the testBadValidateId
     * @return array $data
     */
    public function badIdInputProvider() {
        
         return array(
            array("this is not an id!"),
            array(500000000000000),
            array(4.76),
            array(null),
            array(true, false),
            array((object)array(1 => "randomValue"))
        );
    }
    
    /**
     * This function asserts the correctness of the validatePrice
     * function
     */
    public function testValidatePrice() {
        
        $validPriceDouble = 5.67;
        $validPriceString = "5.67";
        $result = Utility::validatePrice($validPriceDouble);
        $this -> assertTrue($result);
        $result = Utility::validatePrice($validPriceString);
        $this -> assertTrue($result);
    }
    
    /**
     * This test asserts that the only valid input to validatePrice
     * are double and string representing double.
     * @dataProvider badPriceInputProvider
     */
    public function badTestValidatePrice($invalidPrice) {
        
        $result = Utility::validatePrice($invalidPrice);
        $this -> assertFalse($result);
        
    }
    
    /**
     * This function is used as a dataProvider for
     * the testBadValidatePrice
     * @return array $data
     */
    public function badPriceInputProvider() {
        
         return array(
            array("this is not an price!"),
            array(-50),
            array(null),
            array(true, false),
            array((object)array(1 => "randomValue"))
        );
    }
    
    /**
     * This test asserts the correctness of the validateQuantity
     */
    public function testValidateQuantity() {
        
        $validQuantityInt = 54;
        $validQuantityString = "54";
        $result = Utility::validateQuantity($validQuantityInt);
        $this -> assertTrue($result);
        $result = Utility::validateQuantity($validQuantityString);
        $this -> assertTrue($result);
    
    }
    
    /**
     * @dataProvider badQuantityInputProvider
     */
    public function badTestValidateQuantity($invalidQuantityInput) {
        
        $result = Utility::validateQuantity($invalidQuantityInput);
        $this -> assertFalse($result);
    }
    
    /**
     * This function is used as a dataProvider for
     * the testBadValidatePrice
     * @return array $data
     */
    public function badQuantityInputProvider() {
        
         return array(
            array("this is not a quantity value!"),
            array(5.67),
            array(50000000000000000000000000000000000),
            array(-3),
            array(null),
            array(true, false),
            array((object)array(1 => "randomValue"))
        );
    }
    
    /**
     * This test grants the correctness of the validateOrder 
     * function
     */
    public function testValidateOrder() {
        
        $validOrderAsc = "asc";
        $validOrderDesc = "desc";
        
        $result = Utility::validateOrder($validOrderAsc);
        $this -> assertTrue($result);
        $result = Utility::validateOrder($validOrderDesc);
        $this -> assertTrue($result);
           
    }
    
    /**
     * This test grants that the only input valid for the order function is
     * "asc" and "desc"
     * @dataProvider badOrderInputProvider
     */
    public function testBadValidateOrder($invalidOrderValue) {
        
        $result = Utility::validateOrder($invalidOrderValue);
        $this -> assertFalse($result);
        
    }
    
    /**
     * This function is used as a dataProvider for 
     * the testBadValidateOrder
     * @return array $data
     */
    public function badOrderInputProvider() {
        
        return array(
            array("this is not an order value!"),
            array(5.67),
            array(5),
            array(null),
            array(true, false),
            array((object)array(1 => "randomValue"))
        );
        
    }
    
    
    
    
    
    
    
    
    
    
}
