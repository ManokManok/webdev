<?php

namespace App\Entity;

use App\Repository\BookingRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: 'App\\Repository\\BookingRepository')]
class Booking
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['booking:read'])]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?User $user = null;

    #[ORM\ManyToOne(targetEntity: Product::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    #[Groups(['booking:read', 'booking:write'])]
    private ?Product $product = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Groups(['booking:read', 'booking:write'])]
    private ?\DateTimeInterface $scheduledAt = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['booking:read', 'booking:write'])]
    private ?string $notes = null;

    #[ORM\Column(length: 50)]
    #[Groups(['booking:read', 'booking:write'])]
    private ?string $status = 'PENDING';

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Groups(['booking:read'])]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\OneToOne(mappedBy: 'booking', targetEntity: CustomerOrder::class)]
    private ?CustomerOrder $order = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function getProduct(): ?Product
    {
        return $this->product;
    }

    public function setProduct(?Product $product): static
    {
        $this->product = $product;

        return $this;
    }

    public function getScheduledAt(): ?\DateTimeInterface
    {
        return $this->scheduledAt;
    }

    public function setScheduledAt(\DateTimeInterface $scheduledAt): static
    {
        $this->scheduledAt = $scheduledAt;

        return $this;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function setNotes(?string $notes): static
    {
        $this->notes = $notes;

        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getOrder(): ?CustomerOrder
    {
        return $this->order;
    }

    public function setOrder(?CustomerOrder $order): static
    {
        if ($order === null && $this->order !== null) {
            $this->order->setBooking(null);
        }

        if ($order !== null) {
            $order->setBooking($this);
        }

        $this->order = $order;

        return $this;
    }
}
