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
            ->add('externalMatchNo', IntegerType::class, ['required' => false])
            ->add('season', EntityType::class, ['class' => Season::class, 'choice_label' => 'name', 'required' => false])
            ->add('competitions', EntityType::class, ['class' => Competition::class, 'choice_label' => 'name', 'multiple' => true, 'required' => false])
            ->add('editions', EntityType::class, ['class' => Edition::class, 'choice_label' => 'name', 'multiple' => true, 'required' => false])
            ->add('stages', EntityType::class, ['class' => Stage::class, 'choice_label' => 'name', 'multiple' => true, 'required' => false])
            ->add('matchday', EntityType::class, ['class' => Matchday::class, 'choice_label' => 'name', 'required' => false, 'attr' => ['data-live-min3' => '1', 'class' => 'js-min3-autocomplete']])
            ->add('division', EntityType::class, ['class' => Division::class, 'choice_label' => 'name', 'required' => false, 'attr' => ['data-live-min3' => '1', 'class' => 'js-min3-autocomplete']])
            ->add('category', EntityType::class, ['class' => Category::class, 'choice_label' => 'name', 'required' => false, 'attr' => ['data-live-min3' => '1', 'class' => 'js-min3-autocomplete']])
            ->add('matchDate', DateType::class, ['widget' => 'single_text', 'required' => false])
            ->add('stadium', EntityType::class, ['class' => Stadium::class, 'choice_label' => 'name', 'required' => false, 'attr' => ['data-live-min3' => '1', 'class' => 'js-min3-autocomplete']])
            ->add('city', EntityType::class, ['class' => City::class, 'choice_label' => 'name', 'required' => false, 'attr' => ['data-live-min3' => '1', 'class' => 'js-min3-autocomplete']])
            ->add('country', EntityType::class, ['class' => Country::class, 'choice_label' => 'name', 'required' => false, 'attr' => ['data-live-min3' => '1', 'class' => 'js-min3-autocomplete']])
            ->add('played', CheckboxType::class, ['required' => false])
            ->add('isOfficial', CheckboxType::class, ['required' => false])
            ->add('notes', TextareaType::class, ['required' => false])
            ->add('internalNotes', TextareaType::class, ['required' => false])
            ->add('teamA', EntityType::class, [
                'class' => Team::class,
                'choice_label' => 'displayName',
                'placeholder' => 'Choisir une équipe',
                'query_builder' => static fn (EntityRepository $repository) => $repository->createQueryBuilder('t')
                    ->orderBy('t.id', 'DESC')
                    ->setMaxResults(200),
            ])
            ->add('scoreA', IntegerType::class, ['required' => false])
            ->add('teamB', EntityType::class, [
                'class' => Team::class,
                'choice_label' => 'displayName',
                'placeholder' => 'Choisir une équipe',
                'query_builder' => static fn (EntityRepository $repository) => $repository->createQueryBuilder('t')
                    ->orderBy('t.id', 'DESC')
                    ->setMaxResults(200),
            ])
            ->add('scoreB', IntegerType::class, ['required' => false])
            ->add('attendance', IntegerType::class, ['required' => false])
            ->add('fixedTime', TextType::class, ['required' => false])
            ->add('kickoffTime', TextType::class, ['required' => false])
            ->add('halfTime', TextType::class, ['required' => false])
            ->add('secondHalfStart', TextType::class, ['required' => false])
            ->add('fullTime', TextType::class, ['required' => false])
            ->add('stoppageTime', TextType::class, ['required' => false])
            ->add('reservations', TextareaType::class, ['required' => false])
            ->add('signedPlace', TextType::class, ['required' => false])
            ->add('signedOn', DateType::class, ['widget' => 'single_text', 'required' => false])
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
