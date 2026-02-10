<?php

namespace App\Controller\Api;

use App\Repository\PlayerRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

class SeniorNationalTeamPlayerDetailsController extends AbstractController
{
    #[Route('/api/senior-national-team/players/{slug}', name: 'api_senior_players_show', methods: ['GET'])]
    public function __invoke(string $slug, PlayerRepository $playerRepository): JsonResponse
    {
        $player = $playerRepository->findAlgeriaSeniorPlayerBySlug($slug);

        if ($player === null) {
            return $this->json(['message' => 'Joueur introuvable.'], 404);
        }

        return $this->json($player);
    }
}
