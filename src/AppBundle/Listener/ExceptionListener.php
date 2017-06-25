<?php
namespace AppBundle\Listener;

use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Doctrine\DBAL\DBALException as DBException;

class ExceptionListener
{
    /**
     * This class is an EventListener that listens for particular ExceptionEvent,
     * and acts as a consequence sending custom HttpResponse with an \Error object
     * as a json.
     * @param GetResponseForExceptionEvent $event
     */
    public function onKernelException(GetResponseForExceptionEvent $event)
    {
        // You get the exception object from the received event
        $exception = $event->getException();
        //In this case, the event will be handled by JWTAuthBundle
        if($exception->getCode() === 403) {
            return;
        }
        // creating a message with infos of the event
        $message = sprintf(
            'My Error says: %s with code: %s',
            $exception->getMessage(),
            $exception->getCode()
        );
        $title = "Exception occured";
        // get the request from the event
        $request = $event -> getRequest();

        // is the exception due to pdo or db?
        // in both case we have a DB_ERROR, with a 500 http status code
        if ($exception instanceof \PDOException ||
                $exception instanceof DBException) {
            $type = \AppBundle\Utility::DB_ERROR;
            $status_code = Response::HTTP_INTERNAL_SERVER_ERROR;
            // we create the error 
            $error = new \AppBundle\Entity\Error($type, $title, $message);
            // we create the response containing infos of the error
            $response = \AppBundle\Utility::createErrorResponse($request, 
                        $error, 
                        $status_code);
        } else {
            //the error is not caused by the database, so let's do something else
            
            // HttpExceptionInterface is a special type of exception that
            // holds status code and header details     
            if($exception instanceof HttpExceptionInterface) {
                $status_code = $exception -> getStatusCode();
            } else {
                //if the exception is not an instance of HttpExceptionInterface,
                // I have not a status code so we set it with the 500 
                $status_code = Response::HTTP_INTERNAL_SERVER_ERROR;
            }
            $type = $status_code;
            // if the status code is 500, the type of error is a SERVER_ERROR
            if($status_code == Response::HTTP_INTERNAL_SERVER_ERROR) {
                $type = \AppBundle\Utility::SERVER_ERROR;
            }
            // let's create the error
            $error = new \AppBundle\Entity\Error($type, $title, $message);
            // we create the response containing infos of the error
            $response = \AppBundle\Utility::createErrorResponse($request, 
                        $error, 
                        $status_code);
        }

        // let's send the response 
        $event->setResponse($response);
    }
}

