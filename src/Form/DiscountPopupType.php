<?php
namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Validator\Constraints;

class DiscountPopupType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('email', EmailType::class, [
                'attr' => ['placeholder' => 'Email Address'],
                'required' => false,
                'constraints' => [
                    new Constraints\Email(message: 'Please enter a valid email address.')
                ]
            ])
            ->add('phone_number', TelType::class, [
                'attr' => ['placeholder' => 'Phone Number', 'data-numeric-input' => true,'inputmode' => 'numeric', 'data-phone-input' => true],
                'required' => false,
                'constraints' => [
                    new Constraints\Regex(pattern: '^\s*(?:\+?(\d{1,3}))?[-. (]*(\d{3})[-. )]*(\d{3})[-. ]*(\d{4})(?: *x(\d+))?\s*$^', message: 'Please enter a valid phone number.'),
                ]
            ]);
            $builder->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event) {
                $form = $event->getForm();

                $email = $form->get('email')->getData();
                $phoneNumber = $form->get('phone_number')->getData();

                if (empty($email) && empty($phoneNumber)) {
                    $form->get('email')->addError(new FormError('Either an email or a phone number must be provided.'));
                    $form->get('phone_number')->addError(new FormError('Either an email or a phone number must be provided.'));
                }
            });
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            // Configure your form options here
        ]);
    }
}
