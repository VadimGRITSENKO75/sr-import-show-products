<?php

namespace App\Domain;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'product_images')]
class ProductImage
{
    #[ORM\Id, ORM\Column(type: 'integer'), ORM\GeneratedValue(strategy: 'IDENTITY')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Product::class, inversedBy: 'images')]
    #[ORM\JoinColumn(nullable: false)]
    private Product $product;

    #[ORM\Column(type: 'string', length: 2048)]
    private string $url;

    #[ORM\Column(type: 'string', length: 2048)]
    private string $path;

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
    public function getUrl(): string
    {
        return $this->url;
    }
    public function setUrl(string $url): self
    {
        $this->url = $url;
        return $this;
    }
    public function getPath(): string
    {
        return $this->path;
    }
    public function setPath(string $path): self
    {
        $this->path = $path;
        return $this;
    }
}
