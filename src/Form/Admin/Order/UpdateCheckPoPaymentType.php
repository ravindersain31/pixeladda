<?php

namespace App\Form\Admin\Order;

use App\Entity\Currency;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Validator\Constraints;
use Vich\UploaderBundle\Form\Type\VichFileType;
use Vich\UploaderBundle\Form\Type\VichImageType;

class UpdateCheckPoPaymentType extends AbstractType
{

    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
        
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {

        $builder->add('proofFile', VichFileType::class, [
            'label' => 'Proof File',
            'help' => 'pload CheckPO Proof',
            'attr' => [
                // 'accept' => ".pdf",
            ],
            'required' => true,
            'allow_delete' => false,
        ]);

        $builder->add('refNumber',Type\TextType::class,[
            'label' => 'Payment reference number',
            'attr' => [
                'placeholder' => 'Payment reference number',
                'maxlength' => 256,
                'minlength' => 2,
            ],
            'constraints' => [
                new Constraints\NotBlank(),
            ]
        ]);

        $builder->add('poNumber',Type\TextType::class,[
            'label' => 'Po number',
            'attr' => [
                'placeholder' => 'Po number',
                'maxlength' => 256,
                'minlength' => 2,
            ],
            'constraints' => [
                new Constraints\NotBlank(),
            ]
        ]);

        $builder->add('comment',Type\TextareaType::class,[
            'label' => 'Other details',
            'attr' => [
                'placeholder' => 'Other details',
                'maxlength' => 256,
                'minlength' => 2,
            ],
            'constraints' => [
                new Constraints\NotBlank(),
            ]
        ]);

        $builder->add('currency', EntityType::class, [
                'label' => 'Currency',
                'placeholder' => '-- Choose a currency --',
                'class' => Currency::class,
                'data' => $this->entityManager->getRepository(Currency::class)->findOneBy(['code' => 'USD']),
                'row_attr' => [
                    'class' => 'd-none'
                ],
        ]);

        $builder->add('submit', Type\SubmitType::class, [
            'label' => 'Submit',
            'attr' => [
                'class' => 'btn btn-primary'
            ]
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            // Configure your form options here
        ]);
    }
}
