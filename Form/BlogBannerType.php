<?php

namespace Icap\BlogBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class BlogBannerType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('banner_activate', 'checkbox', array(
                'required' => false,
            ))
            ->add('banner_background_color', 'text', array(
                'theme_options' => array('label_width' => '')
            ))
            ->add('banner_height', 'text', array(
                'theme_options' => array('label_width' => ''),
                'attr' => array(
                    'class'    => 'input-sm',
                    'data-min' => 100
                )
            ))
            ->add('banner_background_image', 'file', array(
                'theme_options' => array('label_width' => ''),
                'required' => false
            ))
            ->add('banner_background_image_position', 'integer', array(
                'theme_options' => array('label_width' => ''),
                'required'      => false
            ))
            ->add('banner_background_image_repeat', 'integer', array(
                'theme_options' => array('label_width' => ''),
                'required'      => false
            ))
        ;
    }

    public function getName()
    {
        return 'icap_blog_options_form';
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
           'translation_domain' => 'icap_blog',
            'data_class'        => 'Icap\BlogBundle\Entity\BlogOptions',
            'csrf_protection'   => true,
            'intention'         => 'configure_blog'
        ));
    }
}
