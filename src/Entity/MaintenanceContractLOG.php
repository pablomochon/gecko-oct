<?php

namespace App\Entity;

use App\Repository\MaintenanceContractLOGRepository;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\DBAL\Types\Types;
use App\Entity\Traits\ActiveLOGTrait;
use App\Entity\Traits\DateLOGTrait;
use App\Entity\Traits\UserLOGTrait;
use App\Entity\Traits\ActionLOGTrait;


#[ORM\Entity(repositoryClass: MaintenanceContractLOGRepository::class)]
class MaintenanceContractLOG
{
    use ActiveLOGTrait;
    use DateLOGTrait;
    use UserLOGTrait;
    use ActionLOGTrait;


    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?int $idMaintenanceContract = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTimeInterface $startDate = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTimeInterface $endDate = null;

    #[ORM\Column(length: 255)]
    private ?string $manufacturer = null;

    #[ORM\Column(length: 255)]
    private ?string $provider = null;

    #[ORM\Column]
    private ?bool $status = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $cost = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $notes = null;


    /**
     * Get the value of id
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Get the value of idMaintenanceContract
     */
    public function getIdMaintenanceContract(): ?int
    {
        return $this->idMaintenanceContract;
    }

    /**
     * Set the value of idMaintenanceContract
     */
    public function setIdMaintenanceContract(?int $idMaintenanceContract): self
    {
        $this->idMaintenanceContract = $idMaintenanceContract;

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
     * Get the value of startDate
     */
    public function getStartDate(): ?\DateTimeInterface
    {
        return $this->startDate;
    }

    /**
     * Set the value of startDate
     */
    public function setStartDate(?\DateTimeInterface $startDate): self
    {
        $this->startDate = $startDate;

        return $this;
    }

    /**
     * Get the value of endDate
     */
    public function getEndDate(): ?\DateTimeInterface
    {
        return $this->endDate;
    }

    /**
     * Set the value of endDate
     */
    public function setEndDate(?\DateTimeInterface $endDate): self
    {
        $this->endDate = $endDate;

        return $this;
    }

    /**
     * Get the value of manufacturer
     */
    public function getManufacturer(): ?string
    {
        return $this->manufacturer;
    }

    /**
     * Set the value of manufacturer
     */
    public function setManufacturer(?string $manufacturer): self
    {
        $this->manufacturer = $manufacturer;

        return $this;
    }

    /**
     * Get the value of provider
     */
    public function getProvider(): ?string
    {
        return $this->provider;
    }

    /**
     * Set the value of provider
     */
    public function setProvider(?string $provider): self
    {
        $this->provider = $provider;

        return $this;
    }

    /**
     * Get the value of status
     */
    public function isStatus(): ?bool
    {
        return $this->status;
    }

    /**
     * Set the value of status
     */
    public function setStatus(?bool $status): self
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Get the value of cost
     */
    public function getCost(): ?string
    {
        return $this->cost;
    }

    /**
     * Set the value of cost
     */
    public function setCost(?string $cost): self
    {
        $this->cost = $cost;

        return $this;
    }

    /**
     * Get the value of notes
     */
    public function getNotes(): ?string
    {
        return $this->notes;
    }

    /**
     * Set the value of notes
     */
    public function setNotes(?string $notes): self
    {
        $this->notes = $notes;

        return $this;
    }
}
