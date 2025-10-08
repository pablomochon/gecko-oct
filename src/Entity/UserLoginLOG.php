<?php

namespace App\Entity;

use App\Repository\UserLoginLOGRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: UserLoginLOGRepository::class)]
#[ORM\HasLifecycleCallbacks()]
class UserLoginLOG
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'userLoginLOGs')]
    private ?User $user = null;

    #[ORM\Column]
    private ?bool $success = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $message = null;

    #[ORM\Column(length: 50)]
    private ?string $firewall = null;

    #[ORM\Column(length: 100)]
    private ?string $authenticator = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private $DateLOG;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function isSuccess(): ?bool
    {
        return $this->success;
    }

    public function setSuccess(bool $success): self
    {
        $this->success = $success;

        return $this;
    }

    public function getMessage(): ?string
    {
        return $this->message;
    }

    public function setMessage(?string $message): self
    {
        $this->message = $message;

        return $this;
    }

    public function getFirewall(): ?string
    {
        return $this->firewall;
    }

    public function setFirewall(string $firewall): self
    {
        $this->firewall = $firewall;

        return $this;
    }

    public function getAuthenticator(): ?string
    {
        return $this->authenticator;
    }

    public function setAuthenticator(string $authenticator): self
    {
        $this->authenticator = $authenticator;

        return $this;
    }

    public function getDateLOG(): ?\DateTimeInterface
    {
        return $this->DateLOG;
    }
    
    #[ORM\PrePersist]
    public function setDateLOGValue(): void
    {
        $this->DateLOG = new \DateTimeImmutable();
    }
}
