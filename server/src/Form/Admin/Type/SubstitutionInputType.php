<?php

namespace App\Form\Admin\Type;

use App\Entity\Player;
use App\Entity\Team;
use App\Form\Admin\Model\SubstitutionInput;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SubstitutionInputType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('team', EntityType::class, ['class' => Team::class, 'choice_label' => 'name'])
            ->add('playerOut', EntityType::class, ['class' => Player::class, 'choice_label' => 'personFullName', 'required' => false, 'placeholder' => 'Sortant existant'])
            ->add('newPlayerOutName', TextType::class, ['required' => false, 'label' => 'Nouveau joueur sortant'])
            ->add('playerIn', EntityType::class, ['class' => Player::class, 'choice_label' => 'personFullName', 'required' => false, 'placeholder' => 'Entrant existant'])
            ->add('newPlayerInName', TextType::class, ['required' => false, 'label' => 'Nouveau joueur entrant'])
            ->add('minute', TextType::class, ['required' => false]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => SubstitutionInput::class]);
    }
}
