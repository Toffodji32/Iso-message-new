<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Doctrine\DBAL\Types\Types; // NOUVEAU : Ajoutez ceci pour Types::INTEGER

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\UniqueConstraint(name: 'UNIQ_IDENTIFIER_EMAIL', fields: ['email'])]
#[UniqueEntity(fields: ['email'], message: 'Il y a déjà un compte avec ce email')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;


    #[ORM\Column(length: 180)]
    private ?string $email = null;

    /**
     * @var list<string> The user roles
     */
    #[ORM\Column]
    private array $roles = [];

    /**
     * @var string The hashed password
     */
    #[ORM\Column]
    private ?string $password = null;

    /**
     * NOUVEAU : Propriété pour le solde des crédits SMS
     */
    #[ORM\Column(type: Types::INTEGER, options: ['default' => 0])]
    private int $smsCredits = 0; // Initialisez à 0 par défaut

    /**
     * @var Collection<int, SmsMessage>
     */
    #[ORM\OneToMany(targetEntity: SmsMessage::class, mappedBy: 'user')]
    private Collection $smsMessages;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $apiKey = null;

    #[ORM\Column(length: 255)]
    private ?string $fullName = null;

    #[ORM\Column(length: 255)]
    private ?string $phoneNumber = null;

    public function __construct()
    {
        $this->smsMessages = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    /**
     * @see UserInterface
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

    /**
     * @return Collection<int, SmsMessage>
     */
    public function getSmsMessages(): Collection
    {
        return $this->smsMessages;
    }

    public function addSmsMessage(SmsMessage $smsMessage): static
    {
        if (!$this->smsMessages->contains($smsMessage)) {
            $this->smsMessages->add($smsMessage);
            $smsMessage->setUser($this);
        }

        return $this;
    }

    public function removeSmsMessage(SmsMessage $smsMessage): static
    {
        if ($this->smsMessages->removeElement($smsMessage)) {
            // set the owning side to null (unless already changed)
            if ($smsMessage->getUser() === $this) {
                $smsMessage->setUser(null);
            }
        }

        return $this;
    }

    public function getApiKey(): ?string
    {
        return $this->apiKey;
    }

    public function setApiKey(?string $apiKey): static
    {
        $this->apiKey = $apiKey;

        return $this;
    }

    public function getFullName(): ?string
    {
        return $this->fullName;
    }

    public function setFullName(string $fullName): static
    {
        $this->fullName = $fullName;

        return $this;
    }

    public function getPhoneNumber(): ?string
    {
        return $this->phoneNumber;
    }

    public function setPhoneNumber(string $phoneNumber): static
    {
        $this->phoneNumber = $phoneNumber;

        return $this;
    }


    public function getSmsCredits(): int
    {
        return $this->smsCredits;
    }

    public function setSmsCredits(int $smsCredits): static
    {
        $this->smsCredits = $smsCredits;

        return $this;
    }

    /**
     * Débite des crédits du solde de l'utilisateur.
     *
     * @param int $amount Le montant à débiter.
     * @return bool Vrai si le débit a réussi, Faux si le solde est insuffisant.
     */
    public function deductSmsCredits(int $amount): bool
    {
        if ($this->smsCredits >= $amount) {
            $this->smsCredits -= $amount;
            return true;
        }
        return false;
    }
}
