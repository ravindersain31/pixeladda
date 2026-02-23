<?php

namespace App\Form;

use App\Entity\Product;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ShopFilterType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {

        $categoriesHasProducts = $options['data'] ?? [];
        $subCategories = [];

        $choices = [];
        foreach ($categoriesHasProducts as $category) {
            if (in_array(strtolower($category['slug']), ['sample-category'])) {
                continue;
            }

            $productCount = !empty($category['productCount1']) ? $category['productCount1'] : $category['productCount2'];

            if ($category['parentSlug']) {
                $subCategories[] = $category;
            } else {
                $choices[$category['name'] . ' (' . $productCount . ')'] = $category['slug'];
            }
        }

        $builder->add('c', Type\ChoiceType::class, [
            'label' => false,
            'choices' => $choices,
            'expanded' => true,
            'multiple' => false,
        ]);
        $builder->add('search', Type\HiddenType::class, []);

        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) use ($subCategories) {
            $form = $event->getForm();
            $data = $event->getData() ?? [];

            $submittedCategories = $data['c'] ?? [];
            if (!is_array($submittedCategories)) {
                $submittedCategories = [$submittedCategories];
            }
            $validCategoryValues = array_values($form->get('c')->getConfig()->getOption('choices') ?? []);
            $submittedCategories = array_values(array_intersect($submittedCategories, $validCategoryValues));
            $data['c'] = $submittedCategories;

            if (count($submittedCategories) === 1) {
                $parent = [reset($submittedCategories)];
                $this->addSubcategoryField($form, $parent, $subCategories);
                $submittedSubcats = $data['sc'] ?? [];
                if (!is_array($submittedSubcats)) {
                    $submittedSubcats = [$submittedSubcats];
                }
                $validSubValues = $form->has('sc') ? array_values($form->get('sc')->getConfig()->getOption('choices') ?? []) : [];
                $submittedSubcats = array_values(array_intersect($submittedSubcats, $validSubValues));
                $data['sc'] = $submittedSubcats;
            } else {
                if ($form->has('sc')) {
                    $form->remove('sc');
                }
                $data['sc'] = [];
            }
            $event->setData($data);
        });
    }

    private function addSubcategoryField(FormInterface $form, array $parentCategorySlugs, array $subCategories): void
    {

        $subcategoryChoices = [];
        foreach ($subCategories as $category) {
            if (in_array($category['parentSlug'], $parentCategorySlugs, true)) {
                $productCount = !empty($category['productCount1']) ? $category['productCount1'] : $category['productCount2'];
                $subcategoryChoices[$category['name'] . ' (' . $productCount . ')'] = $category['slug'];
            }
        }

        if (!empty($subcategoryChoices)) {
            if ($form->has('sc')) {
                $form->remove('sc');
            }

            $form->add('sc', Type\ChoiceType::class, [
                'label' => false,
                'choices' => $subcategoryChoices,
                'expanded' => true,
                'multiple' => true,
            ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'csrf_protection' => false,
            // Configure your form options here
        ]);
    }

    public function getBlockPrefix(): string
    {
        return '';
    }
}
