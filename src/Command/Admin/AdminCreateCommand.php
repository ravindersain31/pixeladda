<?php

namespace App\Command\Admin;

use App\Entity\AdminUser;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;

#[AsCommand(
    name: 'admin:create',
    description: 'Create admin account',
)]
class AdminCreateCommand extends Command
{

    private PasswordHasherFactoryInterface $passwordHasher;

    private EntityManagerInterface $entityManager;

    public function __construct(PasswordHasherFactoryInterface $passwordHasher, EntityManagerInterface $entityManager)
    {
        parent::__construct();
        $this->passwordHasher = $passwordHasher;
        $this->entityManager = $entityManager;
    }

    protected function configure(): void
    {
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->section('Please enter the below information to create admin account');

        $name = $io->ask('Name');
        $username = $io->ask('Username');
        $password = $io->ask('Password');

        $admin = new AdminUser();
        $admin->setRoles(['ROLE_ADMIN']);
        $admin->setName($name);
        $admin->setUsername($username);
        $admin->setIsEnabled(true);

        $hasher = $this->passwordHasher->getPasswordHasher($admin);
        $admin->setPassword($hasher->hash($password));

        try {
            $this->entityManager->persist($admin);
            $this->entityManager->flush();
            $io->success("Admin account has been created");
        } catch (UniqueConstraintViolationException $exception) {
            $io->error($exception->getMessage());
        }

        return Command::SUCCESS;
    }
}
