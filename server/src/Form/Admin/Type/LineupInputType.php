<?php

namespace App\Form\Admin\Type;

use App\Entity\Player;
use App\Entity\Position;
use App\Entity\Team;
use App\Form\Admin\Model\LineupInput;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class LineupInputType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('team', EntityType::class, ['class' => Team::class, 'choice_label' => 'name'])
            ->add('player', EntityType::class, ['class' => Player::class, 'choice_label' => 'personFullName', 'required' => false, 'placeholder' => 'Joueur existant'])
            ->add('newPlayerName', TextType::class, ['required' => false, 'label' => 'Nouveau joueur (si absent)'])
            ->add('shirtNumber', IntegerType::class, ['required' => false])
            ->add('lineupRole', ChoiceType::class, [
                'choices' => ['Titulaire' => 'STARTER', 'Remplaçant' => 'BENCH'],
            ])
            ->add('captain', CheckboxType::class, ['required' => false, 'label' => 'Capitaine'])
            ->add('position', EntityType::class, ['class' => Position::class, 'choice_label' => 'name', 'required' => false])
            ->add('sortOrder', IntegerType::class, ['required' => false]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => LineupInput::class]);
    }
}
