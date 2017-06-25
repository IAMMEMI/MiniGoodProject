<?php

namespace AppBundle\Listener;

use Lexik\Bundle\JWTAuthenticationBundle\Event\AuthenticationFailureEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Response\JWTAuthenticationFailureResponse;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use AppBundle\Services\LoginAttemptHandler;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Description of AuthenticationFailureListener
 *
 * @author dezio
 */
class LoginAttemptListener {

    private $loginAttemptHandler;

    public function __construct(LoginAttemptHandler $loginAttemptHandler) {

        $this->loginAttemptHandler = $loginAttemptHandler;
    }

    /**
     * @param 
     */
    public function onKernelRequest($event) {

        $request = $event->getRequest();
        if (!$event->isMasterRequest()) {
            // don't do anything if it's not the master request
            return;
        }
        if ($request->get("_route") === "fos_user_security_check") {
            $container = $this->loginAttemptHandler->getContainer();
            $username = $request->request->get('_username');
            $password = $request->request->get('_password');
            //print_r($username . $password);
            if ($this->loginAttemptHandler->lock($request)) {
                $event->stopPropagation();
                $response = new JWTAuthenticationFailureResponse(sprintf(
                        'You have reached the maximum number of login attempts.'
                        . ' Please try again in %s minutes.', 1), 401);
                print_r(var_dump($response -> getStatusCode()));
                $event->setResponse($response);
            } else if ($this->loginAttemptHandler->unlock($request)) {
                $this->loginAttemptHandler->clear($request);
            }


            // fetch your User from the data storage with $username and $password
            // if password is invalid, log it..
            $userManager = $container->get("fos_user.user_manager");
            $user = $userManager->findUserByUsername($username);

            if (!$container->get("security.password_encoder")->isPasswordValid($user, $password)) {
                $this->loginAttemptHandler->log($request);
                return;
            }
            $this->loginAttemptHandler->clear($request);
            return;
        }
    }

}
