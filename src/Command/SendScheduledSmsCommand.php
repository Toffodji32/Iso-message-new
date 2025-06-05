<?php

namespace App\Command;

use App\Entity\SmsMessage;
use App\Repository\SmsMessageRepository;
use App\Service\SmsSender;
use Doctrine\ORM\EntityManagerInterface; 

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:send-scheduled-sms',
    description: 'Sends scheduled SMS messages whose scheduledAt date has passed.',
)]
class SendScheduledSmsCommand extends Command
{
    private EntityManagerInterface $entityManager;
    private SmsSender $smsSender;


    public function __construct(EntityManagerInterface $entityManager, SmsSender $smsSender)
    {
        parent::__construct();
        $this->entityManager = $entityManager;
        $this->smsSender = $smsSender;
    }

    protected function configure(): void
    {

    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->info('Starting to send scheduled SMS messages...');

        /** @var SmsMessageRepository $smsMessageRepository */
        $smsMessageRepository = $this->entityManager->getRepository(SmsMessage::class);


        $messagesToSend = $smsMessageRepository->findBy([
            'status' => 'scheduled',

        ]);


        $now = new \DateTime();
        $qb = $smsMessageRepository->createQueryBuilder('sm')
            ->where('sm.status = :status')
            ->andWhere('sm.scheduleAt <= :now')
            ->setParameter('status', 'scheduled')
            ->setParameter('now', $now)
            ->getQuery();
        $messagesToSend = $qb->getResult();


        if (empty($messagesToSend)) {
            $io->success('No scheduled SMS messages found to send at this time.');
            return Command::SUCCESS;
        }

        $io->info(sprintf('Found %d scheduled SMS messages to process.', count($messagesToSend)));

        $totalSent = 0;
        $totalFailed = 0;
        $totalProcessed = 0;

        foreach ($messagesToSend as $smsMessage) {
            /** @var SmsMessage $smsMessage */
            $totalProcessed++;
            $io->text(sprintf('Processing SMS message ID: %d - Content: "%s"', $smsMessage->getId(), substr($smsMessage->getMessageContent(), 0, 50) . '...'));


            $smsMessage->setStatus('sending');
            $this->entityManager->persist($smsMessage);
            $this->entityManager->flush();

            $messageSentCount = 0;
            $messageFailedCount = 0;

            foreach ($smsMessage->getSmsRecipients() as $smsRecipient) {
                try {

                    $isSent = $this->smsSender->sendSms($smsRecipient->getPhoneNumber(), $smsMessage->getMessageContent());

                    if ($isSent) {
                        $smsRecipient->setStatus('sent');
                        $smsRecipient->setSentAt(new \DateTime());
                        $messageSentCount++;
                    } else {
                        $smsRecipient->setStatus('failed');

                        $messageFailedCount++;
                    }
                } catch (\Exception $e) {
                    $smsRecipient->setStatus('failed');

                    $messageFailedCount++;
                }
                $this->entityManager->persist($smsRecipient);
            }


            if ($messageSentCount > 0 && $messageFailedCount === 0) {
                $smsMessage->setStatus('sent');
            } elseif ($messageSentCount > 0 && $messageFailedCount > 0) {
                $smsMessage->setStatus('partial_sent');
            } else {
                $smsMessage->setStatus('failed');
            }
            $smsMessage->setSentAt(new \DateTime());
            $this->entityManager->persist($smsMessage);

            $totalSent += $messageSentCount;
            $totalFailed += $messageFailedCount;

            $this->entityManager->flush();
            $io->text(sprintf('  -> Sent: %d, Failed: %d for this message.', $messageSentCount, $messageFailedCount));
        }

        $io->success(sprintf('Finished processing scheduled SMS messages. Total processed: %d, Total sent: %d, Total failed: %d.', $totalProcessed, $totalSent, $totalFailed));

        return Command::SUCCESS;
    }
}
