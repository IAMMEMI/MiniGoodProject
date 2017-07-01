<?php

namespace AppBundle\Services;

use Doctrine\ORM\EntityManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use AppBundle\Entity\LoginAttempt;

/**
 * This class is the service responsible for logging login attempts to a 
 * database. It has two states: locked and unlocked. When it is locked, the 
 * login attempts are blocked; otherwise, clients can attempt to login.
 */
class LoginAttemptHandler {

    //reference to the symfony service container
    protected $container;
    //reference to the entity manager
    protected $em;
    //time interval to wait if the handler become locked
    protected $login_attempt_minutes;
    //max login attempts possible
    protected $max_login_attempts;

    /**
     * The constructor. Symfony handles all the input parameters, they are
     * specified in the services.yml file.
     *
     * @param \Doctrine\ORM\EntityManager $em
     */
    public function __construct(ContainerInterface $container, EntityManager $em, $login_attempt_minutes = 1, $max_login_attempts = 3) {
        $this->container = $container;
        $this->em = $em;
        $this->max_login_attempts = $max_login_attempts;
        $this->login_attempt_minutes = $login_attempt_minutes;
    }

    /**
     * Return the service container.
     * @return \Symfony\Component\DependencyInjection\ContainerInterface
     */
    public function getContainer() {
        return $this->container;
    }

    /**
     * @param \Symfony\Component\HttpFoundation\Request $request
     * It logs a request to the db, 
     * @throws \Exception
     */
    public function log(Request $request) {
        try {

            $now = new \DateTime();
            $now->setTimezone(new \DateTimeZone($this->container->getParameter('timezone')));
            $loginAttempt = new LoginAttempt();
            $loginAttempt->setIpAddr($request->getClientIp());
            $loginAttempt->setUserAgent($request->headers->get('User-Agent'));
            $loginAttempt->setLoginTimestamp($now);
            $this->em->persist($loginAttempt);
            $this->em->flush();
            /* $conn = $this->em->getConnection();
              $conn->insert('login_attempt', [
              'ip_addr' => $request->getClientIp(),
              'user_agent' => $request->headers->get('User-Agent'),
              'login_timestamp' => $now->format('Y-m-d H:i:s')
              ]); */
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * @param \Symfony\Component\HttpFoundation\Request $request
     * Clear the db of all the requests made by this ip. It is called when 
     * a login attempt is successful.
     * @throws \Exception
     */
    public function clear(Request $request) {
        try {
            $loginAttempts = $this->get($request);
            foreach ($loginAttempts as $loginAttempt) {
                $this->em->remove($loginAttempt);
            }
            $this->em->flush();
            //$sql = "DELETE FROM login_attempt WHERE ip_addr = :ip_addr";
            //$params = array('ip_addr' => $request->getClientIp());
            //$stmt   = $this->em->getConnection()->prepare($sql);
            //$stmt->execute($params);
        } catch (\Exception $e) {
            //The exception will be handled by the listener
            throw $e;
        }
    }

    /**
     * @param \Symfony\Component\HttpFoundation\Request $request
     * Fetch the attempts with the same ip address as the source ip of the 
     * request
     * @return array $attempts
     * @throws \Exception
     */
    public function get(Request $request) {
        try {

            /* $conn = $this->em->getConnection();
              $sql = 'SELECT * FROM login_attempt WHERE ip_addr = :ip_addr';
              $params = array('ip_addr' => $request->getClientIp());
              $stmt = $conn->prepare($sql);
              $stmt->execute($params);
              $attempts = $stmt->fetchAll(); */
            $actualIpAddr = $request->getClientIp();
            $attempts = $this->em->getRepository("AppBundle:LoginAttempt")
                    ->findBy(array('ipAddr' => $actualIpAddr));
            return $attempts;
        } catch (\Exception $e) {
            //The exception will be handled by the listener
            throw $e;
        }
    }

    /**
     * @param array $attempts
     * Return the time passed in minutes since the last login attempt. It is used
     * to understand if the handler can be unlocked.
     * @return int
     * @throws \Exception
     */
    public function getLockoutMinutes(array $attempts = array()) {
        try {

            $now = new \DateTime();
            $now->setTimezone(new \DateTimeZone($this->container->getParameter('timezone')));
            $minutes = 0;
            if(!empty($attempts)) {
                $lastAttempt = $attempts[sizeof($attempts) - 1];
                $loginAttemptTime = $lastAttempt -> getLoginTimeStamp();
                $interval = $now->diff($loginAttemptTime);
                $minutes = $interval->format('%i');
            }
            return $minutes;
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * @param \Symfony\Component\HttpFoundation\Request $request
     * This function returns true if it successfully locks the handler, false 
     * otherwise. In the first case, login attempts must be blocked.
     * @return bool
     * @throws \Exception
     */
    public function lock(Request $request) {
        try {

            $attempts = $this->get($request);
            $minutes = $this->getLockoutMinutes($attempts);
                return (count($attempts) >= $this->max_login_attempts 
                        && $minutes <= $this->login_attempt_minutes) ? true : false;
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * @param \Symfony\Component\HttpFoundation\Request $request
     * This function return true if it successfully unlocks the handler.
     * false otherwise.
     * @return bool
     * @throws \Exception
     */
    public function unlock(Request $request) {
        try {

            $attempts = $this->get($request);
            $minutes = $this->getLockoutMinutes($attempts);
            return (count($attempts) >= $this->max_login_attempts 
                    && $minutes > $this->login_attempt_minutes) ? true : false;
        } catch (\Exception $e) {
            throw $e;
        }
    }
    

}
