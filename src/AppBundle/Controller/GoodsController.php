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
        $value = $parameters->get("value");
        $count = $parameters->get("count");
        if (!is_null($field)) {
            //Se viene specificato un campo, allora restituiamo i goods ordinati
            //Verifico che il campo nella queryString sia valido
            if (!in_array($field, $properFields)) {
                throw new HttpException(400, "The field specified is not valid.");
            }
            if (is_null($value)) {
                //Se non viene specificato un valore, allora è richiesto
                //l'ordinamento
                $goods = Utility::orderedGoods($em, $field);
                return Utility::createOkResponse($request, $goods);
            } else {
                //se abbiamo un valore allora dobbiamo effettuare la ricerca
                //Validiamo il value a seconda del campo
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
                    $error = new Error(Utility::BAD_QUERY,
                            "Invalid ".$field." in research query","");
                    Utility::createBadFormatResponse($request, $error);
                }
                //Effettuiamo la ricerca
                $goods = Utility::searchForGoods($em, $field, $value);
                return Utility::createOkResponse($request, $goods);
            }
        } 
        else {
            //Ritorno tutti i goods, in quanto non c'è una querystring che 
            //specifichi l'ordinamento o la ricerca
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
        //controllo se l'id è valido
        if(Utility::validateId($id)) {

            $good = $this->getDoctrine()
                    ->getRepository('AppBundle:Good')
                    ->find($id);
            if (!$good) {
                throw new HttpException(404, "No good found for id " . $id);
            }
            $jsonGood = Utility::getSerializer()->serialize(
                    $good, 'json');
            return Utility::createOkResponse($request, $jsonGood);
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
        if(Utility::validateId($id)) {
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
