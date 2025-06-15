<?php

namespace App\Entity;

use App\Repository\SmsMessageRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SmsMessageRepository::class)]
class SmsMessage
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $messageContent = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTime $scheduleAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTime $sentAt = null;

    #[ORM\Column(length: 255)]
    private ?string $status = null;

    #[ORM\Column(nullable: true)]
    private ?float $cost = null;

    #[ORM\ManyToOne(inversedBy: 'smsMessages')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    /**
     * @var Collection<int, SmsRecipient>
     */
    #[ORM\OneToMany(targetEntity: SmsRecipient::class, mappedBy: 'smsMessage', cascade: ['persist'])] // <--- MODIFICATION ICI
    private Collection $smsRecipients;

    public function __construct()
    {
        $this->smsRecipients = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getMessageContent(): ?string
    {
        return $this->messageContent;
    }

    public function setMessageContent(string $messageContent): static
    {
        $this->messageContent = $messageContent;

        return $this;
    }

    public function getScheduleAt(): ?\DateTime
    {
        return $this->scheduleAt;
    }

    public function setScheduleAt(?\DateTime $scheduleAt): static
    {
        $this->scheduleAt = $scheduleAt;

        return $this;
    }

    public function getSentAt(): ?\DateTime
    {
        return $this->sentAt;
    }

    public function setSentAt(?\DateTime $sentAt): static
    {
        $this->sentAt = $sentAt;

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

    public function getCost(): ?float
    {
        return $this->cost;
    }

    public function setCost(?float $cost): static
    {
        $this->cost = $cost;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

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
            $smsRecipient->setSmsMessage($this);
        }

        return $this;
    }

    public function removeSmsRecipient(SmsRecipient $smsRecipient): static
    {
        if ($this->smsRecipients->removeElement($smsRecipient)) {
            // set the owning side to null (unless already changed)
            if ($smsRecipient->getSmsMessage() === $this) {
                $smsRecipient->setSmsMessage(null);
            }
        }

        return $this;
    }
}
