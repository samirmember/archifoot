<?php

namespace App\Form\Admin\Type;

use App\Entity\Country;
use App\Entity\Person;
use App\Entity\Team;
use App\Form\Admin\Model\StaffInput;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class StaffInputType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('team', EntityType::class, ['class' => Team::class, 'choice_label' => 'name'])
            ->add('role', ChoiceType::class, [
                'choices' => [
                    'Entraîneur principal' => 'HEAD_COACH',
                    'Assistant 1' => 'ASSISTANT_COACH',
                    'Assistant 2' => 'ASSISTANT_COACH',
                ],
            ])
            ->add('person', EntityType::class, ['class' => Person::class, 'choice_label' => 'fullName', 'required' => false, 'placeholder' => 'Membre staff existant'])
            ->add('newFullName', TextType::class, ['required' => false, 'label' => 'Nouveau membre staff'])
            ->add('nationality', EntityType::class, ['class' => Country::class, 'choice_label' => 'name', 'required' => false]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => StaffInput::class]);
    }
}
