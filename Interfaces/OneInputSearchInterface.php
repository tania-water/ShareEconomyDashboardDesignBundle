<?php

namespace Ibtikar\ShareEconomyDashboardDesignBundle\Interfaces;

/**
 * one input search interface
 *
 * @author Karim Shendy <kareem.elshendy@ibtikar.net.sa>
 */
interface OneInputSearchInterface
{

    /**
     * @param \Doctrine\ORM\QueryBuilder $dql QueryBuilder instance
     * @param string $val search keyword
     *
     * @return \Doctrine\ORM\QueryBuilder QueryBuilder instance.
     */
    public function applySearch(\Doctrine\ORM\QueryBuilder $dql, $val);
}