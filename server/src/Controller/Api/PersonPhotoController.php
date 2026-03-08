<?php

namespace App\Controller\Api;

use Psr\Log\LoggerInterface;
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
        private readonly LoggerInterface $logger,
    ) {
    }

    #[Route('/api/person-photo/{category}/{path}', name: 'api_person_photo', requirements: ['path' => '.+'], methods: ['GET'])]
    public function __invoke(string $category, string $path): BinaryFileResponse
    {
        $this->logger->info('Person photo request received', [
            'category' => $category,
            'path' => $path,
        ]);

        try {
            $uploadDirectory = $this->personsUploadDirectory;
        } catch (NotFoundHttpException $e) {
            $this->logger->error('Category resolution failed', [
                'category' => $category,
                'message' => $e->getMessage(),
            ]);
            throw $e;
        }

        $this->logger->debug('Upload directory resolved', [
            'directory' => $uploadDirectory,
        ]);

        $sanitizedPath = ltrim($path, '/');
        $fullPath = $uploadDirectory . '/' . $sanitizedPath;

        $this->logger->debug('Paths constructed', [
            'sanitizedPath' => $sanitizedPath,
            'fullPath' => $fullPath,
        ]);

        $realUploadDirectory = realpath($uploadDirectory);
        $realFilePath = realpath($fullPath);

        $this->logger->debug('Realpath resolution results', [
            'realUploadDirectory' => $realUploadDirectory,
            'realFilePath' => $realFilePath,
        ]);

        if ($realUploadDirectory === false) {
            $this->logger->error('Real upload directory not found', ['directory' => $uploadDirectory]);
            throw new NotFoundHttpException('Upload directory not found.');
        }

        if ($realFilePath === false) {
            $this->logger->error('Real file path not found', ['path' => $fullPath]);
            throw new NotFoundHttpException('Person photo file not found.');
        }

        if (!str_starts_with($realFilePath, $realUploadDirectory . DIRECTORY_SEPARATOR)) {
            $this->logger->error('Path traversal attempt or file outside directory', [
                'realFilePath' => $realFilePath,
                'realUploadDirectory' => $realUploadDirectory,
            ]);
            throw new NotFoundHttpException('Invalid file path.');
        }

        if (!is_file($realFilePath)) {
            $this->logger->error('Path is not a file', ['realFilePath' => $realFilePath]);
            throw new NotFoundHttpException('Target path is not a file.');
        }

        $this->logger->info('Serving person photo', ['realFilePath' => $realFilePath]);

        return new BinaryFileResponse($realFilePath);
    }

    private function resolveUploadDirectory(string $category): string
    {
        return match (strtolower(trim($category))) {
            'players' => $this->playersUploadDirectory,
            'coaches' => $this->coachesUploadDirectory,
            'referees' => $this->refereesUploadDirectory,
            'person', 'persons' => $this->personsUploadDirectory,
            default => throw new NotFoundHttpException('Unknown photo category.'),
        };
    }
}
