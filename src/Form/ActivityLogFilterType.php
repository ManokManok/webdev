<?php

namespace App\Form;

use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ActivityLogFilterType extends AbstractType
{
    public function __construct(
        private UserRepository $userRepository
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $users = $this->userRepository->createQueryBuilder('u')
            ->orderBy('u.username', 'ASC')
            ->getQuery()
            ->getResult();

        $userChoices = [];
        foreach ($users as $user) {
            $userChoices[$user->getUsername()] = $user->getId();
        }

        $builder
            ->add('user', ChoiceType::class, [
                'required' => false,
                'choices' => $userChoices,
                'placeholder' => 'All Users',
                'attr' => [
                    'class' => 'form-control',
                ],
            ])
            ->add('action', ChoiceType::class, [
                'required' => false,
                'choices' => [
                    'Login' => 'login',
                    'Logout' => 'logout',
                    'Create' => 'create',
                    'Update' => 'update',
                    'Delete' => 'delete',
                ],
                'placeholder' => 'All Actions',
                'attr' => [
                    'class' => 'form-control',
                ],
            ])
            ->add('entity', ChoiceType::class, [
                'required' => false,
                'choices' => [
                    'User' => 'User',
                    'Product' => 'Product',
                    'Category' => 'Category',
                    'Supplier' => 'Supplier',
                ],
                'placeholder' => 'All Entities',
                'attr' => [
                    'class' => 'form-control',
                ],
            ])
            ->add('dateFrom', DateType::class, [
                'required' => false,
                'widget' => 'single_text',
                'html5' => true,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'From Date',
                ],
            ])
            ->add('dateTo', DateType::class, [
                'required' => false,
                'widget' => 'single_text',
                'html5' => true,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'To Date',
                ],
            ])
            ->add('filter', SubmitType::class, [
                'attr' => [
                    'class' => 'btn btn-primary',
                ],
            ])
            ->add('reset', SubmitType::class, [
                'label' => 'Reset',
                'attr' => [
                    'class' => 'btn btn-secondary',
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'csrf_protection' => false,
            'method' => 'GET',
        ]);
    }

    public function getBlockPrefix(): string
    {
        return ''; // Remove the form name from query parameters
    }
}
