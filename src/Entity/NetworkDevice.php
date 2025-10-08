<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Patch;
use App\Repository\NetworkDeviceRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

#[ORM\Entity(repositoryClass: NetworkDeviceRepository::class)]
#[UniqueEntity('serialNumber')]
#[ApiResource(
    operations: [
        new GetCollection(),
        new Get(),
        new Post(security: "is_granted('ROLE_ADMIN')"),
        new Patch(security: "is_granted('ROLE_ADMIN')"),
    ]
)]
class NetworkDevice
{
    #[ORM\Id]
    #[ORM\Column]
    #[Groups(["NetworkDevice:read", "NetworkDevice:write"])]
    private ?int $id = null;

    #[Groups(["NetworkDevice:read", "NetworkDevice:write"])]
    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(type: 'string', length: 90)]
    #[Groups(["NetworkDevice:read", "NetworkDevice:write"])]
    private $serialNumber;

    #[Assert\Type('bool')]
    #[ORM\Column(type: 'boolean')]
    #[Groups(["NetworkDevice:read", "NetworkDevice:write"])]
    private bool $active = true;

    /**
     * @var Collection<int, ActivityType>
     */
    #[Groups(["NetworkDevice:read", "NetworkDevice:write"])]
    #[ORM\ManyToMany(targetEntity: ActivityType::class, mappedBy: 'networkDevices')]
    private Collection $activityTypes;

    #[ORM\ManyToOne(inversedBy: 'networkDevices')]
    #[Groups(["NetworkDevice:read", "NetworkDevice:write"])]
    private Environment $environment;

    /**
     * @var Collection<int, NetworkVirtualSystem>
     */
    #[ORM\OneToMany(targetEntity: NetworkVirtualSystem::class, mappedBy: 'networkDevice')]
    private Collection $networkVirtualSystems;

    /**
     * @var Collection<int, MaintenanceContract>
     */
    #[ORM\ManyToMany(targetEntity: MaintenanceContract::class, mappedBy: 'networkDevices')]
    private Collection $maintenanceContracts;

    #[ORM\ManyToOne(inversedBy: 'networkDevice')]
    #[Groups(["NetworkDevice:read", "NetworkDevice:write"])]
    private ?DesiredMaintenance $desiredMaintenance = null;

    public function __construct()
    {
        $this->activityTypes = new ArrayCollection();
        $this->networkVirtualSystems = new ArrayCollection();
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
            $activityType->addNetworkDevice($this);
        }

        return $this;
    }

    public function removeActivityType(ActivityType $activityType): static
    {
        if ($this->activityTypes->removeElement($activityType)) {
            $activityType->removeNetworkDevice($this);
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
            $networkVirtualSystem->setNetworkDevice($this);
        }

        return $this;
    }

    public function removeNetworkVirtualSystem(NetworkVirtualSystem $networkVirtualSystem): static
    {
        if ($this->networkVirtualSystems->removeElement($networkVirtualSystem)) {
            // set the owning side to null (unless already changed)
            if ($networkVirtualSystem->getNetworkDevice() === $this) {
                $networkVirtualSystem->setNetworkDevice(null);
            }
        }

        return $this;
    }    

    /**
     * Get the value of serialNumber
     */
    public function getSerialNumber()
    {
        return $this->serialNumber;
    }

    /**
     * Set the value of serialNumber
     */
    public function setSerialNumber($serialNumber): self
    {
        $this->serialNumber = $serialNumber;

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
            $maintenanceContract->addNetworkDevice($this);
        }

        return $this;
    }

    public function removeMaintenanceContract(MaintenanceContract $maintenanceContract): static
    {
        if ($this->maintenanceContracts->removeElement($maintenanceContract)) {
            $maintenanceContract->removeNetworkDevice($this);
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
