<?php

namespace App\Form;

use App\Entity\Environment;
use App\Entity\Service;
use App\Entity\TimeTable;
use App\Repository\ServiceRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

class EnvironmentType extends AbstractType
{
    public function __construct(private ServiceRepository $serviceRepository)
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name')
            ->add('service', ChoiceType::class, [
                'choices' => [],
            ])
            ->add('type', ChoiceType::class, [
                'choices' => [
                    'Training' => 'training',
                    'Integration' => 'integration',
                    'Pre-production' => 'pre-production',
                    'Other' => 'other',
                    'Test' => 'test',
                    'Development' => 'development',
                    'Production' => 'production',
                ],
                'required' => true,
                'preferred_choices' => ['production', 'pre-production', 'development'],
            ])
        ;

        $builder->addEventListener(
            FormEvents::PRE_SET_DATA,
            function (FormEvent $event) {
                $data = $event->getData();
                if (!$data) {
                    return;
                }
                
                $choices = new ArrayCollection();
                $choices->add($data->getService());
                $event->getForm()->add('service', ChoiceType::class, [
                    'choices' => $choices,
                    'choice_value' => function ($choice) {
                        return $choice ? $choice->getId() : null;
                    },
                    'choice_label' => function ($choice, $key, $value) {
                        return $choice->getClient()->getName() . ': ' . $choice->getName();
                    },
                ]);
            }
        );

        $builder->addEventListener(
            FormEvents::PRE_SUBMIT,
            function (FormEvent $event) {
                $eventData = $event->getData();
                $form = $event->getForm();
                if (!$eventData) {
                    return;
                }

                if(isset($eventData['service'])) {
                    $form->add('service', EntityType::class, [
                        'class' => Service::class,
                        'empty_data' => $this->serviceRepository->find($eventData['service']),
                    ]);
                }

                $event->setData($eventData);
            }
        );
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Environment::class,
        ]);
    }
}
