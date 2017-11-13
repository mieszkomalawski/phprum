<?php

namespace BacklogBundle\Form;

use PHPRum\DomainModel\Backlog\SubItem;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class StatusUpdateSubItemType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => SubItem::class,
        ]);
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name', TextType::class, ['disabled' => true])
            ->add('description', TextareaType::class, ['disabled' => true])
            ->add('status', TaskStatusType::class, ['required' => false])
            ->getForm();
    }
}
