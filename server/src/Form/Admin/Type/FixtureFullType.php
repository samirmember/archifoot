<?php

namespace App\Form\Admin\Type;

use App\Entity\Competition;
use App\Entity\Country;
use App\Entity\Edition;
use App\Entity\Season;
use App\Entity\Stadium;
use App\Entity\Stage;
use App\Entity\Team;
use App\Form\Admin\Model\FixtureFullInput;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class FixtureFullType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('season', EntityType::class, ['class' => Season::class, 'choice_label' => 'name'])
            ->add('competition', EntityType::class, ['class' => Competition::class, 'choice_label' => 'name'])
            ->add('edition', EntityType::class, ['class' => Edition::class, 'choice_label' => 'name', 'required' => false])
            ->add('stage', EntityType::class, ['class' => Stage::class, 'choice_label' => 'name', 'required' => false])
            ->add('teamA', EntityType::class, ['class' => Team::class, 'choice_label' => 'name'])
            ->add('scoreA', IntegerType::class, ['required' => false])
            ->add('teamB', EntityType::class, ['class' => Team::class, 'choice_label' => 'name'])
            ->add('scoreB', IntegerType::class, ['required' => false])
            ->add('matchDate', DateTimeType::class, ['widget' => 'single_text'])
            ->add('stadium', EntityType::class, ['class' => Stadium::class, 'choice_label' => 'name', 'required' => false])
            ->add('country', EntityType::class, ['class' => Country::class, 'choice_label' => 'name', 'required' => false])
            ->add('cityName', TextType::class, ['required' => false, 'label' => 'Ville'])
            ->add('isOfficial', CheckboxType::class, ['required' => false, 'label' => 'Match officiel'])
            ->add('attendance', IntegerType::class, ['required' => false])
            ->add('notes', TextareaType::class, ['required' => false])
            ->add('lineups', CollectionType::class, ['entry_type' => LineupInputType::class, 'allow_add' => true, 'allow_delete' => true, 'by_reference' => false, 'prototype' => true])
            ->add('goals', CollectionType::class, ['entry_type' => GoalInputType::class, 'allow_add' => true, 'allow_delete' => true, 'by_reference' => false, 'prototype' => true])
            ->add('substitutions', CollectionType::class, ['entry_type' => SubstitutionInputType::class, 'allow_add' => true, 'allow_delete' => true, 'by_reference' => false, 'prototype' => true])
            ->add('officials', CollectionType::class, ['entry_type' => OfficialInputType::class, 'allow_add' => true, 'allow_delete' => true, 'by_reference' => false, 'prototype' => true])
            ->add('staff', CollectionType::class, ['entry_type' => StaffInputType::class, 'allow_add' => true, 'allow_delete' => true, 'by_reference' => false, 'prototype' => true]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => FixtureFullInput::class]);
    }
}
