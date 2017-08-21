<?php

namespace Quartet\Payment\WebPayBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

class WebPayType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('number', TextType::class)
            ->add('exp_month', TextType::class)
            ->add('exp_year', TextType::class)
            ->add('cvc', TextType::class)
            ->add('name', TextType::class)
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'quartet_payment_webpay';
    }
}
