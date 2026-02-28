<?php

namespace App\Controller\Admin;

use App\Entity\City;
use App\Entity\Coach;
use App\Entity\Country;
use App\Entity\Region;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FilterCollection;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\SearchDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\Field;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\EntityFilter;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\RequestStack;

class CoachCrudController extends AbstractCrudController
{
    public function __construct(
        #[Autowire('%kernel.project_dir%/public/uploads/person')]
        private readonly string $personPhotoUploadDir,
        private readonly RequestStack $requestStack,
    ) {
    }

    public static function getEntityFqcn(): string
    {
        return Coach::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Entraîneur')
            ->setEntityLabelInPlural('Entraîneurs')
            ->setSearchFields(['person.fullName']);
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(EntityFilter::new('person.nationalityCountry', 'Nationalité'));
    }

    public function configureFields(string $pageName): iterable
    {
        $photoField = ImageField::new('photoUrl', 'Photo')
            ->setBasePath('/uploads/person')
            ->setUploadDir('public/uploads/person')
            ->setUploadedFileNamePattern('[uuid].[extension]')
            ->setRequired(false);

        if ($pageName === Crud::PAGE_INDEX) {
            return [
                TextField::new('personFullName', 'Nom complet'),
                TextField::new('personNationalityCountryName', 'Nationalité'),
                ChoiceField::new('role', 'Rôle')->setChoices(array_flip(Coach::ROLES)),
                $photoField,
            ];
        }

        return [
            TextField::new('personFullName', 'Nom complet'),
            DateField::new('personBirthDate', 'Date de naissance')->setRequired(false),
            Field::new('personBirthCity', 'Ville de naissance')
                ->setFormType(EntityType::class)
                ->setFormTypeOption('class', City::class)
                ->setFormTypeOption('choice_label', 'name')
                ->setFormTypeOption('row_attr', ['class' => 'js-location-source-row'])
                ->setFormTypeOption('required', false),
            TextField::new('newBirthCityName', 'Nouvelle ville (si absente)')
                ->setFormTypeOption('row_attr', ['class' => 'js-location-target-row'])
                ->setRequired(false),
            Field::new('personBirthRegion', 'Région de naissance')
                ->setFormType(EntityType::class)
                ->setFormTypeOption('class', Region::class)
                ->setFormTypeOption('choice_label', 'name')
                ->setFormTypeOption('row_attr', ['class' => 'js-location-source-row'])
                ->setFormTypeOption('required', false),
            TextField::new('newBirthRegionName', 'Nouvelle région (si absente)')
                ->setFormTypeOption('row_attr', ['class' => 'js-location-target-row'])
                ->setRequired(false),
            Field::new('personBirthCountry', 'Pays de naissance')
                ->setFormType(EntityType::class)
                ->setFormTypeOption('class', Country::class)
                ->setFormTypeOption('choice_label', 'name')
                ->setFormTypeOption('required', false),
            Field::new('personNationalityCountry', 'Nationalité')
                ->setFormType(EntityType::class)
                ->setFormTypeOption('class', Country::class)
                ->setFormTypeOption('choice_label', 'name')
                ->setFormTypeOption('required', false),
            ChoiceField::new('role', 'Rôle')
                ->setChoices(array_flip(Coach::ROLES)),
            $photoField,
        ];
    }

    public function createIndexQueryBuilder(SearchDto $searchDto, EntityDto $entityDto, FieldCollection $fields, FilterCollection $filters): QueryBuilder
    {
        $queryBuilder = parent::createIndexQueryBuilder($searchDto, $entityDto, $fields, $filters);

        $request = $this->requestStack->getCurrentRequest();
        $nationalityFilterValue = $request?->query->all('filters')['person.nationalityCountry']['value'] ?? null;

        if ($nationalityFilterValue === null || $nationalityFilterValue === '') {
            $queryBuilder
                ->leftJoin('entity.person', 'personDefaultNationality')
                ->andWhere('personDefaultNationality.nationalityCountry = :defaultNationality')
                ->setParameter('defaultNationality', 1);
        }

        return $queryBuilder;
    }

    public function persistEntity($entityManager, $entityInstance): void
    {
        if (!$entityInstance instanceof Coach || !$entityManager instanceof EntityManagerInterface) {
            parent::persistEntity($entityManager, $entityInstance);
            return;
        }

        $this->applyDynamicLocationEntries($entityInstance, $entityManager);
        $this->optimizeProfilePhoto($entityInstance);
        parent::persistEntity($entityManager, $entityInstance);
    }

    public function updateEntity($entityManager, $entityInstance): void
    {
        if (!$entityInstance instanceof Coach || !$entityManager instanceof EntityManagerInterface) {
            parent::updateEntity($entityManager, $entityInstance);
            return;
        }

        $this->applyDynamicLocationEntries($entityInstance, $entityManager);
        $this->optimizeProfilePhoto($entityInstance);
        parent::updateEntity($entityManager, $entityInstance);
    }

    private function applyDynamicLocationEntries(Coach $coach, EntityManagerInterface $entityManager): void
    {
        $newRegionName = trim((string) $coach->getNewBirthRegionName());
        if ($newRegionName !== '') {
            $region = $entityManager->getRepository(Region::class)->findOneBy(['name' => $newRegionName]);
            if (!$region instanceof Region) {
                $region = (new Region())
                    ->setName($newRegionName)
                    ->setCountry($coach->getPersonBirthCountry());
                $entityManager->persist($region);
            }
            $coach->setPersonBirthRegion($region);
            $coach->setNewBirthRegionName(null);
        }

        $newCityName = trim((string) $coach->getNewBirthCityName());
        if ($newCityName !== '') {
            $city = $entityManager->getRepository(City::class)->findOneBy(['name' => $newCityName]);
            if (!$city instanceof City) {
                $city = (new City())
                    ->setName($newCityName)
                    ->setCountry($coach->getPersonBirthCountry())
                    ->setRegion($coach->getPersonBirthRegion());
                $entityManager->persist($city);
            }
            $coach->setPersonBirthCity($city);
            $coach->setNewBirthCityName(null);
        }
    }

    private function optimizeProfilePhoto(Coach $coach): void
    {
        $photoUrl = $coach->getPhotoUrl();
        if ($photoUrl === null || $photoUrl === '') {
            return;
        }

        $relativePath = ltrim($photoUrl, '/');
        $fullPath = dirname($this->personPhotoUploadDir) . '/' . $relativePath;

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
