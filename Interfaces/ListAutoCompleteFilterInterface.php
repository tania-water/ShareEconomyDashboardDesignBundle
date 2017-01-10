<?php

namespace Ibtikar\ShareEconomyDashboardDesignBundle\Interfaces;

/**
 * list filter interface
 *
 * @author Karim Shendy <kareem.elshendy@ibtikar.net.sa>
 */
interface ListAutoCompleteFilterInterface
{

    /**
     * @return  string  unique filter name
     */
    public function getName();

    /**
     * @return  string  input placeholder
     */
    public function getPlaceholder();

    /**
     * @return string route where you will return the select items
     */
    public function getFilterListRoute();

    /**
     * @return array filter list menu [ ['id' => 1, 'text' => 'option one'], ['id' => 2, 'text' => 'option two'] ]
     */
    public function getFilterList($val);

    /**
     * @param \Doctrine\ORM\QueryBuilder $dql QueryBuilder instance
     * @param string $val filter value
     *
     * @return \Doctrine\ORM\QueryBuilder QueryBuilder instance.
     */
    public function applyFilter(\Doctrine\ORM\QueryBuilder $dql, $val);

    public function getItemTextById($id);
}