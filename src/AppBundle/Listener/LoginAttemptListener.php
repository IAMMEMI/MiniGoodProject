<?php

namespace AppBundle\Listener;

use Lexik\Bundle\JWTAuthenticationBundle\Response\JWTAuthenticationFailureResponse;
use AppBundle\Services\LoginAttemptHandler;

/**
 * It listens for a request to the 'fos_user_security_check' route, and log
 * the attempt using the AppBundle\Services\LoginAttemptHandler if it is failed.
 * It blocks the request if the handler is locked. Under certain conditions 
 * unlock the handler.
 */
class LoginAttemptListener {

    //The handler used for logging and checking the attempts
    private $loginAttemptHandler;

    public function __construct(LoginAttemptHandler $loginAttemptHandler) {

        $this->loginAttemptHandler = $loginAttemptHandler;
    }

    /**
     * This method handles each request and search specifically for a master
     * request with 'fos_user_security_check' as route. It handles the logic
     * for blocking a request after three consequent failed login attempts.
     * @param \Symfony\Component\HttpKernel\Event\GetResponseEvent $event
     */
    public function onKernelRequest($event) {

        $request = $event->getRequest();
        if (!$event->isMasterRequest()) {
            return;
        }
        if ($request->get("_route") === "fos_user_security_check") {
            $container = $this->loginAttemptHandler->getContainer();
            $username = $request->request->get('_username');
            $password = $request->request->get('_password');
            if ($this->loginAttemptHandler->lock($request)) {
                $this->blockLogin($event);
            } else if ($this->loginAttemptHandler->unlock($request)) {
                $this->loginAttemptHandler->clear($request);
            }
            // fetch your User from the data storage with $username and $password
            // if password is invalid, log it, otherwise clear the attempts table
            // because the login is successful
            $userManager = $container->get("fos_user.user_manager");
            $user = $userManager->findUserByUsername($username);
            if (!$container->get("security.password_encoder")->isPasswordValid($user, $password)) {
                $this->loginAttemptHandler->log($request);
            } else {
                $this->loginAttemptHandler->clear($request);
            }
            return;
        }
    }

    /**
     * Block the login if the handler is locked
     * @param \Symfony\Component\HttpKernel\Event\GetResponseEvent $event
     */
    private function blockLogin($event) {

        $event->stopPropagation();
        $response = new JWTAuthenticationFailureResponse(sprintf(
                        'You have reached the maximum number of login attempts.'
                        . ' Please try again in %s minutes.', 1), 401);
        $event->setResponse($response);
    }

}
