<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Patch;
use App\Repository\NetworkVirtualSystemRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

#[ORM\Entity(repositoryClass: NetworkVirtualSystemRepository::class)]
#[UniqueEntity('name')]
#[ApiResource(
    operations: [
        new GetCollection(),
        new Get(),
        new Post(security: "is_granted('ROLE_ADMIN')"),
        new Patch(security: "is_granted('ROLE_ADMIN')"),
    ],
    normalizationContext: ['groups' => ['NetworkVirtualSystem:read']],
    denormalizationContext: ['groups' => ['NetworkVirtualSystem:write']],
)]
class NetworkVirtualSystem
{
    #[ORM\Id]
    #[ORM\Column]
    #[Groups(["NetworkVirtualSystem:read", "NetworkVirtualSystem:write"])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(["NetworkVirtualSystem:read", "NetworkVirtualSystem:write"])]
    private ?string $name = null;

    #[Assert\Type('bool')]
    #[ORM\Column(type: 'boolean')]
    #[Groups(["NetworkVirtualSystem:read", "NetworkVirtualSystem:write"])]
    private bool $active;

    #[ORM\ManyToOne(inversedBy: 'networkVirtualSystems')]
    #[Groups(["NetworkVirtualSystem:read", "NetworkVirtualSystem:write"])]
    private ?NetworkVirtualSystemRole $role = null;

    #[ORM\ManyToMany(targetEntity: NetworkVirtualSystemRole::class)]
    #[Groups(["NetworkVirtualSystem:read", "NetworkVirtualSystem:write"])]
    private Collection $roleSecondary;

    /**
     * @var Collection<int, ActivityType>
     */
    #[ORM\ManyToMany(targetEntity: ActivityType::class, mappedBy: 'networkVirtualSystems')]
    #[Groups(["NetworkVirtualSystem:read", "NetworkVirtualSystem:write"])]
    private Collection $activityTypes;

    #[ORM\ManyToOne(inversedBy: 'networkVirtualSystems')]
    #[Groups(["NetworkVirtualSystem:read", "NetworkVirtualSystem:write"])]
    private ?Environment $environment = null;

    #[ORM\ManyToOne(inversedBy: 'networkVirtualSystems')]
    #[Groups(["NetworkVirtualSystem:read", "NetworkVirtualSystem:write"])]
    private ?NetworkDevice $networkDevice = null;

    /**
     * @var Collection<int, NetworkInterface>
     */
    #[ORM\OneToMany(targetEntity: NetworkInterface::class, mappedBy: 'networkVirtualSystem')]
    private Collection $networkInterfaces;

    /**
     * @var Collection<int, MaintenanceContract>
     */
    #[ORM\ManyToMany(targetEntity: MaintenanceContract::class, mappedBy: 'networkVirtualSystems')]
    private Collection $maintenanceContracts;

    #[ORM\ManyToOne(inversedBy: 'networkVirtualSystems')]
    #[Groups(["NetworkVirtualSystem:read", "NetworkVirtualSystem:write"])]
    private ?DesiredMaintenance $desiredMaintenance = null;

    public function __construct()
    {
        $this->active = true;
        $this->activityTypes = new ArrayCollection();
        $this->networkInterfaces = new ArrayCollection();
        $this->roleSecondary = new ArrayCollection();
        $this->maintenanceContracts = new ArrayCollection();
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function setActive(bool $active): self
    {
        $this->active = $active;
        return $this;
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

    /**
     * @return Collection<int, ActivityType>
     */
    public function getActivityTypes(): Collection
    {
        return $this->activityTypes;
    }

    public function addActivityType(ActivityType $activityType): static
    {
        if (!$this->activityTypes->contains($activityType)) {
            $this->activityTypes->add($activityType);
            $activityType->addNetworkVirtualSystem($this);
        }

        return $this;
    }

    public function removeActivityType(ActivityType $activityType): static
    {
        if ($this->activityTypes->removeElement($activityType)) {
            $activityType->removeNetworkVirtualSystem($this);
        }

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

    public function getNetworkDevice(): ?NetworkDevice
    {
        return $this->networkDevice;
    }

    public function setNetworkDevice(?NetworkDevice $networkDevice): static
    {
        $this->networkDevice = $networkDevice;

        return $this;
    }

    /**
     * @return Collection<int, NetworkInterface>
     */
    public function getNetworkInterfaces(): Collection
    {
        return $this->networkInterfaces;
    }

    public function addNetworkInterface(NetworkInterface $networkInterface): static
    {
        if (!$this->networkInterfaces->contains($networkInterface)) {
            $this->networkInterfaces->add($networkInterface);
            $networkInterface->setNetworkVirtualSystem($this);
        }

        return $this;
    }

    public function removeNetworkInterface(NetworkInterface $networkInterface): static
    {
        if ($this->networkInterfaces->removeElement($networkInterface)) {
            // set the owning side to null (unless already changed)
            if ($networkInterface->getNetworkVirtualSystem() === $this) {
                $networkInterface->setNetworkVirtualSystem(null);
            }
        }

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

    /**
     * @return Collection<int, MaintenanceContract>
     */
    public function getMaintenanceContracts(): Collection
    {
        return $this->maintenanceContracts;
    }

    public function addMaintenanceContract(MaintenanceContract $maintenanceContract): static
    {
        if (!$this->maintenanceContracts->contains($maintenanceContract)) {
            $this->maintenanceContracts->add($maintenanceContract);
            $maintenanceContract->addNetworkVirtualSystem($this);
        }

        return $this;
    }

    public function removeMaintenanceContract(MaintenanceContract $maintenanceContract): static
    {
        if ($this->maintenanceContracts->removeElement($maintenanceContract)) {
            $maintenanceContract->removeNetworkVirtualSystem($this);
        }

        return $this;
    }

    public function getDesiredMaintenance(): ?DesiredMaintenance
    {
        return $this->desiredMaintenance;
    }

    public function setDesiredMaintenance(?DesiredMaintenance $desiredMaintenance): static
    {
        $this->desiredMaintenance = $desiredMaintenance;

        return $this;
    }
}
