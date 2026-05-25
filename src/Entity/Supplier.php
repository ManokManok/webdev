<?php

namespace App\Entity;

use App\Repository\SupplierRepository;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\Delete;

use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    operations: [
        new Get(),
        new GetCollection(),
        new Post(),
        new Put(),
        new Delete()
    ],
    normalizationContext: [
        'groups' => ['supplier:read']
    ],
    denormalizationContext: [
        'groups' => ['supplier:write']
    ]
)]

#[ORM\Entity(repositoryClass: SupplierRepository::class)]
class Supplier
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
        #[Groups(['supplier:read'])]

    private ?int $id = null;

    #[ORM\Column(length: 150)]
    #[Groups(['supplier:read', 'supplier:write'])]
    private ?string $name = null;

    #[ORM\Column(length: 150, nullable: true)]
    #[Groups(['supplier:read', 'supplier:write'])]

    private ?string $contact = null;

    #[ORM\OneToMany(mappedBy: 'supplier', targetEntity: Product::class)]
        #[Groups(['supplier:read', 'supplier:write'])]

    private Collection $products;

    #[ORM\OneToMany(mappedBy: 'supplier', targetEntity: Stock::class)]
        #[Groups(['supplier:read', 'supplier:write'])]

    private Collection $stocks;

    public function __construct()
    {
        $this->products = new ArrayCollection();
        $this->stocks = new ArrayCollection();
    }

    public function getId(): ?int { return $this->id; }
    public function getName(): ?string { return $this->name; }
    public function setName(string $name): static { $this->name = $name; return $this; }
    public function getContact(): ?string { return $this->contact; }
    public function setContact(?string $contact): static { $this->contact = $contact; return $this; }

    /** @return Collection<int, Product> */
    public function getProducts(): Collection { return $this->products; }

    public function addProduct(Product $product): static
    {
        if (!$this->products->contains($product)) {
            $this->products->add($product);
            $product->setSupplier($this);
        }
        return $this;
    }

    public function removeProduct(Product $product): static
    {
        if ($this->products->removeElement($product)) {
            if ($product->getSupplier() === $this) {
                $product->setSupplier(null);
            }
        }
        return $this;
    }

    /** @return Collection<int, Stock> */
    public function getStocks(): Collection
    {
        return $this->stocks;
    }

    public function addStock(Stock $stock): static
    {
        if (!$this->stocks->contains($stock)) {
            $this->stocks->add($stock);
            $stock->setSupplier($this);
        }
        return $this;
    }

    public function removeStock(Stock $stock): static
    {
        if ($this->stocks->removeElement($stock)) {
            if ($stock->getSupplier() === $this) {
                $stock->setSupplier(null);
            }
        }
        return $this;
    }

    public function __toString(): string
    {
        return (string)($this->name ?? '');
    }
}
