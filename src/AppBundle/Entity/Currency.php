<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Currency
 *
 * @ORM\Table(name="currency")
 * @ORM\Entity(repositoryClass="AppBundle\Repository\CurrencyRepository")
 */
class Currency
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
     * @ORM\Column(name="currency_type", type="string", length=50, unique=true)
     */
    private $currencyType;


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
     * Set currencyType
     *
     * @param string $currencyType
     *
     * @return Currency
     */
    public function setCurrencyType($currencyType)
    {
        $this->currencyType = $currencyType;

        return $this;
    }

    /**
     * Get currencyType
     *
     * @return string
     */
    public function getCurrencyType()
    {
        return $this->currencyType;
    }
}

