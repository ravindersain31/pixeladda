<?php

namespace App\Controller\Api;

use App\Entity\State;
use App\Repository\StateRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class AjaxController extends AbstractController
{

    #[Route('/get-states/{countryId}', name: 'get_states', methods: ['GET'])]
    public function getStates(int $countryId, StateRepository $stateRepository)
    {
        $states = $stateRepository->findEnabledByCountry($countryId);
        $data = [];
        foreach ($states as $state) {
            $data[] = [
                'id' => $state->getId(),
                'name' => $state->getName(),
            ];
        }

        return $this->json($data);
    }
}
