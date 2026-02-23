<?php

namespace App\Command\Migrations;

use App\Entity\Admin\Coupon;
use App\Entity\Store;
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
    name: 'migrations:migrate:coupon',
    description: 'Migrate coupon from the source database to your application database.',
)]
class MigrateCouponCommand extends Command
{
    public function __construct(private readonly EntityManagerInterface $entityManager, private Connection $sourceConnection)
    {
        parent::__construct();
        $this->sourceConnection = DriverManager::getConnection(['url' => $_ENV['DATABASE1_URL']]);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $yspStore = $this->entityManager->find(Store::class, 1);
        $query = "SELECT * FROM sm3_coupons";

        $coupon = $this->sourceConnection->fetchAllAssociative($query);

        if (!empty($coupon)) {
            $io->section('Coupon');
            $io->note(sprintf('Total Coupon: %s', count($coupon)) . "\n");
            $progressBar = new ProgressBar($output, count($coupon));
            $progressBar->start();
            foreach ($coupon as $key => $coupon) {
                $io->success(sprintf('Migrating : %s', $coupon['coupon_name'], "\n"));
                $newCoupon = new Coupon;
                $newCoupon->setStore($yspStore);
                $newCoupon->setCouponName($coupon['coupon_name']);
                $newCoupon->setCode($coupon['code']);
                $newCoupon->setDiscount($coupon['discount']);
                $newCoupon->setType($coupon['type']);
                $newCoupon->setUsesTotal(empty($coupon['uses_total']) ? 0 : $coupon['uses_total']);
                $newCoupon->setStartDate(new \DateTimeImmutable($coupon['start_date']));
                $newCoupon->setEndDate(new \DateTimeImmutable($coupon['end_date']));
                $newCoupon->setIsEnabled($coupon['status'] ? true : false);
               
                $this->entityManager->persist($newCoupon);
            }
            $this->entityManager->flush();
        } else {
            $io->warning('No Coupon found.');
        }

        $io->success('Migration completed.');

        return Command::SUCCESS;
    }
}
