<?php

namespace App\Form\Admin\Faq;

use App\Repository\FaqTypeRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class FilterFaqTypeForm extends AbstractType
{
    public function __construct(private readonly FaqTypeRepository $faqTypeRepo) {}

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('question', TextType::class, [
                'required' => false,
                'label' => 'Question',
                'attr' => [
                    'placeholder' => 'Search by question...',
                ],
            ])
            ->add('type', ChoiceType::class, [
                'choices' => $this->getFaqTypeChoices(), 
                'multiple' => true,
                'expanded' => false, 
                'required' => false,
                'mapped' => false,
                "autocomplete" => true,
                'attr' => [
                    'data-placeholder' => 'Select FAQ types...', 
                ],
                'help' => 'Select one or more FAQ types.',
            ]);
    }

    private function getFaqTypeChoices(): array
    {
        $types = $this->faqTypeRepo->findBy([], ['sortOrder' => 'ASC']);
        $choices = [];
        foreach ($types as $type) {
            $choices[$type->getName()] = $type->getId(); 
        }
        return $choices;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'method' => 'POST',
            'csrf_protection' => false,
        ]);
    }
}
