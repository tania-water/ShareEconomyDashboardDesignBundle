<?php

namespace Ibtikar\ShareEconomyDashboardDesignBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class NavBarController extends Controller
{

    /**
     *
     * @return string
     */
    public function getNavBarAdditionalLinksAction()
    {
        $return = '';

        if ($this->container->hasParameter('ibtikar_share_economy_dashboard_design.navBarMenuBundles')) {
            $navBarMenuBundles = $this->getParameter('ibtikar_share_economy_dashboard_design.navBarMenuBundles');
            $kernelBundles          = $this->getParameter('kernel.bundles');

            if (count($navBarMenuBundles)) {
                foreach ($navBarMenuBundles as $bundleName) {
                    if (isset($kernelBundles[$bundleName]) && $this->get('templating')->exists($bundleName . '::navBarLinks.html.twig')) {
                        $return .= $this->renderView($bundleName . '::navBarLinks.html.twig');
                    }
                }
            }
        }

        return new \Symfony\Component\HttpFoundation\Response($return);
    }
}