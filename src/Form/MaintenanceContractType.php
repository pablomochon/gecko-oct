<?php

namespace App\Form;

use App\Entity\MaintenanceContract;
use App\Entity\NetworkDevice;
use App\Entity\NetworkVirtualSystem;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use App\Repository\NetworkDeviceRepository;
use App\Repository\NetworkVirtualSystemRepository;

class MaintenanceContractType extends AbstractType
{
    public function __construct(private NetworkDeviceRepository $networkDeviceRepository
    , private NetworkVirtualSystemRepository $networkVirtualSystemRepository) {
        
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'required' => true,
            ])
            ->add('startDate', DateType::class, [
                'widget' => 'single_text',
                'required' => true,
            ])
            ->add('endDate', DateType::class, [
                'widget' => 'single_text',
                'required' => true,
            ])
            ->add('manufacturer', TextType::class, [
                'required' => true,
            ])
            ->add('provider', TextType::class, [
                'required' => true,
            ])
            ->add('status', ChoiceType::class, [
                'required' => true,
                'choices' => [
                    'Active' => 'active',
                    'Expired' => 'expired',
                    'Pending' => 'pending',
                ],
            ])
            ->add('cost', MoneyType::class, [
                'currency' => false,
                'scale' => 2,
                'attr' => [
                    'placeholder' => 'Enter amount (example: 99.99)',
                ],
                'required' => true,
            ])
            ->add('notes',TextType::class, [
                'attr' => [
                    'placeholder' => 'Enter additional notes or details',
                ],
                'required' => false,
            ])
            ->add('networkDevices', ChoiceType::class, [
                'choices' => [],
                'multiple' => true,
                'required' => false,
            ])
            ->add('networkVirtualSystems', ChoiceType::class, [
                'choices' => [],
                'multiple' => true,
                'required' => false,
            ]);

        $builder->addEventListener(
            FormEvents::PRE_SET_DATA,
            function (FormEvent $event) {
                $data = $event->getData();
                if (!$data) {
                    return;
                }
                
                $choices = new ArrayCollection();
                foreach($data->getNetworkDevices() as $networkDevice) {
                    $choices->add($networkDevice);
                }
                $event->getForm()->add('networkDevices', EntityType::class, [
                    'class' => NetworkDevice::class,
                    'choices' => $choices,
                    'choice_value' => function ($choice) {
                        return $choice ? $choice->getId() : null;
                    },
                    'choice_label' => function ($choice, $key, $value) {
                        return $choice ? $choice->getName() . ' (SN: ' . $choice->getSerialNumber() . ')' : null;
                    },
                    'required' => false,
                    'multiple' => true,
                ]);
                
                $choices = new ArrayCollection();
                foreach($data->getNetworkVirtualSystems() as $networkVirtualSystem) {
                    $choices->add($networkVirtualSystem);
                }
                $event->getForm()->add('networkVirtualSystems', EntityType::class, [
                    'class' => NetworkVirtualSystem::class,
                    'choices' => $choices,
                    'choice_value' => function ($choice) {
                        return $choice ? $choice->getId() : null;
                    },
                    'choice_label' => function ($choice, $key, $value) {
                        return ($choice->getNetworkDevice() ? $choice->getNetworkDevice()->getName() . ': ' : null) . $choice->getName();
                    },
                    'required' => false,
                    'multiple' => true,
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

                if(!isset($eventData['networkDevices'])) {
                    $eventData['networkDevices'] = [];
                }
                $networkDevices = new ArrayCollection();
                foreach($eventData['networkDevices'] as $idMember) {
                    $networkDevices[] = $this->networkDeviceRepository->find($idMember);
                }
                
                $form->add('networkDevices', EntityType::class, [
                    'class' => NetworkDevice::class,
                    'choices' => $networkDevices,
                    'multiple' => true,
                ]);

                if(!isset($eventData['networkVirtualSystems'])) {
                    $eventData['networkVirtualSystems'] = [];
                }
                $networkVirtualSystems = new ArrayCollection();
                foreach($eventData['networkVirtualSystems'] as $idMember) {
                    $networkVirtualSystems[] = $this->networkVirtualSystemRepository->find($idMember);
                }
                
                $form->add('networkVirtualSystems', EntityType::class, [
                    'class' => NetworkVirtualSystem::class,
                    'choices' => $networkVirtualSystems,
                    'multiple' => true,
                ]);

                $event->setData($eventData);
            }
        );
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => MaintenanceContract::class,
        ]);
    }
}
