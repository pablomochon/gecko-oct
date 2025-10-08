<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\ApiProperty;
use App\Repository\NetworkInterfaceRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

#[ORM\Entity(repositoryClass: NetworkInterfaceRepository::class)]
#[ORM\Cache(usage: 'NONSTRICT_READ_WRITE')]
#[UniqueEntity(
    fields: ['networkVirtualSystem', 'name', 'active'],
    errorPath: 'name',
    message: 'The Network Interface already exists for this network system',
)]
#[ApiResource(
    operations: [
        new GetCollection(),
        new Get(),
        new Post(security: "is_granted('ROLE_ADMIN')"),
        new Patch(security: "is_granted('ROLE_ADMIN')"),
    ]
)]
class NetworkInterface
{
    #[ORM\Id]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[Assert\Type('bool')]
    #[ORM\Column(type: 'boolean', options: [
        "default" => true
    ])]
    #[Groups(["NetworkInterface:read", "NetworkInterface:write"])]
    private bool $active;

    #[Assert\Regex(
        pattern: '/^([0-9A-Fa-f]{2}[:-]){5}([0-9A-Fa-f]{2})$/',
        message: 'MAC Address not in format 00:00:00:00:00:00',
    )]
    #[ORM\Column(length: 100, nullable: true)]
    private ?string $macAddress = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $defaultGateway = null;

    #[ORM\Column]
    private ?bool $dhcpEnabled = false;

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

    /**
     * @var Collection<int, ActivityType>
     */
    #[ORM\ManyToMany(targetEntity: ActivityType::class, mappedBy: 'networkInterfaces')]
    private Collection $activityTypes;

    #[ORM\ManyToOne(inversedBy: 'networkInterfaces')]
    private ?Environment $environment = null;

    #[Groups(["NetworkInterface:read", "NetworkInterface:write"])]
    #[ApiProperty(readableLink: false, writableLink: false)]
    #[ORM\ManyToOne(inversedBy: 'networkInterfaces')]
    private ?NetworkVirtualSystem $networkVirtualSystem = null;

    public function __construct()
    {
        $this->active = true;
        $this->dhcpEnabled = false;
        $this->activityTypes = new ArrayCollection();
    }

    // GETTERS AND SETTERS
    /**
     * Get the value of id
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): static
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Get the value of name
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * Set the value of name
     */
    public function setName(?string $name): self
    {
        $this->name = $name;

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
     * Get the value of macAddress
     */
    public function getMacAddress(): ?string
    {
        return $this->macAddress;
    }

    /**
     * Set the value of macAddress
     */
    public function setMacAddress(?string $macAddress): self
    {
        $this->macAddress = $macAddress;

        return $this;
    }

    /**
     * Get the value of defaultGateway
     */
    public function getDefaultGateway(): ?string
    {
        return $this->defaultGateway;
    }

    /**
     * Set the value of defaultGateway
     */
    public function setDefaultGateway(?string $defaultGateway): self
    {
        $this->defaultGateway = $defaultGateway;

        return $this;
    }

    /**
     * Get the value of dhcpEnabled
     */
    public function isDhcpEnabled(): ?bool
    {
        return $this->dhcpEnabled;
    }

    /**
     * Set the value of dhcpEnabled
     */
    public function setDhcpEnabled(?bool $dhcpEnabled): self
    {
        $this->dhcpEnabled = $dhcpEnabled;

        return $this;
    }

    /**
     * Get the value of dhcpServer
     */
    public function getDhcpServer(): ?string
    {
        return $this->dhcpServer;
    }

    /**
     * Set the value of dhcpServer
     */
    public function setDhcpServer(?string $dhcpServer): self
    {
        $this->dhcpServer = $dhcpServer;

        return $this;
    }

    /**
     * Get the value of dnsHostname
     */
    public function getDnsHostname(): ?string
    {
        return $this->dnsHostname;
    }

    /**
     * Set the value of dnsHostname
     */
    public function setDnsHostname(?string $dnsHostname): self
    {
        $this->dnsHostname = $dnsHostname;

        return $this;
    }

    /**
     * Get the value of dnsDomain
     */
    public function getDnsDomain(): ?string
    {
        return $this->dnsDomain;
    }

    /**
     * Set the value of dnsDomain
     */
    public function setDnsDomain(?string $dnsDomain): self
    {
        $this->dnsDomain = $dnsDomain;

        return $this;
    }

    /**
     * Get the value of dnsServer
     */
    public function getDnsServer(): ?string
    {
        return $this->dnsServer;
    }

    /**
     * Set the value of dnsServer
     */
    public function setDnsServer(?string $dnsServer): self
    {
        $this->dnsServer = $dnsServer;

        return $this;
    }

    /**
     * Get the value of adapterType
     */
    public function getAdapterType(): ?string
    {
        return $this->adapterType;
    }

    /**
     * Set the value of adapterType
     */
    public function setAdapterType(?string $adapterType): self
    {
        $this->adapterType = $adapterType;

        return $this;
    }

    /**
     * Get the value of description
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * Set the value of description
     */
    public function setDescription(?string $description): self
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Get the value of comments
     */
    public function getComments(): ?string
    {
        return $this->comments;
    }

    /**
     * Set the value of comments
     */
    public function setComments(?string $comments): self
    {
        $this->comments = $comments;

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
            $activityType->addNetworkInterface($this);
        }

        return $this;
    }

    public function removeActivityType(ActivityType $activityType): static
    {
        if ($this->activityTypes->removeElement($activityType)) {
            $activityType->removeNetworkInterface($this);
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

    public function getNetworkVirtualSystem(): ?NetworkVirtualSystem
    {
        return $this->networkVirtualSystem;
    }

    public function setNetworkVirtualSystem(?NetworkVirtualSystem $networkVirtualSystem): static
    {
        $this->networkVirtualSystem = $networkVirtualSystem;

        return $this;
    }
}
