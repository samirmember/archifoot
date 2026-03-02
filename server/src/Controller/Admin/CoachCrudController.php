<?php

namespace App\Controller\Admin;

use App\Entity\City;
use App\Entity\Coach;
use App\Entity\Country;
use App\Entity\Region;
use App\Filter\PersonNationalityCountryFilter;
use App\Service\ImageUploadOptimizer;
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
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\HttpFoundation\RequestStack;

class CoachCrudController extends AbstractCrudController
{
    public function __construct(
        private readonly RequestStack $requestStack,
        private readonly ImageUploadOptimizer $imageUploadOptimizer,
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
            ->add(PersonNationalityCountryFilter::new('nationality', 'Nationalité'));
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
        $nationalityFilterValue = $request?->query->all('filters')['nationality']['value'] ?? null;
        $searchQuery = trim((string) ($request?->query->get('query') ?? ''));

        if (($nationalityFilterValue === null || $nationalityFilterValue === '') && $searchQuery === '') {
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
        $this->optimizeUploadedPhotos($entityInstance);
        parent::persistEntity($entityManager, $entityInstance);
    }

    public function updateEntity($entityManager, $entityInstance): void
    {
        if (!$entityInstance instanceof Coach || !$entityManager instanceof EntityManagerInterface) {
            parent::updateEntity($entityManager, $entityInstance);
            return;
        }

        $this->applyDynamicLocationEntries($entityInstance, $entityManager);
        $this->optimizeUploadedPhotos($entityInstance);
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

    private function optimizeUploadedPhotos(Coach $coach): void
    {
        $coach->setPhotoUrl($this->imageUploadOptimizer->optimizePublicUploadPath($coach->getPhotoUrl()));
    }
}
