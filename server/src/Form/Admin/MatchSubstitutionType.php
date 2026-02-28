<?php

namespace App\Form\Admin;

use App\Entity\Player;
use App\Form\Admin\Data\MatchSubstitutionData;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class MatchSubstitutionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('teamRole', ChoiceType::class, ['choices' => ['Équipe A' => 'A', 'Équipe B' => 'B'], 'label' => 'Équipe'])
            ->add('playerOut', EntityType::class, [
                'class' => Player::class,
                'required' => false,
                'autocomplete' => true,
                'choice_label' => static fn (Player $player) => $player->getPersonFullName() ?? ('#'.$player->getId()),
                'label' => 'Sortant existant',
            ])
            ->add('playerOutName', TextType::class, ['required' => false, 'label' => 'Nouveau sortant'])
            ->add('playerIn', EntityType::class, [
                'class' => Player::class,
                'required' => false,
                'autocomplete' => true,
                'choice_label' => static fn (Player $player) => $player->getPersonFullName() ?? ('#'.$player->getId()),
                'label' => 'Entrant existant',
            ])
            ->add('playerInName', TextType::class, ['required' => false, 'label' => 'Nouveau entrant'])
            ->add('minute', TextType::class, ['required' => false]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => MatchSubstitutionData::class]);
    }
}
