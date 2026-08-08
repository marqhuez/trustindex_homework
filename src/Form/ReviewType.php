<?php

namespace App\Form;

use App\Form\Dto\CreateReviewRequest;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class ReviewType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('rating', ChoiceType::class, [
                'choices' => ['1' => 1, '2' => 2, '3' => 3, '4' => 4, '5' => 5],
                'expanded' => true,
                'multiple' => false,
                'label' => false,
            ])
            ->add('reviewText', TextareaType::class, [
                'label' => 'Text',
            ])
            ->add('authorEmail', EmailType::class, [
                'label' => 'Email',
            ])
            ->add('companyName', TextType::class, [
                'label' => 'Company',
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'Submit Review',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => CreateReviewRequest::class,
        ]);
    }
}
