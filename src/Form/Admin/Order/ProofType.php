<?php

namespace App\Form\Admin\Order;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints;
use Vich\UploaderBundle\Form\Type\VichFileType;
use Vich\UploaderBundle\Form\Type\VichImageType;
use App\Entity\Order;
use App\Helper\PromoStoreHelper;
use App\Service\GoogleDriveService;
use Eckinox\TinymceBundle\Form\Type\TinymceType;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class ProofType extends AbstractType
{
    public function __construct(
        private readonly UrlGeneratorInterface  $urlGenerator,
        private readonly GoogleDriveService     $driveService,
        private readonly PromoStoreHelper     $promoStoreHelper,
    ) {}

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var Order|null $order */
        $order = $options['order'] ?? null;
        $latestFiles = $this->driveService->getLatestProofFiles($order->getOrderId());

        // $builder->add('isBlank', CheckboxType::class, [
        //     'label' => 'Blank Proof',
        //     'required' => false,
        // ]);

        $storeInfo = $this->promoStoreHelper->storeInfo( $order->getStoreDomain());
        $defaultMessage = 'Hi! Thank you for your order! Please review your proof. Please ensure all details are correct including the colors and artwork. Please note we strictly follow the proof once approved. We make all customizations proportional to the proof and requested sizes. If any changes are required, please click Request Changes and submit a comment. If the proof looks great, please click Approve and submit the order for production. For questions, please call <a href="tel:+1-877-958-1499">+1-877-958-1499</a>, email   <a href="mailto:' . $storeInfo['storeEmail'] . '">' . $storeInfo['storeEmail'] . '</a>, or message us on our <a href="#" class="live-chat-trigger">live chat</a>.';

        $builder->add('content', TinymceType::class, [
            'label' => 'Message',
            'data' => ($order && count($order->getOrderMessages()) === 0) ? $defaultMessage : null,
            'attr' => [
                'height' => '300',
                'toolbar' => 'insertfile a11ycheck undo redo | bold italic | forecolor backcolor | alignleft aligncenter alignright alignjustify | bullist numlist | link image | code',
                'plugins' => 'advcode advlist anchor autolink fullscreen help image tinydrive lists link media preview searchreplace table visualblocks wordcount',
                'content_style' => 'body { font-family:Helvetica,Arial,sans-serif; font-size:16px }',
                'images_upload_url' => $this->urlGenerator->generate('admin_blog_upload_file', [], UrlGeneratorInterface::ABSOLUTE_URL),
            ],
        ]);

        $imageHelp = 'Allowed formats: PNG, JPG, JPEG. (Max 6MB)';
        if (!empty($latestFiles['image']['url'])) {
            $imageHelp .= '<br><a href="' . $latestFiles['image']['preview'] . '" target="_blank" class="existing-file-link">View drive proof image</a>';
        }

        $builder->add('proofImage', VichImageType::class, [
            'label' => 'Proof Image',
            'help' => $imageHelp,
            'help_html' => true,
            'attr' => [
                'accept' => "image/png, image/jpeg, image/*",
                'class' => 'proof-file-input',
                'data-existing-url' => $latestFiles['image']['url'] ?? '',
            ],
            'image_uri' => false,
            'required' => empty($latestFiles['image']['url']),
            'allow_delete' => false,
            'constraints' => [
                new Constraints\Image([
                    'maxSize' => '6M',
                    'mimeTypes' => [
                        'image/png',
                        'image/jpeg',
                        'image/*',
                    ],
                ]),
            ],
        ]);

        $fileHelp = 'Allowed format: PDF. (Max 50MB)';
        if (!empty($latestFiles['pdf']['url'])) {
            $fileHelp .= '<br><a href="' . $latestFiles['pdf']['preview'] . '" target="_blank" class="existing-file-link">View drive proof PDF</a>';
        }

        $builder->add('proofFile', VichFileType::class, [
            'label' => 'Proof File',
            'help' => $fileHelp,
            'help_html' => true,
            'attr' => [
                'accept' => ".pdf",
                'class' => 'proof-file-input',
                'data-existing-url' => $latestFiles['pdf']['url'] ?? '',
            ],
            'required' => empty($latestFiles['pdf']['url']),
            'allow_delete' => false,
            'constraints' => [
                new Constraints\File([
                    'maxSize' => '50M',
                    'mimeTypes' => [
                        'application/pdf',
                    ],
                ]),
            ],
        ]);

        $builder->add('submit', SubmitType::class, [
            'label' => 'Submit',
        ]);

        $builder->addEventListener(FormEvents::SUBMIT, function (FormEvent $event) use ($latestFiles) {
            $data = $event->getData();
            $form = $event->getForm();

            $proofImageData = $form->get('proofImage')->getData();
            $proofFileData  = $form->get('proofFile')->getData();
            $hasDriveImage  = !empty($latestFiles['image']);
            $hasDriveFile   = !empty($latestFiles['pdf']);

            // Check if both empty (nothing uploaded + nothing in Drive)
            if (
                !$proofImageData && !$hasDriveImage &&
                !$proofFileData && !$hasDriveFile
            ) {
                $form->addError(new FormError('Please upload a proof image or proof file (either manually or via Google Drive).'));
                return;
            }

            // If proof image missing but PDF present, warn for image
            if (
                !$proofImageData && !$hasDriveImage &&
                ($proofFileData || $hasDriveFile)
            ) {
                $form->get('proofImage')->addError(
                    new FormError('Proof image is missing. Please upload an image or ensure it exists in Google Drive.')
                );
                return;
            }

            // If proof PDF missing but image present, warn for PDF
            if (
                !$proofFileData && !$hasDriveFile &&
                ($proofImageData || $hasDriveImage)
            ) {
                $form->get('proofFile')->addError(
                    new FormError('Proof PDF is missing. Please upload a PDF or ensure it exists in Google Drive.')
                );
                return;
            }

            if (!$proofImageData && !empty($latestFiles['image'])) {
                try {
                    $fileContent = $this->driveService->downloadFile($latestFiles['image']['id']);
                    $extension = pathinfo($latestFiles['image']['name'], PATHINFO_EXTENSION) ?: 'jpg';
                    $tmpPath = sys_get_temp_dir() . '/' . uniqid('proof_img_', true) . '.' . $extension;
                    file_put_contents($tmpPath, $fileContent);

                    $uploadedFile = new UploadedFile(
                        path: $tmpPath,
                        originalName: $latestFiles['image']['name'],
                        mimeType: $latestFiles['image']['mimeType'] ?? null,
                        error: null,
                        test: true
                    );

                    $data['proofImage'] = $uploadedFile;
                } catch (\Exception $e) {
                    $form->get('proofImage')->addError(
                        new FormError('Failed to download existing image from Google Drive: ' . $e->getMessage())
                    );
                    return;
                }
            }

            if (!$proofFileData && !empty($latestFiles['pdf']['id'])) {
                try {
                    $fileContent = $this->driveService->downloadFile($latestFiles['pdf']['id']);
                    $tmpPath = sys_get_temp_dir() . '/' . uniqid('proof_pdf_', true) . '.pdf';
                    file_put_contents($tmpPath, $fileContent);
                    $uploadedFile = new UploadedFile(
                        path: $tmpPath,
                        originalName: $latestFiles['pdf']['name'],
                        mimeType: 'application/pdf',
                        error: null,
                        test: true
                    );

                    $data['proofFile'] = $uploadedFile;
                } catch (\Exception $e) {
                    $form->get('proofFile')->addError(
                        new FormError('Failed to download existing PDF from Google Drive: ' . $e->getMessage())
                    );
                    return;
                }
            }

            $event->setData($data);
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'order' => null,
        ]);
    }
}
