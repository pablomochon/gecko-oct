<?php

namespace App\Entity;

use App\Repository\UserLOGRepository;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\Collection;

#[ORM\Entity(repositoryClass: UserLOGRepository::class)]
#[ORM\HasLifecycleCallbacks()]
class UserLOG
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;
    
    #[ORM\Column(type: 'integer')]
    private $idUser;

    #[ORM\Column(type: 'string', length: 255)]
    private $name;

    #[ORM\Column(type: 'string', length: 180)]
    private $username;

    #[ORM\Column(type: 'json')]
    private $roles = [];

    /*#[ORM\Column(type: 'array', nullable: true)]
    private $rolesFixed = [];*/

    #[ORM\Column(type: 'string', length: 255)]
    private $email;

    #[ORM\Column(type: 'boolean')]
    private $active;

    #[ORM\Column(type: 'string', length: 10)]
    private $action;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private $UserLOG;

    #[ORM\Column(type: 'datetime_immutable')]
    private $DateLOG;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getIdUser(): ?int
    {
        return $this->idUser;
    }

    public function setIdUser(int $idUser): self
    {
        $this->idUser = $idUser;

        return $this;
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

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;

        return $this;
    }

    public function getRoles(): array
    {
        return array_unique($this->roles);
    }

    public function setRoles(array $roles): self
    {
        $this->roles = $roles;

        return $this;
    }

    /*public function getRolesFixed(): ?array
    {
        return $this->rolesFixed;
    }

    public function setRolesFixed(?array $rolesFixed): self
    {
        $this->rolesFixed = $rolesFixed;

        return $this;
    }*/

    public function isActive(): ?bool
    {
        return $this->active;
    }

    public function setActive(bool $active): self
    {
        $this->active = $active;

        return $this;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setUsername(string $username): self
    {
        $this->username = $username;

        return $this;
    }

    public function getAction(): ?string
    {
        return $this->action;
    }

    public function setAction(string $action): self
    {
        $this->action = $action;

        return $this;
    }

    public function getUserLOG(): ?User
    {
        return $this->UserLOG;
    }

    public function setUserLOG(?User $UserLOG): self
    {
        $this->UserLOG = $UserLOG;

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
