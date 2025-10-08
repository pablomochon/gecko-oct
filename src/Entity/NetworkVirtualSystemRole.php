<?php

namespace App\Entity;

use App\Repository\NetworkVirtualSystemRoleRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Doctrine\ORM\Mapping as ORM;
use ApiPlatform\Metadata\ApiResource;
use Symfony\Component\Serializer\Annotation\Groups;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Serializer\Filter\PropertyFilter;

#[UniqueEntity('name')]
#[ORM\Entity(repositoryClass: NetworkVirtualSystemRoleRepository::class)]
#[ApiResource(
    operations: [
        new Post(security: "is_granted('ROLE_ADMIN')"),
        new Get(),
        new Patch(security: "is_granted('ROLE_ADMIN')"),
        new GetCollection(),
    ],
    normalizationContext: ['groups' => ['NetworkVirtualSystemRole:read']],
    denormalizationContext: ['groups' => ['NetworkVirtualSystemRole:write']],
)]
#[ApiFilter(PropertyFilter::class)]
#[ORM\Cache(usage: 'NONSTRICT_READ_WRITE')]
class NetworkVirtualSystemRole
{
    #[ORM\Id]
    #[ORM\Column]
    #[Groups(["NetworkVirtualSystemRole:read", "NetworkVirtualSystemRole:write"])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(["NetworkVirtualSystemRole:read", "NetworkVirtualSystemRole:write"])]
    private ?string $name = null;

    #[ORM\Column(length: 10)]
    #[Groups(["NetworkVirtualSystemRole:read", "NetworkVirtualSystemRole:write"])]
    private ?string $code = null;

    #[ORM\OneToMany(mappedBy: 'role', targetEntity: NetworkVirtualSystem::class)]
    private Collection $networkVirtualSystems;

    public function __construct()
    {
        $this->networkVirtualSystems = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): static
    {
        $this->id = $id;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(string $code): static
    {
        $this->code = $code;

        return $this;
    }

    /**
     * @return Collection<int, NetworkVirtualSystem>
     */
    public function getNetworkVirtualSystems(): Collection
    {
        return $this->networkVirtualSystems;
    }

    public function addNetworkVirtualSystem(NetworkVirtualSystem $networkVirtualSystem): static
    {
        if (!$this->networkVirtualSystems->contains($networkVirtualSystem)) {
            $this->networkVirtualSystems->add($networkVirtualSystem);
            $networkVirtualSystem->setRole($this);
        }

        return $this;
    }

    public function removeNetworkVirtualSystem(NetworkVirtualSystem $networkVirtualSystem): static
    {
        if ($this->networkVirtualSystems->removeElement($networkVirtualSystem)) {
            // set the owning side to null (unless already changed)
            if ($networkVirtualSystem->getRole() === $this) {
                $networkVirtualSystem->setRole(null);
            }
        }

        return $this;
    }
}
