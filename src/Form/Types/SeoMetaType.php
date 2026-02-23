<?php

namespace App\Form\Types;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Bundle\SecurityBundle\Security;

class SeoMetaType extends AbstractType
{
    public function __construct(private readonly Security $security)
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $rolesToDisable = ['ROLE_SUPER_ADMIN', 'ROLE_OTHER'];

        $disabled = empty(array_intersect($rolesToDisable, $this->security->getUser()?->getRoles() ?? []));

        $builder->add('title', null, [
            'label' => 'Title Tag',
            'required' => false,
            'disabled' => $disabled,
        ]);
        $builder->add('headerTag', null, [
            'label' => 'Header Tag',
            'required' => false,
            'disabled' => $disabled,
        ]);
        $builder->add('description', Type\TextType::class, [
            'label' => 'Meta Description',
            'required' => false,
            'disabled' => $disabled,
        ]);
        $builder->add('keywords', Type\TextType::class, [
            'label' => 'CSR - Search Keywords',
            'required' => false,
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
        ]);
    }
}
