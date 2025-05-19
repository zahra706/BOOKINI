<?php

namespace App\Form;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;

use App\Entity\Commentaire;
use App\Entity\Livre;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CommentaireTypeForm extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
       $builder
            ->add('texte', TextareaType::class, [
                'label' => 'Votre commentaire',
                'attr' => [
                    'rows' => 4,
                    'placeholder' => 'Écrivez votre avis ici...',
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Commentaire::class,
        ]);
    }
}
