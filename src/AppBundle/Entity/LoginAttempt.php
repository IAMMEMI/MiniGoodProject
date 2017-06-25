<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * LoginAttempt
 *
 * @ORM\Table(name="login_attempt")
 * @ORM\Entity(repositoryClass="AppBundle\Repository\LoginAttemptRepository")
 */
class LoginAttempt
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="ip_addr", type="string", length=500, nullable=true)
     */
    private $ipAddr;

    /**
     * @var string
     *
     * @ORM\Column(name="user_agent", type="string", length=500, nullable=true)
     */
    private $userAgent;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="login_timestamp", type="datetime", nullable=true)
     */
    private $loginTimestamp;


    /**
     * Get id
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set ipAddr
     *
     * @param string $ipAddr
     *
     * @return LoginAttempt
     */
    public function setIpAddr($ipAddr)
    {
        $this->ipAddr = $ipAddr;

        return $this;
    }

    /**
     * Get ipAddr
     *
     * @return string
     */
    public function getIpAddr()
    {
        return $this->ipAddr;
    }

    /**
     * Set userAgent
     *
     * @param string $userAgent
     *
     * @return LoginAttempt
     */
    public function setUserAgent($userAgent)
    {
        $this->userAgent = $userAgent;

        return $this;
    }

    /**
     * Get userAgent
     *
     * @return string
     */
    public function getUserAgent()
    {
        return $this->userAgent;
    }

    /**
     * Set loginTimestamp
     *
     * @param \DateTime $loginTimestamp
     *
     * @return LoginAttempt
     */
    public function setLoginTimestamp($loginTimestamp)
    {
        $this->loginTimestamp = $loginTimestamp;

        return $this;
    }

    /**
     * Get loginTimestamp
     *
     * @return \DateTime
     */
    public function getLoginTimestamp()
    {
        return $this->loginTimestamp;
    }
}

