<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * ProductBundle
 *
 * @ORM\Table(name="product_bundle")
 * @ORM\Entity(repositoryClass="AppBundle\Repository\ProductBundleRepository")
 */
class ProductBundle
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
     * @var int
     *
     * @ORM\Column(name="bundle_product_id", type="integer")
     */
    private $bundleProductId;

    /**
     * @var int
     *
     * @ORM\Column(name="product_id", type="integer")
     */
    private $productId;

    /**
     * @var int
     *
     * @ORM\Column(name="quantity", type="integer")
     */
    private $quantity;


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
     * Set bundleProductId
     *
     * @param integer $bundleProductId
     *
     * @return ProductBundle
     */
    public function setBundleProductId($bundleProductId)
    {
        $this->bundleProductId = $bundleProductId;

        return $this;
    }

    /**
     * Get bundleProductId
     *
     * @return int
     */
    public function getBundleProductId()
    {
        return $this->bundleProductId;
    }

    /**
     * Set productId
     *
     * @param integer $productId
     *
     * @return ProductBundle
     */
    public function setProductId($productId)
    {
        $this->productId = $productId;

        return $this;
    }

    /**
     * Get productId
     *
     * @return int
     */
    public function getProductId()
    {
        return $this->productId;
    }

    /**
     * Set quantity
     *
     * @param integer $quantity
     *
     * @return ProductBundle
     */
    public function setQuantity($quantity)
    {
        $this->quantity = $quantity;

        return $this;
    }

    /**
     * Get quantity
     *
     * @return int
     */
    public function getQuantity()
    {
        return $this->quantity;
    }
}

