<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\UniqueConstraint(name: 'UNIQ_IDENTIFIER_USERNAME', fields: ['username'])]
#[ORM\Cache(usage: 'NONSTRICT_READ_WRITE')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180)]
    private ?string $username = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(length: 255)]
    private ?string $email = null;

    #[ORM\Column]
    private ?bool $active = null;

    /**
     * @var list<string> The user roles
     */
    #[ORM\Column]
    private array $roles = [];

    #[ORM\Column(type: 'array', nullable: true)]
    private $rolesFixed = [];

    /**
     * @var string The hashed password
     */
    #[ORM\Column]
    private ?string $password = null;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: UserLoginLOG::class)]
    private Collection $userLoginLOGs;

    #[ORM\ManyToMany(targetEntity: Client::class)]
    private Collection $filterClient;

    #[ORM\Column(type: 'array', nullable: true)]
    private $filterClientFixed = [];

    #[ORM\ManyToMany(targetEntity: Service::class)]
    private Collection $filterService;

    #[ORM\Column(type: 'array', nullable: true)]
    private $filterServiceFixed = [];

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: ApiApp::class)]
    private Collection $apiApps;

    public function __construct()
    {
        $this->userLoginLOGs = new ArrayCollection();
        $this->filterClient = new ArrayCollection();
        $this->filterService = new ArrayCollection();
        $this->apiApps = new ArrayCollection();
    }
    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setUsername(string $username): static
    {
        $this->username = $username;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    public function isActive(): ?bool
    {
        return $this->active;
    }

    public function setActive(bool $active): static
    {
        $this->active = $active;

        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->username;
    }

    /**
     * @see UserInterface
     *
     * @return list<string>
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    /**
     * @param list<string> $roles
     */
    public function setRoles(array $roles): static
    {
        $this->roles = $roles;

        return $this;
    }

    public function getRolesFixed(): ?array
    {
        return $this->rolesFixed;
    }

    public function setRolesFixed(?array $rolesFixed): self
    {
        $this->rolesFixed = $rolesFixed;

        return $this;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials(): void
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
    }

    public function getUserLoginLOGs(): Collection
    {
        return $this->userLoginLOGs;
    }

    public function addUserLoginLOG(UserLoginLOG $userLoginLOG): self
    {
        if (!$this->userLoginLOGs->contains($userLoginLOG)) {
            $this->userLoginLOGs->add($userLoginLOG);
            $userLoginLOG->setUser($this);
        }

        return $this;
    }

    public function removeUserLoginLOG(UserLoginLOG $userLoginLOG): self
    {
        if ($this->userLoginLOGs->removeElement($userLoginLOG)) {
            // set the owning side to null (unless already changed)
            if ($userLoginLOG->getUser() === $this) {
                $userLoginLOG->setUser(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Client>
     */
    public function getFilterClient(): Collection
    {
        return $this->filterClient;
    }

    public function addFilterClient(Client $filterClient): self
    {
        if (!$this->filterClient->contains($filterClient)) {
            $this->filterClient->add($filterClient);
        }

        return $this;
    }

    public function removeFilterClient(Client $filterClient): self
    {
        $this->filterClient->removeElement($filterClient);

        return $this;
    }

    public function setFilterClient(?Collection $filterClient): self
    {
        $this->filterClient = $filterClient;

        return $this;
    }

    public function getFilterClientFixed(): ?array
    {
        return $this->filterClientFixed;
    }

    public function setFilterClientFixed(?array $filterClientFixed): self
    {
        $this->filterClientFixed = $filterClientFixed;

        return $this;
    }

    /**
     * @return Collection<int, Service>
     */
    public function getFilterService(): Collection
    {
        return $this->filterService;
    }

    public function addFilterService(Service $filterService): self
    {
        if (!$this->filterService->contains($filterService)) {
            $this->filterService->add($filterService);
        }

        return $this;
    }

    public function removeFilterService(Service $filterService): self
    {
        $this->filterService->removeElement($filterService);

        return $this;
    }

    public function setFilterService(?Collection $filterService): self
    {
        $this->filterService = $filterService;

        return $this;
    }

    public function getFilterServiceFixed(): ?array
    {
        return $this->filterServiceFixed;
    }

    public function setFilterServiceFixed(?array $filterServiceFixed): self
    {
        $this->filterServiceFixed = $filterServiceFixed;

        return $this;
    }
}
