<?php

namespace App\Command\Admin;

use App\Entity\RolePermission;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Routing\RouterInterface;

#[AsCommand(
    name: 'admin:role:sync-permission',
    description: 'Pull the routes names from symfony and load into database.',
)]
class AdminRoleSyncPermissionCommand extends Command
{
    public function __construct(private readonly RouterInterface $router, private readonly EntityManagerInterface $entityManager)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $routes = $this->router->getRouteCollection()->all();

        $excludedRoutes = ['admin_login', 'admin_logout', 'admin_forgot_password'];

        $count = 0;
        foreach ($routes as $routeName => $route) {
            if(in_array($routeName, $excludedRoutes)) {
                continue;
            }
            if (!str_contains($routeName, 'admin')) {
                continue;
            }
            $name = str_replace('admin_', '', $routeName);

            $permissionExists = $this->entityManager->getRepository(RolePermission::class)->findOneBy(['name' => $name]);
            if($permissionExists) {
                continue;
            }
            $permission = new RolePermission();
            $permission->setName($name);
            $this->entityManager->persist($permission);
            $this->entityManager->flush();
            $io->comment("Permission $name has been created");
            $count++;
        }

        $io->success("$count permissions has been created");

        return Command::SUCCESS;
    }
}
