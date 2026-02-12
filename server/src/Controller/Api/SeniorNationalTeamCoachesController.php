<?php

namespace App\Controller\Api;

use App\Repository\CoachRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/senior-national-team/coaches', name: 'api_senior_coaches_', methods: ['GET'])]
class SeniorNationalTeamCoachesController extends AbstractController
{
    public function __invoke(Request $request, CoachRepository $coachRepository): JsonResponse
    {
        $query = trim((string) $request->query->get('q', ''));
        $page = max(1, (int) $request->query->get('page', 1));

        $requestedPerPage = (int) $request->query->get('perPage', 12);
        $perPage = in_array($requestedPerPage, [12, 24], true) ? $requestedPerPage : 12;

        $result = $coachRepository->findCoaches($query, $page, $perPage);

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
