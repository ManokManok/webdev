<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: UserRepository::class)]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['user:read'])]

    private ?int $id = null;

    #[ORM\Column(length: 180, unique: true)]
    #[Groups(['user:read', 'user:write'])]

    private ?string $username = null;

    #[ORM\Column(length: 180, unique: true)]
    #[Groups(['user:read', 'user:write'])]

    private ?string $email = null;

    #[ORM\Column(length: 100, nullable: true)]
    #[Groups(['user:read', 'user:write'])]

    private ?string $fullName = null;

    #[ORM\Column]
    #[Groups(['user:read', 'user:write'])]

    private array $roles = [];

    /**
     * @var string The hashed password
     */
    #[ORM\Column]

    private ?string $password = null;

    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => true])]
    #[Groups(['user:read', 'user:write'])]

    private ?bool $isActive = true;

    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => false])]
    #[Groups(['user:read', 'user:write'])]

    private ?bool $isArchived = false;

    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => false])]
    #[Groups(['user:read', 'user:write'])]

    private ?bool $isVerified = false;

    #[ORM\Column(length: 64, nullable: true)]

    private ?string $verificationToken = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['user:read', 'user:write'])]
    private ?string $googleId = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Groups(['user:read', 'user:write'])]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\OneToMany(mappedBy: 'managedBy', targetEntity: Stock::class)]

    #[Groups(['user:read', 'user:write'])]

    private Collection $managedStocks;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setUsername(string $username): static
    {
        $this->username = $username;

        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    public function setRoles(array $roles): static
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials(): void
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    public function getFullName(): ?string
    {
        return $this->fullName;
    }

    public function setFullName(?string $fullName): static
    {
        $this->fullName = $fullName;

        return $this;
    }

    public function getGoogleId(): ?string
    {
        return $this->googleId;
    }

    public function setGoogleId(?string $googleId): static
    {
        $this->googleId = $googleId;

        return $this;
    }

    public function isActive(): ?bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): static
    {
        $this->isActive = $isActive;

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

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->isActive = true;
        $this->isArchived = false;
        $this->isVerified = false;
        $this->roles = [];
        $this->managedStocks = new ArrayCollection();
    }

    public function hasRole(string $role): bool
    {
        return in_array($role, $this->getRoles(), true);
    }

    public function isAdmin(): bool
    {
        return $this->hasRole('ROLE_ADMIN');
    }

    public function isStaff(): bool
    {
        return $this->hasRole('ROLE_STAFF');
    }

    public function isArchived(): ?bool
    {
        return $this->isArchived;
    }

    public function setIsArchived(bool $isArchived): static
    {
        $this->isArchived = $isArchived;

        return $this;
    }

    public function isVerified(): ?bool
    {
        return $this->isVerified;
    }

    public function setIsVerified(bool $isVerified): static
    {
        $this->isVerified = $isVerified;

        return $this;
    }

    public function getVerificationToken(): ?string
    {
        return $this->verificationToken;
    }

    public function setVerificationToken(?string $verificationToken): static
    {
        $this->verificationToken = $verificationToken;

        return $this;
    }

    /** @return Collection<int, Stock> */
    public function getManagedStocks(): Collection
    {
        return $this->managedStocks;
    }

    public function addManagedStock(Stock $managedStock): static
    {
        if (!$this->managedStocks->contains($managedStock)) {
            $this->managedStocks->add($managedStock);
            $managedStock->setManagedBy($this);
        }

        return $this;
    }

    public function removeManagedStock(Stock $managedStock): static
    {
        if ($this->managedStocks->removeElement($managedStock)) {
            if ($managedStock->getManagedBy() === $this) {
                $managedStock->setManagedBy(null);
            }
        }

        return $this;
    }
}
