<?php

namespace Ibtikar\ShareEconomyDashboardDesignBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\ORM\Query\Expr\Andx;

class DashboardController extends Controller
{

    protected $className = '';
    protected $preFix = '';

    protected $entityBundle = '';

    protected $listColumns = array();

    protected $listSearchColumns = array();

    protected $listActions = array();

    protected $formType = '';
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



    public function __construct()
    {

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
        $formType = $this->formType? $this->formType: $this->entityBundle."\\Form\\".$this->className.'Type';
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

        $templateVars = [
            'list'                    => $list,
            'action_form'             => $this->createActionForm()->createView(),
            'list_filters'            => $this->getListFilters(),
            'oneInputSearch'          => $this->getListOneInputSearch(),
            'listOneFieldSearchParam' => $this->listOneFieldSearchParam
        ];

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
            $andX = new Andx();
            $searchKey = json_decode($request->get('searchKey'));
            $searchValue = json_decode($request->get('searchValue'));

            if(count($searchKey) == count($searchValue)){
                for($i=0; $i<count($searchKey); $i++){
                    if(in_array($searchKey[$i], $this->listSearchColumns)){
                        if (strpos($searchKey[$i], ".")){
                            $andX->add($searchKey[$i]." like '%".$searchValue[$i]."%'");
                        }
                        else{
                            $andX->add("e.".$searchKey[$i]." like '%".$searchValue[$i]."%'");
                        }
                    }
                }
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
                if ($request->query->has($listFilter->getName()) && $request->query->get($listFilter->getName())) {
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
            if ($request->query->has($this->listOneFieldSearchParam) && $request->query->get($this->listOneFieldSearchParam)) {
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
        foreach($this->listColumns as $column){
            $datatableColumns[$datatableColumnsIndex] = array('data'=>$column[0]);
            if(count($column)>1){
                $datatableColumns[$datatableColumnsIndex]['orderable'] = array_key_exists('isSortable', $column[1])?$column[1]['isSortable']:true;
            }
            else{
                $datatableColumns[$datatableColumnsIndex]['orderable'] = true;
            }
            $columnArray[] = $column;
            if ((isset($column[1]['sort']) && $column[1]['sort'] == $sort) || (isset($column[1]['entity']) && $column[1]['entity'].'.'.$column[0] == $sort) || $sort == $column[0]) {
                $sortIndex = $datatableColumnsIndex;
            }
            $datatableColumnsIndex++;
        }
        if(count($this->listActions) || $this->isSearchable){
            $datatableColumns[$datatableColumnsIndex] = array('data'=>'actions', 'orderable'=>false);
            $columnArray[]='actions';
            $datatableColumnsIndex++;
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
            'pagination'  => $pagination,
            'columns'   => $this->listColumns,
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
            'sort' => $paginationSort
        );
    }

    public function getListJsonData($request, $renderingParams)
    {
        $entityObjects = array();

        foreach ($renderingParams['pagination'] as $entity) {
            $templateVars = ['entity' => $entity, 'list' => $renderingParams];

            foreach ($renderingParams['columnArray'] as $value) {
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

                $getfunction = isset($value[1]['method']) ? $value[1]['method'] : "get" . ucfirst($value[0]);

                if ($entity->$getfunction() instanceof \DateTime) {
                    $oneEntity[$value[0]] = $entity->$getfunction() ? $entity->$getfunction()->format($this->defaultDateFormat) : null;
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
        $className = $this->entityBundle."\\Entity\\".$this->className;
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
        $em->persist($entity);
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
                    $className = $this->entityBundle . "\\Entity\\" . $this->className;
                    $staticFuction = $column[1]['selectOptionsList'];
                    $this->listColumns[$key][1]['selectOptions'] = call_user_func(array($className, $column[1]['selectOptionsList']));
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
        return array();
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
}
