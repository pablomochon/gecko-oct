<?php

namespace App\Entity;

use App\Entity\Traits\ActiveLOGTrait;
use App\Entity\Traits\DateLOGTrait;
use App\Entity\Traits\UserLOGTrait;
use App\Repository\NetworkInterfaceLOGRepository;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\DBAL\Types\Types;

#[ORM\Entity(repositoryClass: NetworkInterfaceLOGRepository::class)]
#[ORM\HasLifecycleCallbacks()]
class NetworkInterfaceLOG
{

    use ActiveLOGTrait;
    use DateLOGTrait;
    use UserLOGTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?int $idNetworkInterface = null;

    #[ORM\Column(type: 'string', length: 255)]
    private $name;

    #[ORM\Column(type: 'string', length: 10)]
    private $action;

    #[ORM\Column]
    private ?bool $active = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $macAddress = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $defaultGateway = null;

    #[ORM\Column]
    private ?bool $dhcpEnabled = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $dhcpServer = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $dnsHostname = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $dnsDomain = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $dnsServer = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $adapterType = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $comments = null;

    #[ORM\ManyToOne]
    private ?Environment $environment = null;

    #[ORM\ManyToOne]
    private ?NetworkVirtualSystem $networkVirtualSystem = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getIdNetworkInterface(): ?int
    {
        return $this->idNetworkInterface;
    }

    public function setIdNetworkInterface(int $idNetworkInterface): self
    {
        $this->idNetworkInterface = $idNetworkInterface;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
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

    public function setAction(string $action): self
    {
        $this->action = $action;

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

    public function getMacAddress(): ?string
    {
        return $this->macAddress;
    }

    public function setMacAddress(?string $macAddress): self
    {
        $this->macAddress = $macAddress;

        return $this;
    }

    public function getDefaultGateway(): ?string
    {
        return $this->defaultGateway;
    }

    public function setDefaultGateway(?string $defaultGateway): self
    {
        $this->defaultGateway = $defaultGateway;

        return $this;
    }

    public function isDhcpEnabled(): ?bool
    {
        return $this->dhcpEnabled;
    }

    public function setDhcpEnabled(bool $dhcpEnabled): self
    {
        $this->dhcpEnabled = $dhcpEnabled;

        return $this;
    }

    public function getDhcpServer(): ?string
    {
        return $this->dhcpServer;
    }

    public function setDhcpServer(?string $dhcpServer): self
    {
        $this->dhcpServer = $dhcpServer;

        return $this;
    }

    public function getDnsHostname(): ?string
    {
        return $this->dnsHostname;
    }

    public function setDnsHostname(?string $dnsHostname): self
    {
        $this->dnsHostname = $dnsHostname;

        return $this;
    }

    public function getDnsDomain(): ?string
    {
        return $this->dnsDomain;
    }

    public function setDnsDomain(?string $dnsDomain): self
    {
        $this->dnsDomain = $dnsDomain;

        return $this;
    }

    public function getDnsServer(): ?string
    {
        return $this->dnsServer;
    }

    public function setDnsServer(?string $dnsServer): self
    {
        $this->dnsServer = $dnsServer;

        return $this;
    }

    public function getAdapterType(): ?string
    {
        return $this->adapterType;
    }

    public function setAdapterType(?string $adapterType): self
    {
        $this->adapterType = $adapterType;

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

    public function getComments(): ?string
    {
        return $this->comments;
    }

    public function setComments(?string $comments): self
    {
        $this->comments = $comments;

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

    public function getNetworkVirtualSystem(): ?NetworkVirtualSystem
    {
        return $this->networkVirtualSystem;
    }

    public function setNetworkVirtualSystem(?NetworkVirtualSystem $networkVirtualSystem): self
    {
        $this->networkVirtualSystem = $networkVirtualSystem;

        return $this;
    }
}
