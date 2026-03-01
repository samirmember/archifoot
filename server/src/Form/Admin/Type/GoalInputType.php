<?php

namespace App\Form\Admin\Type;

use App\Entity\Player;
use App\Entity\Team;
use App\Form\Admin\Model\GoalInput;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class GoalInputType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('team', EntityType::class, ['class' => Team::class, 'choice_label' => 'name'])
            ->add('scorer', EntityType::class, ['class' => Player::class, 'choice_label' => 'personFullName', 'required' => false, 'placeholder' => 'Buteur existant'])
            ->add('newScorerName', TextType::class, ['required' => false, 'label' => 'Nouveau buteur (si absent)'])
            ->add('minute', TextType::class, ['required' => false])
            ->add('goalType', ChoiceType::class, [
                'choices' => ['But (jeu)' => 'OPEN_PLAY', 'Penalty' => 'PENALTY', 'CSC' => 'OWN_GOAL'],
                'required' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => GoalInput::class]);
    }
}
