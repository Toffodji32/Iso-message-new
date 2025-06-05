<?php

namespace App\Entity;

use App\Repository\SmsRecipientRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SmsRecipientRepository::class)]
class SmsRecipient
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'smsRecipients')]
    #[ORM\JoinColumn(nullable: false)]
    private ?SmsMessage $smsMessage = null;

    #[ORM\ManyToOne(inversedBy: 'smsRecipients')]
    private ?Contact $contact = null;

    #[ORM\Column(length: 20)] // Ajout de la propriété phoneNumber
    private ?string $phoneNumber = null;

    #[ORM\Column(length: 50)] // Ajout de la propriété status
    private ?string $status = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSmsMessage(): ?SmsMessage
    {
        return $this->smsMessage;
    }

    public function setSmsMessage(?SmsMessage $smsMessage): static
    {
        $this->smsMessage = $smsMessage;

        return $this;
    }

    public function getContact(): ?Contact
    {
        return $this->contact;
    }

    public function setContact(?Contact $contact): static
    {
        $this->contact = $contact;

        return $this;
    }

    // --- NOUVELLES MÉTHODES À AJOUTER ---

    public function getPhoneNumber(): ?string
    {
        return $this->phoneNumber;
    }

    public function setPhoneNumber(string $phoneNumber): static
    {
        $this->phoneNumber = $phoneNumber;

        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;

        return $this;
    }

    private ?\DateTimeInterface $sentAt = null;

    public function getSentAt(): ?\DateTimeInterface
       {
        return $this->sentAt;
        }

    public function setSentAt(?\DateTimeInterface $sentAt): self
       {
          $this->sentAt = $sentAt;
          return $this;
            }
}
