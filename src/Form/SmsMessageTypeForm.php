<?php

namespace App\Form;

use App\Entity\Contact;
use App\Entity\ContactGroup;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\FileType; // Importez le FileType
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;


class SmsMessageTypeForm extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('sender', ChoiceType::class, [
                'label' => 'Expéditeur',
                'choices' => [
                    'IsoMessage' => 'IsoMessage',
                    // Ajoutez d'autres expéditeurs si dynamiques (ex: depuis la BDD)
                ],
                'placeholder' => 'Choisissez l\'expéditeur...',
                'required' => false,
            ])
            ->add('smsType', ChoiceType::class, [
                'label' => 'Type de SMS',
                'choices' => [
                    'SMS Classique' => 'classic',
                    'SMS Flash' => 'flash',
                ],
                'data' => 'classic',
                'required' => true,
            ])
            ->add('timezone', ChoiceType::class, [
                'label' => 'Fuseau horaire',
                'choices' => [
                    '(GMT +1:00) Europe/Paris' => 'Europe/Paris',
                    // Considérez d'ajouter d'autres fuseaux horaires dynamiquement ou de rendre ce champ plus flexible
                ],
                'data' => 'Europe/Paris',
                'required' => false,
            ])
            ->add('scheduleAt', DateTimeType::class, [
                'label' => 'Date et heure d\'envoi',
                'required' => false,
                'widget' => 'single_text',
                'html5' => true,
                'attr' => [
                    'placeholder' => 'Gardez vide pour l\'envoi instantané'
                ],
            ])
            // Nouveau champ pour le choix de la méthode d'ajout des destinataires
            ->add('recipientOption', ChoiceType::class, [
                'label' => 'Comment souhaitez-vous ajouter les destinataires ?',
                'choices' => [
                    'Saisir des numéros manuellement' => 'manual',
                    'Sélectionner un groupe de contacts' => 'group',
                    'Importer un fichier de contacts' => 'import',
                ],
                'expanded' => true, // Rend les choix sous forme de boutons radio
                'multiple' => false, // Un seul choix autorisé
                'data' => 'manual', // Option par défaut au chargement du formulaire
                'attr' => ['class' => 'recipient-options'], // Classe pour le ciblage JS dans le Twig
            ])
            // Champs conditionnels, tous non requis par défaut au niveau du FormType
            // La validation (rendre un champ requis selon le choix) sera gérée dans le contrôleur ou via des événements de formulaire.
            ->add('directNumbers', TextareaType::class, [
                'label' => 'Numéros de téléphone (manuels)',
                'required' => false, // Rendu non requis ici
                'attr' => [
                    'placeholder' => 'Saisissez les numéros (un par ligne, ex: +229XXXXXXXX)',
                    'rows' => 5,
                ],
                'help' => 'Séparez les numéros par des sauts de ligne. Ex: +22997000001',
            ])
            ->add('contactGroups', EntityType::class, [
                'class' => ContactGroup::class,
                'choice_label' => 'name', // Affichera le nom du groupe
                'multiple' => true,
                'expanded' => false, // Rendu en <select multiple>
                'required' => false, // Rendu non requis ici
                'label' => 'Sélectionnez des groupes de contacts existants',
                // Supprimez 'attr' => ['style' => 'display:none;'] car la visibilité est gérée par JS
            ])
            ->add('importFile', FileType::class, [
                'label' => 'Fichier de contacts (CSV, TXT)',
                'mapped' => false, // Important : ce champ n'est pas directement mappé à une propriété de l'entité SMS
                'required' => false, // Rendu non requis ici
                'help' => 'Formats supportés : CSV, TXT. Un numéro par ligne.',
                'attr' => ['class' => 'form-control'],
            ])
            ->add('messageContent', TextareaType::class, [
                'label' => 'Message',
                'required' => true,
                'attr' => [
                    'class' => 'message-textarea',
                    'placeholder' => 'Tapez votre message...',
                    'maxlength' => 1600,
                ],
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'Envoyer',
                'attr' => ['class' => 'btn btn-primary'],
            ]);
            // Le champ 'contacts' n'est pas directement utilisé dans ce nouveau schéma de choix,
            // car l'option 'group' est privilégiée pour la sélection de multiples contacts.
            // Si vous avez besoin de sélectionner des contacts individuels en dehors des groupes,
            // il faudrait ajouter une option 'select_individual_contacts' au recipientOption
            // et rendre le champ 'contacts' visible conditionnellement.
            // Pour l'instant, je vais laisser le champ 'contacts' de côté ou le supprimer si non utilisé.
            // Si vous souhaitez le conserver pour une autre raison ou le rendre conditionnel, merci de préciser.
            // Je le laisse commenté pour l'instant car la requête initiale ne le mentionnait pas dans les 3 options.
            /*
            ->add('contacts', EntityType::class, [
                'class' => Contact::class,
                'choice_label' => 'phoneNumber', // Ou 'fullName' si disponible
                'multiple' => true,
                'expanded' => false,
                'required' => false,
                'label' => 'Sélectionner des contacts individuels',
                // Supprimez 'attr' => ['style' => 'display:none;']
            ])
            */
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            // 'data_class' => VotreEntiteSms::class, // Assurez-vous que votre Data Class est configurée ici si vous en avez une
        ]);
    }
}
