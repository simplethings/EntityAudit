<?php

namespace SimpleThings\Tests\EntityAudit\Fixtures\Relation;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\InheritanceType("JOINED")
 * @ORM\DiscriminatorColumn(name="discr", type="string")
 * @ORM\DiscriminatorMap({"food" = "FoodCategory", "books" = "BookCategory"})
 */
abstract class Category extends SomeEntity
{
    /** @ORM\OneToMany(targetEntity="Product", mappedBy="category") */
    private $products;

    public function __construct()
    {
        $this->products = new ArrayCollection();
    }

    public function addProduct(Product $product)
    {
        $product->setCategory($this);
        $this->products->add($product);
    }

    public function getProducts()
    {
        return $this->products;
    }
}
