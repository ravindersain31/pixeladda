<?php

namespace App\Command\Migrations;

use App\Entity\AppUser;
use App\Entity\Store;
use App\Entity\User;
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
    name: 'migrations:migrate:users',
    description: 'Migrate users and address from the source database to your application database.',
)]
class MigrateUsersCommand extends Command
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
        $query = "SELECT * FROM `sm3_customers` as c LEFT JOIN sm3_address as a on c.address_id = a.id;";
        $users = $this->sourceConnection->fetchAllAssociative($query);
        if (!empty($users)) {
            $io->section('User and Address');
            $io->note(sprintf('Total users: %s', count($users)) . "\n");
            $progressBar = new ProgressBar($output, count($users));
            $progressBar->start();
            foreach ($users as $key => $user) {
                if($this->entityManager->getRepository(AppUser::class)->findOneBy(["email"=> $user['email']]) && $this->entityManager->getRepository(User::class)->findOneBy(["username" => $user['email']])){
                    $io->note(sprintf('user already exist: %s', $user['email']) . "\n");
                    continue;
                }
                $io->success(sprintf('Migrating : '. $key .' : %s', $user['firstname'] . ' : ' . $user['email'], "\n"));
                $u = new AppUser;;
                $u->setUsername((string) $user['email']);
                $u->setName($user['firstname'] . ' ' . $user['lastname']);
                $u->setFirstName($user['firstname']);
                $u->setLastName($user['lastname']);
                $u->setEmail($user['email']);
                $u->setPassword((string) $user['password']);
                $u->setRoles(['ROLE_USER']);
                $u->setIp($user['ip']);
                $u->setIsEnabled(($user['status'] === 'Active') ? true : false);
                $u->setCreatedAt(new \DateTimeImmutable($user['created_at']));
                $u->setUpdatedAt(new \DateTimeImmutable($user['updated_at']));
                $migratedData = [
                    "firstname" => $user["firstname"],
                    "lastname" => $user["lastname"],
                    "email" => $user["email"],
                    "password" => $user["password"],
                    "telephone" => $user["telephone"],
                    "fax" => $user["fax"],
                    "address_id" => $user["address_id"],
                    "ip" => $user["ip"],
                    "status" => $user["status"],
                    "remember_token" => $user["remember_token"],
                    "created_at" => $user["created_at"],
                    "updated_at" => $user["updated_at"],
                    "temp_pass_set" => $user["temp_pass_set"],
                    "customer_id" => $user["customer_id"],
                    "company" => $user["company"],
                    "address_line_1" => $user["address_line_1"],
                    "address_line_2" => $user["address_line_2"],
                    "city" => $user["city"],
                    "postcode" => $user["postcode"],
                    "country_id" => $user["country_id"],
                    "zone_id" => $user["zone_id"],
                ];
                $u->setMigratedData($migratedData);
                $this->entityManager->persist($u);

            }
            $this->entityManager->flush();
            $io->success('Migration completed.');
            return Command::SUCCESS;
        } else {
            $io->warning('No enquiries found.');
            return Command::FAILURE;
        }
    }
}
