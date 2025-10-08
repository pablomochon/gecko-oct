<?php

namespace App\Form;

use App\Entity\ActivityType;
use App\Entity\NetworkDevice;
use App\Entity\NetworkVirtualSystem;
use App\Entity\NetworkInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Bundle\SecurityBundle\Security;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use App\Repository\NetworkDeviceRepository;
use App\Repository\NetworkInterfaceRepository;
use App\Repository\NetworkVirtualSystemRepository;

class ActivityTypeType extends AbstractType
{
    public function __construct(private NetworkDeviceRepository $networkDeviceRepository
    , private NetworkVirtualSystemRepository $networkVirtualSystemRepository
    , private NetworkInterfaceRepository $networkInterfaceRepository
    , private Security $security) {

    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('code', TextType::class, [
                'attr' => [
                    'placeholder' => 'Enter unique activity code',
                ],
                'required' => true,
            ])
            ->add('description', TextType::class, [
                'attr' => [
                    'placeholder' => 'Describe the activity type',
                ],
                'required' => true,
            ])
            ->add('price', MoneyType::class, [
                'currency' => false,
                'scale' => 2,
                'attr' => [
                    'placeholder' => 'Enter amount (example: 99.99)',
                    'class' => 'form-control',
                ],
                'required' => true,
            ])
            ->add('SAPname', TextType::class, [
                'attr' => [
                    'placeholder' => 'Enter SAP name',
                ],
                'required' => false,
            ])
            ->add('type', ChoiceType::class, [
                'choices' => [
                    'Type A' => 'a',
                    'Type B' => 'b',
                    'Type C' => 'c',
                    'Other' => 'other'
                ],
                'required' => true
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
            ])
            ->add('networkInterfaces', ChoiceType::class, [
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

                    $choices = new ArrayCollection();
                    foreach($data->getNetworkInterfaces() as $networkInterface) {
                        $choices->add($networkInterface);
                    }
                    $event->getForm()->add('networkInterfaces', EntityType::class, [
                        'class' => NetworkInterface::class,
                        'choices' => $choices,
                        'choice_value' => function ($choice) {
                            return $choice ? $choice->getId() : null;
                        },
                        'choice_label' => function ($choice, $key, $value) {
                            if($choice->getNetworkVirtualSystem()) {
                                return $choice->getNetworkVirtualSystem()->getNetworkDevice()->getName() . ' (' . $choice->getNetworkVirtualSystem()->getName() . '): ' . $choice->getName();
                            }
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

                    if(!isset($eventData['networkInterfaces'])) {
                        $eventData['networkInterfaces'] = [];
                    }
                    $networkInterfaces = new ArrayCollection();
                    foreach($eventData['networkInterfaces'] as $idMember) {
                        $networkInterfaces[] = $this->networkInterfaceRepository->find($idMember);
                    }
                    
                    $form->add('networkInterfaces', EntityType::class, [
                        'class' => NetworkInterface::class,
                        'choices' => $networkInterfaces,
                        'multiple' => true,
                    ]);
    
                    $event->setData($eventData);
                }
            );
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ActivityType::class,
        ]);
    }
}