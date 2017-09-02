<?php


namespace BacklogBundle\Form;


use BacklogBundle\Entity\Item;
use BacklogBundle\Entity\User;
use PHPRum\DomainModel\Backlog\Backlog;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CreateItemType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Item::class
        ])->setRequired(['user', 'backlog']);
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        /** @var User $user */
        $user = $options['user'];
        /** @var Backlog $backlog */
        $backlog = $options['backlog'];
        $emptyData = function (FormInterface $form) use ($user, $backlog) {
            if ($form->has('name') && is_string($form->get('name')->getData())) {
                return $backlog->createItem($form->get('name')->getData(), $user);
            }
        };
        $builder->setEmptyData(
            $emptyData
        )
            ->add('name', TextType::class)
            ->add('save', SubmitType::class, ['label' => 'Save'])
            ->getForm();
    }

}