<?php

namespace App\Entity;

use App\Repository\ContactRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ContactRepository::class)]
// <<< NOUVEAU : SUPPRIMEZ LA LIGNE #[ORM\HasLifecycleCallbacks()]
class Contact
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $phoneNumber = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $firstName = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $lastName = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $email = null;

    /**
     * @var Collection<int, ContactGroup>
     */
    #[ORM\ManyToMany(targetEntity: ContactGroup::class, inversedBy: 'contacts')]
    private Collection $contactGroups;

    /**
     * @var Collection<int, SmsRecipient>
     */
    #[ORM\OneToMany(targetEntity: SmsRecipient::class, mappedBy: 'contact')]
    private Collection $smsRecipients;

    // <<< NOUVEAU : SUPPRIMEZ TOUT LE BLOC SUIVANT CONCERNANT createdAt
    // #[ORM\Column(type: 'datetime_immutable', nullable: false)]
    // private ?\DateTimeImmutable $createdAt = null;
    // FIN DU BLOC À SUPPRIMER

    public function __construct()
    {
        $this->contactGroups = new ArrayCollection();
        $this->smsRecipients = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setFirstName(?string $firstName): static
    {
        $this->firstName = $firstName;
        return $this;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function setLastName(?string $lastName): static
    {
        $this->lastName = $lastName;
        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): static
    {
        $this->email = $email;
        return $this;
    }

    /**
     * @return Collection<int, ContactGroup>
     */
    public function getContactGroups(): Collection
    {
        return $this->contactGroups;
    }

    public function addContactGroup(ContactGroup $contactGroup): static
    {
        if (!$this->contactGroups->contains($contactGroup)) {
            $this->contactGroups->add($contactGroup);
        }
        return $this;
    }

    public function removeContactGroup(ContactGroup $contactGroup): static
    {
        $this->contactGroups->removeElement($contactGroup);
        return $this;
    }

    /**
     * @return Collection<int, SmsRecipient>
     */
    public function getSmsRecipients(): Collection
    {
        return $this->smsRecipients;
    }

    public function addSmsRecipient(SmsRecipient $smsRecipient): static
    {
        if (!$this->smsRecipients->contains($smsRecipient)) {
            $this->smsRecipients->add($smsRecipient);
            $smsRecipient->setContact($this);
        }
        return $this;
    }

    public function removeSmsRecipient(SmsRecipient $smsRecipient): static
    {
        if ($this->smsRecipients->removeElement($smsRecipient)) {
            // set the owning side to null (unless already changed)
            if ($smsRecipient->getContact() === $this) {
                $smsRecipient->setContact(null);
            }
        }
        return $this;
    }

    // <<< NOUVEAU : SUPPRIMEZ TOUT LE BLOC SUIVANT CONCERNANT les méthodes de createdAt
    // public function getCreatedAt(): ?\DateTimeImmutable
    // {
    //     return $this->createdAt;
    // }
    // public function setCreatedAt(\DateTimeImmutable $createdAt): static
    // {
    //     $this->createdAt = $createdAt;
    //     return $this;
    // }
    // #[ORM\PrePersist]
    // public function setCreatedAtValue(): void
    // {
    //     $this->createdAt = new \DateTimeImmutable();
    // }
    // FIN DU BLOC À SUPPRIMER
}
