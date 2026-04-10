<?php

namespace App\Form;

use App\Entity\Product;
use App\Entity\Category;
use App\Entity\Supplier;
use App\Repository\SupplierRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;

class Product1Type extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name')
            ->add('issue')
            ->add('price')
            ->add('category', EntityType::class, [
                'class' => Category::class,
                'choice_label' => 'name',
                'required' => false,
                'placeholder' => 'Select category',
            ])
            ->add('supplier', EntityType::class, [
                'class' => Supplier::class,
                'choice_label' => 'name',
                'required' => false,
                'placeholder' => 'Select supplier',
                'query_builder' => function (SupplierRepository $repo) {
                    return $repo->createQueryBuilder('s')
                        ->andWhere('LOWER(s.name) NOT LIKE :excluded')
                        ->setParameter('excluded', '%city electronics wholesale%')
                        ->orderBy('s.name', 'ASC');
                },
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Product::class,
        ]);
    }
}

