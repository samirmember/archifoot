<?php

namespace App\Form\Admin;

use App\Entity\Category;
use App\Entity\City;
use App\Entity\Competition;
use App\Entity\Country;
use App\Entity\Division;
use App\Entity\Edition;
use App\Entity\Matchday;
use App\Entity\Season;
use App\Entity\Stadium;
use App\Entity\Stage;
use App\Entity\Team;
use App\Form\Admin\Data\FixtureCompleteData;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class FixtureCompleteType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('externalMatchNo', IntegerType::class, ['required' => false, 'label' => 'Numéro de match'])
            ->add('season', EntityType::class, ['class' => Season::class, 'choice_label' => 'name', 'required' => false, 'label' => 'Saison'])
            ->add('competitions', EntityType::class, ['class' => Competition::class, 'choice_label' => 'name', 'multiple' => true, 'required' => false, 'label' => 'Compétitions'])
            ->add('editions', EntityType::class, ['class' => Edition::class, 'choice_label' => 'name', 'multiple' => true, 'required' => false, 'label' => 'Éditions'])
            ->add('stages', EntityType::class, ['class' => Stage::class, 'choice_label' => 'name', 'multiple' => true, 'required' => false, 'label' => 'Stades'])
            ->add('matchday', EntityType::class, ['class' => Matchday::class, 'choice_label' => 'name', 'required' => false, 'attr' => ['data-live-min3' => '1', 'class' => 'js-min3-autocomplete', 'data-remote-type' => 'matchday'], 'label' => 'Journée'])
            ->add('division', EntityType::class, ['class' => Division::class, 'choice_label' => 'name', 'required' => false, 'attr' => ['data-live-min3' => '1', 'class' => 'js-min3-autocomplete', 'data-remote-type' => 'division'], 'label' => 'Division'])
            ->add('category', EntityType::class, ['class' => Category::class, 'choice_label' => 'name', 'required' => false, 'attr' => ['data-live-min3' => '1', 'class' => 'js-min3-autocomplete', 'data-remote-type' => 'category'], 'label' => 'Catégorie'])
            ->add('matchDate', DateType::class, ['widget' => 'single_text', 'required' => false, 'label' => 'Date'])
            ->add('stadium', EntityType::class, ['class' => Stadium::class, 'choice_label' => 'name', 'required' => false, 'attr' => ['data-live-min3' => '1', 'class' => 'js-min3-autocomplete', 'data-remote-type' => 'stadium'], 'label' => 'Stade'])
            ->add('city', EntityType::class, ['class' => City::class, 'choice_label' => 'name', 'required' => false, 'attr' => ['data-live-min3' => '1', 'class' => 'js-min3-autocomplete', 'data-remote-type' => 'city'], 'label' => 'Ville'])
            ->add('country', EntityType::class, ['class' => Country::class, 'choice_label' => 'name', 'required' => false, 'attr' => ['data-live-min3' => '1', 'class' => 'js-min3-autocomplete', 'data-remote-type' => 'country'], 'label' => 'Pays'])
            ->add('played', CheckboxType::class, ['required' => false, 'label' => 'Match joué'])
            ->add('isOfficial', CheckboxType::class, ['required' => false, 'label' => 'Match officiel'])
            ->add('notes', TextareaType::class, ['required' => false, 'label' => 'Notes'])
            ->add('internalNotes', TextareaType::class, ['required' => false, 'label' => 'Notes internes'])
            ->add('teamA', EntityType::class, [
                'class' => Team::class,
                'label' => 'Équipe A',
                'choice_label' => 'displayName',
                'placeholder' => 'Choisir une équipe',
                'query_builder' => static fn (EntityRepository $repository) => $repository->createQueryBuilder('t')
                    ->orderBy('t.id', 'DESC')
                    ->setMaxResults(200),
            ])
            ->add('scoreA', IntegerType::class, ['required' => false, 'label' => 'Score A'])
            ->add('teamB', EntityType::class, [
                'label' => 'Équipe B',
                'class' => Team::class,
                'choice_label' => 'displayName',
                'placeholder' => 'Choisir une équipe',
                'query_builder' => static fn (EntityRepository $repository) => $repository->createQueryBuilder('t')
                    ->orderBy('t.id', 'DESC')
                    ->setMaxResults(200),
            ])
            ->add('scoreB', IntegerType::class, ['required' => false, 'label' => 'Score B'])
            ->add('attendance', IntegerType::class, ['required' => false, 'label' => 'Nombre de spectateurs'])
            ->add('fixedTime', TextType::class, ['required' => false, 'label' => 'Heure fixe'])
            ->add('kickoffTime', TextType::class, ['required' => false, 'label' => 'Heure de départ'])
            ->add('halfTime', TextType::class, ['required' => false, 'label' => 'Heure de mi-temps'])
            ->add('secondHalfStart', TextType::class, ['required' => false, 'label' => 'Heure de début de la seconde moitié'])
            ->add('fullTime', TextType::class, ['required' => false, 'label' => 'Heure de fin'])
            ->add('stoppageTime', TextType::class, ['required' => false, 'label' => 'Heure de pause'])
            ->add('reservations', TextareaType::class, ['required' => false, 'label' => 'Reservations'])
            ->add('signedPlace', TextType::class, ['required' => false, 'label' => 'Signé sur place'])
            ->add('signedOn', DateType::class, ['widget' => 'single_text', 'required' => false, 'label' => 'Signé le'])
            ->add('lineups', CollectionType::class, [
                'entry_type' => MatchLineupType::class,
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
                'prototype' => true,
            ])
            ->add('goals', CollectionType::class, [
                'entry_type' => MatchGoalType::class,
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
                'prototype' => true,
            ])
            ->add('substitutions', CollectionType::class, [
                'entry_type' => MatchSubstitutionType::class,
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
                'prototype' => true,
            ])
            ->add('headCoachA', MatchPersonType::class)
            ->add('assistantCoachA1', MatchPersonType::class)
            ->add('assistantCoachA2', MatchPersonType::class)
            ->add('headCoachB', MatchPersonType::class)
            ->add('assistantCoachB1', MatchPersonType::class)
            ->add('assistantCoachB2', MatchPersonType::class)
            ->add('mainReferee', MatchPersonType::class)
            ->add('assistantReferee1', MatchPersonType::class)
            ->add('assistantReferee2', MatchPersonType::class)
            ->add('fourthReferee', MatchPersonType::class);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => FixtureCompleteData::class]);
    }
}
