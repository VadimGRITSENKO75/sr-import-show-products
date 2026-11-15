<?php

namespace App\Domain;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'product_attributes')]
class ProductAttribute
{
    #[ORM\Id, ORM\Column(type: 'integer'), ORM\GeneratedValue(strategy: 'IDENTITY')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Product::class, inversedBy: 'attributes')]
    #[ORM\JoinColumn(nullable: false)]
    private Product $product;

    #[ORM\Column(type: 'string')]
    private string $key;

    #[ORM\Column(type: 'text')]
    private string $value;

    public function getId(): ?int
    {
        return $this->id;
    }
    public function getProduct(): Product
    {
        return $this->product;
    }
    public function setProduct(Product $product): self
    {
        $this->product = $product;
        return $this;
    }
    public function getKey(): string
    {
        return $this->key;
    }
    public function setKey(string $key): self
    {
        $this->key = $key;
        return $this;
    }
    public function getValue(): string
    {
        return $this->value;
    }
    public function setValue(string $value): self
    {
        $this->value = $value;
        return $this;
    }
}
