<?php

namespace AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use AppBundle\Utility;
use AppBundle\Entity\Error as Error;

/**
 * Class GoodsController handles all CRUD operations with good object.
 */
class GoodsController extends Controller {

    /**
     * This method returns an array of goods.
     * Depending on the number of the goods, they will be ordered or not. 
     * If the number of the goods is more than 20 
     * and we have a field then we have to sort the array 
     * before we send it to the client.
     * Otherwise if goods are less than 20, we have to send it as they are.
     * If the request contains the count queryparam then it returns the number 
     * of goods to the client.
     * 
     * @param Request $request
     * @return Response 
     * @throws HttpException
     */
    public function getGoodsAction(Request $request) {


        $parameters = $request->query;
        $em = $this->getDoctrine()->getManager();
        $properFields = $em->getClassMetaData("AppBundle:Good")
                ->getColumnNames();
        $field = $parameters->get("field");
        $order = $parameters->get("order");
        $value = $parameters->get("value");
        $count = $parameters->get("count");
        if (!is_null($field)) {
            //if a field is specified, we have to return all goods 
            //ordered by that field.
            //Verifying that the field is valid... 
            if (!in_array($field, $properFields)) {
                 //if the field is not valid, we have to send an error 
                    $error = new Error(Utility::BAD_QUERY,
                            "Invalid ".$field." in research query","");
                    return Utility::createBadFormatResponse($request, $error);
            }
            if (is_null($value)) {
                //if value is null then we have to order goods
                
                $isValid = Utility::validateOrder($order);
                if($isValid){
                    $goods = Utility::orderedGoods($em, $field,$order);
                return Utility::createOkResponse($request, $goods);
                } 
                
                
            } else {
                //if we have a value then we have to do the research
                //first we verify that value is ok depending on the field.
                
                $isValid = false;
                switch ($field) {
                    case "description":
                        $isValid = Utility::validateDescription($value);
                        break;
                    case "id":
                        $isValid = Utility::validateId($value);
                        break;
                    case "quantity":
                        $isValid = Utility::validateQuantity($value);
                        break;
                    case "price":
                        $isValid = Utility::validatePrice($value);
                }
                if(!$isValid) {
                    //if the value is not valid, we have to send an error 
                    $error = new Error(Utility::BAD_QUERY,
                            "Invalid ".$field."value in research query","");
                    return Utility::createBadFormatResponse($request, $error);
                }
                //let's do the research
                $goods = Utility::searchForGoods($em, $field, $value);
                return Utility::createOkResponse($request, $goods);
            }
            
        }
        elseif($count){
            $count = Utility::countGoods($em);
            return Utility::createOkResponse($request, $count);
        }
        else {
            
            //get all goods because there is no queryparam for order or research            
            $goods = $em->getRepository('AppBundle:Good')->findAll();
            return Utility::createOkResponse($request, $goods);
        }
    }

    // "get_goods"  [GET] /goods
   

    /**
     * This method searches the good id and then sends it back.
     * @param Request $request
     * @param int $id the id we are looking for
     * @return Response
     * @throws HttpException
     */
    public function getGoodAction(Request $request, $id) {
        //is the id valid?
        if(Utility::validateId($id)) {
            
            $good = $this->getDoctrine()
                    ->getRepository('AppBundle:Good')
                    ->find($id);
            if (!$good) {
                throw new HttpException(404, "No good found for id " . $id);
            }
            return Utility::createOkResponse($request, $good);
        } else {
            $error = new Error(Utility::BAD_QUERY, "No valid ".$id." value",
                    "id must be an integer, max 11 digits");
            return Utility::createBadFormatResponse($request, $error);
        }
    }

// "get_goods" [GET] /goods/{id} will be the root
    
    

    /**
     * This method handles the creation of a good object.
     * Infos are sent by the client in the request.
     * 
     * @param Request $request
     * @return Response
     * @throws HttpException
     */
    public function postGoodsAction(Request $request) {

        $jsonGood = $request->getContent();
        try {
            $newGood = Utility::getSerializer()->deserialize(
                    $jsonGood, 'AppBundle\Entity\Good', "json");
        } catch (Symfony\Component\Serializer\Exception\UnexpectedValueException
        $ex) {
            $error = new Error(Utility::BAD_JSON, "Error parsing json object!",
                    $ex -> getMessage());
            return Utility::createBadFormatResponse($request, $error);
        }

        //Here we're validating the new object, using assertions
        //from the annotations of Good entity
        $validator = $this->get("validator");
        $errors = $validator->validate($newGood);
        if (count($errors) > 0) {
            $error = new Error(Utility::BAD_JSON, 
                    "Error validating json object!",
                    (string)$errors);
            return Utility::createBadFormatResponse($request, $error);
        }
        
        //This control is for the id, because it is auto-generated and can't
        //be specified by the client
        if ($newGood->getId() != null) {
            $error = new Error(Utility::BAD_JSON, 
                    "Error validating json object!",
                    "id field is auto-generated!");
            return Utility::createBadFormatResponse($request, $error);
        }

        $em = $this->getDoctrine()->getManager();
        $em->persist($newGood);
        $em->flush();
        $response = new Response("Saved new good with id " . $newGood->getId());
        $response->prepare($request);
        return $response;
    }

    // "insert_good" [POST] /goods

    /**
     * This method handles the change of a good object.
     * @param Request $request
     * @param int $id the id of the good that have to be modified
     * @return Response
     * @throws HttpException
     */
    public function patchGoodsAction(Request $request, $id) {

        $jsonGood = $request->getContent();
        try {
            $newGood = Utility::getSerializer()->deserialize(
                    $jsonGood, 'AppBundle\Entity\Good', "json");
        } catch (Symfony\Component\Serializer\Exception\UnexpectedValueException
        $ex) {
            $error = new Error(Utility::BAD_JSON, "Error parsing json object!",
                    $ex -> getMessage());
            return Utility::createBadFormatResponse($request, $error);
        }
        //This control is for the id, because it is auto-generated and can't
        //be specified by the client
        if ($newGood->getId() != null) {
            $error = new Error(Utility::BAD_JSON, 
                    "Error validating json object!",
                    "id field is auto-generated!");
            return Utility::createBadFormatResponse($request, $error);
        }
        //Here we're validating the new object, using assertions
        //from the annotations of Good entity
        $validator = $this->get("validator");
        $errors = $validator->validate($newGood);
        if (count($errors) > 0) {
           $error = new Error(Utility::BAD_JSON, 
                    "Error validating json object!",
                    (string)$errors);
            return Utility::createBadFormatResponse($request, $error);
        }

        $em = $this->getDoctrine()->getManager();
        $oldGood = $em->getRepository('AppBundle:Good')->find($id);
        if (!$oldGood) {
            throw new HttpException(404, "No good found for id" . $id);
        }
        $oldGood->setDescription($newGood->getDescription());
        $oldGood->setQuantity($newGood->getQuantity());
        $oldGood->setPrice($newGood->getPrice());
        $em->flush();
        $response = new Response("Updated good with id " . $id);
        $response->prepare($request);
        return $response;
    }

    // "modify_good" [PUT] /goods/{id}

    /**
     * This method handles the deletion of a good object.
     * @param Request $request
     * @param type $id the id of the good that have to be deleted
     * @return Response
     * @throws HttpException
     */
    public function deleteGoodsAction(Request $request, $id) {

        $em = $this->getDoctrine()->getManager();
        //if the id is ok
        if(Utility::validateId($id)) {
            //find the good with the id specified in the request
            $good = $em->getRepository('AppBundle:Good')->find($id);
            if (!$good) {
                throw new HttpException(404, "No good found for id" . $id);
            }
            $em->remove($good);
            $em->flush();
            $response = new Response("Deleted good with id " . $id);
            $response->prepare($request);
            return $response;
        } else {
            $error = new Error(Utility::BAD_QUERY, "No valid ".$id." value",
                    "id must be an integer, max 11 digits");
            return Utility::createBadFormatResponse($request, $error);
        }
    }

    // "delete_good" [DELETE] /goods/{id}
}
