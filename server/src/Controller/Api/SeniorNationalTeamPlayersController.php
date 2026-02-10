<?php

namespace App\Controller\Api;

use App\Repository\PlayerRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/senior-national-team/players', name: 'api_senior_players_', methods: ['GET'])]
class SeniorNationalTeamPlayersController extends AbstractController
{
    public function __invoke(Request $request, PlayerRepository $playerRepository): JsonResponse
    {
        $query = trim((string) $request->query->get('q', ''));
        $page = max(1, (int) $request->query->get('page', 1));

        $requestedPerPage = (int) $request->query->get('perPage', 10);
        $perPage = in_array($requestedPerPage, [10, 20], true) ? $requestedPerPage : 10;

        $result = $playerRepository->findAlgeriaSeniorPlayers($query, $page, $perPage);

        return $this->json([
            'items' => $result['items'],
            'meta' => [
                'page' => $page,
                'perPage' => $perPage,
                'total' => $result['total'],
                'totalPages' => max(1, (int) ceil($result['total'] / $perPage)),
            ],
        ]);
    }
}

