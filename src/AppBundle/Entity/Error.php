<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace AppBundle\Entity;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * This class represents an error, that will be parsed as
 * json in the HttpResponse if an error is present.
 *
 * @author dezio
 */
class Error {
    
    /**
     *
     * @var string
     * @Assert\NotBlank()
     * 
     */
    private $type;
    
    /**
     *
     * @var string
     * @Assert\Type(
     *  type="string",
     *  message="the title must be a string"
     * )
     * @Assert\Length(max=200) 
     */
    private $title;
    
    /**
     *
     * @var string
     * @Assert\Type(
     *  type="string",
     *  message="the message must be a string"
     * )
     * @Assert\Length(max=300)
     */
    private $message;
    
    /**
     * The constructor of the Error object.
     * @param string $type
     * @param string $title
     * @param string $message
     */
    public function __construct($type, $title, $message) {
        
        $this -> type = $type;
        $this -> title = $title;
        $this -> message = $message;
        
    }
    
    /**
     * Getter method of the type attribute
     * @return string type
     */
    public function getType() {
        
        return $this ->type;
    
        
    }
    
    /**
     * Setter method of the type attribute
     * @param string type
     * @return $this
     */
    public function setType($type) {

        $this -> type = $type;
        return $this;
        
    }
    
    /**
     * Getter method of the title attribute
     * @return string title
     */
    public function getTitle() {
        
        return $this ->title;
        
    }
    
    /**
     * Setter method of the title attribute
     * @param string title
     * @return $this
     */
    public function setTitle($title) {
        
        $this -> title = $title;
        return $this;
        
    }
    
    /**
     * Getter method of the message attribute
     * @return string message
     */
    public function getMessage() {
        
        return $this -> message;
        
    }
    
    /**
     * Setter method of the message attribute
     * @param string message
     * @return $this
     */
    public function setMessage($message) {
        
        $this -> message = $message;
        return $this;
        
    }
    
    /**
     * This function validate the type property
     * @Assert\Callback
     * @param \AppBundle\Entity\ExecutionContextInterface $context
     * @param type $payload
     */
    public function validate(ExecutionContextInterface $context, $payload)
    {
        $permittedTypes = array(\AppBundle\Utility::BAD_JSON,
            \AppBundle\Utility::BAD_QUERY,
            \AppBundle\Utility::DB_ERROR,
            \AppBundle\Utility::SERVER_ERROR
        );
        //Controlling that the type is one of the permitted types,
        //or a status code
        if(in_array($this -> type, $permittedTypes)) {
            return;
        } else {
            if(!preg_match("/(0-9)(0-9)(0-9)/", $this -> type)) {
                $context->buildViolation('The type must be one of the '
                        . 'permitted ones, or a status code!')
                ->atPath('type')
                ->addViolation();
            }
            return;
        }
        
        
    }
    
}
