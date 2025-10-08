<?php

namespace App\Entity;

use App\Repository\DesiredMaintenanceRepository;
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
#[ORM\Entity(repositoryClass: DesiredMaintenanceRepository::class)]
#[ApiResource(
    operations: [
        new Post(security: "is_granted('ROLE_ADMIN')"),
        new Get(),
        new Patch(security: "is_granted('ROLE_ADMIN')"),
        new GetCollection(),
    ],
    normalizationContext: ['groups' => ['DesiredMaintenance:read']],
    denormalizationContext: ['groups' => ['DesiredMaintenance:write']],
)]
#[ApiFilter(PropertyFilter::class)]
#[ORM\Cache(usage: 'NONSTRICT_READ_WRITE')]
class DesiredMaintenance
{
    #[ORM\Id]
    #[ORM\Column]
    #[Groups(["DesiredMaintenance:read", "DesiredMaintenance:write"])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(["DesiredMaintenance:read", "DesiredMaintenance:write"])]
    private ?string $name = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(["DesiredMaintenance:read", "DesiredMaintenance:write"])]
    private ?string $description = null;

    /**
     * @var Collection<int, NetworkDevice>
     */
    #[ORM\OneToMany(targetEntity: NetworkDevice::class, mappedBy: 'desiredMaintenance')]
    private Collection $networkDevice;

    /**
     * @var Collection<int, NetworkVirtualSystem>
     */
    #[ORM\OneToMany(targetEntity: NetworkVirtualSystem::class, mappedBy: 'desiredMaintenance')]
    private Collection $networkVirtualSystems;

    public function __construct()
    {
        $this->networkDevice = new ArrayCollection();
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

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    /**
     * @return Collection<int, NetworkDevice>
     */
    public function getNetworkDevice(): Collection
    {
        return $this->networkDevice;
    }

    public function addNetworkDevice(NetworkDevice $networkDevice): static
    {
        if (!$this->networkDevice->contains($networkDevice)) {
            $this->networkDevice->add($networkDevice);
            $networkDevice->setDesiredMaintenance($this);
        }

        return $this;
    }

    public function removeNetworkDevice(NetworkDevice $networkDevice): static
    {
        if ($this->networkDevice->removeElement($networkDevice)) {
            // set the owning side to null (unless already changed)
            if ($networkDevice->getDesiredMaintenance() === $this) {
                $networkDevice->setDesiredMaintenance(null);
            }
        }

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
            $networkVirtualSystem->setDesiredMaintenance($this);
        }

        return $this;
    }

    public function removeNetworkVirtualSystem(NetworkVirtualSystem $networkVirtualSystem): static
    {
        if ($this->networkVirtualSystems->removeElement($networkVirtualSystem)) {
            // set the owning side to null (unless already changed)
            if ($networkVirtualSystem->getDesiredMaintenance() === $this) {
                $networkVirtualSystem->setDesiredMaintenance(null);
            }
        }

        return $this;
    }
}
