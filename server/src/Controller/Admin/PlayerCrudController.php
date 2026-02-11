<?php

namespace App\Controller\Admin;

use App\Entity\Player;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class PlayerCrudController extends AbstractCrudController
{
    public function __construct(
        #[Autowire('%kernel.project_dir%/public/uploads/players')]
        private readonly string $playerPhotoUploadDir,
    ) {
    }

    public static function getEntityFqcn(): string
    {
        return Player::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Joueur')
            ->setEntityLabelInPlural('Joueurs');
    }

    public function configureFields(string $pageName): iterable
    {
        $photoField = ImageField::new('photoUrl', 'Photo')
            ->setBasePath('/uploads/players')
            ->setUploadDir('public/uploads/players')
            ->setUploadedFileNamePattern('[uuid].[extension]')
            ->setRequired(false);

        return [
            TextField::new('personFullName', 'Nom complet'),
            AssociationField::new('primaryPosition', 'Poste'),
            $photoField,
        ];
    }

    public function persistEntity($entityManager, $entityInstance): void
    {
        if (!$entityInstance instanceof Player) {
            parent::persistEntity($entityManager, $entityInstance);
            return;
        }

        $this->optimizeProfilePhoto($entityInstance);
        parent::persistEntity($entityManager, $entityInstance);
    }

    public function updateEntity($entityManager, $entityInstance): void
    {
        if (!$entityInstance instanceof Player) {
            parent::updateEntity($entityManager, $entityInstance);
            return;
        }

        $this->optimizeProfilePhoto($entityInstance);
        parent::updateEntity($entityManager, $entityInstance);
    }

    private function optimizeProfilePhoto(Player $player): void
    {
        $photoUrl = $player->getPhotoUrl();
        if ($photoUrl === null || $photoUrl === '') {
            return;
        }

        $relativePath = ltrim($photoUrl, '/');
        $fullPath = dirname($this->playerPhotoUploadDir) . '/' . $relativePath;

        if (!is_file($fullPath) || !function_exists('getimagesize')) {
            return;
        }

        $imageInfo = @getimagesize($fullPath);
        if ($imageInfo === false) {
            return;
        }

        [$width, $height, $type] = $imageInfo;
        if ($width < 1 || $height < 1) {
            return;
        }

        $source = match ($type) {
            IMAGETYPE_JPEG => @imagecreatefromjpeg($fullPath),
            IMAGETYPE_PNG => @imagecreatefrompng($fullPath),
            IMAGETYPE_WEBP => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($fullPath) : false,
            default => false,
        };

        if ($source === false) {
            return;
        }

        $cropSize = min($width, $height);
        $srcX = (int) floor(($width - $cropSize) / 2);
        $srcY = (int) floor(($height - $cropSize) / 2);

        $targetSize = min(512, $cropSize);
        $target = imagecreatetruecolor($targetSize, $targetSize);

        imagealphablending($target, false);
        imagesavealpha($target, true);

        imagecopyresampled(
            $target,
            $source,
            0,
            0,
            $srcX,
            $srcY,
            $targetSize,
            $targetSize,
            $cropSize,
            $cropSize,
        );

        if ($type === IMAGETYPE_PNG) {
            imagepng($target, $fullPath, 7);
        } elseif ($type === IMAGETYPE_WEBP && function_exists('imagewebp')) {
            imagewebp($target, $fullPath, 82);
        } else {
            imagejpeg($target, $fullPath, 82);
        }

        imagedestroy($source);
        imagedestroy($target);
    }
}
