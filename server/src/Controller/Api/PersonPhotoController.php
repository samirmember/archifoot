<?php

namespace App\Controller\Api;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;

final class PersonPhotoController extends AbstractController
{
    public function __construct(
        #[Autowire('%kernel.project_dir%/public/uploads/players')]
        private readonly string $playersUploadDirectory,
        #[Autowire('%kernel.project_dir%/public/uploads/coaches')]
        private readonly string $coachesUploadDirectory,
        #[Autowire('%kernel.project_dir%/public/uploads/referees')]
        private readonly string $refereesUploadDirectory,
        #[Autowire('%kernel.project_dir%/public/uploads/person')]
        private readonly string $personsUploadDirectory,
    ) {
    }

    #[Route('/api/person-photo/{category}/{path}', name: 'api_person_photo', requirements: ['path' => '.+'], methods: ['GET'])]
    public function __invoke(string $category, string $path): BinaryFileResponse
    {
        $uploadDirectory = $this->personsUploadDirectory;
        $sanitizedPath = ltrim($path, '/');
        $fullPath = $uploadDirectory . '/' . $sanitizedPath;

        $realUploadDirectory = realpath($uploadDirectory);
        $realFilePath = realpath($fullPath);

        if (
            $realUploadDirectory === false
            || $realFilePath === false
            || !str_starts_with($realFilePath, $realUploadDirectory . DIRECTORY_SEPARATOR)
            || !is_file($realFilePath)
        ) {
            throw new NotFoundHttpException('Person photo not found.');
        }

        return new BinaryFileResponse($realFilePath);
    }

    private function resolveUploadDirectory(string $category): string
    {
        return match (strtolower(trim($category))) {
            'players', 'players' => $this->playersUploadDirectory,
            'coaches', 'coaches' => $this->coachesUploadDirectory,
            'referees', 'referees' => $this->refereesUploadDirectory,
            default => throw new NotFoundHttpException('Unknown photo category.'),
        };
    }
}
