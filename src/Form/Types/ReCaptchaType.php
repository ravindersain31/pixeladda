<?php

namespace App\Form\Types;

use App\Helper\RecaptchaValidatorHelper;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints;

class ReCaptchaType extends AbstractType
{
    public function __construct(private readonly RecaptchaValidatorHelper $recaptchaValidator)
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('token', Type\HiddenType::class, [
            'constraints' => [
                new Constraints\NotBlank(['message' => 'Please click "I\'m not a robot".']),
            ]
        ]);

        $builder->addEventListener(FormEvents::POST_SUBMIT, function ($event) {
            $form = $event->getForm();
            $data = $form->getData();
            $token = $data['token'];
            if ($token) {
                $isValid = $this->recaptchaValidator->validate($token);
                if (!$isValid) {
                    $form->get('token')->addError(new FormError('reCAPTCHA validation failed.'));
                }
            }
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            // Configure your form options here
        ]);
    }
}
