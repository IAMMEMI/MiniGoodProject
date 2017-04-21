<?php

namespace AppBundle;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Normalizer\ArrayDenormalizer;
use Symfony\Component\Serializer\Normalizer\GetSetMethodNormalizer;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use AppBundle\Entity\Error;


/**
 * Utility is a class that supports GoodsController's class methods
 *
 * @author dezio
 */
class Utility {
    
    
    const BAD_JSON = "BAD_JSON";
    const BAD_QUERY = "BAD_QUERY";
    
    /**
     * This method returns a serializer used to parse and serialize
     * json objects.
     * @return \AppBundle\Serializer serializer
     */
    public static function getSerializer() {
        $encoder = array(new JsonEncoder());
        $normalizer = array(new ObjectNormalizer());
        return new Serializer($normalizer, $encoder);
    }
    
    
    /**
     * This method creates a response using the Symfony serializer to turn 
     * input goods into json format: the output will be the body of the response
     * @param Array $goods, as an array of Good objects
     * @return Symfony\Component\HttpFoundation\Response response
     */
    public static function createOkResponse($request, $goods) {

        $json = Utility::getSerializer()->serialize($goods, 'json');
        $response = new Response($json, Response::HTTP_OK, 
                array("Content-type" => "application/json"));
        $response->prepare($request);
        return $response;
    }
    
    /**
     * This function sends an Http 400 Bad Format Response, adding a json object
     * to specify the kind of error happened, in order to give more explanation
     * @param Symfony\Component\HttpFoundation\Request $request
     * @param AppBundle\Error $error
     * @return Symfony\Component\HttpFoundation\JsonResponse $response
     */
    public static function createBadFormatResponse($request, $error) {
        
        $json = Utility::getSerializer()->serialize($error, "json");
        $response = new Response($json, Response::HTTP_BAD_REQUEST,
                array("Content-type" => "application/json"));
        $response -> prepare($request);
        return $response;
    }
    
    /**
     * This method returns the number of goods.
     * @param type $em
     * @return type 
     */
    public static function countGoods($em) {

        //we create a query for having the number of goods 
        $query = $em->createQuery(
                'SELECT COUNT(g.id) '
                . 'FROM AppBundle:Good g');
        return $count = $query->getResult();
    }
    
    /**
     * Return all goods in the database
     * @param Doctrine\ORM\EntityManager $em
     */
    public static function getAllGoods($em) {
        $goods = $em -> getRepository('AppBundle:Good') -> findAll();
        return $goods;
    }

    /**
     * This method orders the goods if they are more than 20, otherwise
     * it returns all the goods as an array, leaving this task to the client.
     * If the order is not specified then is ascending by default.
     * @param type $field
     * @param Doctrine\ORM\EntityManager $em
     * @return Array $goods if it is all correct, Error $error if the query
     * params are not correct
     * @throws HttpException
     */
    public static function orderedGoods($em, $field, $order) {
        
        //If the order param is null, than we order asc mode by 
        // default
        if(is_null($order)) {
            $order = "asc";
            $isOrderValid = true;
        } else {
            $isOrderValid = Utility::validateOrder($order);
        }
        $isFieldValid = Utility::validateField($em, $field);
        if(!$isOrderValid || !$isFieldValid) {
            //if the params aren't valid, we have to send an error 
            $error = new Error(Utility::BAD_QUERY,
                "Invalid ".$field."value in research query","");
            return $error;
        }
        $count = Utility::countGoods($em);
        //if goods are more than 20 we have to order them 
        if ($count > 20) {
            //so goods are more than 20
            try {
                //let's create a query. This way we will have goods 
                //ordered by the field sent by the client.
                $query = $em
                        ->getRepository('AppBundle:Good')
                        ->createQueryBuilder('p')
                        ->orderBy('p.' . $field, $order)
                        ->getQuery();
                $goods = $query->getResult();
                return $goods;
            } catch (\Doctrine\ORM\Query\QueryException $ex) {
                //the server encountered an unexpected condition 
                //which prevented it from fulfilling the request.
                throw new HttpException(500, "Fatal Exception: " . $ex->getMessage());
            }
        } else {
            //goods are less then 20, so nothing else have to be done by the server
            //if the number of goods is 0, 
            //a 404 not found exception is sent to the client
            if ($count == 0) {
                throw new HttpException(404, "No good found");
            }
            //so goods are less than 20 and more than 0.
            //let's import all goods
            return Utility::getAllGoods($em);
        }
    }

    /**
     * It searches for goods that have $value
     * inside their specified $field value,
     * using the regex %$value% if the field is description:
     * it means that the value string is searched inside the 
     * field.
     * @param Doctrine\ORM\EntityManager $em
     * @param type $field
     * @param type $value
     * @return Array $goods if it is all correct, Error $error if the query
     * params are not correct
     */
    public static function searchForGoods($em, $field, $value, $order = null) {
        
        //If the order param is null, than we order asc mode by 
        // default
        $isOrderValid = Utility::validateOrder($order);
        $isFieldValid = Utility::validateField($em, $field);
        if($isFieldValid) {
            $isValueValid = Utility::validateValue($field, $value);
        }
        if(!$isFieldValid || !$isValueValid) {
            //if the value is not valid, we have to send an error 
            $error = new Error(Utility::BAD_QUERY,
                "Invalid ".$field."value in research query","");
            return $error;
        }
        $queryBuilder = $em -> createQueryBuilder();
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
        if($isOrderValid || is_null($order)) {
            if($isOrderValid)
                $queryBuilder-> orderBy('p.'.$field, $order);
            //PER IL MOMENTO FUNZIONA MA VEDERE SE SI TROVA UNA SOL PIU'
            //ELEGANTE A MENTE FRESCA
            $query = $queryBuilder -> getQuery();
            $goods = $query -> getResult();
            return $goods;
        } else {
            //if the value is not valid, we have to send an error 
            $error = new Error(Utility::BAD_QUERY,
                "Invalid ".$field."value in research query","");
            return $error;
        }
        
    }
    
    /**
     * This method validates a field, represented as a string,
     * for the good entity.
     * @param Doctrine\ORM\EntityManager $em
     * @param string $field
     * @return bool $result
     */
    public static function validateField($em, $field) {
        $result = false;
        if(is_string($field)) {
            $properFields = $em->getClassMetaData("AppBundle:Good")
                ->getColumnNames();
            $result =  in_array($field, $properFields);
        }
        return $result;
        
    }
    
    /**
     * This methods validates a value, given its field, 
     * for the good entity
     * @param string $field
     * @param type $value
     * @return bool $result
     */
    public static function validateValue($field, $value) {
        
        $result = false;
        switch ($field) {
            case "description":
                $result = Utility::validateDescription($value);
                break;
            case "id":
                $result = Utility::validateId($value);
                break;
            case "quantity":
                $result = Utility::validateQuantity($value);
                break;
            case "price":
                $result = Utility::validatePrice($value);
        }
        return $result;
       
    }
    
   
    /**
     * This method validates the description field value used in searching
     * @param type $value as the description value
     * @return boolean true if there isn't any problem with the input value
     * @throws HttpException
     */
    public static function validateDescription($value) {
        
         if(is_string($value)) {
             return (strlen($value) <= 25);
         }
         else {
             return false;
         }
    }

    /**
     * This method validates the id field value used in searching
     * @param integer $value as the id value 
     * @return boolean true if there isn't any problem with the input value
     * false otherwise
     */
    public static function validateId($value) {

        $result = false;
        if(is_numeric($value)) {
            $numericValue = $value + 0;
            if (is_int($numericValue) && $numericValue >= 0) { 
                $result = strlen((string) $value) <= 11;
            }
        }
        return $result;
    }

    /**
     * This method validates the quantity field value used in searching
     * @param type $value as the quanity value
     * @return boolean true if there isn't any problem with the input value,
     * false otherwise
     */
    public static function validateQuantity($value) {
        
        $result = false;
        if(is_numeric($value)) {
            $numericValue = $value + 0;
            if (is_int($numericValue) && $numericValue >= 0) { 
                $result = strlen((string) $value) <= 11;
            }
        }
        return $result;
    }

    /**
     * This method validates the price field value used in searching. 
     * Value must be numeric and major than zero.
     * @param double $value as the price value
     * @return boolean $result
     */
    public static function validatePrice($value) {
        if(is_numeric($value)) {
            //I make a conversion in case the input is a string
            //and return true if is positive
            return ($value + 0 > 0);
        }
    }
    
    
    /**
     * This method validates the order query-param value: the only
     * values accepted are "asc" and "desc", case insensitive
     * @param string $ord
     * @return boolean $result
     */
    public static function validateOrder($ord){
        
        $result = false;
        if(is_string($ord)){
        $order = strtolower($ord);
            if($order == "asc" || $order=="desc"){
                $result = true;
            }
        }
        return $result;
    }
}
