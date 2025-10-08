<?php

namespace App\Entity\Traits;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\DBAL\Types\Types;

trait DateLOGTrait
{
    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private ?\DateTimeInterface $DateLOG = null;

    public function getDateLOG(): ?\DateTimeInterface
    {
        return $this->DateLOG;
    }

    #[ORM\PrePersist]
    public function setDateLOGValue(): void
    {
        $this->DateLOG = new \DateTimeImmutable();
    }
}
