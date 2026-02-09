<?php

namespace App\Form;

use App\Entity\Cours;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\All;
use Symfony\Component\Validator\Constraints\File;

class CoursType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'label' => 'Titre du cours',
                'attr' => ['class' => 'form-control', 'placeholder' => 'Ex: Introduction à Docker']
            ])
            ->add('contenu', TextareaType::class, [
                'label' => 'Contenu',
                'attr' => ['class' => 'form-control', 'rows' => 4]
            ])

            ->add('documentFiles', FileType::class, [
                'label' => 'Ajouter des documents (PDF)',
                'multiple' => true,
                'mapped' => false,
                'required' => false,
                'attr' => ['class' => 'form-control', 'accept' => '.pdf'],
                'constraints' => [
                    new All([
                        new File([
                            'maxSize' => '10M',
                            'mimeTypes' => ['application/pdf'],
                            'mimeTypesMessage' => 'Veuillez uploader un PDF valide',
                        ])
                    ])
                ],
            ])

            ->add('videoFiles', FileType::class, [
                'label' => 'Ajouter des vidéos (MP4)',
                'multiple' => true,
                'mapped' => false,
                'required' => false,
                'attr' => ['class' => 'form-control', 'accept' => 'video/mp4'],
                'constraints' => [
                    new All([
                        new File([
                            'maxSize' => '300M',
                            'mimeTypes' => ['video/mp4'],
                            'mimeTypesMessage' => 'Veuillez uploader un fichier MP4 valide',
                        ])
                    ])
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Cours::class,
        ]);
    }
}