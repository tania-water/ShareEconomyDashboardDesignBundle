<?php

namespace Ibtikar\ShareEconomyDashboardDesignBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;

class DashboardController extends Controller
{

    /**
     * Dashboard home page
     * @author Mahmoud Mostafa <mahmoud.mostafa@ibtikar.net.sa>
     * @return Response
     */
    public function homeAction()
    {
        return $this->render('IbtikarShareEconomyDashboardDesignBundle:Dashboard:home.html.twig');
    }
}
