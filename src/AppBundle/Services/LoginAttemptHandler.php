<?php

namespace AppBundle\Services;

use Doctrine\ORM\EntityManager;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
 
class LoginAttemptHandler
{
    protected $container;
    protected $em;
    protected $login_attempt_minutes;
    protected $max_login_attempts;
 
    /**
     * Constructor.
     *
     * @param \Doctrine\ORM\EntityManager $em
     */
    public function __construct(ContainerInterface $container, EntityManager $em, 
            $login_attempt_minutes = 1, $max_login_attempts = 3)
    {
        $this->container = $container;
        $this->em = $em;
        $this->max_login_attempts = $max_login_attempts;
        $this->login_attempt_minutes = $login_attempt_minutes;
    }
 

    public function getContainer() {
        return $this->container;
    }
 
    /**
     * @param \Symfony\Component\HttpFoundation\Request $request
     *
     * @throws \Exception
     */
    public function log(Request $request)
    {
        try {
 
            $now = new \DateTime();
            $now->setTimezone(new \DateTimeZone($this->container->getParameter('timezone')));
 
            $conn = $this->em->getConnection();
            $conn->insert('login_attempt', [
                'ip_addr' => $request->getClientIp(),
                'user_agent' => $request->headers->get('User-Agent'),
                'login_timestamp' => $now->format('Y-m-d H:i:s')
            ]);
 
        }
        catch(\Exception $e) {
            throw $e;
        }
    }
 
    /**
     * @param \Symfony\Component\HttpFoundation\Request $request
     *
     * @throws \Exception
     */
    public function clear(Request $request)
    {
        try {
            $sql = "DELETE FROM login_attempt WHERE ip_addr = :ip_addr";
 
            $params = array('ip_addr' => $request->getClientIp());
            $stmt   = $this->em->getConnection()->prepare($sql);
            $stmt->execute($params);
        }
        catch(\Exception $e) {
            throw $e;
        }
    }
 
    /**
     * @param \Symfony\Component\HttpFoundation\Request $request
     *
     * @return mixed
     * @throws \Exception
     */
    public function get(Request $request)
    {
        try {
 
            /**
             * If after three attempts you've still failed, you'll have to wait
             * 15 minutes to attempt to login again..
             */
            $conn = $this->em->getConnection();
            $sql = 'SELECT * FROM login_attempt WHERE ip_addr = :ip_addr';
            $params = array('ip_addr' => $request->getClientIp());
            $stmt = $conn->prepare($sql);
            $stmt->execute($params);
            $attempts = $stmt->fetchAll();
 
            return $attempts;
 
        }
        catch(\Exception $e) {
            throw $e;
        }
    }
 
    /**
     * @param array $attempts
     *
     * @return int|string
     * @throws \Exception
     */
    public function getLockoutMinutes(array $attempts = array())
    {
        try {
 
            $now = new \DateTime();
            $now ->setTimezone(new \DateTimeZone($this->container->getParameter('timezone')));
            $minutes = 0;
 
            foreach($attempts as $attempt) {
                $timestamp = $attempt['login_timestamp'];
                $loginAttempt = new \DateTime($timestamp);
                $interval = $now->diff($loginAttempt);
                $minutes = $interval->format('%i');
            }
 
            return $minutes;
 
        }
        catch(\Exception $e) {
            throw $e;
        }
    }
 
    /**
     * @param \Symfony\Component\HttpFoundation\Request $request
     *
     * @return bool
     * @throws \Exception
     */
    public function lock(Request $request)
    {
        try {
 
            $attempts = $this->get($request);
            $minutes = $this->getLockoutMinutes($attempts);
            return (count($attempts) >= 3 && $minutes <= 1) ? true : false;
 
        }
        catch(\Exception $e) {
            throw $e;
        }
    }
 
    /**
     * @param \Symfony\Component\HttpFoundation\Request $request
     *
     * @return bool
     * @throws \Exception
     */
    public function unlock(Request $request)
    {
        try {
 
            $attempts = $this->get($request);
            $minutes = $this->getLockoutMinutes($attempts);
            return (count($attempts) > $this->max_login_attempts && $minutes > $this->login_attempt_minutes) ? true : false;
 
        }
        catch(\Exception $e) {
            throw $e;
        }
    }
 
}