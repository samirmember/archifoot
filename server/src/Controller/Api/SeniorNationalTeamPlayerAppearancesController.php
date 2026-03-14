<?php

namespace App\Controller\Api;

use App\Repository\PlayerRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

class SeniorNationalTeamPlayerAppearancesController extends AbstractController
{
    #[Route('/api/senior-national-team/players/{slug}/appearances', name: 'api_senior_players_appearances', methods: ['GET'])]
    public function __invoke(string $slug, Request $request, PlayerRepository $playerRepository): JsonResponse
    {
        $page = max(1, (int) $request->query->get('page', 1));
        $requestedItemsPerPage = (int) $request->query->get('itemsPerPage', 20);
        $itemsPerPage = max(1, min($requestedItemsPerPage, 20));

        $filters = [
            'seasonName' => trim((string) $request->query->get('seasonName', '')) ?: null,
            'teamIso3' => trim((string) $request->query->get('teamIso3', '')) ?: null,
            'competitionId' => $request->query->has('competitionId')
                ? (int) $request->query->get('competitionId')
                : null,
        ];

        $result = $playerRepository->findAlgeriaSeniorPlayerAppearancesBySlug(
            $slug,
            $filters,
            $page,
            $itemsPerPage
        );

        if ($result === null) {
            return $this->json(['message' => 'Joueur introuvable.'], 404);
        }

        return $this->json([
            'items' => $result['items'],
            'meta' => [
                'page' => $page,
                'itemsPerPage' => $itemsPerPage,
                'total' => $result['total'],
                'totalPages' => max(1, (int) ceil($result['total'] / $itemsPerPage)),
            ],
        ]);
    }
}
