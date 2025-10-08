<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Patch;
use App\Repository\EnvironmentRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: EnvironmentRepository::class)]
#[ApiResource(
    operations: [
        new GetCollection(),
        new Post(security: "is_granted('ROLE_ADMIN')"),
        new Get(),
        new Patch(security: "is_granted('ROLE_ADMIN')"),
    ],
    normalizationContext: ['groups' => ['Environment:read']],
    denormalizationContext: ['groups' => ['Environment:write']]
)]
#[ApiFilter(SearchFilter::class, properties: ['name' => 'exact', 'service' => 'exact', 'type' => 'exact'])]
#[ORM\Cache(usage: 'NONSTRICT_READ_WRITE')]
class Environment
{
    #[ORM\Id]
    #[ORM\Column]
    #[Groups(["Environment:read", "Environment:write"])]
    private ?int $id = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(["Environment:read", "Environment:write"])]
    private ?string $name = null;

    #[Assert\Choice(choices: ['training'
    , 'integration'
    , 'pre-production'
    , 'other'
    , 'test'
    , 'development'
    , 'production'], message: 'Choose a valid type: training, integration, pre-production, other, test, development, production')]
    #[ORM\Column(length: 255)]
    #[Groups(["Environment:read", "Environment:write"])]
    private ?string $type = null;

    #[Assert\Type('bool')]
    #[ORM\Column(type: 'boolean')]
    #[Groups(["Environment:read", "Environment:write"])]
    private ?bool $active = null;

    /**
     * @var Collection<int, NetworkVirtualSystem>
     */
    #[ORM\OneToMany(targetEntity: NetworkVirtualSystem::class, mappedBy: 'environment')]
    private Collection $networkVirtualSystems;

    /**
     * @var Collection<int, NetworkDevice>
     */
    #[ORM\OneToMany(targetEntity: NetworkDevice::class, mappedBy: 'environment')]
    private Collection $networkDevices;

    /**
     * @var Collection<int, NetworkInterface>
     */
    #[ORM\OneToMany(targetEntity: NetworkInterface::class, mappedBy: 'environment')]
    private Collection $networkInterfaces;

    #[ORM\ManyToOne(inversedBy: 'environments')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(["Environment:read", "Environment:write"])]
    private ?Service $service = null;

    public function __construct()
    {
        $this->active = true;
        $this->networkVirtualSystems = new ArrayCollection();
        $this->networkDevices = new ArrayCollection();
        $this->networkInterfaces = new ArrayCollection();
    }

    // GETTERS AND SETTERS
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

    public function setName(?string $name): static
    {
        $this->name = $name;

        return $this;
    }


    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(?string $type): static
    {
        $this->type = $type;

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
            $networkVirtualSystem->setEnvironment($this);
        }

        return $this;
    }

    public function removeNetworkVirtualSystem(NetworkVirtualSystem $networkVirtualSystem): static
    {
        if ($this->networkVirtualSystems->removeElement($networkVirtualSystem)) {
            // set the owning side to null (unless already changed)
            if ($networkVirtualSystem->getEnvironment() === $this) {
                $networkVirtualSystem->setEnvironment(null);
            }
        }

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
            $networkDevice->setEnvironment($this);
        }

        return $this;
    }

    public function removeNetworkDevice(NetworkDevice $networkDevice): static
    {
        if ($this->networkDevices->removeElement($networkDevice)) {
            // set the owning side to null (unless already changed)
            if ($networkDevice->getEnvironment() === $this) {
                $networkDevice->setEnvironment(null);
            }
        }

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
            $networkInterface->setEnvironment($this);
        }

        return $this;
    }

    public function removeNetworkInterface(NetworkInterface $networkInterface): static
    {
        if ($this->networkInterfaces->removeElement($networkInterface)) {
            // set the owning side to null (unless already changed)
            if ($networkInterface->getEnvironment() === $this) {
                $networkInterface->setEnvironment(null);
            }
        }

        return $this;
    }

    public function getService(): ?Service
    {
        return $this->service;
    }

    public function setService(?Service $service): static
    {
        $this->service = $service;

        return $this;
    }
}
