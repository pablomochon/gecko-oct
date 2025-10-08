<?php

namespace App\Entity;

use App\Repository\EnvironmentLOGRepository;
use Doctrine\ORM\Mapping as ORM;
use App\Entity\Traits\ActiveLOGTrait;
use App\Entity\Traits\DateLOGTrait;
use App\Entity\Traits\UserLOGTrait;

#[ORM\Entity(repositoryClass: EnvironmentLOGRepository::class)]
#[ORM\HasLifecycleCallbacks()]
class EnvironmentLOG
{
    use ActiveLOGTrait;
    use DateLOGTrait;
    use UserLOGTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column()]
    private ?int $id = null;

    #[ORM\Column]
    private ?int $idEnvironment = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(type: 'string', length: 10)]
    private $action;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Service $service = null;

    #[ORM\Column(length: 255)]
    private ?string $type = null;

    #[ORM\Column]
    private ?bool $active = null;

    // Getters and Setters

    /**
     * Get the value of id
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Set the value of id
     */
    public function setId(?int $id): self
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Get the value of idEnvironment
     */
    public function getIdEnvironment(): ?int
    {
        return $this->idEnvironment;
    }

    /**
     * Set the value of idEnvironment
     */
    public function setIdEnvironment(?int $idEnvironment): self
    {
        $this->idEnvironment = $idEnvironment;

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
     * Get the value of action
     */
    public function getAction()
    {
        return $this->action;
    }

    /**
     * Set the value of action
     */
    public function setAction($action): self
    {
        $this->action = $action;

        return $this;
    }

    /**
     * Get the value of service
     */
    public function getService(): ?Service
    {
        return $this->service;
    }

    /**
     * Set the value of service
     */
    public function setService(?Service $service): self
    {
        $this->service = $service;

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
