<?php

namespace App\Form\Admin;

use App\Entity\Player;
use App\Entity\Position;
use App\Form\Admin\Data\MatchLineupData;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class MatchLineupType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('teamRole', ChoiceType::class, [
                'choices' => ['Équipe A' => 'A', 'Équipe B' => 'B'],
                'label' => 'Équipe',
            ])
            ->add('lineupRole', ChoiceType::class, [
                'choices' => ['Titulaire' => 'STARTER', 'Remplaçant' => 'SUBSTITUTE'],
                'label' => 'Rôle',
            ])
            ->add('player', EntityType::class, [
                'class' => Player::class,
                'required' => false,
                'placeholder' => 'Sélectionner un joueur (200 derniers)',
                'query_builder' => static fn (EntityRepository $repository) => $repository->createQueryBuilder('p')
                    ->orderBy('p.id', 'DESC')
                    ->setMaxResults(200),
                'choice_label' => static fn (Player $player) => $player->getPersonFullName() ?? ('#'.$player->getId()),
                'label' => 'Joueur existant',
                'attr' => ['data-live-min3' => '1', 'class' => 'js-min3-autocomplete', 'data-remote-type' => 'player'],
            ])
            ->add('playerName', TextType::class, [
                'required' => false,
                'label' => 'Nouveau joueur',
            ])
            ->add('shirtNumber', IntegerType::class, [
                'required' => false,
                'label' => 'N°',
            ])
            ->add('position', EntityType::class, [
                'class' => Position::class,
                'required' => false,
                'choice_label' => 'label',
                'label' => 'Poste',
            ])
            ->add('isCaptain', ChoiceType::class, [
                'required' => false,
                'label' => 'Capitaine',
                'choices' => ['Non' => false, 'Oui' => true],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => MatchLineupData::class]);
    }
}
