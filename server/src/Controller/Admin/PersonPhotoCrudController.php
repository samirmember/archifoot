<?php

namespace App\Controller\Admin;

use App\Entity\Person;
use App\Entity\PersonPhoto;
use App\Entity\Player;
use App\Service\ImageUploadOptimizer;
use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class PersonPhotoCrudController extends AbstractCrudController
{
    public function __construct(private readonly ImageUploadOptimizer $imageUploadOptimizer)
    {
    }

    public static function getEntityFqcn(): string
    {
        return PersonPhoto::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Photo joueur')
            ->setEntityLabelInPlural('Photos joueur')
            ->setSearchFields(['person.fullName', 'caption', 'imageUrl'])
            ->setDefaultSort(['sortOrder' => 'ASC', 'id' => 'ASC']);
    }

    public function configureFields(string $pageName): iterable
    {
        $imageField = ImageField::new('imageUrl', 'Photo')
            ->setBasePath('/uploads/person/gallery')
            ->setUploadDir('public/uploads/person/gallery')
            ->setUploadedFileNamePattern('[uuid].[extension]')
            ->setRequired(true);
        $personField = AssociationField::new('person', 'Personne')
            ->autocomplete(callback: static fn (Person $person): string => $person->getFullName() ?? sprintf('Person #%s', $person->getId() ?? '?'))
            ->setQueryBuilder(static function (QueryBuilder $queryBuilder): QueryBuilder {
                return $queryBuilder
                    ->distinct()
                    ->innerJoin(Player::class, 'player', 'WITH', 'player.person = entity')
                    ->andWhere('entity.fullName IS NOT NULL')
                    ->orderBy('entity.fullName', 'ASC')
                    ->addOrderBy('entity.id', 'ASC');
            });

        if ($pageName === Crud::PAGE_INDEX) {
            return [
                AssociationField::new('person', 'Personne')->formatValue(fn ($value, PersonPhoto $photo) => $photo->getPerson()?->getFullName() ?? '-'),
                $imageField,
                TextField::new('caption', 'Légende'),
                IntegerField::new('sortOrder', 'Ordre'),
            ];
        }

        return [
            $personField,
            $imageField,
            TextField::new('caption', 'Légende')->setRequired(false),
            IntegerField::new('sortOrder', 'Ordre'),
        ];
    }

    public function persistEntity($entityManager, $entityInstance): void
    {
        if ($entityInstance instanceof PersonPhoto) {
            $entityInstance->setImageUrl($this->imageUploadOptimizer->optimizePublicUploadPath($entityInstance->getImageUrl()));
        }

        parent::persistEntity($entityManager, $entityInstance);
    }

    public function updateEntity($entityManager, $entityInstance): void
    {
        if ($entityInstance instanceof PersonPhoto) {
            $entityInstance->setImageUrl($this->imageUploadOptimizer->optimizePublicUploadPath($entityInstance->getImageUrl()));
        }

        parent::updateEntity($entityManager, $entityInstance);
    }
}
