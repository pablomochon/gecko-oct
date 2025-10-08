<?php

namespace App\Form;

use App\Entity\Client;
use App\Entity\Service;
use App\Repository\ClientRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

class ServiceType extends AbstractType
{
    public function __construct(
        private readonly ClientRepository $clientRepository
    ) {}

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Nombre del servicio',
                'attr' => ['class' => 'form-control'],
            ])
            ->add('tcosrv', TextType::class, [
                'label' => 'TCO Servicio',
                'attr' => ['class' => 'form-control'],
            ])
            ->add('pep', TextType::class, [
                'label' => 'PEP',
                'attr' => ['class' => 'form-control'],
            ])
            ->add('description', TextType::class, [
                'label' => 'Descripción',
                'attr' => ['class' => 'form-control'],
            ])
            ->add('client', ChoiceType::class, [
                'choices' => [],
                'label' => 'Cliente',
                'attr' => ['class' => 'form-control'],
            ]);

        // Evento para precargar datos al editar
        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event): void {
            $data = $event->getData();
            $form = $event->getForm();

            if (!$data || !$data->getClient()) {
                return;
            }

            $form->add('client', EntityType::class, [
                'class' => Client::class,
                'choices' => [$data->getClient()],
                'choice_label' => fn(Client $choice) => $choice->getName(),
                'label' => 'Cliente',
                'attr' => ['class' => 'form-control'],
            ]);
        });

        // Evento para manejar el envío del formulario
        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event): void {
            $eventData = $event->getData();
            $form = $event->getForm();

            if (!$eventData || !isset($eventData['client'])) {
                return;
            }

            $client = $this->clientRepository->find($eventData['client']);

            $form->add('client', EntityType::class, [
                'class' => Client::class,
                'choices' => $client ? [$client] : [],
                'choice_label' => fn(Client $choice) => $choice->getName(),
                'label' => 'Cliente',
                'attr' => ['class' => 'form-control'],
            ]);

            $eventData['client'] = $client ? $client->getId() : null;
            $event->setData($eventData);
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Service::class,
        ]);
    }
}
