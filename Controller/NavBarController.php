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
        $return        = '';
        $kernelBundles = $this->getParameter('kernel.bundles');

        if (count($kernelBundles)) {
            foreach ($kernelBundles as $bundleName => $bundlePath) {
                if ($this->get('templating')->exists($bundleName . '::navBarLinks.html.twig')) {//echo $bundleName . '<br>';
                    $return .= $this->renderView($bundleName . '::navBarLinks.html.twig');
                }
            }
        }

        return new \Symfony\Component\HttpFoundation\Response($return);
    }
}