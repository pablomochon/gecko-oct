<?php

namespace App\Entity\Traits;

use Doctrine\ORM\Mapping as ORM;

trait ActiveLOGTrait
{
    #[ORM\Column]
    private ?bool $active = null;

    public function isActive(): bool
    {
        return $this->active;
    }

    public function setActive(bool $active): self
    {
        $this->active = $active;
        return $this;
    }
}
