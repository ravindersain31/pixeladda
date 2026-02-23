<?php

namespace App\Command\Migrations;

use App\Entity\Store;
use App\Service\ContactUsService;
use App\Service\SubscriberService;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'migrations:migrate:subscribers',
    description: 'Migrate subscriber:contactus:enquiry:text-update:email-offers from the source database to your application database.',
)]
class MigrateSubscribersCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private Connection                      $sourceConnection,
        private readonly SubscriberService      $subscriberService,
        private readonly ContactUsService       $contactUsService
    )
    {
        parent::__construct();
        $this->sourceConnection = DriverManager::getConnection(['url' => $_ENV['DATABASE1_URL']]);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $yspStore = $this->entityManager->find(Store::class, 1);
        $query = "SELECT * FROM sm3_contact_enquiries WHERE email not in ('ABILMAKNOJIA@GMAIL.COM', 'Khalilmaknojia@gmail.com','rajansharmaa46@gmail.com', 'rajan.kumar@geeky.dev', 'rajan.kumadf@ae.com', 'rajankumar@geeky.dev')";

        $enquiries = $this->sourceConnection->fetchAllAssociative($query);
        // contactUS : Type = 3
        // comming soon : Type = 0
        // saveCollUsNowData : Type = 2
        // SaveOffer : Type = 1
        // Enquiry : Type = 4
        if (!empty($enquiries)) {
            $io->section('enquiries');
            $io->note(sprintf('Total enquiries: %s', count($enquiries)) . "\n");
            $progressBar = new ProgressBar($output, count($enquiries));
            $progressBar->start();
            $i = 1;
            foreach ($enquiries as $key => $enquiry) {
                $io->comment(sprintf('%s: Migrating : %s', $i, $enquiry['name'] . ' : ' . $enquiry['email'], "\n"));
                if ($enquiry['email'] == null) continue;
                if ($enquiry['type'] == '3') {
                    $this->contactUsService->contactUs(
                        email: $enquiry['email'],
                        fullName: $enquiry['name'],
                        phone: $enquiry['phone'],
                        comment: htmlspecialchars($enquiry['comment']),
                        store: in_array($enquiry['store_id'], [0, 1, 2]) ? 1 : 2,
                        isOpened: false,
                        createdAt: new \DateTimeImmutable($enquiry['created_at']),
                        updatedAt: new \DateTimeImmutable ($enquiry['updated_at'])
                    );
                } else {
                    $this->subscriberService->subscribe(
                        email: $enquiry['email'],
                        fullName: $enquiry['name'],
                        type: $enquiry['type'],
                        phone: $enquiry['phone'],
                        mobileAlert: $enquiry['mobile_alert'] == '0' ? null : true,
                        marketing: true,
                        offers: $enquiry['offer_email'] == '0' ? null : true,
                        store: in_array($enquiry['store_id'], [0, 1, 2]) ? 1 : 2,
                        createdAt: new \DateTimeImmutable($enquiry['created_at']),
                        updatedAt: new \DateTimeImmutable($enquiry['updated_at'])

                    );
                }
                $i++;
            }
            $this->entityManager->flush();
        } else {
            $io->warning('No enquiries found.');
        }

        $io->success('Migration completed.');

        return Command::SUCCESS;
    }
}
