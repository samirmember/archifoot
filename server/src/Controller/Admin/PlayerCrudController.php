<?php

namespace App\Controller\Admin;

use App\Entity\City;
use App\Entity\Country;
use App\Entity\Player;
use App\Entity\Region;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
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
            DateField::new('personBirthDate', 'Date de naissance')->setRequired(false),
            AssociationField::new('personBirthCity', 'Ville de naissance')
                ->setFormTypeOption('class', City::class)
                ->setFormTypeOption('choice_label', 'name')
                ->autocomplete(),
            TextField::new('newBirthCityName', 'Nouvelle ville (si absente)')->setRequired(false),
            AssociationField::new('personBirthRegion', 'Région de naissance')
                ->setFormTypeOption('class', Region::class)
                ->setFormTypeOption('choice_label', 'name')
                ->autocomplete(),
            TextField::new('newBirthRegionName', 'Nouvelle région (si absente)')->setRequired(false),
            AssociationField::new('personBirthCountry', 'Pays de naissance')
                ->setFormTypeOption('class', Country::class)
                ->setFormTypeOption('choice_label', 'name')
                ->autocomplete(),
            AssociationField::new('personNationalityCountry', 'Nationalité')
                ->setFormTypeOption('class', Country::class)
                ->setFormTypeOption('choice_label', 'name')
                ->autocomplete(),
            AssociationField::new('primaryPosition', 'Poste')->autocomplete(),
            $photoField,
        ];
    }

    public function persistEntity($entityManager, $entityInstance): void
    {
        if (!$entityInstance instanceof Player || !$entityManager instanceof EntityManagerInterface) {
            parent::persistEntity($entityManager, $entityInstance);
            return;
        }

        $this->applyDynamicLocationEntries($entityInstance, $entityManager);
        $this->optimizeProfilePhoto($entityInstance);
        parent::persistEntity($entityManager, $entityInstance);
    }

    public function updateEntity($entityManager, $entityInstance): void
    {
        if (!$entityInstance instanceof Player || !$entityManager instanceof EntityManagerInterface) {
            parent::updateEntity($entityManager, $entityInstance);
            return;
        }

        $this->applyDynamicLocationEntries($entityInstance, $entityManager);
        $this->optimizeProfilePhoto($entityInstance);
        parent::updateEntity($entityManager, $entityInstance);
    }

    private function applyDynamicLocationEntries(Player $player, EntityManagerInterface $entityManager): void
    {
        $newRegionName = trim((string) $player->getNewBirthRegionName());
        if ($newRegionName !== '') {
            $region = $entityManager->getRepository(Region::class)->findOneBy(['name' => $newRegionName]);
            if (!$region instanceof Region) {
                $region = (new Region())
                    ->setName($newRegionName)
                    ->setCountry($player->getPersonBirthCountry());
                $entityManager->persist($region);
            }
            $player->setPersonBirthRegion($region);
            $player->setNewBirthRegionName(null);
        }

        $newCityName = trim((string) $player->getNewBirthCityName());
        if ($newCityName !== '') {
            $city = $entityManager->getRepository(City::class)->findOneBy(['name' => $newCityName]);
            if (!$city instanceof City) {
                $city = (new City())
                    ->setName($newCityName)
                    ->setCountry($player->getPersonBirthCountry())
                    ->setRegion($player->getPersonBirthRegion());
                $entityManager->persist($city);
            }
            $player->setPersonBirthCity($city);
            $player->setNewBirthCityName(null);
        }
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
