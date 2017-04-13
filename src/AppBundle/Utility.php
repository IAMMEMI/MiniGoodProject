<?php

namespace AppBundle;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Normalizer\ArrayDenormalizer;
use Symfony\Component\Serializer\Normalizer\GetSetMethodNormalizer;
use Symfony\Component\Serializer\Encoder\JsonEncoder;

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
     * This method orders the goods if they are more than 20, otherwise
     * it returns all the goods as an array, leaving this task to the client
     * @param type $field
     * @param Doctrine\ORM\EntityManager $em
     * @return Array $goods
     * @throws HttpException
     */
    public static function orderedGoods($em, $field, $order) {

       
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
            $goods = $em->getRepository('AppBundle:Good')->findAll();
            return $goods;
        }
    }

    /**
     * It searches for goods with the specified field value and returns 
     * the proper array of goods
     * @param Doctrine\ORM\EntityManager $em
     * @param type $field
     * @param type $value
     * @return Array $goods
     */
    public static function searchForGoods($em, $field, $value) {

        $query = $em->createQuery("SELECT p "
                . 'FROM AppBundle\Entity\Good p '
                . "WHERE p." . $field . " = :value");
        $query->setParameter('value', $value);
        $goods = $query->getResult();
        return $goods;
    }

    /**
     * This method validates the description field value used in searching
     * @param type $value as the description value
     * @return boolean true if there isn't any problem with the input value
     * @throws HttpException
     */
    public static function validateDescription($value) {
         if(is_string($value))
             return (strlen($value) <= 25);
         else
             return false;
    }

    /**
     * This method validates the id field value used in searching
     * @param integer $value as the id value 
     * @return boolean true if there isn't any problem with the input value
     * false otherwise
     */
    public static function validateId($value) {

        if (is_numeric($value) && $value >= 0)
            return (strlen((string) $value) <= 11);
        else
            return false;
    }

    /**
     * This method validates the quantity field value used in searching
     * @param type $value as the quanity value
     * @return boolean true if there isn't any problem with the input value,
     * false otherwise
     */
    public static function validateQuantity($value) {
        if (is_int($value + 0) && $value >= 0) 
            return(strlen((string) $value) <= 11);
        else
            return false;
        
    }

    /**
     * This method validates the price field value used in searching. 
     * Value must be numeric and major than zero.
     * @param type $value as the price value
     * @return boolean true if there isn't any problem with the input value
     * false otherwise
     */
    public static function validatePrice($value) {
        return (is_numeric($value) && $value > 0);
    }
    
    public static function validateOrder($ord){
        if(is_string($ord)){
        $order = strtolower($ord);
            if($order == "asc" || $order=="desc"){
                return true;
            }  else {
            return false;
            }
        }
    }
}
