<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType; // Assurez-vous que SubmitType est bien importé
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ApiIntegrationSettingsType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            // Le champ s'appelle bien 'regenerate' et est de type SubmitType
            ->add('regenerate', SubmitType::class, [
                'label' => 'Régénérer', // Le label du bouton
                'attr' => ['class' => 'btn btn-primary'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'csrf_protection' => true,
            'csrf_field_name' => '_token',
            'csrf_token_id'   => 'regenerate_api_key',
            'data_class' => null, // Assurez-vous que data_class est null
        ]);
    }
}
