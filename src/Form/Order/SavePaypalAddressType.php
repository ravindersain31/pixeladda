<?php

namespace App\Form\Order;

use App\Entity\Country;
use App\Entity\State;
use App\Entity\Order;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Validator\Constraints;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;

class SavePaypalAddressType extends AbstractType
{
    public function __construct(private readonly EntityManagerInterface $entityManager){

    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {

        /** @var Country $country */
        $country = $options['country'];

        /** @var Order $order */
        $order = $options['order'];

        $builder->add('phone', Type\TextType::class, [
            'label' => 'Phone Number',
            'attr' => ['placeholder' => 'XXX-XXX-XXXX', 'inputmode' => 'numeric', 'data-phone-input' => true],
            'label_attr' => [
                'class' => 'text-nowrap',
            ],
            'help_html' => true,
            'help' => '<small>Msg & data rates may apply.</small>',
            'help_attr' => [
                'class' => 'm-0 p-0'
            ],
            'constraints' => [
                new Constraints\NotBlank(message: 'Phone number cannot be empty.'),
                new Constraints\Regex(pattern: '^\s*(?:\+?(\d{1,3}))?[-. (]*(\d{3})[-. )]*(\d{3})[-. ]*(\d{4})(?: *x(\d+))?\s*$^', message: 'Please enter a valid phone number.'),
            ]
        ]);
        if (!isset($order->getBillingAddress()['state']) || empty($order->getBillingAddress()['state'])) {
            $builder->add('state', EntityType::class, [
                'label' => 'Select State',
                'class' => State::class,
                'choice_label' => 'name',
                'choice_value' => 'isoCode',
                'query_builder' => function (EntityRepository $er) use ($country) {
                    $qb = $er->createQueryBuilder('S');
                    return $qb->andWhere($qb->expr()->eq('S.country', ':country'))
                        ->setParameter('country', $country)
                        ->andWhere($qb->expr()->eq('S.enabled', ':enabled'))
                        ->setParameter('enabled', true)
                        ->orderBy('S.name', 'ASC');
                },
                'placeholder' => '-- Select State --',
                'auto_initialize' => false,
                'invalid_message' => false,
                'constraints' => [
                    new Constraints\NotBlank(message: 'State cannot be empty.'),
                ],
            ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'country' => null,
            'order' => null
        ]);
        $resolver->setAllowedTypes('country', ['null', Country::class]);
        $resolver->setAllowedTypes('order', ['null', Order::class]);
    }
}
