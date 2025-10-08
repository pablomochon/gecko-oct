<?php

namespace App\Entity;

use App\Repository\ServiceRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Patch;

#[ORM\Entity(repositoryClass: ServiceRepository::class)]
#[UniqueEntity(
    fields: ['client', 'name'],
    errorPath: 'name',
    message: 'This service exists on this client',
)]
#[ApiResource(
    operations: [
        new GetCollection(),
        new Post(security: "is_granted('ROLE_ADMIN')"),
        new Get(),
        new Patch(security: "is_granted('ROLE_ADMIN')"),
    ],
    normalizationContext: ['groups' => ['Service:read']],
    denormalizationContext: ['groups' => ['Service:write']]
)]
#[ORM\Cache(usage: 'NONSTRICT_READ_WRITE')]
class Service
{
    #[ORM\Id]
    #[ORM\Column]
    #[Groups(["Service:read", "Service:write"])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(["Service:read", "Service:write"])]
    private ?string $name = null;

    #[Assert\NotBlank]
    #[Assert\Valid]
    #[Assert\Type(Client::class)]
    #[ORM\ManyToOne(inversedBy: 'services')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(["Service:read", "Service:write"])]
    private Client $client;

    #[ORM\Column(length: 100, nullable: true)]
    #[Groups(["Service:read", "Service:write"])]
    private ?string $tcosrv = null;

    #[ORM\Column(length: 100, nullable: true)]
    #[Groups(["Service:read", "Service:write"])]
    private ?string $pep = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(["Service:read", "Service:write"])]
    private ?string $description = null;

    #[Assert\Type('bool')]
    #[ORM\Column(type: 'boolean')]
    #[Groups(["Service:read", "Service:write"])]
    private ?bool $active = null;

    /**
     * @var Collection<int, Environment>
     */
    #[ORM\OneToMany(targetEntity: Environment::class, mappedBy: 'service')]
    private Collection $environments;

    public function __construct()
    {
        $this->active = true;
        $this->environments = new ArrayCollection();
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

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getClient(): Client
    {
        return $this->client;
    }

    public function setClient(?Client $client): self
    {
        $this->client = $client;
        
        return $this;
    }

    public function getTcosrv(): ?string
    {
        return $this->tcosrv;
    }

    public function setTcosrv(?string $tcosrv): self
    {
        $this->tcosrv = $tcosrv;

        return $this;
    }

    public function getPep(): ?string
    {
        return $this->pep;
    }

    public function setPep(?string $pep): self
    {
        $this->pep = $pep;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function isActive(): ?bool
    {
        return $this->active;
    }

    public function setActive(bool $active): self
    {
        if($active == false) {
            if($this->getEnvironments()) {
                foreach($this->getEnvironments() as $el) {
                    $el->setActive(false);
                }
            }
        }
        $this->active = $active;

        return $this;
    }

    /**
     * @return Collection<int, Environment>
     */
    public function getEnvironments(): Collection
    {
        return $this->environments;
    }

    public function addEnvironment(Environment $environment): static
    {
        if (!$this->environments->contains($environment)) {
            $this->environments->add($environment);
            $environment->setService($this);
        }

        return $this;
    }

    public function removeEnvironment(Environment $environment): static
    {
        if ($this->environments->removeElement($environment)) {
            // set the owning side to null (unless already changed)
            if ($environment->getService() === $this) {
                $environment->setService(null);
            }
        }

        return $this;
    }
}
