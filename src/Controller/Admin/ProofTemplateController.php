<?php

namespace App\Controller\Admin;

use App\Constant\Editor\Addons;
use App\Entity\Product;
use App\Entity\ProductType;
use App\Entity\ProofFrameTemplate;
use App\Entity\ProofGrommetTemplate;
use App\Entity\ProofWireStakeTemplate;
use App\Entity\ProofTemplate;
use App\Repository\ProofFrameTemplateRepository;
use App\Repository\ProofGrommetTemplateRepository;
use App\Repository\ProofWireStakeTemplateRepository;
use App\Repository\ProofTemplateRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/proof-template', name: 'proof_template_')]
class ProofTemplateController extends AbstractController
{
    private const MAX_FILE_SIZE = 6 * 1024 * 1024; // 6MB
    private const ALLOWED_MIME_TYPES = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];

    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {}

    #[Route('', name: 'list', methods: ['GET'])]
    public function index(
        ProofTemplateRepository $repository,
        ProofGrommetTemplateRepository $grommetRepository,
        ProofWireStakeTemplateRepository $wireStakeRepository,
        SerializerInterface $serializer
    ): Response
    {
        /* @var ProductType|null $productType */
        $productType = $this->em->getRepository(ProductType::class)->findBySlug('yard-sign');
        $frameProduct = $this->em->getRepository(Product::class)->findOneBy(['sku' => 'WIRE-STAKE']);
        $frameVariants = $this->em->getRepository(Product::class)->findActiveVariants($frameProduct);
        $variants = $productType->getDefaultVariants();
        $proofTemplates = $repository->findBy([], ['id' => 'DESC']);
        $grommetTemplates = $grommetRepository->findBy([], ['id' => 'DESC']);
        $wireStakeTemplates = $wireStakeRepository->findBy([], ['id' => 'DESC']);

        // Get grommet colors from Addons constant
        $grommetColors = [
            ['name' => Addons::GROMMET_COLOR_SILVER, 'label' => 'Silver'],
            ['name' => Addons::GROMMET_COLOR_GOLD, 'label' => 'Gold'],
            ['name' => Addons::GROMMET_COLOR_BLACK, 'label' => 'Black'],
        ];

        return $this->render('admin/proof-template/index.html.twig', [
            'initialTemplates' => json_decode($serializer->serialize($proofTemplates, 'json', [AbstractNormalizer::GROUPS => ['apiData']])),
            'initialGrommetTemplates' => json_decode($serializer->serialize($grommetTemplates, 'json', [AbstractNormalizer::GROUPS => ['apiData']])),
            'initialWireStakeTemplates' => json_decode($serializer->serialize($wireStakeTemplates, 'json', [AbstractNormalizer::GROUPS => ['apiData']])),
            'variantChoices' => $variants,
            'frameVariants' => json_decode($serializer->serialize($frameVariants, 'json', [AbstractNormalizer::GROUPS => ['apiData']])),
            'grommetColors' => $grommetColors,
        ]);
    }

    #[Route('/save', name: 'save', methods: ['POST'])]
    public function saveProofTemplate(
        Request $request,
        EntityManagerInterface $em,
        ProofTemplateRepository $proofTemplateRepository,
        ProofFrameTemplateRepository $proofFrameTemplateRepository,
        ValidatorInterface $validator
    ): JsonResponse {
        try {
            $templatesData = json_decode($request->get('templates'), true);
            $deletedVariants = json_decode($request->get('deletedVariants', '[]'), true);
            $deletedFrames = json_decode($request->get('deletedFrames', '[]'), true);

            if ($templatesData === null) {
                return new JsonResponse(['error' => 'Invalid templates data'], 400);
            }

            if (empty($templatesData) && empty($deletedVariants) && empty($deletedFrames)) {
                return new JsonResponse(['error' => 'No changes to save'], 400);
            }

            // Handle deleted variants
            foreach ($deletedVariants as $variantId) {
                $variant = $proofTemplateRepository->find($variantId);
                if ($variant) {
                    $em->remove($variant);
                }
            }

            // Handle deleted frames
            foreach ($deletedFrames as $frameId) {
                $frame = $proofFrameTemplateRepository->find($frameId);
                if ($frame) {
                    $em->remove($frame);
                }
            }

            // Process templates
            if (!empty($templatesData)) {
                foreach ($templatesData as $variantIndex => $variantData) {
                    $action = $variantData['_action'] ?? 'create';
                    $variantId = $variantData['id'] ?? null;

                    if ($action === 'update' && $variantId) {
                        $proofTemplate = $proofTemplateRepository->find($variantId);
                        if (!$proofTemplate) {
                            continue;
                        }
                    } else {
                        $proofTemplate = new ProofTemplate();
                    }

                    $proofTemplate->setSize($variantData['size'] ?? '');

                    // Handle variant image
                    if ($variantData['hasNewImage'] ?? false) {
                        $imageKey = $variantId ? "template_{$variantId}_image" : "template_new_{$variantIndex}_image";
                        $variantImage = $request->files->get($imageKey);

                        if ($variantImage instanceof UploadedFile) {
                            $validationResult = $this->validateImage($variantImage, $validator);
                            if ($validationResult !== true) {
                                return new JsonResponse(['error' => "Variant #{$variantIndex} image: {$validationResult}"], 400);
                            }
                            $proofTemplate->setImageFile($variantImage);
                        }
                    }

                    // Handle ProofFrameTemplates
                    if (!empty($variantData['proofFrameTemplates'])) {
                        foreach ($variantData['proofFrameTemplates'] as $frameIndex => $frameData) {
                            $frameAction = $frameData['_action'] ?? 'create';
                            $frameId = $frameData['id'] ?? null;

                            if ($frameAction === 'update' && $frameId) {
                                $frameTemplate = $proofFrameTemplateRepository->find($frameId);
                                if (!$frameTemplate) {
                                    continue;
                                }
                            } else {
                                $frameTemplate = new ProofFrameTemplate();
                                $frameTemplate->setProofTemplate($proofTemplate);
                            }

                            $frameTemplate->setFrameType($frameData['frameType'] ?? '');

                            if ($frameData['hasNewImage'] ?? false) {
                                $imageKey = $frameId ? "frame_{$frameId}_image" : "frame_new_{$variantIndex}_{$frameIndex}_image";
                                $frameImage = $request->files->get($imageKey);
                                
                                if ($frameImage instanceof UploadedFile) {
                                    $validationResult = $this->validateImage($frameImage, $validator);
                                    if ($validationResult !== true) {
                                        return new JsonResponse(['error' => "Variant #{$variantIndex}, Frame #{$frameIndex} image: {$validationResult}"], 400);
                                    }
                                    $frameTemplate->setImageFile($frameImage);
                                }
                            }

                            $em->persist($frameTemplate);
                        }
                    }

                    $em->persist($proofTemplate);
                }
            }

            $em->flush();

            return new JsonResponse([
                'status' => 'success',
                'message' => 'Proof templates saved successfully.',
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'status' => 'error',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    #[Route('/save-grommets', name: 'save_grommets', methods: ['POST'])]
    public function saveGrommetTemplates(
        Request $request,
        EntityManagerInterface $em,
        ProofGrommetTemplateRepository $grommetRepository,
        ValidatorInterface $validator
    ): JsonResponse {
        try {
            $grommetsData = json_decode($request->get('grommets'), true);
            $deletedGrommets = json_decode($request->get('deletedGrommets', '[]'), true);

            if ($grommetsData === null) {
                return new JsonResponse(['error' => 'Invalid grommets data'], 400);
            }

            if (empty($grommetsData) && empty($deletedGrommets)) {
                return new JsonResponse(['error' => 'No changes to save'], 400);
            }

            // Handle deleted grommets
            foreach ($deletedGrommets as $grommetId) {
                $grommet = $grommetRepository->find($grommetId);
                if ($grommet) {
                    $em->remove($grommet);
                }
            }

            // Process grommet templates
            if (!empty($grommetsData)) {
                foreach ($grommetsData as $index => $grommetData) {
                    $action = $grommetData['_action'] ?? 'create';
                    $grommetId = $grommetData['id'] ?? null;

                    if ($action === 'update' && $grommetId) {
                        $grommetTemplate = $grommetRepository->find($grommetId);
                        if (!$grommetTemplate) {
                            continue;
                        }
                    } else {
                        $grommetTemplate = new ProofGrommetTemplate();
                    }

                    $grommetTemplate->setGrommetColor($grommetData['grommetColor'] ?? '');

                    // Handle image
                    if ($grommetData['hasNewImage'] ?? false) {
                        $imageKey = $grommetId ? "grommet_{$grommetId}_image" : "grommet_new_{$index}_image";
                        $image = $request->files->get($imageKey);

                        if ($image instanceof UploadedFile) {
                            $validationResult = $this->validateImage($image, $validator);
                            if ($validationResult !== true) {
                                return new JsonResponse(['error' => "Grommet #{$index} image: {$validationResult}"], 400);
                            }
                            $grommetTemplate->setImageFile($image);
                        }
                    }

                    $em->persist($grommetTemplate);
                }
            }

            $em->flush();

            return new JsonResponse([
                'status' => 'success',
                'message' => 'Grommet templates saved successfully.',
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'status' => 'error',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    #[Route('/save-wire-stakes', name: 'save_wire_stakes', methods: ['POST'])]
    public function saveWireStakeTemplates(
        Request $request,
        EntityManagerInterface $em,
        ProofWireStakeTemplateRepository $wireStakeRepository,
        ValidatorInterface $validator
    ): JsonResponse {
        try {
            $wireStakesData = json_decode($request->get('wireStakes'), true);
            $deletedWireStakes = json_decode($request->get('deletedWireStakes', '[]'), true);

            if ($wireStakesData === null) {
                return new JsonResponse(['error' => 'Invalid wire stakes data'], 400);
            }

            if (empty($wireStakesData) && empty($deletedWireStakes)) {
                return new JsonResponse(['error' => 'No changes to save'], 400);
            }

            // Handle deleted wire stakes
            foreach ($deletedWireStakes as $wireStakeId) {
                $wireStake = $wireStakeRepository->find($wireStakeId);
                if ($wireStake) {
                    $em->remove($wireStake);
                }
            }

            // Process wire stake templates
            if (!empty($wireStakesData)) {
                foreach ($wireStakesData as $index => $wireStakeData) {
                    $action = $wireStakeData['_action'] ?? 'create';
                    $wireStakeId = $wireStakeData['id'] ?? null;

                    if ($action === 'update' && $wireStakeId) {
                        $wireStakeTemplate = $wireStakeRepository->find($wireStakeId);
                        if (!$wireStakeTemplate) {
                            continue;
                        }
                    } else {
                        $wireStakeTemplate = new ProofWireStakeTemplate();
                    }

                    $wireStakeTemplate->setWireStakeType($wireStakeData['wireStakeType'] ?? '');

                    // Handle image
                    if ($wireStakeData['hasNewImage'] ?? false) {
                        $imageKey = $wireStakeId ? "wirestake_{$wireStakeId}_image" : "wirestake_new_{$index}_image";
                        $image = $request->files->get($imageKey);

                        if ($image instanceof UploadedFile) {
                            $validationResult = $this->validateImage($image, $validator);
                            if ($validationResult !== true) {
                                return new JsonResponse(['error' => "Wire Stake #{$index} image: {$validationResult}"], 400);
                            }
                            $wireStakeTemplate->setImageFile($image);
                        }
                    }

                    $em->persist($wireStakeTemplate);
                }
            }

            $em->flush();

            return new JsonResponse([
                'status' => 'success',
                'message' => 'Wire stake templates saved successfully.',
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'status' => 'error',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    private function validateImage(UploadedFile $file, ValidatorInterface $validator): string|bool
    {
        if (!$file->isValid()) {
            return 'File upload failed: ' . $file->getErrorMessage();
        }

        $constraints = [
            new Assert\File([
                'maxSize' => self::MAX_FILE_SIZE,
                'mimeTypes' => self::ALLOWED_MIME_TYPES,
                'maxSizeMessage' => 'File size must be less than {{ limit }}{{ suffix }}',
                'mimeTypesMessage' => 'Only JPEG, PNG, and WebP images are allowed',
            ]),
        ];

        $violations = $validator->validate($file, $constraints);

        if (count($violations) > 0) {
            return (string) $violations[0]->getMessage();
        }

        return true;
    }
}