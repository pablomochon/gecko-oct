<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Patch;
use App\Repository\ClientRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;

#[ORM\Entity(repositoryClass: ClientRepository::class)]
#[UniqueEntity('name')]
#[ApiResource(
    operations: [
        new GetCollection(),
        new Post(security: "is_granted('ROLE_ADMIN')"),
        new Get(),
        new Patch(security: "is_granted('ROLE_ADMIN')"),
    ],
    normalizationContext: ['groups' => ['Client:read']],
    denormalizationContext: ['groups' => ['Client:write']]
)]
#[ORM\Cache(usage: 'NONSTRICT_READ_WRITE')]
class Client
{
    #[ORM\Id]
    #[ORM\Column]
    #[Groups(["Client:read", "Client:write"])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(["Client:read", "Client:write"])]
    private ?string $name = null;

    #[ORM\OneToMany(mappedBy: 'client', targetEntity: Service::class)]
    #[Groups(["Client:read", "Client:write"])]
    private Collection $services;

    #[Assert\Type('bool')]
    #[ORM\Column(type: 'boolean')]
    #[Groups(["Client:read", "Client:write"])]
    private ?bool $active = null;

    #[ORM\Column(length: 50, nullable: true)]
    #[Groups(["Client:read", "Client:write"])]
    private ?string $code = null;

    public function __construct()
    {
        $this->services = new ArrayCollection();
        $this->active = true;
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
    /**
     * @return Collection<int, Service>
     */
    
    public function getServices(): Collection
    {
        return $this->services;
    }

    public function addService(Service $service): self
    {
        if (!$this->services->contains($service)) {
            $this->services[] = $service;
            $service->setClient($this);
        }

        return $this;
    }
    
    public function removeService(Service $service): self
    {
        if ($this->services->removeElement($service)) {
            // set the owning side to null (unless already changed)
            if ($service->getClient() === $this) {
                // @intelephense-ignore
                $service->setClient(null); 
            }
        }

        return $this;
    }

    public function isActive(): ?bool
    {
        return $this->active;
    }
    
    public function setActive(bool $active): self
    {
        if($active == false) {
            if($this->getServices()) {
                foreach($this->getServices() as $el) {
                    $el->setActive(false);
                }
            }
        }

        $this->active = $active;

        return $this;
    }
    
    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(?string $code): static
    {
        $this->code = $code;

        return $this;
    }
}
