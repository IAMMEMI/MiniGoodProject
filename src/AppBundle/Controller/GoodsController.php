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

/**
 * Class GoodsController handles all CRUD operations with good object.
 */
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
        return $response;
    }

    /**
     * This private function orders the goods if they are more than 20, otherwise
     * it returns all the goods as an array, leaving this task to the client
     * @param type $field
     * @param Doctrine\ORM\EntityManager $em
     * @return Array $goods
     * @throws HttpException
     */
    private function orderedGoods($em, $field) {

        //we create a query for having the number of goods 
        $query = $em->createQuery(
                'SELECT COUNT(g.id) '
                . 'FROM AppBundle:Good g');
        $count = $query->getResult();
        
        //if goods are more than 20 we have to order them 
        if ($count > 20) {
            //so goods are more than 20
            try {
                //let's create a query. This way we will have goods ordered by the field sent by the client.
                $query = $em
                        ->getRepository('AppBundle:Good')
                        ->createQueryBuilder('p')
                        ->orderBy('p.' . $field, 'ASC')
                        ->getQuery();
                $goods = $query->getResult();
                return $goods;
            } catch (\Doctrine\ORM\Query\QueryException $ex) {
                //the server encountered an unexpected condition which prevented it from fulfilling the request.
                throw new HttpException(500, "Fatal Exception: ". $ex->getMessage());
            }
        } else {
            //goods are less then 20, so nothing else have to be done by the server
            //if the number of goods is 0, a 404 not found exception is sent to the client
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
            if (strlen($value) > 25) {
                throw new HttpException(400, "Description value too long, it must be less than 25 characters");
            }
        } else {
            throw new HttpException(400, "Description value must be a string");
        }
        return true;
    }

    /**
     * This method validates the id field value used in searching
     * @param integer $value as the id value 
     * @return boolean true if there isn't any problem with the input value
     * @throws HttpException
     */
    private function validateId($value) {
        
        if(!is_null($value)) {
            if (is_numeric($value) && $value >=0) {
                if (strlen((string) $value) > 11) {
                    throw new HttpException(400, 
                            "11 is the maximum number of digits for the id");
                }
            } else {
                throw new HttpException(400, 
                        "Id value must be a positive integer");
            }
        }
        return true;
    }

    /**
     * This method validates the quantity field value used in searching
     * @param type $value as the quanity value
     * @return boolean true if there isn't any problem with the input value
     * @throws HttpException
     */
    private function validateQuantity($value) {
        if (is_int($value + 0) && $value >=0) {
            if (strlen((string) $value) > 11) {
                throw new HttpException(400, "For the quantity the maximum digits is 11.");
            }
        } else {
            throw new HttpException(400, "Quantity value must be a positive integer");
        }
        return true;
    }

    /**
     * This method validates the price field value used in searching. 
     * Value must be numeric and major than zero.
     * @param type $value as the price value
     * @return boolean true if there isn't any problem with the input value
     * @throws HttpException
     */
    private function validatePrice($value) {
        if (!is_numeric($value) || $value <0) {
            throw new HttpException(400, "Price value must be a positive double");
        }
        return true;
    }

    public function __construct() {
        $encoder = array(new JsonEncoder());
        $normalizer = array(new ObjectNormalizer());
        $this->serializer = new Serializer($normalizer, $encoder);
    }

    /**
     * This method returns an array of goods.
     * Depending on the number of the goods, they will be ordered or not. 
     * If the number of the goods is more than 20 and we have a field then we have to sort the array before we send it to the client.
     * Otherwise if goods are less than 20, we have to send it as they are.
     * 
     * @param Request $request
     * @return Response 
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
            if (!in_array($field, $properFields)) {
                throw new HttpException(400, "The field specified is not valid.");
            }
            if (is_null($value)) {
                //Se non viene specificato un valore, allora è richiesto
                //l'ordinamento
                $goods = $this -> orderedGoods($em, $field);
                return $this -> createResponse($request, $goods);
            } else {
                //se abbiamo un valore allora dobbiamo effettuare la ricerca
                //Validiamo il value a seconda del campo
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
            return $this -> createResponse($request, $goods);
        }
    }

// "get_goods"  [GET] /goods
    
    
/**
 * This method searches the good id and then sends it back.
 * @param Request $request
 * @param type $id the id we are looking for
 * @return Response
 * @throws HttpException
 */
    public function getGoodAction(Request $request, $id) {
        //controllo se l'id è valido
        $this ->validateId($id);
        
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

    /**
     * This method handles the creation of a good object.
     * Infos are sent by the client in the request.
     * 
     * @param Request $request
     * @return Response|HttpException
     * @throws HttpException
     */
    public function postGoodsAction(Request $request) {

        $jsonGood = $request->getContent();
        try {
            $newGood = $this->serializer->deserialize(
                    $jsonGood, 'AppBundle\Entity\Good', "json");
        } catch (Symfony\Component\Serializer\Exception\UnexpectedValueException
        $ex) {
            throw new HttpException(400, "Error parsing json object: ". $ex->getMessage());
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
        if($newGood -> getId() != null) {
            throw new HttpException(400, "Error validating Json object: "
                    ."id field is auto-generated!");
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

    /**
     * This method handles the change of a good object.
     * @param Request $request
     * @param type $id the id of the good that have to be modified
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
        $oldGood->setDescription($newGood -> getDescription());
        $oldGood->setQuantity($newGood -> getQuantity());
        $oldGood->setPrice($newGood -> getPrice());
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
