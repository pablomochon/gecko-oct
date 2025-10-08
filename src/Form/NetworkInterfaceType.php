<?php

namespace App\Form;

use App\Entity\NetworkInterface;
use App\Entity\NetworkVirtualSystem;
use App\Entity\Environment;
use App\Repository\NetworkVirtualSystemRepository;
use App\Repository\EnvironmentRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;

class NetworkInterfaceType extends AbstractType
{
    public function __construct(
        private NetworkVirtualSystemRepository $networkVirtualSystemRepository,
        // private EnvironmentRepository $environmentRepository
    ) {}

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name')
            ->add('macAddress')
            ->add('defaultGateway')
            ->add('dhcpEnabled')
            ->add('dhcpServer')
            ->add('dnsHostname')
            ->add('dnsDomain')
            ->add('dnsServer')
            ->add('adapterType')
            ->add('networkVirtualSystem', ChoiceType::class, [
                'choices' => [],
                'required' => false,
            ])
            ->add('environment', ChoiceType::class, [
                'choices' => [],
                'required' => false,
            ])
            ->add('comments')
            ->add('description')
        ;

        $builder->addEventListener(
            FormEvents::PRE_SET_DATA,
            function (FormEvent $event) {
                $data = $event->getData();
                if (!$data) {
                    return;
                }

                if ($data->getNetworkVirtualSystem()) {
                    $choices = new ArrayCollection();
                    $choices->add($data->getNetworkVirtualSystem());
                    $event->getForm()->add('networkVirtualSystem', ChoiceType::class, [
                        'choices' => $choices,
                        'choice_value' => function ($choice) {
                            return $choice ? $choice->getId() : null;
                        },
                        'choice_label' => function ($choice, $key, $value) {
                            return ($choice->getNetworkDevice() ? $choice->getNetworkDevice()->getName() . ': ' : null) . $choice->getName();
                        },
                        'required' => false,
                    ]);
                }

                $choices = new ArrayCollection();
                $choices->add($data->getEnvironment());
                $event->getForm()->add('environment', ChoiceType::class, [
                    'choices' => $choices,
                    'choice_value' => function ($choice) {
                        return $choice ? $choice->getId() : null;
                    },
                    'choice_label' => function ($choice, $key, $value) {
                        return $choice ? $choice->getService()->getClient()->getName() . ' (' . $choice->getService()->getName() . '): ' . $choice->getName() : null;
                    },
                    'required' => false,
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

/*
                if (isset($eventData['networkVirtualSystem'])) {
                    $form->add('networkVirtualSystem', EntityType::class, [
                        'class' => NetworkVirtualSystem::class,
                        'empty_data' => $this->networkVirtualSystemRepository->find($eventData['networkVirtualSystem']),
                        'required' => false,
                    ]);
                }

                if (isset($eventData['environment'])) {
                    $form->add('environment', EntityType::class, [
                        'class' => Environment::class,
                        'empty_data' => $this->environmentRepository->find($eventData['environment']),
                        'required' => false,
                    ]);
                } 
*/

                $event->setData($eventData);
            }
        );
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => NetworkInterface::class,
        ]);
    }
}
