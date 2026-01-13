<?php

namespace App\Form;

use App\Entity\Genre;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AnimeSearchFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'label' => 'Título',
                'required' => false,
                'attr' => ['placeholder' => 'Buscar por nombre...'],
            ])
            ->add('genres', EntityType::class, [
                'label' => 'Géneros',
                'class' => Genre::class,
                'choice_label' => 'name',
                'multiple' => true,
                'required' => false,
                'attr' => ['class' => 'form-select'],
            ])
            ->add('year', IntegerType::class, [
                'label' => 'Año',
                'required' => false,
                'attr' => ['placeholder' => 'Ej: 2024', 'type' => 'number', 'min' => '1970', 'max' => '2050'],
            ])
            ->add('status', ChoiceType::class, [
                'label' => 'Estado',
                'required' => false,
                'choices' => [
                    'Transmitiendo' => 'RELEASING',
                    'Finalizado' => 'FINISHED',
                    'Cancelado' => 'CANCELLED',
                    'Próximamente' => 'NOT_YET_RELEASED',
                ],
                'placeholder' => 'Todos',
            ])
            ->add('minRating', IntegerType::class, [
                'label' => 'Puntuación mínima',
                'required' => false,
                'attr' => ['type' => 'range', 'min' => '0', 'max' => '100', 'step' => '5'],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'method' => 'GET',
            'csrf_protection' => false,
        ]);
    }
}
