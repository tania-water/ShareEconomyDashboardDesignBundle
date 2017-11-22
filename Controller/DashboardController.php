<?php

namespace Ibtikar\ShareEconomyDashboardDesignBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\ORM\Query\Expr\Andx;

class DashboardController extends Controller
{

    protected $className = '';
    protected $fullClassName = '';
    protected $preFix = '';
    protected $isExportToExcelEnabled = false;
    protected $exportToExcelRoute = '';

    protected $entityBundle = '';

    protected $listColumns = array();

    protected $listSearchColumns = array();

    protected $listActions = array();

    protected $isClickableRow = false;
    protected $clickableRowRouteName = null;

    /**
     * path to the template which contains the additional actions.
     * the row entity will passed to the template as "entity".
     *
     * @var string
     */
    protected $listAdditionalActionsTemplate;

    protected $listGlobalActions = array();

    protected $listBulkActions = array();

    protected $defaultSort = array('column' => 'createdAt', 'sort' => 'desc');

    protected $translationDomain = '';

    protected $pageTitle = '';

    protected $pagesLimit = 20;

    protected $pagesOffset = 0;

    protected  $defaultDateFormat = 'd/m/Y';

    protected  $isSearchable = true;

    protected  $isPrintable = false;

    private $listOneFieldSearchParam             = "oneFieldSearch";
    private $listOneFieldSearchInterfaceFQNS     = "Ibtikar\ShareEconomyDashboardDesignBundle\Interfaces\OneInputSearchInterface";
    private $listFilterInterfaceFQNS             = "Ibtikar\ShareEconomyDashboardDesignBundle\Interfaces\ListFilterInterface";
    private $listAutoCompleteFilterInterfaceFQNS = "Ibtikar\ShareEconomyDashboardDesignBundle\Interfaces\ListAutoCompleteFilterInterface";

    protected $maxRecords = null;

    protected $minRecords = 0;

    protected $formName = '';

    protected $listAdditionalVars = [];

    protected $modulesGroups = array(
    );
    protected $internalPermissions = array(
        'ROLE_ADMIN', 'ROLE_STAFF'
    );

    public function __construct()
    {

    }

    /**
     *
     * @param EntityManager $em
     */
    public static function refreshDatabaseConnection($em)
    {
        if (false === $em->getConnection()->ping()) {
            $em->getConnection()->close();
            $em->getConnection()->connect();
        }
    }

    /**
     * Mahmoud Mostafa <mahmoud.mostafa@ibtikar.net.sa>
     * @param Request $request
     * @return StreamedResponse
     * @throws \Exception
     */
    public function exportAction(Request $request) {
        $this->setListParameters();
        $response = new StreamedResponse();
        $fileName = $request->get('fileName', 'list');
        $response->headers->add(array(
            'Content-Type' => 'application/vnd.ms-excel; charset=utf-8',
            'Content-Disposition' => "attachment; filename=$fileName.xls"
        ));
        $em = $this->getDoctrine()->getManager();
        $query = $this->getListQuery();
        $sort = null;
        if ($request->get('sort')) {
            $sort = $request->get('sort');
            $sortOrder = $request->get('columnDir');
        } else if (count($this->defaultSort)) {
            $sort = $this->defaultSort['column'];
            $sortOrder = $this->defaultSort['sort'];
        }
        if (strpos($sort, "."))
            $query = $query->addOrderBy($sort, $sortOrder);
        else
            $query = $query->addOrderBy('e.' . $sort, $sortOrder);
        if ($request->get('searchKey')) {
            $searchKey = json_decode($request->get('searchKey'));
            $searchValue = json_decode($request->get('searchValue'));
            if (count($searchKey) == count($searchValue)) {
                $andX = new Andx();
                $this->appendSearchtoQuery($query, $andX, $searchKey, $searchValue);
                if ($andX->count() > 0)
                    $query = $query->andWhere($query->expr()->andX($andX));
            }
        }
        // apply list filters
        $listFilters = $this->getListFilters();
        if (count($listFilters)) {
            $filtersNames = [];
            foreach ($listFilters as $listFilter) {
                // validate filters
                if (!in_array($this->listFilterInterfaceFQNS, class_implements($listFilter)) && !in_array($this->listAutoCompleteFilterInterfaceFQNS, class_implements($listFilter))) {
                    throw new \Exception('List filter class should implements ' . $this->listFilterInterfaceFQNS . ' or ' . $this->listAutoCompleteFilterInterfaceFQNS);
                }
                if (array_search($listFilter->getName(), $filtersNames) === false) {
                    $filtersNames[] = $listFilter->getName();
                } else {
                    throw new \Exception('Filter named "' . $listFilter->getName() . '" has been found many times. filters names should be unique.');
                }
                // apply filter if its parameter exists
                if ($request->query->has($listFilter->getName()) && $request->query->get($listFilter->getName()) !== null) {
                    $query = $listFilter->applyFilter($query, $request->query->get($listFilter->getName()));
                }
            }
        }
        // apply one field search
        $oneInputSearch = $this->getListOneInputSearch();
        if (null !== $oneInputSearch) {
            // validate filters
            if (!in_array($this->listOneFieldSearchInterfaceFQNS, class_implements($oneInputSearch))) {
                throw new \Exception('One field search class should implements ' . $this->listOneFieldSearchInterfaceFQNS);
            }

            // apply filter if its parameter exists
            if ($request->query->has($this->listOneFieldSearchParam) && $request->query->get($this->listOneFieldSearchParam) !== null) {
                $query = $oneInputSearch->applySearch($query, $request->query->get($this->listOneFieldSearchParam));
            }
        }
        $twig = $this->get('twig');
        $translator = $this->get('translator');
        $response->setCallback(function () use ($em, $query, $twig, $translator) {
            // File header
            echo '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40"><head><!--[if gte mso 9]><xml><x:ExcelWorkbook><x:ExcelWorksheets><x:ExcelWorksheet>';
            echo '<x:Name>Sheet 1</x:Name>';
            echo '<x:WorksheetOptions><x:DisplayGridlines/></x:WorksheetOptions></x:ExcelWorksheet></x:ExcelWorksheets></x:ExcelWorkbook></xml><![endif]--><meta http-equiv="content-type" content="application/vnd.ms-excel; charset=UTF-8"></head><body><table>';
            ob_flush();
            flush();
            // File columns headers
            echo '<tr>';
            foreach ($this->listColumns as $columnData) {
                $columnName = $columnData[0];
                if (isset($columnData[1]) && isset($columnData[1]['name'])) {
                    $columnName = $columnData[1]['name'];
                }
                $value = $twig->createTemplate('{{ value|humanize|title|trans({}, translationDomain) }}')->render(array('value' => $columnName, 'translationDomain' => $this->translationDomain));
                echo '<td>' . htmlentities($value, null, 'UTF-8') . '</td>';
            }
            echo '</tr>';
            $iterationItemsCount = 50;
            $page = 1;
            do {
                $query = $query->setFirstResult(($page - 1) * $iterationItemsCount)
                    ->setMaxResults($iterationItemsCount);
                static::refreshDatabaseConnection($em);
                $results = $query->getQuery()->execute();
                foreach ($results as $result) {
                    echo '<tr>';
                    foreach ($this->listColumns as $columnData) {
                        $translateData = false;
                        $valueType = 'string';
                        $dataGetter = 'get' . ucfirst($columnData[0]);
                        if (isset($columnData[1])) {
                            if (isset($columnData[1]['method'])) {
                                $dataGetter = $columnData[1]['method'];
                            }
                            if (isset($columnData[1]['type'])) {
                                $valueType = $columnData[1]['type'];
                            }
                            if (isset($columnData[1]['selectSearch'])) {
                                $translateData = true;
                            }
                        }
                        $value = call_user_func(array($result, $dataGetter));
                        if ($valueType === 'date') {
                            $value = $value->format($this->defaultDateFormat);
                        }
                        if ($valueType === 'numeric') {
                            $value = '"'.$value.'"';
                        }
                        if ($translateData) {
                            $value = $translator->trans($value, array(), $this->translationDomain);
                        }
                        echo '<td>' . htmlentities($value, null, 'UTF-8') . '</td>';
                    }
                    echo '</tr>';
                }
                $page++;
            } while ($iterationItemsCount === count($results));
            // File end
            echo '</table></body></html>';
            ob_flush();
            flush();
        });
        return $response;
    }

    /**
     * Mahmoud Mostafa <mahmoud.mostafa@ibtikar.net.sa>
     * @param array $selectedPermissions
     * @return array
     */
    protected function getSystemModulesGroupsData(array $selectedPermissions = array())
    {
        $notFoundModules = array();
        $modulesGroupsData = array();
        foreach ($this->modulesGroups as $moduleGroupName => $modules) {
            $modulesGroupsData[$moduleGroupName] = array(
                'selectedPermissionsCount' => 0,
                'permissionsCount' => 0,
                'name' => $moduleGroupName,
                'permissionsNames' => array(),
                'modulesData' => array(),
            );
            foreach ($modules as $module) {
                $modulesGroupsData[$moduleGroupName]['modulesData'][$module] = array(
                    'selectedPermissionsCount' => 0,
                    'permissionsCount' => 0,
                    'name' => $module,
                    'permissionsData' => array(),
                );
            }
        }
        if (!$this->container->hasParameter('permissions')) {
            throw new \Exception('Please define permissions parameter');
        }
        $systemPermissions = $this->container->getParameter('permissions');
        foreach ($this->internalPermissions as $permission) {
            unset($systemPermissions[$permission]);
        }
        foreach ($systemPermissions as $permission => $subPermissions) {
            // 0 => ROLE, 1 => module name, 2 => permission
            $permissionNameParts = explode('_', $permission);
            $moduleName = $permissionNameParts[1];
            $permissionName = $permissionNameParts[2];
            $permissionModuleGroupName = 'not found';
            foreach ($this->modulesGroups as $moduleGroupName => $modules) {
                if (in_array($moduleName, $modules)) {
                    $permissionModuleGroupName = $moduleGroupName;
                    break;
                }
            }
            if ($permissionModuleGroupName !== 'not found') {
                $modulesGroupsData[$moduleGroupName]['permissionsCount'] ++;
                $modulesGroupsData[$moduleGroupName]['permissionsNames'][$permissionName] = $permissionName;
                $modulesGroupsData[$moduleGroupName]['modulesData'][$moduleName]['permissionsCount'] ++;
                $selectedPermission = in_array($permission, $selectedPermissions);
                if ($selectedPermission) {
                    $modulesGroupsData[$moduleGroupName]['selectedPermissionsCount'] ++;
                    $modulesGroupsData[$moduleGroupName]['modulesData'][$moduleName]['selectedPermissionsCount'] ++;
                }
                $modulesGroupsData[$moduleGroupName]['modulesData'][$moduleName]['permissionsData'][$permissionName] = array(
                    'selected' => $selectedPermission,
                    'permission' => $permission,
                );
            } else {
                if (!isset($notFoundModules[$moduleName])) {
                    $notFoundModules[$moduleName] = array();
                }
                $notFoundModules[$moduleName] [] = $permission;
            }
        }
//        echo "<pre style=\"border: 1px solid #000; overflow: auto; margin: 0.5em;\">";
//        var_dump($notFoundModules);
//        echo "</pre>\n";
//        exit;
        return $modulesGroupsData;
    }

    /**
     * Dashboard home page
     * @author Mahmoud Mostafa <mahmoud.mostafa@ibtikar.net.sa>
     * @return Response
     */
    public function homeAction()
    {
        return $this->render('IbtikarShareEconomyDashboardDesignBundle:Dashboard:home.html.twig');
    }

    public function getFlashBag($status, $message){
        $this->get('session')->getFlashBag()->add($status, $message);
    }

    protected function getEntityPerId($entityId){
        $em = $this->getDoctrine()->getManager();
        $entity = $em->getRepository($this->entityBundle.":".$this->className)->findOneBy(array('id'=>$entityId));
        return $entity;
    }

    public function deleteEntity($entity){
        $em = $this->getDoctrine()->getManager();
        $em->remove($entity);
        $em->flush();
        return new JsonResponse(array('status' => 'success', 'message' => $this->get('translator')->trans('Done Successfully'), 'allowAdd'=>true));
    }

    protected function deleteFailedOperation(){
        return new JsonResponse(array('status' => 'error', 'message' => $this->get('translator')->trans('Already Deleted'), 'allowAdd'=>true));
    }

    public function deleteAction($entityId){
        $entity = $this->getEntityPerId($entityId);
        if($entity){
            return $this->deleteEntity($entity);
        }
        return $this->deleteFailedOperation();
    }

    public function entityActivation($entity, $activate){
        $em = $this->getDoctrine()->getManager();
        $activation = $activate =='true'?true:false;
        $entity->setEnabled($activation);
        $em->persist($entity);
        $em->flush();
        return new JsonResponse(array('status' => 'success', 'message' => $this->get('translator')->trans('Done Successfully')));
    }

    public function activationAction($entityId, $activate){
        $entity = $this->getEntityPerId($entityId);
        if($entity){
            return $this->entityActivation($entity, $activate);
        }
        return new JsonResponse(array('status' => 'error', 'message' => $this->get('translator')->trans('Failed Operation')));
    }

    protected function getCreateFormOptions(){
        $options = array('translation_domain'=>$this->translationDomain);
        return $options;
    }

    protected function prePostParametersCreate(){
        return array('closeRedirection'=>$this->generateUrl(strtolower($this->preFix .$this->className) . '_list'));
    }

    protected function postValidCreate(Request $request, $entity){
        $em = $this->get('doctrine')->getManager();
        $em->persist($entity);
        $em->flush();
        $this->getFlashBag("success", $this->get('translator')->trans('Done Successfully'));
        return $this->redirect($this->generateUrl(strtolower($this->preFix .$this->className) . '_list'));
    }

    public function createAction(Request $request){
        $className = $this->entityBundle."\\Entity\\".$this->className;
        $createNewClass = new $className();
        $formType = $this->entityBundle."\\Form\\".$this->className.'Type';
        $formOptions = $this->getCreateFormOptions();
        $form = $this->createForm($formType, $createNewClass, $formOptions);
        $prePostParameters = $this->prePostParametersCreate();
        if ($request->getMethod() === 'POST') {
            $em = $this->get('doctrine')->getManager();
            $formData = $request->get(strtolower($this->className).'_type');
            $form->handleRequest($request);
            if ($form->isValid()) {
                return $this->postValidCreate($request, $createNewClass);
            }
        }
        $title = $this->getPageTitle()? $this->getPageTitle():$title = $this->get('translator')->trans('Add New '.$this->className, array(), $this->translationDomain);;

        if (!$this->get('templating')->exists($this->entityBundle.':Create:'.strtolower($this->className).'.html.twig'))
            return $this->render($this->entityBundle.':Layout:dashboard_form.html.twig',  array_merge (array(
                'form' => $form->createView(),
                'className' => $this->className,
                'title' => $title,
                'translationDomain' => $this->translationDomain
            ), $prePostParameters));
        else
            return $this->render($this->entityBundle.':Create:'.strtolower($this->className).'.html.twig', array_merge (array(
                'form' => $form->createView(),
                'className' => $this->className,
                'title' => $title,
                'translationDomain' => $this->translationDomain
            ), $prePostParameters));
    }

    protected function getPageTitle()
    {
    }

    /**
     * @author Moemen Hussein <moemen.hussein@ibtikar.net.sa>
     */
    public function listAction(Request $request){
        $this->setListParameters();
        $list = $this->getListParameters($request);

        if ($request->isXmlHttpRequest()) {
            return $this->getListJsonData($request, $list);
        }

        $templateVars = array_merge([
            'list'                    => $list,
            'action_form'             => $this->createActionForm()->createView(),
            'list_filters'            => $this->getListFilters(),
            'oneInputSearch'          => $this->getListOneInputSearch(),
            'listOneFieldSearchParam' => $this->listOneFieldSearchParam
        ], $this->listAdditionalVars);

        if ($this->get('templating')->exists($this->entityBundle . ':List:' . strtolower($this->className) . '.html.twig')) {
            return $this->render($this->entityBundle . ':List:' . strtolower($this->className) . '.html.twig', $templateVars);
        }

        if ($this->get('templating')->exists($this->entityBundle . ':List:list.html.twig')) {
            return $this->render($this->entityBundle . ':List:list.html.twig', $templateVars);
        }

        return $this->render('IbtikarShareEconomyDashboardDesignBundle:List:list.html.twig', $templateVars);
    }

    public function getListQuery(){
        $em = $this->getDoctrine()->getManager();
        return $em->createQueryBuilder()
                ->select('e')
                ->from($this->entityBundle.':'.$this->className, 'e');
    }

    protected function getListParameters(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $query = $this->getListQuery();

        if ($request->get('autocompleteField') && $request->isXmlHttpRequest()) {
            $autocompleteField = $request->get('autocompleteField');
            $autocompleteValue = $request->get('autocompleteValue');

            if(strpos($autocompleteField, '.') === false){
                $query->andWhere('e.' . $autocompleteField . ' like :autocompleteValue')
                        ->select('e.' . $autocompleteField)
                        ->distinct()
                        ->setParameter('autocompleteValue', '%' . $autocompleteValue . '%')
                        ->setMaxResults(5);
                $query = $query->addOrderBy('e.'.$autocompleteField, 'ASC');
                $result = $query->getQuery()->getResult(\Doctrine\ORM\Query::HYDRATE_ARRAY);

                $autocompleteresult = array();
                $methodName = 'get' . ucfirst($autocompleteField);
                foreach ($result as $row) {
                    $autocompleteresult[] = (string)$row[$autocompleteField];
                }
            }
            else{
                $entityFieldArray = explode('.', $autocompleteField);
                $query->innerJoin('e.'.$entityFieldArray[0], 'u')
                        ->select('u.'.$entityFieldArray[1])
                        ->andWhere('u.' . $entityFieldArray[1] . ' like :autocompleteValue')
                        ->distinct()
                        ->setParameter('autocompleteValue', '%' . $autocompleteValue . '%')
                        ->setMaxResults(5);
                $query = $query->addOrderBy('u.'.$entityFieldArray[1], 'ASC');
                $result = $query->getQuery()->getResult();
                $autocompleteresult = array();
                $methodName = 'get' . ucfirst($entityFieldArray[1]);
                foreach ($result as $row) {
                    $autocompleteresult[] = (string)$row[$entityFieldArray[1]];
                }
            }
            return array('autocompelete'=>$autocompleteresult);
        }
        $this->setPageTitle();//remove
        $limit = $this->pagesLimit;
        if($request->get('limit') && in_array($request->get('limit'), array(10, 20, 50)))
            $limit = $request->get('limit');
        $offset = $this->pagesOffset;
        if($request->get('page'))
            $offset = ($request->get('page')-1)*$limit;
        $query = $query->setFirstResult($offset)
                        ->setMaxResults($limit);
        $sort = null;
        if($request->get('sort')){
            $sort = $request->get('sort');
            $sortOrder = $request->get('columnDir');
        }
        else if(count($this->defaultSort)){
            $sort = $this->defaultSort['column'];
            $sortOrder = $this->defaultSort['sort'];
        }

        if (strpos($sort, "."))
            $query = $query->addOrderBy($sort, $sortOrder);
        else
            $query = $query->addOrderBy('e.'.$sort, $sortOrder);

        if($request->get('searchKey')){

            $searchKey = json_decode($request->get('searchKey'));
            $searchValue = json_decode($request->get('searchValue'));

            if(count($searchKey) == count($searchValue)){
                $andX = new Andx();
                $this->appendSearchtoQuery($query,$andX,$searchKey,$searchValue, $request->get('isExactSearch', false));
                if($andX->count()>0)
                    $query = $query->andWhere($query->expr()->andX($andX));
            }
        }

        // apply list filters
        $listFilters = $this->getListFilters();
        if (count($listFilters)) {
            $filtersNames = [];
            foreach ($listFilters as $listFilter) {
                // validate filters
                if (!in_array($this->listFilterInterfaceFQNS, class_implements($listFilter)) && !in_array($this->listAutoCompleteFilterInterfaceFQNS, class_implements($listFilter))) {
                    throw new \Exception('List filter class should implements ' . $this->listFilterInterfaceFQNS . ' or ' . $this->listAutoCompleteFilterInterfaceFQNS);
                }

                if (array_search($listFilter->getName(), $filtersNames) === false) {
                    $filtersNames[] = $listFilter->getName();
                } else {
                    throw new \Exception('Filter named "' . $listFilter->getName() . '" has been found many times. filters names should be unique.');
                }

                // apply filter if its parameter exists
                if ($request->query->has($listFilter->getName()) && $request->query->get($listFilter->getName()) !== null) {
                    $query = $listFilter->applyFilter($query, $request->query->get($listFilter->getName()));
                }
            }
        }

        // apply one field search
        $oneInputSearch = $this->getListOneInputSearch();
        if (null !== $oneInputSearch) {
            // validate filters
            if (!in_array($this->listOneFieldSearchInterfaceFQNS, class_implements($oneInputSearch))) {
                throw new \Exception('One field search class should implements ' . $this->listOneFieldSearchInterfaceFQNS);
            }

            // apply filter if its parameter exists
            if ($request->query->has($this->listOneFieldSearchParam) && $request->query->get($this->listOneFieldSearchParam) !== null) {
                $query = $oneInputSearch->applySearch($query, $request->query->get($this->listOneFieldSearchParam));
            }
        }

        $query = $query->getQuery();
        $paginator = new Paginator($query, true);
        $totalNumber = count($paginator);
        $pagination = $query->execute();
        $datatableColumns = array();
        $columnArray=array();
        $datatableColumnsIndex = 0;
        if(count($this->listBulkActions) || $this->isSearchable){
            $datatableColumns[$datatableColumnsIndex] = array('data'=>'checkBox', 'orderable'=>false);
            $columnArray[]='checkBox';
            $datatableColumnsIndex++;
        }
        foreach($this->listColumns as &$column){
            if(isset($column[1]['permission']) && (!$this->get('security.authorization_checker')->isGranted($column[1]['permission']) && !$this->get('security.authorization_checker')->isGranted('ROLE_ADMIN')))
                unset($column[1]['isClickableField']);

            $datatableColumns[$datatableColumnsIndex] = array('data'=>$column[0]);
            if(count($column)>1){
                $datatableColumns[$datatableColumnsIndex]['orderable'] = array_key_exists('isSortable', $column[1])?$column[1]['isSortable']:true;
            }
            else{
                $datatableColumns[$datatableColumnsIndex]['orderable'] = true;
            }
            $column[1]['autocompelete'] = isset($column[1]['autocompelete']) ? $column[1]['autocompelete'] : $this->getParameter('ibtikar_share_economy_dashboard_design.dashboard_list_autocompelete');
            $columnArray[] = $column;
            if ((isset($column[1]['sort']) && $column[1]['sort'] == $sort) || (isset($column[1]['entity']) && $column[1]['entity'].'.'.$column[0] == $sort) || $sort == $column[0]) {
                $sortIndex = $datatableColumnsIndex;
            }
            $datatableColumnsIndex++;
        }
        if(count($this->listActions) || $this->listAdditionalActionsTemplate || $this->isSearchable){
            $datatableColumns[$datatableColumnsIndex] = array('data'=>'actions', 'orderable'=>false);
            $columnArray[]='actions';
            $datatableColumnsIndex++;
        }
        if($this->isClickableRow){
            $columnArray[] = 'isClickableRow';
        }
        $paginationSort = null;
        if($sort)
            $paginationSort = json_encode(array($sortIndex, $sortOrder));
//        if($reference)
//            $query->leftJoin($reference, 'l', "WITH", "e." . strtolower(explode(":", $reference)[1]) . " =l.id");
//        if($interval)
//        {
//            if(array_key_exists('from', $interval)){
//                $intervalFrom = \DateTime::createFromFormat('d/m/Y', $interval['from'])->setTime(0, 0, 0);;
//                $query->andWhere("e.createdAt >= '" . $intervalFrom->format('Y-m-d H:i:s') . "'");
//            }
//            if(array_key_exists('to', $interval)){
//                $intervalTo = \DateTime::createFromFormat('d/m/Y', $interval['to'])->setTime(23, 59, 59);
//                $query->andWhere("e.createdAt <= '" . $intervalTo->format('Y-m-d H:i:s') . "'");
//            }
//        }
//        foreach($listQueryParameters as $key => $val )
//        {
//                $query->andWhere('e.'.$key. ' in (:val)')
//                      ->setParameter('val',$val);
//        }
//        foreach($listQuerySort as $key => $val )
//                $query->addOrderBy('e.'.$key,$val);
//
//        foreach($listReferenceQuerySort as $key => $val )
//                $query->addOrderBy('l.'.$key,$val);
        return array(
            'className' => $this->className,
            'preFix' => $this->preFix,
            'totalNumber' => $totalNumber,
            'listRowDataParameters' => $this->getListRowDataParameters(),
            'pagination'  => $pagination,
            'columns'   => $this->listColumns,
            'autocompeleteMinNoOfCharacter'   => $this->getParameter('ibtikar_share_economy_dashboard_design.dashboard_list_autocompeleteMinNoOfCharacter'),
            'datatableColumns'   => json_encode($datatableColumns),
            'columnArray'   => $columnArray,
            'actions'   => $this->listActions,
            'additionalActionsTemplate' => $this->listAdditionalActionsTemplate,
            'globalActions'   => $this->listGlobalActions,
            'isPrintable'   => $this->isPrintable,
            'bulkActions' => $this->listBulkActions,
            'translationDomain' => $this->translationDomain,
            'pageTitle' => $this->pageTitle,
            'pagesLimit' => $limit,
            'pagesOffset' => $offset,
            'defaultDateFormat' => $this->defaultDateFormat,
            'isSearchable' => $this->isSearchable,
            'isExportToExcelEnabled' => false,
            'isClickableRow' => $this->isClickableRow,
            'isExportToExcelEnabled' => $this->isExportToExcelEnabled,
            'exportToExcelRoute' => $this->exportToExcelRoute,
            'clickableRowRouteName' => $this->clickableRowRouteName,
            'sort' => $paginationSort
        );
    }

    public function appendSearchtoQuery($query, $andX, $searchKey, $searchValue, $isExactSearch = false) {
        for ($i = 0; $i < count($searchKey); $i++) {
            if (in_array($searchKey[$i], $this->listSearchColumns)) {
                if (strpos($searchKey[$i], ".")) {
                    if($isExactSearch)
                        $andX->add($searchKey[$i] . " = :searchValue" . $i);
                    else
                        $andX->add($searchKey[$i] . " like :searchValue" . $i);
                } else {
                    if($isExactSearch)
                        $andX->add("e." . $searchKey[$i] . " = :searchValue" . $i);
                    else
                        $andX->add("e." . $searchKey[$i] . " like :searchValue" . $i);
                }

                if($isExactSearch)
                    $query->setParameter(':searchValue' . $i, $searchValue[$i]);
                else
                    $query->setParameter(':searchValue' . $i, '%' . $searchValue[$i] . '%');
            }
        }
    }

    /**
     * Mahmoud Mostafa <mahmoud.mostafa@ibtikar.net.sa>
     * @return array
     */
    public function getListRowDataParameters() {
        return array();
    }

    public function getListJsonData($request, $renderingParams)
    {

        if (array_key_exists('autocompelete', $renderingParams)) {
            return new JsonResponse($renderingParams['autocompelete']);
        }
        $entityObjects = array();

        foreach ($renderingParams['pagination'] as $result) {

            if(is_array($result))
                $entity= $result[0];
            else
                $entity = $result;

            $templateVars = ['entity' => $entity, 'list' => $renderingParams];

            foreach ($renderingParams['columnArray'] as $value) {
                if(method_exists($entity, 'getId')) {
                    $oneEntity['id'] = $entity->getId();
                }
                $oneEntity['rowData'] = array();
                foreach ($this->getListRowDataParameters() as $parameter => $getter) {
                    if (method_exists($entity, $getter)) {
                        $oneEntity['rowData']['data-' . $parameter] = $entity->$getter();
                    }
                }
                if ($value == 'checkBox') {
                    $oneEntity['checkBox'] = '';
                    if (count($this->listBulkActions)) {
                        $oneEntity['checkBox'] = $this->renderView('IbtikarShareEconomyDashboardDesignBundle:List:_listCheckBox.html.twig', $templateVars);
                    }
                    continue;
                }

                if ($value == 'actions') {
                    $oneEntity['actions'] = $this->renderView('IbtikarShareEconomyDashboardDesignBundle:List:_listActions.html.twig', $templateVars);
                    continue;
                }

                if ($value == 'isClickableRow') {
                    $oneEntity['isClickableRow'] = $this->clickableRowRouteName ? $this->generateUrl($this->clickableRowRouteName, array('id' => $entity->getId())) : $this->generateUrl(strtolower($this->className).'_details', array('id' => $entity->getId()));
                    continue;
                }

                $getfunction = isset($value[1]['method']) ? $value[1]['method'] : "get" . ucfirst($value[0]);

                if (isset($value[1]['index'])){
                    $index = $value[1]['index'];
                    $oneEntity[$value[0]] = $result[$index];
                }
                else if ($entity->$getfunction() instanceof \DateTime) {
                    $oneEntity[$value[0]] = $entity->$getfunction() ? $entity->$getfunction()->format($this->defaultDateFormat) : null;
                }
                else if(isset($value[1]['type']) && $value[1]['type'] == 'bool'){
                    $oneEntity[$value[0]] = $entity->$getfunction()?$this->get('translator')->trans('true'):$this->get('translator')->trans('false');
                }
                else if (isset($value[1]['type']) && $value[1]['type'] == 'image'){
                    $getfn = $value[1]['image'];
                    $getWebPath = $entity->$getfn()?$entity->$getfn():"bundles/ibtikarshareeconomydashboarddesign/images/profile.jpg";
                    $oneEntity[$value[0]] = '<div class="media-left media-middle">
                                            <a href="#"><img src="/'.$getWebPath.'" class="img-circle img-lg" alt=""></a>
                                        </div>';
                }
                else if (isset($value[1]['class']) && $value[1]['class'] == 'phoneNumberLtr'){
                    $oneEntity[$value[0]] = '<div class="phoneNumberLtr">'.$entity->$getfunction().'</div>';
                }
                else if (isset($value[1]['ishtml']) && $value[1]['ishtml']){
                    $fieldData = $entity->$getfunction();
                    $oneEntity[$value[0]] = $fieldData;
                }
                else if(isset($value[1]['isClickableField']) && $value[1]['isClickableField']){

                    $getSearchValue = $value[1]['searchValue'];
                    $clickUrl = $this->generateUrl($value[1]['routeName']).'?searchKey=["'.$value[1]['searchKey'].'"]&searchValue=["'.$entity->$getSearchValue().'"]';

                    $oneEntity[$value[0]] = '<div data-clickablefield='.$clickUrl.'>'.$entity->$getfunction().'</div>';
                }
                else {
                    $fieldData = $entity->$getfunction();

                    if (is_object($fieldData)) {
                        $oneEntity[$value[0]] = $fieldData->__toString();
                    } elseif (strlen($fieldData) > 50) {
                        $oneEntity[$value[0]] = mb_substr($fieldData, 0, 49);
                    } elseif (isset($value[1]['selectSearch'])) {
                        $oneEntity[$value[0]] = $this->get('translator')->trans($fieldData, array(), $this->translationDomain);
                    }
                    else{
                        $oneEntity[$value[0]] = $fieldData;
                    }
                }

                if(isset($value[1]['translate']) && $value[1]['translate'])
                      $oneEntity[$value[0]] = $this->get('translator')->trans($oneEntity[$value[0]], array(), $this->translationDomain);

            }

            $entityObjects[] = $oneEntity;
        }

        return new JsonResponse([
            'status'          => 'success',
            'data'            => $entityObjects,
            "draw"            => 0,
            'sEcho'           => 0,
            'columns'         => $renderingParams['columns'],
            "recordsTotal"    => $renderingParams['totalNumber'],
            "recordsFiltered" => $renderingParams['totalNumber']
        ]);
    }

    /**
     * Creates a form for actions like (delete, cancel, activate, deactivate...etc)
     *
     * @return \Symfony\Component\Form\Form The form
     */
    protected function createActionForm()
    {
        return $this->createFormBuilder()
                ->setMethod('POST')
                ->add('data', \Symfony\Component\Form\Extension\Core\Type\HiddenType::class)
                ->getForm();
    }

//    public function getColumnHeaderAndSort($request){
//        $this->configureListParameters($request);
//        $sortIndex = null;
//        $index = 0;
//        $prepareColumns = array();
//        if ($this->listViewOptions->hasBulkActions($this->calledClassName)) {
//            $prepareColumns = array(array('data' => 'id', 'name'=>'id','orderable' => false,'class'=>'text-center', 'title' => '<div class="form-group">'
//                            . '<label class="checkbox-inline"> <input type="checkbox" class="styled dev-checkbox-all"  >'
//                            . ' </label></div>'));
//            $index++;
//        }
//        foreach ($this->listViewOptions->getFields() as $name => $value) {
//            $column = array('data' => $name, 'orderable' => $value->isSortable,'class'=>'', 'title' => $this->trans($name, array(), $this->translationDomain), 'name' => $name);
//            $prepareColumns[] = $column;
//            if ($this->listViewOptions->getDefaultSortBy() == $name) {
//                $sortIndex = $index;
//            }
//            $index++;
//        }
//        if ($this->listViewOptions->hasActionsColumn($this->calledClassName)) {
//            $prepareColumns[] = array('data' => 'actions', 'orderable' => FALSE, 'name' => 'actions', 'title' => $this->trans('actions'));
//        }
//        if ($sortIndex) {
//            $sort = json_encode(array($sortIndex, $this->listViewOptions->getDefaultSortOrder()));
//        } else {
//            $sort = null;
//        }
//        return array('columnHeader' => $prepareColumns, 'sort' => $sort);
//    }

    function setPageTitle(){}

    /**
     * override this method to apply your one field search
     * should implement Ibtikar\ShareEconomyDashboardDesignBundle\Interfaces\OneInputSearchInterface
     *
     * @return null|Ibtikar\ShareEconomyDashboardDesignBundle\Interfaces\OneInputSearchInterface
     */
    protected function getListOneInputSearch()
    {
        return null;
    }

    /**
     * override this method to apply your filters
     *
     * @return array
     */
    protected function getListFilters()
    {
        return [];
    }

    /**
     * get auto-complete filter items
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getListAutoCompleteFilterItems(Request $request)
    {
        $output      = ['items' => []];
        $listFilters = $this->getListFilters();

        if (count($listFilters)) {
            $filterName = $request->query->get('filterName');
            $searchKey  = $request->query->get('searchKey');

            foreach ($listFilters as $filter) {
                if ($filterName == $filter->getName()) {
                    $output['items'] = $filter->getFilterList($searchKey);
                }
            }
        }

        return new JsonResponse($output);
    }

    /**
     *
     * @param Request $request
     * @param type $id
     * @return type
     * @author Sarah Mostafa <sarah.marzouk@ibtikar.net.sa>
     */
    public function editAction(Request $request, $id) {
        $em = $this->getDoctrine()->getManager();
        $entity = $em->getRepository($this->entityBundle.":".$this->className)->findOneBy(array('id'=>$id));
        $this->setEditOptions();
        if(!$this->formName)
            $this->formName = $this->className;

        $formType = $this->entityBundle."\\Form\\".$this->formName.'Type';
        if(!$entity){
            return $this->notExistsEntityAfterEdit();
        }
        $options = $this->getEditFormOptions();

        $locale = $request->getLocale();
        $request->setLocale('en'); //for the date not to be translated

        $form = $this->createForm($formType, $entity, $options);

        $prePostParameters = $this->prePostParametersEdit($entity);
        if ($request->getMethod() === 'POST') {
            $form->handleRequest($request);
            $request->setLocale($locale);
            if ($form->isValid()) {
                return $this->postValidEdit($request, $entity);
            }
            $em->refresh($entity);
        }

        $request->setLocale($locale);

        $params = array(
                'form' => $form->createView(),
                'entityId' => $id,
                'title' => $this->get('translator')->trans('Edit '.$this->className, array(), $this->translationDomain),
            );

        if ($this->get('templating')->exists($this->entityBundle.':Edit:'.strtolower($this->className).'.html.twig'))
            return $this->render($this->entityBundle.':Edit:'.strtolower($this->className).'.html.twig', array_merge ($params, $prePostParameters));
        else if ($this->get('templating')->exists($this->entityBundle . ':Edit:edit.html.twig'))
            return $this->render($this->entityBundle.':Edit:edit.html.twig', array_merge ($params, $prePostParameters));
        else
            return $this->render('IbtikarShareEconomyDashboardDesignBundle:Edit:edit.html.twig', array_merge ($params, $prePostParameters));
    }

    protected function notExistsEntityAfterEdit(){
        $this->addFlash("error", $this->get('translator')->trans("This action can't be completed"));
        return $this->redirect($this->generateUrl(strtolower($this->preFix . $this->className).'_list'));
    }

    protected function getEditFormOptions($options = array()){
        $options = array('translation_domain'=>$this->translationDomain);
        return $options;
    }

    protected function postValidEdit(Request $request, $entity){
        $em = $this->get('doctrine')->getManager();
        $em->flush();
        $this->addFlash("success", $this->get('translator')->trans("Successfully updated"));
        return $this->redirect($this->generateUrl(strtolower($this->preFix . $this->className).'_list'));
    }

    protected function setListParameters(){
        foreach ($this->listColumns as $key => $column) {
            if (count($column) > 1) {
                if (isset($column[1]['entity'])) {
                    if (isset($column[1]['sort'])) {
                        $this->listSearchColumns[] = $column[1]['sort'];
                    } else {
                        $this->listSearchColumns[] = $column[1]['entity'] . '.' . $column[0];
                    }
                } else if (isset($column[1]['selectOptionsList'])) {
                    $staticFuction = $column[1]['selectOptionsList'];
                    $this->listColumns[$key][1]['selectOptions'] = call_user_func(array($this->fullClassName, $column[1]['selectOptionsList']));
                    $this->listSearchColumns[] = $column[0];
                } else {
                    $this->listSearchColumns[] = $column[0];
                }
            } else {
                $this->listSearchColumns[] = $column[0];
            }
        }
    }

    protected function setEditOptions(){

    }

    protected function prePostParametersEdit($entity){
        return array('closeRedirection'=>$this->generateUrl(strtolower($this->preFix .$this->className) . '_list'));
    }

    /**
     *
     * @return type
     */
    public function getNavBarAdditionalLinks()
    {
        $return = '';
        $kernelBundles = $this->getParameter('kernel.bundles');

        if (count($kernelBundles)){
            foreach($kernelBundles as $bundleName => $bundlePath){
                if ($this->get('templating')->exists($bundleName . ':navBarLinks.html.twig')){
                    $return .= $this->renderView($bundleName . ':navBarLinks.html.twig');
                }
            }
        }

        return $return;
    }

    public function detailsAction(Request $request, $id)
    {
        $em = $this->get('doctrine')->getManager();
        $entity = $em->getRepository($this->entityBundle.":".$this->className)->findOneBy(array('id'=>$id));
        if(!$entity){
            return $this->notExistsEntityAfterEdit();
        }

        if ($this->get('templating')->exists($this->entityBundle.':Details:'.strtolower($this->className).'.html.twig'))
            $template = $this->entityBundle.':Details:'.strtolower($this->className).'.html.twig';
        else if ($this->get('templating')->exists($this->entityBundle.':Details:details.html.twig'))
            $template = $this->entityBundle.':Details:details.html.twig';
        else
            $template = 'IbtikarShareEconomyDashboardDesignBundle:Details:details.html.twig';

        return $this->render($template, array(
            'translationDomain' => $this->translationDomain,
            'title' => $this->get('translator')->trans(strtolower($this->className).'_details', array(), $this->translationDomain),
            'className' => $this->className,
            'detailsPageData' => $this->detailsPageData,
            'defaultDateFormat' => $this->defaultDateFormat,
            'entity' => $entity
            )
        );
    }
}
