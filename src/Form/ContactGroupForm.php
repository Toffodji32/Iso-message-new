<?php
// Fichier : src/Form/ContactGroupForm.php

namespace App\Form;

use App\Entity\Contact;
use App\Entity\ContactGroup;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ContactGroupForm extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name') // Le champ pour le nom du groupe ne change pas
            ->add('contacts', EntityType::class, [
                'class' => Contact::class,

                // --- MODIFICATION N°1 : LA CORRECTION PRINCIPALE ---
                // On retire 'choice_label' => 'id'.
                // Symfony va maintenant utiliser automatiquement la méthode __toString()
                // que nous avons ajoutée à l'entité Contact.
                // 'choice_label' est donc maintenant inutile ici.

                // --- MODIFICATION N°2 : LE STYLE D'AFFICHAGE ---
                // 'expanded' => true, va afficher les contacts sous forme de cases à cocher.
                // C'est beaucoup plus facile à utiliser qu'une liste déroulante !
                'expanded' => true,
                'multiple' => true,

                // --- MODIFICATION N°3 : CORRECTION TECHNIQUE INDISPENSABLE ---
                // 'by_reference' => false, est essentiel pour que Symfony gère correctement
                // l'ajout et la suppression de contacts dans une relation ManyToMany.
                'by_reference' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ContactGroup::class,
        ]);
    }
}
