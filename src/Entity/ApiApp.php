<?php

namespace App\Entity;

use App\Repository\ApiAppRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ApiAppRepository::class)]
#[ORM\Cache(usage: 'NONSTRICT_READ_WRITE')]
class ApiApp
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[Assert\NotBlank]
    #[Assert\Valid]
    #[Assert\Type(User::class)]
    #[ORM\ManyToOne(inversedBy: 'apiApps')]
    #[ORM\JoinColumn(nullable: false)]
    private User $user;

    #[ORM\Column(length: 100)]
    private ?string $clientId = null;

    #[ORM\Column(length: 100)]
    private ?string $clientSecret = null;

    #[ORM\Column(length: 100)]
    private ?string $accessToken = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $expiresAt = null;

    public function __construct()
    {
        $this->clientId = bin2hex(random_bytes(30));
        $this->clientSecret = bin2hex(random_bytes(30));
        $this->accessToken = bin2hex(random_bytes(30));
        $this->expiresAt = new \DateTimeImmutable('+1 hour');
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function setUser(User $user): self
    {
        $this->user = $user;
        return $this;
    }

    public function getClientId(): ?string
    {
        return $this->clientId;
    }

    public function getClientSecret(): ?string
    {
        return $this->clientSecret;
    }

    public function getAccessToken(): ?string
    {
        return $this->accessToken;
    }

    public function getExpiresAt(): ?\DateTimeImmutable
    {
        return $this->expiresAt;
    }

    public function refreshToken()
    {
        $this->accessToken = bin2hex(random_bytes(30));
        $this->expiresAt = new \DateTimeImmutable('+1 hour');
    }
}
