<?php

namespace App\Entity\Traits;

use Doctrine\ORM\Mapping as ORM;
use App\Entity\User;

trait UserLOGTrait
{
    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $UserLOG = null;

    public function getUserLOG(): ?User
    {
        return $this->UserLOG;
    }

    public function setUserLOG(?User $UserLOG): self
    {
        $this->UserLOG = $UserLOG;

        return $this;
    }
}
