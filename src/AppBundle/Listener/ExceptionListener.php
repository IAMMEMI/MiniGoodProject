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
        $message = sprintf(
            'My Error says: %s with code: %s',
            $exception->getMessage(),
            $exception->getCode()
        );
        $title = "Exception occured";
        $request = $event -> getRequest();

        // HttpExceptionInterface is a special type of exception that
        // holds status code and header details           
        if ($exception instanceof \PDOException ||
                $exception instanceof DBException) {
            $type = \AppBundle\Utility::DB_ERROR;
            $status_code = Response::HTTP_INTERNAL_SERVER_ERROR;
            $error = new \AppBundle\Entity\Error($type, $title, $message);
            $response = \AppBundle\Utility::createErrorResponse($request, 
                        $error, 
                        $status_code);
        } else {
            
            if($exception instanceof HttpExceptionInterface) {
                $status_code = $exception -> getStatusCode();
            } else {
                $status_code = Response::HTTP_INTERNAL_SERVER_ERROR;
            }
            $type = $status_code;
            if($status_code == Response::HTTP_INTERNAL_SERVER_ERROR) {
                $type = \AppBundle\Utility::SERVER_ERROR;
            }
            $error = new \AppBundle\Entity\Error($type, $title, $message);
            $response = \AppBundle\Utility::createErrorResponse($request, 
                        $error, 
                        $status_code);
        }

        // Send the modified response object to the event
        $event->setResponse($response);
    }
}

