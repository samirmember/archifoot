<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;

final class PlayerPhotoController extends AbstractController
{
    public function __construct(
        #[Autowire('%kernel.project_dir%/public/uploads/players')]
        private readonly string $playersUploadDirectory,
    ) {
    }

    #[Route('/uploads/players/{path}', name: 'app_player_photo', requirements: ['path' => '.+'], methods: ['GET'])]
    public function __invoke(string $path): BinaryFileResponse
    {
        $sanitizedPath = ltrim($path, '/');
        $fullPath = $this->playersUploadDirectory . '/' . $sanitizedPath;

        $realUploadDir = realpath($this->playersUploadDirectory);
        $realFilePath = realpath($fullPath);

        if (
            $realUploadDir === false
            || $realFilePath === false
            || !str_starts_with($realFilePath, $realUploadDir . DIRECTORY_SEPARATOR)
            || !is_file($realFilePath)
        ) {
            throw new NotFoundHttpException('Player photo not found.');
        }

        return new BinaryFileResponse($realFilePath);
    }
}
