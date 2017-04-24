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
use AppBundle\Entity\Error;

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

        $field = $parameters->get("field");
        $order = $parameters->get("order");
        $value = $parameters->get("value");
        //We can use is_null because the variable is already declared
        //isset can be used also for unknown variables
        if (is_null($value) && !is_null($field)) {
            //if value is null then we have to order goods
            $result = Utility::orderedGoods($em, $field, $order);
        } else if (!is_null($value) && !is_null($field)) {
            //if we have a value then we have to do the research                
            //let's do the research
            $result = Utility::searchForGoods($em, $field, $value, $order);
        } else {
            $result = Utility::getAllGoods($em);
        }
        if (!is_array($result) && get_class($result) == Error::class) {
            return Utility::createBadFormatResponse($request, $result);
        }
        return Utility::createOkResponse($request, $result);
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
        if (Utility::validateId($id)) {

            $good = $this->getDoctrine()
                    ->getRepository('AppBundle:Good')
                    ->find($id);
            if (!$good) {
                throw new HttpException(404, "No good found for id " . $id);
            }
            return Utility::createOkResponse($request, $good);
        } else {
            $error = new Error(Utility::BAD_QUERY, "No valid " . $id . " value", "id must be an integer, max 11 digits");
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
            $error = new Error(Utility::BAD_JSON, "Error parsing json object!", $ex->getMessage());
            return Utility::createBadFormatResponse($request, $error);
        }

        //Here we're validating the new object, using assertions
        //from the annotations of Good entity
        $validator = $this->get("validator");
        $errors = $validator->validate($newGood);
        if (count($errors) > 0) {
            $error = new Error(Utility::BAD_JSON, "Error validating json object!", (string) $errors);
            return Utility::createBadFormatResponse($request, $error);
        }

        //This control is for the id, because it is auto-generated and can't
        //be specified by the client
        if ($newGood->getId() != null) {
            $error = new Error(Utility::BAD_JSON, "Error validating json object!", "id field is auto-generated!");
            return Utility::createBadFormatResponse($request, $error);
        }

        $em = $this->getDoctrine()->getManager();
        $em->persist($newGood);
        $em->flush();
        return Utility::createOkResponse($request, $newGood);
    }

    // "insert_good" [POST] /goods

    /**
     * This method handles the change of a good object.
     * @param Request $request
     * @param int $id the id of the good that have to be modified
     * @return Response
     * @throws HttpException
     */
    public function putGoodsAction(Request $request, $id) {

        $jsonGood = $request->getContent();
        try {
            $newGood = Utility::getSerializer()->deserialize(
                    $jsonGood, 'AppBundle\Entity\Good', "json");
        } catch (Symfony\Component\Serializer\Exception\UnexpectedValueException
        $ex) {
            $error = new Error(Utility::BAD_JSON, "Error parsing json object!", $ex->getMessage());
            return Utility::createBadFormatResponse($request, $error);
        }
        //This control is for the id, because it is auto-generated and can't
        //be specified by the client
        if ($newGood->getId() != null) {
            $error = new Error(Utility::BAD_JSON, "Error validating json object!", "id field is auto-generated!");
            return Utility::createBadFormatResponse($request, $error);
        }
        //Here we're validating the new object, using assertions
        //from the annotations of Good entity
        $validator = $this->get("validator");
        $errors = $validator->validate($newGood);
        if (count($errors) > 0) {
            $error = new Error(Utility::BAD_JSON, "Error validating json object!", (string) $errors);
            return Utility::createBadFormatResponse($request, $error);
        }

        $em = $this->getDoctrine()->getManager();
        $dbGood = $em->getRepository('AppBundle:Good')->find($id);
        if (!$dbGood) {
            throw new HttpException(404, "No good found for id" . $id);
        }
        $dbGood->setDescription($newGood->getDescription());
        $dbGood->setQuantity($newGood->getQuantity());
        $dbGood->setPrice($newGood->getPrice());
        $em->flush();
        return Utility::createOkResponse($request, $dbGood);
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
        if (Utility::validateId($id)) {
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
            $error = new Error(Utility::BAD_QUERY, "No valid " . $id . " value", "id must be an integer, max 11 digits");
            return Utility::createBadFormatResponse($request, $error);
        }
    }

    // "delete_good" [DELETE] /goods/{id}
}
