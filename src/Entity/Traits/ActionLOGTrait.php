<?php


namespace App\Entity\Traits;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\DBAL\Types\Types;


trait ActionLOGTrait
{
    #[ORM\Column(type: Types::STRING, length: 10)]
    private ?string $action = null;

    public function getAction(): ?string
    {
        return $this->action;
    }

    #[ORM\PrePersist]
    public function setAction(string $action): self
    {
        $this->action = $action;

        return $this;
    }
}
