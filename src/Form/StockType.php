<?php

namespace App\Form;

use App\Entity\Stock;
use App\Entity\Supplier;
use App\Repository\SupplierRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class StockType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('itemName', TextType::class, [
                'label' => 'Item Name',
                'attr' => ['placeholder' => 'Enter item name']
            ])
            ->add('sku', TextType::class, [
                'label' => 'SKU',
                'required' => false,
                'attr' => ['placeholder' => 'Enter SKU code']
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => false,
                'attr' => ['placeholder' => 'Enter item description', 'rows' => 3]
            ])
            ->add('quantity', IntegerType::class, [
                'label' => 'Quantity',
                'attr' => ['min' => 0]
            ])
            ->add('minThreshold', IntegerType::class, [
                'label' => 'Minimum Threshold',
                'required' => false,
                'attr' => ['min' => 0, 'placeholder' => 'Low stock alert level']
            ])
            ->add('unit', TextType::class, [
                'label' => 'Unit',
                'required' => false,
                'attr' => ['placeholder' => 'e.g., units, pieces, kg']
            ])
            ->add('unitCost', MoneyType::class, [
                'label' => 'Unit Cost',
                'required' => false,
                'currency' => 'PHP',
                'attr' => ['placeholder' => 'Cost per unit']
            ])
            ->add('location', TextType::class, [
                'label' => 'Storage Location',
                'required' => false,
                'attr' => ['placeholder' => 'e.g., Warehouse A-1']
            ])
            ->add('supplier', EntityType::class, [
                'class' => Supplier::class,
                'choice_label' => 'name',
                'required' => true,
                'placeholder' => 'Select a supplier',
                'query_builder' => function (SupplierRepository $repo) {
                    return $repo->createQueryBuilder('s')
                        ->orderBy('s.name', 'ASC');
                },
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Stock::class,
        ]);
    }
}
