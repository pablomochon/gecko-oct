<?php

namespace App\Entity;

use App\Repository\ActivityTypeRepository;
use App\Validator\Constraints as AppAssert;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Patch;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

#[ORM\Entity(repositoryClass: ActivityTypeRepository::class)]
#[ApiResource(
    operations: [
        new GetCollection(),
        new Get(),
        new Post(security: "is_granted('ROLE_ADMIN') or is_granted('ROLE_ACTIVITY_TYPE_ROLE')"),
        new Patch(security: "is_granted('ROLE_ADMIN') or is_granted('ROLE_ACTIVITY_TYPE_ROLE')"),
    ],
    normalizationContext: ['groups' => ['ActivityType:read']],
    denormalizationContext: ['groups' => ['ActivityType:write']]
)]
#[ORM\Cache(usage: 'NONSTRICT_READ_WRITE')]
#[UniqueEntity('description')]
#[AppAssert\SingleRelationshipType]
class ActivityType
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    #[Groups(["ActivityType:read"])]
    private $id;

    #[Assert\NotBlank(message: 'Code is required.')]
    #[Assert\Type(type: 'string', message: 'The code must be a string.')]
    #[ORM\Column(type: 'string', length: 6, unique: true, nullable: false)]
    #[Groups(["ActivityType:read", "ActivityType:write"])]
    private $code;

    #[Assert\NotBlank(message: 'Description is required.')]
    #[Assert\Type(type: 'string', message: 'The description must be a string.')]
    #[ORM\Column(type: 'string', length: 64, unique: false, nullable: false)]
    #[Groups(["ActivityType:read", "ActivityType:write"])]
    private $description;

    #[Assert\NotBlank(message: 'Price is required.')]
    #[Assert\Type(type: 'numeric', message: 'The price must be a valid number.')]
    #[Assert\Positive(message: 'The price must be a positive value.')]
    #[ORM\Column(type: 'decimal', precision: 10, scale: 2, unique: false, nullable: false)]
    #[Groups(["ActivityType:read", "ActivityType:write"])]
    private $price;

    #[Assert\NotBlank(message: 'SAP name is required.')]
    #[Assert\Type(type: 'string', message: 'The SAP name must be a string.')]
    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(["ActivityType:read", "ActivityType:write"])]
    private ?string $SAPname = null;

    #[Assert\NotBlank(message: 'Price is required.')]
    #[Assert\Type(type: 'string', message: 'The type must be a string.')]
    #[Assert\Choice(choices: [
        'a',
        'b',
        'c',
        'other'
    ], message: 'Choose a valid type: a, b, c, other')]
    #[ORM\Column(length: 50)]
    #[Groups(["ActivityType:read", "ActivityType:write"])]
    private ?string $type = null;

    #[Assert\NotBlank(message: 'Active status is required.')]
    #[Assert\Type(type: 'boolean', message: 'The active status must be a boolean value.')]
    #[ORM\Column(type: 'boolean')]
    #[Groups(["Environment:read", "Environment:write"])]
    private ?bool $active = null;

    /**
     * @var Collection<int, NetworkDevice>
     */
    #[ORM\ManyToMany(targetEntity: NetworkDevice::class, inversedBy: 'activityTypes')]
    private Collection $networkDevices;

    /**
     * @var Collection<int, NetworkVirtualSystem>
     */
    #[ORM\ManyToMany(targetEntity: NetworkVirtualSystem::class, inversedBy: 'activityTypes')]
    private Collection $networkVirtualSystems;

    /**
     * @var Collection<int, NetworkInterface>
     */
    #[ORM\ManyToMany(targetEntity: NetworkInterface::class, inversedBy: 'activityTypes')]
    private Collection $networkInterfaces;

    public function __construct(string $code = '', string $description = '', string $price = '0.00')
    {
        $this->code = $code;
        $this->description = $description;
        $this->price = $price;
        $this->active = true;
        $this->networkDevices = new ArrayCollection();
        $this->networkVirtualSystems = new ArrayCollection();
        $this->networkInterfaces = new ArrayCollection();
    }

    // GETTERS AND SETTERS
    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(string $code): self
    {
        $this->code = $code;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;
        return $this;
    }

    public function getPrice(): ?string
    {
        return $this->price;
    }

    public function setPrice(string $price): self
    {
        $this->price = $price;
        return $this;
    }

    public function getSAPname(): ?string
    {
        return $this->SAPname;
    }

    public function setSAPname(?string $SAPname): static
    {
        $this->SAPname = $SAPname;

        return $this;
    }

    /**
     * @return Collection<int, networkDevice>
     */
    public function getNetworkDevices(): Collection
    {
        return $this->networkDevices;
    }

    public function addNetworkDevice(networkDevice $networkDevice): static
    {
        if (!$this->networkDevices->contains($networkDevice)) {
            $this->networkDevices->add($networkDevice);
        }

        return $this;
    }

    public function removeNetworkDevice(networkDevice $networkDevice): static
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
        }

        return $this;
    }

    public function removeNetworkInterface(NetworkInterface $networkInterface): static
    {
        $this->networkInterfaces->removeElement($networkInterface);

        return $this;
    }

    public function isActive(): ?bool
    {
        return $this->active;
    }

    public function setActive(bool $active): static
    {
        $this->active = $active;

        return $this;
    }

    /**
     * Get the value of type
     */
    public function getType(): ?string
    {
        return $this->type;
    }

    /**
     * Set the value of type
     */
    public function setType(?string $type): self
    {
        $this->type = $type;

        return $this;
    }
}
