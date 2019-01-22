<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Products
 *
 * @ORM\Table(name="products")
 * @ORM\Entity(repositoryClass="AppBundle\Repository\ProductsRepository")
 */
class Products
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
     * @ORM\Column(name="name", type="text")
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(name="description", type="text")
     */
    private $description;

    /**
     * @var string
     *
     * @ORM\Column(name="price", type="decimal", precision=3, scale=10)
     */
    private $price;

    /**
     * @var int
     *
     * @ORM\Column(name="quanity", type="integer")
     */
    private $quanity;

    /**
     * @var int
     *
     * @ORM\Column(name="currency_type_mapping", type="integer")
     */
    private $currencyTypeMapping;

    /**
     * @var int
     *
     * @ORM\Column(name="is_valid", type="smallint")
     */
    private $isValid;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="reg_on", type="datetime")
     */
    private $regOn;


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
     * Set name
     *
     * @param string $name
     *
     * @return Products
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set description
     *
     * @param string $description
     *
     * @return Products
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Get description
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Set price
     *
     * @param string $price
     *
     * @return Products
     */
    public function setPrice($price)
    {
        $this->price = $price;

        return $this;
    }

    /**
     * Get price
     *
     * @return string
     */
    public function getPrice()
    {
        return $this->price;
    }

    /**
     * Set quanity
     *
     * @param integer $quanity
     *
     * @return Products
     */
    public function setQuanity($quanity)
    {
        $this->quanity = $quanity;

        return $this;
    }

    /**
     * Get quanity
     *
     * @return int
     */
    public function getQuanity()
    {
        return $this->quanity;
    }

    /**
     * Set currencyTypeMapping
     *
     * @param integer $currencyTypeMapping
     *
     * @return Products
     */
    public function setCurrencyTypeMapping($currencyTypeMapping)
    {
        $this->currencyTypeMapping = $currencyTypeMapping;

        return $this;
    }

    /**
     * Get currencyTypeMapping
     *
     * @return int
     */
    public function getCurrencyTypeMapping()
    {
        return $this->currencyTypeMapping;
    }

    /**
     * Set isValid
     *
     * @param integer $isValid
     *
     * @return Products
     */
    public function setIsValid($isValid)
    {
        $this->isValid = $isValid;

        return $this;
    }

    /**
     * Get isValid
     *
     * @return int
     */
    public function getIsValid()
    {
        return $this->isValid;
    }

    /**
     * Set regOn
     *
     * @param \DateTime $regOn
     *
     * @return Products
     */
    public function setRegOn($regOn)
    {
        $this->regOn = $regOn;

        return $this;
    }

    /**
     * Get regOn
     *
     * @return \DateTime
     */
    public function getRegOn()
    {
        return $this->regOn;
    }
}

