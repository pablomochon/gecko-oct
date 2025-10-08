<?php

namespace App\Entity;

use App\Entity\Traits\ActiveLOGTrait;
use App\Entity\Traits\DateLOGTrait;
use App\Entity\Traits\UserLOGTrait;
use App\Repository\NetworkDeviceLOGRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: NetworkDeviceLOGRepository::class)]
#[ORM\HasLifecycleCallbacks()]
class NetworkDeviceLOG
{

    use ActiveLOGTrait;
    use DateLOGTrait;
    use UserLOGTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    private ?int $id = null;

    #[ORM\Column(type: 'integer')]
    private ?int $idNetworkDevice = null;

    #[ORM\Column(type: "string", length: 255)]
    private string $name;

    #[ORM\Column(type: 'string', length: 90)]
    private $serialNumber;
    
    #[ORM\Column(length: 10)]
    private ?string $action = null;

    #[ORM\ManyToOne]
    private ?Environment $environment = null;

    // Getters y Setters
    public function getId(): ?int
    {
        return $this->id;
    }

    public function getIdNetworkDevice(): ?int
    {
        return $this->idNetworkDevice;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setIdNetworkDevice(?int $idNetworkDevice): self
    {
        $this->idNetworkDevice = $idNetworkDevice;
        return $this;
    }

    public function setName(string $name): self
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
}
