<?php

namespace App\Controller\Api;

use App\Repository\PlayerRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/senior-national-team/players/{slug}', name: 'api_senior_players_profile', methods: ['GET'])]
class SeniorNationalTeamPlayerProfileController extends AbstractController
{
    public function __invoke(string $slug, PlayerRepository $playerRepository): JsonResponse
    {
        $profile = $playerRepository->findAlgeriaSeniorPlayerProfileBySlug(trim($slug));

        if ($profile === null) {
            return $this->json(['message' => 'Joueur introuvable'], 404);
        }

        return $this->json($profile);
    }
}
