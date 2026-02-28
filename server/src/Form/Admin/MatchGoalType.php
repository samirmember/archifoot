<?php

namespace App\Form\Admin;

use App\Entity\Player;
use App\Form\Admin\Data\MatchGoalData;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class MatchGoalType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('teamRole', ChoiceType::class, ['choices' => ['Équipe A' => 'A', 'Équipe B' => 'B'], 'label' => 'Équipe'])
            ->add('scorer', EntityType::class, [
                'class' => Player::class,
                'required' => false,
                'placeholder' => 'Sélectionner un joueur (200 derniers)',
                'query_builder' => static fn (EntityRepository $repository) => $repository->createQueryBuilder('p')
                    ->orderBy('p.id', 'DESC')
                    ->setMaxResults(200),
                'choice_label' => static fn (Player $player) => $player->getPersonFullName() ?? ('#'.$player->getId()),
                'label' => 'Buteur existant',
            ])
            ->add('scorerName', TextType::class, ['required' => false, 'label' => 'Nouveau buteur'])
            ->add('minute', TextType::class, ['required' => false])
            ->add('goalType', ChoiceType::class, [
                'required' => false,
                'choices' => [
                    'Jeu' => 'OPEN_PLAY',
                    'Penalty' => 'PENALTY',
                    'CSC' => 'OWN_GOAL',
                    'CPA' => 'SET_PIECE',
                ],
                'label' => 'Type',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => MatchGoalData::class]);
    }
}
