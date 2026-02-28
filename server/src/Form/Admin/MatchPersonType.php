<?php

namespace App\Form\Admin;

use App\Entity\Country;
use App\Entity\Person;
use App\Form\Admin\Data\MatchPersonData;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class MatchPersonType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('person', EntityType::class, [
                'class' => Person::class,
                'required' => false,
                'autocomplete' => true,
                'choice_label' => 'fullName',
                'label' => 'Personne existante',
            ])
            ->add('name', TextType::class, [
                'required' => false,
                'label' => 'Nouveau nom',
            ])
            ->add('nationality', EntityType::class, [
                'class' => Country::class,
                'required' => false,
                'choice_label' => 'name',
                'autocomplete' => true,
                'label' => 'Nationalité',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => MatchPersonData::class,
        ]);
    }
}
