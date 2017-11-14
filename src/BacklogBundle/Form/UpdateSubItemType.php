<?php

namespace BacklogBundle\Form;

use Ivory\CKEditorBundle\Form\Type\CKEditorType;
use PHPRum\DomainModel\Backlog\ItemStatus;
use PHPRum\DomainModel\Backlog\SubItem;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class UpdateSubItemType extends AbstractType
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
            ->add('name', TextType::class, ['required' => false])
            ->add('description', CKEditorType::class, ['required' => false, 'empty_data' => ''])
            ->add('status', TaskStatusType::class, ['required' => false])
            ->add('Save', SubmitType::class);

        $builder
            ->get('status')
            ->addModelTransformer(new ItemStatusTransformer())->getForm();
    }
}
