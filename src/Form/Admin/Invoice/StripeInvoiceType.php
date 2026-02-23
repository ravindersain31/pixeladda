<?php

namespace App\Form\Admin\Invoice;

use App\Form\Admin\Configuration\StripeInvoiceFieldType;
use App\Service\Admin\StripeInvoiceService;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints;
use Symfony\UX\LiveComponent\Form\Type\LiveCollectionType;

class StripeInvoiceType extends AbstractType
{
    public function __construct(private readonly StripeInvoiceService $stripeInvoiceService)
    {
    }
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $data = $options['data']['invoiceData'];

        $builder->add('firstName', Type\TextType::class,[
            'label' => 'First Name',
            'attr' => [],
            'data' => $data['primary_recipients'][0]['billing_info']['name']['given_name'] ?? '',
            'constraints' => [
                new Constraints\NotBlank()
            ],
        ]);

        $builder->add('lastName', Type\TextType::class,[
            'label' => 'Last Name',
            'attr' => [],
            'data' => $data['primary_recipients'][0]['billing_info']['name']['surname'] ?? '',
            'constraints' => [
                new Constraints\NotBlank()
            ]
        ]);

        $builder->add('email', Type\EmailType::class, [
            'label' => 'Email Address',
            'attr' => [],
            'data' => $data['primary_recipients'][0]['billing_info']['email_address'] ?? '',
            'constraints' => [
                new Constraints\NotBlank()
            ]
        ]);

       $builder->add('items', LiveCollectionType::class, [
            'label' => false,
            'entry_type' => StripeInvoiceFieldType::class,
            'data' => $data['items'],
            'required' => true,
            'allow_add' => true,
            'button_add_options' => [
                'label' => 'Add Items',
                'attr' => [
                    'class' => 'btn btn-dark btn-sm',
                ],
            ],
            'allow_delete' => true,
            'button_delete_options' => [
                'label' => 'X',
                'attr' => [
                    'class' => 'btn btn-danger',
                ],
            ],
        ]);

    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([]);
    }
}
