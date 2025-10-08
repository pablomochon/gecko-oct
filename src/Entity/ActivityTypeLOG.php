<?php

namespace App\Entity;

use App\Repository\ActivityTypeLOGRepository;
use App\Entity\Traits\DateLOGTrait;
use App\Entity\Traits\UserLOGTrait;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\Collection;

#[ORM\Entity(repositoryClass: ActivityTypeLOGRepository::class)]
#[ORM\HasLifecycleCallbacks()]
class ActivityTypeLOG
{
    use DateLOGTrait;
    use UserLOGTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    #[ORM\Column(type: 'integer')]
    private $idActivityType;

    #[ORM\Column(type: 'string', length: 6, unique: false, nullable: false)]
    private $code;

    #[ORM\Column(type: 'string', length: 64, unique: false, nullable: false)]
    private $description;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2, unique: false, nullable: false)]
    private $price;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $SAPname = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $type = null;

    #[ORM\Column(type: 'boolean')]
    private $active;

    #[ORM\Column(type: 'string', length: 10)]
    private $action;

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

    // Getters y setters (los añadimos completos)

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getIdActivityType(): ?int
    {
        return $this->idActivityType;
    }
    public function setIdActivityType(int $idActivityType): self
    {
        $this->idActivityType = $idActivityType;
        return $this;
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
    public function setSAPname(?string $SAPname): self
    {
        $this->SAPname = $SAPname;
        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }
    public function setType(?string $type): self
    {
        $this->type = $type;
        return $this;
    }

    public function isActive(): ?bool
    {
        return $this->active;
    }
    public function setActive(bool $active): self
    {
        $this->active = $active;
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

    public function setNetworkDevices(Collection $networkDevices): self
    {
        $this->networkDevices = $networkDevices;
        return $this;
    }

/*     public function getNetworkVirtualSystems(): ?string
    {
        return $this->networkVirtualSystems;
    }
    public function setNetworkVirtualSystems(?string $networkVirtualSystems): self
    {
        $this->networkVirtualSystems = $networkVirtualSystems;
        return $this;
    }

    public function getNetworkInterfaces(): ?string
    {
        return $this->networkInterfaces;
    }
    public function setNetworkInterfaces(?string $networkInterfaces): self
    {
        $this->networkInterfaces = $networkInterfaces;
        return $this;
    } */

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

    public function setNetworkVirtualSystems(Collection $networkVirtualSystems): self
    {
        $this->networkVirtualSystems = $networkVirtualSystems;
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

    public function setNetworkInterfaces(Collection $networkInterfaces): self
    {
        $this->networkInterfaces = $networkInterfaces;
        return $this;
    }
}
