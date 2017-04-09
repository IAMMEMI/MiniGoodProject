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
                $goods = $this->orderedGoods($em, $field);
                return $this->createResponse($request, $goods);
            } else {
                //se abbiamo un valore allora dobbiamo effettuare la ricerca
                //Validiamo il value a seconda del campo
                switch ($field) {
                    case "description":
                        $this->validateDescription($value);
                        break;
                    case "id":
                        $this->validateId($value);
                        break;
                    case "quantity":
                        $this->validateQuantity($value);
                        break;
                    case "price":
                        $this->validatePrice($value);
                }
                //Effettuiamo la ricerca
                $goods = $this->searchForGoods($em, $field, $value);
                return $this->createResponse($request, $goods);
            }
        } 
        else {
            //Ritorno tutti i goods, in quanto non c'è una querystring che 
            //specifichi l'ordinamento o la ricerca
            $goods = $em->getRepository('AppBundle:Good')->findAll();
            return $this->createResponse($request, $goods);
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
        $this->validateId($id);

        $good = $this->getDoctrine()
                ->getRepository('AppBundle:Good')
                ->find($id);
        if (!$good) {
            throw new HttpException(404, "No good found for id " . $id);
        }
        $jsonGood = Utility::getSerializer()->serialize(
                $good, 'json');
        return Utility::createOkResponse($request, $jsonGood);
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
            $newGood = $this->serializer->deserialize(
                    $jsonGood, 'AppBundle\Entity\Good', "json");
        } catch (Symfony\Component\Serializer\Exception\UnexpectedValueException
        $ex) {
            throw new HttpException(400, "Error parsing json object: " . $ex->getMessage());
        }

        //Here we're validating the new object, using assertions
        //from the annotations of Good entity
        $validator = $this->get("validator");
        $errors = $validator->validate($newGood);
        if (count($errors) > 0) {
            throw new HttpException(400, "Error validatin Json object: "
            . (string) $errors);
        }
        //This control is for the id, because it is auto-generated and can't
        //be specified by the client
        if ($newGood->getId() != null) {
            throw new HttpException(400, "Error validating Json object: "
            . "id field is auto-generated!");
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
            $newGood = $this->serializer->deserialize(
                    $jsonGood, 'AppBundle\Entity\Good', "json");
        } catch (Symfony\Component\Serializer\Exception\UnexpectedValueException
        $ex) {
            throw new HttpException(400, "Error parsing json object");
        }
        //Here we're validating the new object, using assertions
        //from the annotations of Good entity
        $validator = $this->get("validator");
        $errors = $validator->validate($newGood);
        if (count($errors) > 0) {
            throw new HttpException(400, "Error validatin Json object: "
            . (string) $errors);
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
        $this->validateId($id);
        $good = $em->getRepository('AppBundle:Good')->find($id);
        if (!$good) {
            throw new HttpException(404, "No good found for id" . $id);
        }
        $em->remove($good);
        $em->flush();
        $response = new Response("Deleted good with id " . $id);
        $response->prepare($request);
        return $response;
    }

    // "delete_good" [DELETE] /goods/{id}
}
