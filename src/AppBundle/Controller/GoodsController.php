<?php

namespace AppBundle\Controller;

use FOS\RestBundle\Controller\Annotations\View;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\HttpKernel\Exception\HttpException;
use FOS\RestBundle\Controller\Annotations\QueryParam;
use FOS\RestBundle\Controller\Annotations\RouteResource;
use FOS\RestBundle\Controller\FOSRestController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class GoodsController extends Controller {

    private $serializer;

    /**
     * This method creates a response using the Symfony serializer to turn 
     * input goods into json format: the output will be the body of the response
     * @param Array $goods, as an array of Good objects
     * @return Symfony\Component\HttpFoundation\Response response
     */
    private function createResponse($request, $goods) {

        $json = $this->serializer->serialize($goods, 'json');
        $response = new Response($json, Response::HTTP_OK, array('content-type' => 'application/json'));
        $response->prepare($request);
        $response->headers->set('Access-Control-Allow-Origin', '*');
        return $response;
    }

    /**
     * This function orders the goods if they are more than 20, otherwise
     * it returns simply all the goods as an array, 
     * leaving this task to the client
     * @param type $field
     * @param Doctrine\ORM\EntityManager $em
     * @return Array $goods
     * @throws HttpException
     */
    private function orderedGoods($em, $field) {

        //Facciamo una query per contare il numero di good da ordinare
        $query = $em->createQuery(
                'SELECT COUNT(g.id) '
                . 'FROM AppBundle:Good g');
        $count = $query->getResult();

        if ($count > 20) {
            //Come da specifica, verifichiamo se i goods sono maggiori di 20
            try {
                //Faccio una query per restituire i goods ordinati 
                //secondo il field. E' sicuro da sql Injection come
                //specificato nelle API
                $query = $em
                        ->getRepository('AppBundle:Good')
                        ->createQueryBuilder('p')
                        ->orderBy('p.' . $field, 'ASC')
                        ->getQuery();
                $goods = $query->getResult();
                return $goods;
            } catch (\Doctrine\ORM\Query\QueryException $ex) {
                //Un eccezione di questo tipo non si dovrebbe verificare,
                //comunque in ogni caso sarebbe un internal server error
                throw new HttpException(500, "Fatal Exception");
            }
        } else {
            //Altrimenti, ritorno i goods in blocco
            if ($count == 0) {
                throw new HttpException(404, "No good found");
            }
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
    private function searchForGoods($em, $field, $value) {

        $query = $em -> createQuery("SELECT p "
                  . 'FROM AppBundle\Entity\Good p '
                  . "WHERE p.".$field." = :value");
        $query -> setParameter('value', $value);
        $goods = $query->getResult();
        return $goods;
    }

    /**
     * This method validates the description field value used in searching
     * @param type $value as the description value
     * @return boolean true if there isn't any problem with the input value
     * @throws HttpException
     */
    private function validateDescription($value) {
        if (is_string($value)) {
            if (strlen($value) > 25)
                throw new HttpException(400, "Description value too long");
        } else
            throw new HttpException(400, "Description value must be a string");
        return true;
    }

    /**
     * This method validates the id field value used in searching
     * @param type $value as the id value
     * @return boolean true if there isn't any problem with the input value
     * @throws HttpException
     */
    private function validateId($value) {
        
        if (is_int($value)) {
            if (strlen((string) $value) > 11)
                throw new HttpException(400, "Max 11 digits for the id!");
        } else
            throw new HttpException(400, "Id value must be integer");
    }

    /**
     * This method validates the quantity field value used in searching
     * @param type $value as the quanity value
     * @return boolean true if there isn't any problem with the input value
     * @throws HttpException
     */
    private function validateQuantity($value) {
        if (is_int($value)) {
            if (strlen((string) $value) > 11)
                throw new HttpException(400, "Max 11 digits for the quantity!");
        } else
            throw new HttpException(400, "Quantity value must be integer");
        return true;
    }

    /**
     * This method validates the price field value used in searching
     * @param type $value as the price value
     * @return boolean true if there isn't any problem with the input value
     * @throws HttpException
     */
    private function validatePrice($value) {
        if (!is_double($value))
            throw new HttpException(400, "Price value must be double");
        return true;
    }

    public function __construct() {
        $encoder = array(new JsonEncoder());
        $normalizer = array(new ObjectNormalizer());
        $this->serializer = new Serializer($normalizer, $encoder);
    }

    /**
     * @QueryParam(name="field", default=null, nullable=true)
     * @QueryParam(name="value", default=null, nullable=true)
     * @param Request $request
     * @return type
     * @throws HttpException
     */
    public function getGoodsAction(Request $request) {


        $parameters = $request ->query;
        $em = $this->getDoctrine()->getManager();
        $properFields = array('id', 'description', 'quantity', 'price');
        $field = $parameters ->get("field");
        $value = $parameters -> get("value");
        if (!is_null($field)) {
            //Se viene specificato un campo, allora restituiamo i goods ordinati
            //Verifico che il campo nella queryString sia valido
            if (!in_array($field, $properFields))
                throw new HttpException(400,
                        "The field specified doesn't exist");
            if (is_null($value)) {
                //Se non viene specificato un valore, allora è richiesto
                //l'ordinamento
                $goods = $this -> orderedGoods($em, $field);
                return $this -> createResponse($request, $goods);
            } else {
                //Validiamo il value
                switch ($field) {
                    case "description":
                        $this -> validateDescription($value);
                        break;
                    case "id":
                        $this -> validateId($value);
                        break;
                    case "quantity":
                        $this -> validateQuantity($value);
                        break;
                    case "price":
                        $this -> validatePrice($value);
                }
                //Effettuiamo la ricerca
                $goods = $this -> searchForGoods($em, $field, $value);
                return $this -> createResponse($request, $goods);
            }
        } else {
            //Ritorno tutti i goods, in quanto non c'è una querystring che 
            //specifichi l'ordinamento o la ricerca
            $goods = $em->getRepository('AppBundle:Good')->findAll();
            return $this -> createResponse($goods);
        }



        /* $query = $goodRepository ->createQueryBuilder('p')
          ->where('p.'.$field.'='.$value)
          ->getQuery();
          $goods = $query->getResult(); */
    }

// "get_goods"  [GET] /goods

    public function getGoodAction(Request $request, $id) {
        $good = $this->getDoctrine()
                ->getRepository('AppBundle:Good')
                ->find($id);
        if (!$good) {
            throw new HttpException(404, "No good found for id " . $id);
        }
        $jsonGood = $this->serializer->serialize(
                $good, 'json');
        $response = new Response(
                $jsonGood, Response::HTTP_OK, array('content-type' => 'application/json')
        );
        $response->headers->set('Access-Control-Allow-Origin', '*');
        $response->prepare($request);
        return $response;
    }

// "get_goods" [GET] /goods/{id}

    public function postGoodsAction(Request $request) {

        $jsonGood = $request->getContent();
        try {
            $newGood = $this->serializer->deserialize(
                    $jsonGood, 'AppBundle\Entity\Good', "json");
        } catch (Symfony\Component\Serializer\Exception\UnexpectedValueException
        $ex) {
            throw new HttpException(400, "Error parsing json object");
        }

        $validator = $this->get("validator");
        $errors = $validator->validate($newGood);

        if (count($errors) > 0) {

            return new HttpException(400, "Error validatin Json object: " . (string) $errors);
        }

        $em = $this->getDoctrine()->getManager();
        $em->persist($newGood);
        $em->flush();
        $response = new Response("Saved new good with id " . $newGood->getId());
        $response->headers->set('Access-Control-Allow-Origin', '*');
        $response->prepare($request);
        return $response;
    }

// "insert_good" [POST] /goods

    public function patchGoodsAction(Request $request, $id) {
        $jsonGood = $request->getContent();
        try {
            $newGood = $this->serializer->deserialize(
                    $jsonGood, 'AppBundle\Entity\Good', "json");
        } catch (Symfony\Component\Serializer\Exception\UnexpectedValueException
        $ex) {
            throw new HttpException(400, "Error parsing json object");
        }
        $em = $this->getDoctrine()->getManager();
        $oldGood = $em->getRepository('AppBundle:Good')->find($id);
        if (!$oldGood) {
            throw new HttpException(400, "No good found for id" . $id);
        }
        $newDescription = $newGood->getDescription();
        if ($newDescription != NULL) {
            $oldGood->setDescription($newDescription);
        }
        $newQuantity = $newGood->getQuantity();
        if ($newQuantity != NULL) {
            $oldGood->setQuantity($newQuantity);
        }
        $newPrice = $newGood->getPrice();
        if ($newPrice != NULL) {
            $oldGood->setPrice($newPrice);
        }
        $em->flush();

        $response = new Response("Updated good with id " . $id);
        $response->headers->set('Access-Control-Allow-Origin', '*');
        $response->prepare($request);
        return $response;
    }

// "modify_good" [PUT] /goods/{id}

    public function deleteGoodsAction(Request $request, $id) {

        $em = $this->getDoctrine()->getManager();
        $good = $em->getRepository('AppBundle:Good')->find($id);
        if (!$good) {
            throw new HttpException(400, "no good found for id" . $id);
        }
        $em->remove($good);
        $em->flush();

        $response = new Response("Deleted good with id " . $id);
        $response->headers->set('Access-Control-Allow-Origin', '*');
        $response->prepare($request);
        return $response;
    }

// "delete_good" [DELETE] /goods/{id}
}
