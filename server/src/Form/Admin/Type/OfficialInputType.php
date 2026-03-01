<?php

namespace App\Form\Admin\Type;

use App\Entity\Country;
use App\Entity\Person;
use App\Form\Admin\Model\OfficialInput;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class OfficialInputType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('role', ChoiceType::class, [
                'choices' => [
                    'Arbitre principal' => 'REFEREE_MAIN',
                    'Assistant 1' => 'REFEREE_ASSISTANT_1',
                    'Assistant 2' => 'REFEREE_ASSISTANT_2',
                    '4e arbitre' => 'REFEREE_FOURTH',
                ],
            ])
            ->add('person', EntityType::class, ['class' => Person::class, 'choice_label' => 'fullName', 'required' => false, 'placeholder' => 'Officiel existant'])
            ->add('newFullName', TextType::class, ['required' => false, 'label' => 'Nouvel officiel'])
            ->add('nationality', EntityType::class, ['class' => Country::class, 'choice_label' => 'name', 'required' => false]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => OfficialInput::class]);
    }
}
