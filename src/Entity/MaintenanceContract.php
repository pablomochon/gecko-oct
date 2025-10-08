<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Patch;
use App\Repository\MaintenanceContractRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use App\Validator\Constraints as AppAssert;


#[ORM\Entity(repositoryClass: MaintenanceContractRepository::class)]
#[ApiResource(
    operations: [
        new GetCollection(),
        new Post(security: "is_granted('ROLE_ADMIN')"),
        new Get(),
        new Patch(security: "is_granted('ROLE_ADMIN')"),
    ],
    normalizationContext: ['groups' => ['MaintenanceContract:read']],
    denormalizationContext: ['groups' => ['MaintenanceContract:write']]
)]
#[ORM\Cache(usage: 'NONSTRICT_READ_WRITE')]
#[AppAssert\EndDateGreaterThanStartDate(startDateField: 'startDate', endDateField: 'endDate')]
class MaintenanceContract
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(["MaintenanceContract:read"])]
    private ?int $id = null;

    #[Assert\NotBlank(message: 'name is required.')]
    #[Assert\Type(type: 'string', message: 'The name must be a string.')]
    #[ORM\Column(length: 255)]
    #[Groups(["MaintenanceContract:read", "MaintenanceContract:write"])]
    private ?string $name = null;

    #[Assert\NotBlank(message: 'start date is required.')]
    #[Assert\Type(type: 'DateTime', message: 'The start date must be a valid date.')]
    #[ORM\Column(type: Types::DATE_MUTABLE)]
    #[Groups(["MaintenanceContract:read", "MaintenanceContract:write"])]
    private ?\DateTimeInterface $startDate = null;

    #[Assert\NotBlank(message: 'end date is required.')]
    #[Assert\Type(type: 'DateTime', message: 'The end date must be a valid date.')]
    #[ORM\Column(type: Types::DATE_MUTABLE)]
    #[Groups(["MaintenanceContract:read", "MaintenanceContract:write"])]
    private ?\DateTimeInterface $endDate = null;

    #[Assert\NotBlank(message: 'manufacturer is required.')]
    #[Assert\Type(type: 'string', message: 'The manufacturer must be a string.')]
    #[ORM\Column(length: 255)]
    #[Groups(["MaintenanceContract:read", "MaintenanceContract:write"])]
    private ?string $manufacturer = null;

    #[Assert\NotBlank(message: 'provider is required.')]
    #[Assert\Type(type: 'string', message: 'The provider must be a string.')]
    #[ORM\Column(length: 255)]
    #[Groups(["MaintenanceContract:read", "MaintenanceContract:write"])]
    private ?string $provider = null;

    #[ORM\Column]
    #[Assert\NotBlank(message: 'status is required.')]
    #[Assert\Type(type: 'bool', message: 'The status must be a boolean value.')]
    #[Groups(["MaintenanceContract:read", "MaintenanceContract:write"])]
    private ?bool $status = null;

    #[Assert\NotBlank(message: 'cost is required.')]
    #[Assert\Type(type: 'numeric', message: 'The price must be a valid number.')]
    #[Assert\Positive(message: 'The price must be a positive value.')]
    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    #[Groups(["MaintenanceContract:read", "MaintenanceContract:write"])]
    private ?string $cost = null;

    #[Assert\Type(type: 'string', message: 'The notes must be a string.')]
    #[Assert\Length(max: 65535, maxMessage: 'Notes cannot exceed 65535 characters.')]
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(["MaintenanceContract:read", "MaintenanceContract:write"])]
    private ?string $notes = null;

    #[Assert\NotBlank(message: 'active status is required.')]
    #[Assert\Type(type: 'bool', message: 'The active status must be a boolean value.')]
    #[ORM\Column]
    #[Groups(["MaintenanceContract:read", "MaintenanceContract:write"])]
    private ?bool $active = null;

    /**
     * @var Collection<int, NetworkDevice>
     */
    #[ORM\ManyToMany(targetEntity: NetworkDevice::class, inversedBy: 'maintenanceContracts')]
    private Collection $networkDevices;

    /**
     * @var Collection<int, NetworkVirtualSystem>
     */
    #[ORM\ManyToMany(targetEntity: NetworkVirtualSystem::class, inversedBy: 'maintenanceContracts')]
    private Collection $networkVirtualSystems;

    public function __construct()
    {
        $this->networkDevices = new ArrayCollection();
        $this->networkVirtualSystems = new ArrayCollection();
        $this->active = true;
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getStartDate(): ?\DateTimeInterface
    {
        return $this->startDate;
    }

    public function setStartDate(\DateTimeInterface $startDate): static
    {
        $this->startDate = $startDate;

        return $this;
    }

    public function getEndDate(): ?\DateTimeInterface
    {
        return $this->endDate;
    }

    public function setEndDate(\DateTimeInterface $endDate): static
    {
        $this->endDate = $endDate;

        return $this;
    }

    public function getManufacturer(): ?string
    {
        return $this->manufacturer;
    }

    public function setManufacturer(string $manufacturer): static
    {
        $this->manufacturer = $manufacturer;

        return $this;
    }

    public function getProvider(): ?string
    {
        return $this->provider;
    }

    public function setProvider(string $provider): static
    {
        $this->provider = $provider;

        return $this;
    }

    public function isStatus(): ?bool
    {
        return $this->status;
    }

    public function setStatus(bool $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getCost(): ?string
    {
        return $this->cost;
    }

    public function setCost(string $cost): static
    {
        $this->cost = $cost;

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

    /**
     * @return Collection<int, NetworkDevice>
     */
    public function getNetworkDevices(): Collection
    {
        return $this->networkDevices;
    }

    public function addNetworkDevice(NetworkDevice $networkDevice): static
    {
        if (!$this->networkDevices->contains($networkDevice)) {
            $this->networkDevices->add($networkDevice);
        }

        return $this;
    }

    public function removeNetworkDevice(NetworkDevice $networkDevice): static
    {
        $this->networkDevices->removeElement($networkDevice);

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
        }

        return $this;
    }

    public function removeNetworkVirtualSystem(NetworkVirtualSystem $networkVirtualSystem): static
    {
        $this->networkVirtualSystems->removeElement($networkVirtualSystem);

        return $this;
    }

    /**
     * Get the value of active
     */
    public function isActive(): ?bool
    {
        return $this->active;
    }

    /**
     * Set the value of active
     */
    public function setActive(?bool $active): self
    {
        $this->active = $active;

        return $this;
    }
}
