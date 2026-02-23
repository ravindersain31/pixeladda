<?php

namespace App\Controller\Api;

use App\Entity\Country;
use App\Entity\State;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;

class GEOController extends AbstractController
{
    #[Route(path: '/geo/states/{countryCode}', name: 'list_states', defaults: ['countryCode' => 'US'], methods: ['GET'])]
    public function states(string $countryCode, Request $request, EntityManagerInterface $entityManager, SerializerInterface $serializer): Response
    {
        $country = $entityManager->getRepository(Country::class)->findOneBy(['isoCode' => strtoupper($countryCode)]);
        if (!$country instanceof Country) {
            return $this->json([
                'success' => false,
                'message' => 'Country not found'
            ]);
        }
        $states = $entityManager->getRepository(State::class)->findBy(['country' => $country], ['name' => 'ASC']);
        $data = json_decode($serializer->serialize($states, 'json', ['groups' => 'apiData']));
        return $this->json([
            'success' => true,
            'number' => count($data),
            'data' => $data,
        ]);
    }
}