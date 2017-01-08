<?php

namespace Ibtikar\ShareEconomyDashboardDesignBundle\Interfaces;

/**
 * list filter interface
 *
 * @author Karim Shendy <kareem.elshendy@ibtikar.net.sa>
 */
interface ListFilterInterface
{

    /**
     * @return  string  unique filter name
     */
    public function getName();

    /**
     * @return array filter list menu (value => label)
     */
    public function getFilterList();

    /**
     * @param \Doctrine\ORM\QueryBuilder $dql QueryBuilder instance
     * @param string $val filter value
     *
     * @return \Doctrine\ORM\QueryBuilder QueryBuilder instance.
     */
    public function applyFilter(\Doctrine\ORM\QueryBuilder $dql, $val);
}