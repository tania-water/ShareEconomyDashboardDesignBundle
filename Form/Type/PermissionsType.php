<?php

namespace Ibtikar\ShareEconomyDashboardDesignBundle\Form\Type;

use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormView;

class PermissionsType extends AbstractType
{

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return TextType::class;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);
        $resolver->setRequired(array('modulesGroupsData'));
        $resolver->setDefault('showModulesGroupsPermissionsCount', true);
    }

    /**
     * {@inheritdoc}
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        parent::buildView($view, $form, $options);
        $parentData = $form->getParent()->getData()->getPermissions();
        $view->vars['modulesGroupsData'] = $options['modulesGroupsData'];
        $view->vars['showModulesGroupsPermissionsCount'] = $options['showModulesGroupsPermissionsCount'];
    }
}
