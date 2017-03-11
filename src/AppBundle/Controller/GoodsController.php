<?php

namespace AppBundle\Controller;

use FOS\RestBundle\Controller\Annotations\View;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use  Symfony\Component\HttpKernel\Exception\HttpException;
use FOS\RestBundle\Controller\Annotations\RouteResource;
use FOS\RestBundle\Controller\FOSRestController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;


class GoodsController extends Controller
{
    
    private $serializer;
    
    
    public function __construct() {
        $encoder = array(new JsonEncoder());
        $normalizer = array(new ObjectNormalizer());
        $this -> serializer = new Serializer($normalizer, $encoder);
    }
    
   
    
    
    public function getGoodsAction(Request $request) {
        //order = true/false; field=qualcosa che esiste; 
        //search = true; field=qualcosa; value=qualcosa;
        
        //importo tutti i products dal db
        $goodRepository = $this->getDoctrine()
                ->getRepository('AppBundle:Good');
            $goods = $goodRepository ->findAll();
        
        $parameters = $request ->query;
       
        
        if($parameters ->get("order")){
            $field = $parameters ->get("field");
            
                if(count($goods)>20) {
                try{
                $query = $goodRepository->createQueryBuilder('p')
                ->orderBy('p.'.$field, 'ASC')
                ->getQuery();
                $goods = $query->getResult();
                } 
                catch(\Doctrine\ORM\Query\QueryException $ex){
                    throw new HttpException(400, "Incorrect field value");
                }

                }
        }
        
        if($parameters ->get("search")){
           $field = $parameters ->get("field");
           $value = $parameters ->get("value");
           
           if($field == "" || $value == ""){
               throw new HttpException(400, "Bad or incomplete request");
           }
           try{
           $query = $goodRepository ->createQueryBuilder('p')
                   ->where('p.'.$field.'='.$value)
                   ->getQuery();
           $goods = $query->getResult();
           } catch(\Doctrine\ORM\Query\QueryException $ex){
                    throw new HttpException(400, "Incorrect field or value");
                }
           
           
        }
            if(count($goods)==0){
                throw new HttpException(404, "No good found");
            }     
            $json = $this -> serializer -> serialize($goods, 'json');
            $response = new Response($json, Response::HTTP_OK, array('content-type' => 'application/json'));
            $response -> prepare ($request);
            $response -> headers -> set('Access-Control-Allow-Origin', '*');
            return $response;
        
        
    } // "get_goods"  [GET] /goods
    
    
    public function getGoodAction(Request $request, $id) 
    {
            $good = $this->getDoctrine()
                ->getRepository('AppBundle:Good')
                ->find($id);
        if(!$good) {
            throw new HttpException(404, "No good found for id ".$id);
        }
        $jsonGood = $this -> serializer -> serialize(
                $good, 'json');
        $response = new Response(
                $jsonGood,
                Response::HTTP_OK,
                array('content-type' => 'application/json')
                );
        $response -> headers -> set('Access-Control-Allow-Origin', '*');
        $response -> prepare($request);
        return $response;
    }// "get_goods" [GET] /goods/{id}
    
    public function postGoodsAction(Request $request) {
        
        $jsonGood = $request -> getContent();
        try {
        $newGood = $this -> serializer -> deserialize(
                $jsonGood, 'AppBundle\Entity\Good', "json");
        } catch (Symfony\Component\Serializer\Exception\UnexpectedValueException 
                $ex) {
                    throw new HttpException(400, "Error parsing json object");
                }
        
        $validator = $this -> get("validator");
        $errors = $validator -> validate($newGood);
        
        if (count($errors) > 0) {
            
            return new HttpException(400, "Error validatin Json object: ".(string)$errors);
        
        }
        
        $em = $this -> getDoctrine()->getManager();
        $em -> persist($newGood);
        $em -> flush();
        $response = new Response("Saved new good with id ".$newGood -> getId());
        $response -> headers -> set('Access-Control-Allow-Origin', '*');
        $response ->prepare($request);
        return $response;
        
        
    } // "insert_good" [POST] /goods
    
    public function patchGoodsAction(Request $request, $id) {$jsonGood = $request -> getContent();
        try {
        $newGood = $this -> serializer -> deserialize(
                $jsonGood, 'AppBundle\Entity\Good', "json");
        } catch (Symfony\Component\Serializer\Exception\UnexpectedValueException 
                $ex) {
                    throw new HttpException(400, "Error parsing json object");
                }
        $em = $this ->getDoctrine() ->getManager();
        $oldGood = $em ->getRepository('AppBundle:Good')-> find($id);
        if(!$oldGood) {
            throw new HttpException(400, "No good found for id".$id);
        }
        $newDescription = $newGood -> getDescription();
        if($newDescription != NULL) {
            $oldGood -> setDescription($newDescription);
        }
        $newQuantity = $newGood -> getQuantity();
        if($newQuantity != NULL) {
            $oldGood -> setQuantity($newQuantity);
        }
        $newPrice = $newGood -> getPrice();
        if($newPrice != NULL) {
            $oldGood -> setPrice($newPrice);
        }
        $em -> flush();
        
        $response = new Response("Updated good with id ".$id);
        $response -> headers -> set('Access-Control-Allow-Origin', '*');
        $response ->prepare($request);
        return $response;
        
    } // "modify_good" [PUT] /goods/{id}
    
    public function deleteGoodsAction(Request $request, $id) {
        
        $em = $this ->getDoctrine() ->getManager();
        $good = $em ->getRepository('AppBundle:Good')-> find($id);
        if(!$good) {
            throw new HttpException(400, "no good found for id".$id);
        }
        $em -> remove($good);
        $em -> flush();
        
        $response =  new Response("Deleted good with id ".$id);
        $response -> headers -> set('Access-Control-Allow-Origin', '*');
        $response ->prepare($request);
        return $response;

    } // "delete_good" [DELETE] /goods/{id}
}