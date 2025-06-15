<?php

namespace App\Form;

use App\Entity\ContactGroup;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class SmsMessageTypeForm extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('sender', ChoiceType::class, [
                'label' => 'Expéditeur',
                'choices' => [
                    'IsoMessage' => 'IsoMessage',
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
            ->add('scheduleAt', DateTimeType::class, [
                'label' => 'Date et heure d\'envoi',
                'required' => false,
                'widget' => 'single_text',
                'html5' => true,
                'attr' => [
                    'placeholder' => 'Gardez vide pour l\'envoi instantané'
                ],
            ])

            ->add('recipientOption', ChoiceType::class, [
                'label' => 'Comment souhaitez-vous ajouter les destinataires ?',
                'choices' => [
                    'Saisir des numéros manuellement' => 'manual',
                    'Sélectionner un groupe de contacts' => 'group',
                    'Importer un fichier de contacts' => 'import',
                ],
                'expanded' => true,
                'multiple' => false,
                'data' => 'manual',
                'attr' => ['class' => 'recipient-options'],
            ])

            ->add('directNumbers', TextareaType::class, [
                'label' => 'Numéros de téléphone (manuels)',
                'required' => false,
                'attr' => [
                    'placeholder' => 'Saisissez les numéros (un par ligne, ex: +229012345678)',
                    'rows' => 5,
                ],
                'help' => 'Séparez les numéros par des sauts de ligne. Chaque numéro doit être au format +22901XXXXXXXX.',
                // NOUVEAU : Ajout des contraintes de validation
                'constraints' => [
                    new Assert\Callback([
                        'callback' => function (?string $value, ExecutionContextInterface $context) {
                            if (null === $value || '' === $value) {
                                return; // Le champ n'est pas requis, donc on ne valide pas s'il est vide.
                            }

                            $numbers = preg_split('/[\r\n]+/', $value, -1, PREG_SPLIT_NO_EMPTY);
                            $phoneNumberRegex = '/^\+22901\d{8}$/'; // Seulement +22901 suivi de 8 chiffres

                            foreach ($numbers as $number) {
                                $trimmedNumber = trim($number);
                                if (!preg_match($phoneNumberRegex, $trimmedNumber)) {
                                    $context->buildViolation('Le numéro "{{ value }}" n\'est pas au format attendu (+22901XXXXXXXX).')
                                        ->setParameter('{{ value }}', $trimmedNumber)
                                        ->addViolation();
                                }
                            }
                        },
                    ]),
                ],
            ])
            ->add('contactGroups', EntityType::class, [
                'class' => ContactGroup::class,
                'choice_label' => 'name',
                'multiple' => true,
                'expanded' => false,
                'required' => false,
                'label' => 'Sélectionnez des groupes de contacts existants',

            ])
            ->add('importFile', FileType::class, [
                'label' => 'Fichier de contacts (CSV, TXT)',
                'mapped' => false,
                'required' => false,
                'help' => 'Formats supportés : CSV, TXT. Un numéro par ligne. Chaque numéro doit être au format +22901XXXXXXXX.',
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
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            // 'data_class' => VotreEntiteSms::class, // Si applicable
        ]);
    }
}
