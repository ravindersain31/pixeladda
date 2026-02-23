<?php 

namespace App\Controller\Admin\Component;

use App\Enum\Admin\CacheEnum;
use App\Form\Admin\CacheClearFormType;
use App\Service\CacheService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\ComponentWithFormTrait;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent(
    name: 'AdminCacheForm',
    template: 'admin/components/cache-form.html.twig'
)]
class CacheFormComponent extends AbstractController
{
    use DefaultActionTrait;
    use ComponentWithFormTrait;

    #[LiveProp]
    public ?string $flashMessage = null;

    #[LiveProp]
    public ?string $flashType = null;

    #[LiveProp(writable: true)]
    public ?string $selectedKey = null;

    public function __construct(private readonly CacheService $cacheService) 
    {
    }

    public function instantiateForm(): FormInterface
    {
        return $this->createForm(CacheClearFormType::class);
    }

    #[LiveAction]
    public function clearSelected(): void
    {
        if ($this->selectedKey) {
            $enum = CacheEnum::tryFrom($this->selectedKey);
            if ($enum) {
                $result = $this->cacheService->clearPool($enum->value);
                if ($result === true) {
                    $this->setFlash('success', sprintf('Cache pool "%s" cleared successfully.', $enum->label()));
                } else {
                    $this->setFlash('danger', sprintf('Failed to clear "%s": %s', $enum->label(), $result));
                }
            } else {
                $this->setFlash('danger', sprintf('Invalid cache key "%s" selected.', $this->selectedKey));
            }
        } else {
            $this->setFlash('warning', 'Please select a cache key to clear.');
        }
    }

    #[LiveAction]
    public function clearAll(): void
    {
        $errors = $this->cacheService->clearAllPools();

        if (empty($errors)) {
            $this->setFlash('success', 'All cache pools cleared successfully.');
        } else {
            $this->setFlash('danger', "Some pools failed:\n" . implode("\n", $errors));
        }
    }

    private function setFlash(string $type, string $message): void
    {
        $this->flashType = $type;
        $this->flashMessage = $message;
    }
}
