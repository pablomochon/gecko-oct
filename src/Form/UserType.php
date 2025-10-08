<?php

namespace App\Form;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Bundle\SecurityBundle\Security;

class UserType extends AbstractType
{
    private $entityManager;
    private $security;

    public function __construct(EntityManagerInterface $entityManager, Security $security)
    {
        $this->entityManager = $entityManager;
        $this->security = $security;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
        ->add('username', TextType::class, [
            'empty_data' => '',
        ])
        ->add('password', PasswordType::class, [
            'always_empty' => false,
            'trim' => true,
            'empty_data' => '',
            'required' => false,
        ])
        ->add('name')
        ->add('email', EmailType::class)
        ->add('roles', ChoiceType::class, [
            'label' => 'Effective Roles',
            'choices' => [
                'ROLE_ACTIVITY_TYPE_EDITOR' => 'ROLE_ACTIVITY_TYPE_EDITOR',
                'ROLE_ADMIN' => 'ROLE_ADMIN',
                'ROLE_USER' => 'ROLE_USER',
            ],
            'multiple' => true,
            'disabled' => true,
        ])
        ->add('rolesFixed', ChoiceType::class, [
            'label' => 'Fixed Roles',
            'choices' => [
                'ROLE_ACTIVITY_TYPE_EDITOR' => 'ROLE_ACTIVITY_TYPE_EDITOR',
                'ROLE_ADMIN' => 'ROLE_ADMIN',
                'ROLE_API_EDITOR' => 'ROLE_API_EDITOR',
                'ROLE_DATA_UPLOADER' => 'ROLE_DATA_UPLOADER',
                'ROLE_USER' => 'ROLE_USER',
            ],
            'required' => false,
            'multiple' => true,
            'disabled' => !$this->security->isGranted('ROLE_ADMIN'),
        ])
        ;

        $builder->addEventListener(
            FormEvents::PRE_SET_DATA,
            function (FormEvent $event) {
                $data = $event->getData();
                if (!$data) {
                    return;
                }

                $event->getForm()->add('username', TextType::class, [
                    'empty_data' => '',
                    'disabled' => true,
                ])
                ->add('name', TextType::class, [
                    'disabled' => (($this->security->isGranted('ROLE_ADMIN') || $this->security->getUser()->getId() == $data->getId()) ? false : true),
                ])
                ->add('email', EmailType::class, [
                    'disabled' => (($this->security->isGranted('ROLE_ADMIN') || $this->security->getUser()->getId() == $data->getId()) ? false : true),
                ])
                ->add('password', PasswordType::class, [
                    'always_empty' => false,
                    'trim' => true,
                    'empty_data' => '',
                    'required' => false,
                    'disabled' => (($this->security->isGranted('ROLE_ADMIN') || $this->security->getUser()->getId() == $data->getId()) ? false : true),
                ])
                ;
            }
        );

    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
