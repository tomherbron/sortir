<?php

namespace App\Form;

use App\Entity\Campus;
use App\Entity\Lieu;
use App\Entity\Sortie;
use App\Entity\Utilisateur;
use App\Entity\Ville;
use App\Repository\CampusRepository;
use App\Repository\LieuRepository;
use App\Repository\UtilisateurRepository;
use Doctrine\ORM\Mapping\Entity;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ButtonType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SortieType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom', TextType::class, [
                'label' => 'Nom de la sortie : '
            ])
            ->add('dateHeureDebut', DateTimeType::class, [
                'html5' => true,
                'widget' => 'single_text',
                'label' => 'Date et heure de début : '
            ])
            ->add('duree', NumberType::class, [
                'label' => 'Durée de la sortie : '
            ])
            ->add('dateLimiteInscription', DateType::class,  [
                'html5' => true,
                'widget' => 'single_text',
                'label' => 'Date limite d\'inscription :'
            ])
            ->add('nbMaxInscriptions', NumberType::class, [
                'label' => 'Nombre de participants : '
            ])
            ->add('infos', TextareaType::class, [
                'label' => 'Informations : '
            ])
            ->add('campus', EntityType::class, [
                'class' => Campus::class,
                'choice_label' => 'nom',
                'label' => 'Campus :',
                'query_builder' => function(CampusRepository $repository){
                    return $repository->createQueryBuilder('c');
                }
            ])

            ->add('lieu', EntityType::class, [
                'class' => Lieu::class,
                'label' => 'Lieu : ',
                'choice_label' => 'nom',
                'required' => false, // Rend ce champ facultatif
            ])
            ->add('ajouterLieu', ButtonType::class, [
                'label' => '+',
                'attr' => ['name' => 'addlieu',
                    'class' => 'btn btn-outline-light rounded-0 text-center']
            ])

        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Sortie::class,
        ]);
    }
}
