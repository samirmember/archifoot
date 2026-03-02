<?php

namespace App\Service;

use Symfony\Component\DependencyInjection\Attribute\Autowire;

class ImageUploadOptimizer
{
    public function __construct(
        #[Autowire('%kernel.project_dir%')]
        private readonly string $projectDir,
    ) {
    }

    public function optimizePublicUploadPath(?string $storedPath): ?string
    {
        if ($storedPath === null || trim($storedPath) === '' || !function_exists('getimagesize') || !function_exists('imagewebp')) {
            return $storedPath;
        }

        $hasLeadingSlash = str_starts_with($storedPath, '/');
        $relativePath = ltrim($storedPath, '/');
        $fullPath = $this->projectDir . '/public/' . $relativePath;

        if (!is_file($fullPath)) {
            return $storedPath;
        }

        $imageInfo = @getimagesize($fullPath);
        if ($imageInfo === false) {
            return $storedPath;
        }

        [$width, $height, $type] = $imageInfo;
        if ($width < 1 || $height < 1) {
            return $storedPath;
        }

        $source = match ($type) {
            IMAGETYPE_JPEG => @imagecreatefromjpeg($fullPath),
            IMAGETYPE_PNG => @imagecreatefrompng($fullPath),
            IMAGETYPE_WEBP => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($fullPath) : false,
            default => false,
        };

        if ($source === false) {
            return $storedPath;
        }

        $maxDimension = 1600;
        $ratio = min($maxDimension / $width, $maxDimension / $height, 1);
        $targetWidth = max(1, (int) round($width * $ratio));
        $targetHeight = max(1, (int) round($height * $ratio));

        $target = imagecreatetruecolor($targetWidth, $targetHeight);
        imagealphablending($target, false);
        imagesavealpha($target, true);

        imagecopyresampled($target, $source, 0, 0, 0, 0, $targetWidth, $targetHeight, $width, $height);

        $pathInfo = pathinfo($relativePath);
        $webpRelativePath = ($pathInfo['dirname'] ?? '.') !== '.'
            ? $pathInfo['dirname'] . '/' . ($pathInfo['filename'] ?? 'image') . '.webp'
            : ($pathInfo['filename'] ?? 'image') . '.webp';
        $webpFullPath = $this->projectDir . '/public/' . $webpRelativePath;

        $isSaved = @imagewebp($target, $webpFullPath, 82);

        imagedestroy($source);
        imagedestroy($target);

        if (!$isSaved) {
            return $storedPath;
        }

        if (realpath($fullPath) !== realpath($webpFullPath) && is_file($fullPath)) {
            @unlink($fullPath);
        }

        return $hasLeadingSlash ? '/' . $webpRelativePath : $webpRelativePath;
    }
}
