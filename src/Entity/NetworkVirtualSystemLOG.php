<?php

namespace App\Entity;

use App\Entity\Traits\ActiveLOGTrait;
use App\Entity\Traits\DateLOGTrait;
use App\Entity\Traits\UserLOGTrait;
use App\Repository\NetworkVirtualSystemLOGRepository;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

#[ORM\Entity(repositoryClass: NetworkVirtualSystemLOGRepository::class)]
class NetworkVirtualSystemLOG
{
    use ActiveLOGTrait;
    use DateLOGTrait;
    use UserLOGTrait;
    
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: 'integer')]
    private ?int $idNetworkVirtualSystem = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(length: 10)]
    private ?string $action = null;

    #[ORM\ManyToOne]
    private ?Environment $environment = null;

    #[ORM\ManyToOne(inversedBy: 'networkVirtualSystems')]
    private ?NetworkVirtualSystemRole $role = null;

    #[ORM\ManyToMany(targetEntity: NetworkVirtualSystemRole::class)]
    private Collection $roleSecondary;

    public function __construct()
    {
        $this->roleSecondary = new ArrayCollection();
    }

    // GETTERS AND SETTERS
    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id): self
    {
        $this->id = $id;

        return $this;
    }

    public function getIdNetworkVirtualSystem(): ?int
    {
        return $this->idNetworkVirtualSystem;
    }


    public function setIdNetworkVirtualSystem(?int $idNetworkVirtualSystem): self
    {
        $this->idNetworkVirtualSystem = $idNetworkVirtualSystem;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getAction(): ?string
    {
        return $this->action;
    }

    public function setAction(?string $action): self
    {
        $this->action = $action;

        return $this;
    }

    public function getEnvironment(): ?Environment
    {
        return $this->environment;
    }

    public function setEnvironment(?Environment $environment): static
    {
        $this->environment = $environment;

        return $this;
    }

    
    public function getRole(): ?NetworkVirtualSystemRole
    {
        return $this->role;
    }

    public function setRole(?NetworkVirtualSystemRole $role): static
    {
        $this->role = $role;

        return $this;
    }

    /**
     * @return Collection<int, NetworkVirtualSystemRole>
     */
    public function getRoleSecondary(): Collection
    {
        return $this->roleSecondary;
    }

    public function addRoleSecondary(NetworkVirtualSystemRole $roleSecondary): static
    {
        if (!$this->roleSecondary->contains($roleSecondary)) {
            $this->roleSecondary->add($roleSecondary);
        }

        return $this;
    }

    public function removeRoleSecondary(NetworkVirtualSystemRole $roleSecondary): static
    {
        $this->roleSecondary->removeElement($roleSecondary);

        return $this;
    }

    public function setRoleSecondary(Collection $roleSecondary): self
    {
        $this->roleSecondary = $roleSecondary;

        return $this;
    }
}
