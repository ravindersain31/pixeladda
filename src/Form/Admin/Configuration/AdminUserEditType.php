<?php

namespace App\Form\Admin\Configuration;

use App\Entity\AdminUser;
use App\Entity\Role;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AdminUserEditType extends AbstractType
{

    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var AdminUser $user */
        $user = $options['user'];

        $builder->add('name', Type\TextType::class, [
            'label' => 'Account Name',
            'data' => $user->getName()
        ]);

        $builder->add('email', Type\EmailType::class, [
            'label' => 'Email',
            'data' => $user->getEmail()
        ]);

        $builder->add('mobile', Type\TextType::class, [
            'label' => 'Mobile',
            'required' => false,
            'data' => $user->getMobile()
        ]);

        $roles = $this->entityManager->getRepository(Role::class)->findByRoles($user->getRoles());

        $builder->add('roles', EntityType::class, [
            'label' => 'Access Roles',
            'class' => Role::class,
            'placeholder' => '--- Select ---',
            'multiple' => true,
            'expanded' => true,
            'attr' => ['class' => 'd-flex role-list flex-wrap'],
            'data' => $roles
        ]);

        $builder->add('enabled', Type\CheckboxType::class, [
            'label' => 'Allow Login?',
            'help' => 'If this is ticked, the user will be able to login, otherwise not',
            'required' => false,
            'data' => $user->isEnabled()
        ]);

        $builder->add('submit', Type\SubmitType::class, [
            'label' => 'Update Account',
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => null,
        ]);
        $resolver->setRequired('user');
    }
}
