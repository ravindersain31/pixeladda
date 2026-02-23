<?php

namespace App\Controller\Admin\Component\Order;

use App\Entity\Order;
use App\Entity\OrderLog;
use App\Entity\User;
use App\Enum\DesignerTaskEnum;
use App\Enum\PrintFilesStatus;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\Attribute\LiveArg;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent(
    name: 'OrderDesignerTasks',
    template: 'admin/components/order/order_designer_tasks.html.twig'
)]
class OrderDesignerTasksComponent extends AbstractController
{
    use DefaultActionTrait;

    #[LiveProp(writable: true)]
    public ?Order $order = null;

    /** @return DesignerTaskEnum[] */
    public function getTasks(): array
    {
        return DesignerTaskEnum::cases();
    }

    public function __construct(
        private EntityManagerInterface $entityManager,
        private Security $security
    ) {}

    #[LiveAction]
    public function toggle(#[LiveArg('taskValue')] string $taskValue): void
    {
        $user = $this->security->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        $order = $this->entityManager->getRepository(Order::class)
            ->find($this->order->getId());

        $tasks = $order->getDesignerTasks() ?? [];

        $taskEnum = DesignerTaskEnum::from($taskValue);
        $taskLabel = $taskEnum->label();
        $logType = $taskEnum->logType(); 

        $tasks[$taskValue] = !($tasks[$taskValue] ?? false);

        if ($taskValue === DesignerTaskEnum::PRINT_FILE->value) {
            $order->setPrintFilesStatus($tasks[$taskValue] 
                ? PrintFilesStatus::UPLOADED->value 
                : PrintFilesStatus::PENDING->value   
            );
        }

        $actionMessage = $tasks[$taskValue]
            ? "Marked <b>{$taskLabel}</b> as Done"
            : "Marked <b>{$taskLabel}</b> as Pending";

        $order->setDesignerTasks($tasks);
        $this->entityManager->flush();
        $this->order = $order;

        $this->logAction($order, $user, $actionMessage, $logType ?? null);
    }

    private function logAction(Order $order, User $user, string $action, string $logType): void
    {
        $log = new OrderLog();
        $log->setOrder($order)
            ->setChangedBy($user)
            ->setContent($action . '<br/>By: ' . $user->getUserIdentifier())
            ->setType($logType);

        $this->entityManager->persist($log);
        $this->entityManager->flush();
    }

    public function isCompleted(DesignerTaskEnum $task): bool
    {
        $tasks = $this->order?->getDesignerTasks() ?? [];
        return $tasks[$task->value] ?? false;
    }
}
