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
                'placeholder' => 'Sélectionner un joueur',
                'query_builder' => static fn (EntityRepository $repository) => $repository->createQueryBuilder('p')
                    ->orderBy('p.id', 'DESC')
                    ->setMaxResults(50),
                'choice_label' => static fn (Player $player) => $player->getPersonFullName() ?? ('#'.$player->getId()),
                'label' => 'Buteur existant',
                'attr' => ['data-live-min3' => '1', 'class' => 'js-min3-autocomplete', 'data-remote-type' => 'player'],
            ])
            ->add('scorerName', TextType::class, ['required' => false, 'label' => 'Nouveau buteur'])
            ->add('minute', TextType::class, ['required' => false])
            ->add('goalType', ChoiceType::class, [
                'required' => false,
                'choices' => [
                    'Normal' => 'normal',
                    'Penalty' => 'penalty',
                    'Contre son camp' => 'own_goal',
                ],
                'label' => 'Type',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => MatchGoalData::class]);
    }
}
