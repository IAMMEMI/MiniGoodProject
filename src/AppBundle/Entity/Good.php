<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * This class represents a good, it is mapped to the table "goods"
 * using Doctrine
 *
 * @ORM\Table(name="goods")
 * @ORM\Entity(repositoryClass="AppBundle\Repository\GoodRepository")
 */
class Good
{
    /**
     * It's the identificator of the good within the database, it's
     * auto-generated when inserted.
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * 
     * 
     */
    private $id;

    /**
     * It's a short description of the good
     * @var string
     * @Assert\Type(
     *  type="string",
     *  message="the description must be a string"
     * )
     * @Assert\Length(
     *  max=25,
     *  maxMessage="Maximum length for a description is 25 chars"
     * )
     * @ORM\Column(name="description", type="string", length=25)
     */
    private $description;

    /**
     * It's the available quantity of the good
     * @var int
     * @Assert\NotNull()
     * @Assert\Type(
     *  type="integer",
     *  message="quantity must be an integer")
     * @Assert\Range(
     *  min = 1,
     *  minMessage = "At least one good")
     * @ORM\Column(name="quantity", type="integer")
     * 
     */
    private $quantity;

    /**
     * It's the unitary price of the good.
     * @var float
     * @Assert\Type(
     *  type="float",
     *  message="price must be a float"
     * )
     * @Assert\NotNull()
     * @Assert\Range(
     *    min = 0.01,
     *    minMessage = "There is no free good!"
     * )
     * @ORM\Column(name="price", type="float")
     */
    private $price;


    /**
     * Get id
     *
     * @return int id
     *  */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set description
     *
     * @param string $description
     *
     */
    public function setDescription($description)
    {
        $this->description = $description;
        return $this;
    }

    /**
     * Get description
     *
     * @return string description
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Set quantity
     *
     * @param integer $quantity
     *
     * @return Good instance as the object itself
     */
    public function setQuantity($quantity)
    {
        $this->quantity = $quantity;

        return $this;
    }

    /**
     * Get quantity
     *
     * @return int quantity
     */
    public function getQuantity()
    {
        return $this->quantity;
    }

    /**
     * Set price
     *
     * @param float $price
     *
     * @return Good instance as the object itself
     */
    public function setPrice($price)
    {
        $this->price = $price;

        return $this;
    }

    /**
     * Get price
     *
     * @return float price
     */
    public function getPrice()
    {
        return $this->price;
    }
    
    
}
