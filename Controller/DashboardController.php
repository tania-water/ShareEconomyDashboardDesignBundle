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

    protected $entityBundle = '';

    protected $listColumns = array();

    protected $listActions = array();

    protected $listGlobalActions = array();

    protected $listBulkActions = array();

    protected $defaultSort = array('column' => 'createdAt', 'sort' => 'desc');

    protected $translationDomain = '';

    protected $pageTitle = '';

    protected $pagesLimit = 20;

    protected $pagesOffset = 0;

    protected  $defaultDateFormat = 'd/m/Y';

    protected  $isSearchable = true;

    /**
     * Dashboard home page
     * @author Mahmoud Mostafa <mahmoud.mostafa@ibtikar.net.sa>
     * @return Response
     */
    public function homeAction()
    {
        return $this->render('IbtikarShareEconomyDashboardDesignBundle:Dashboard:home.html.twig');
    }

    private function getEntityPerId($entityId){
        $em = $this->getDoctrine()->getManager();
        $entity = $em->getRepository($this->entityBundle.":".$this->className)->findOneBy(array('id'=>$entityId));
        return $entity;
    }

    public function deleteEntity($entity){
        $em->remove($entity);
        $this->getFlashBag("success", "Successfully deleted");
//        return $this->;
    }

    public function deleteAction(Request $request){
        $em = $this->getDoctrine()->getManager();
        $entityId = $request->get('entityId');
        $entity = $this->getEntityPerId($entityId);
        if($entity){
            return $this->deleteEntity($entity);
        }
    }

    /**
     * @author Moemen Hussein <moemen.hussein@ibtikar.net.sa>
     */
    public function listAction(Request $request){
        $list = $this->getListParameters($request);
        if($request->isXmlHttpRequest()){
            return $this->getListJsonData($request,$list);
        }
        if ($this->get('templating')->exists($this->entityBundle.':List:'.strtolower($this->className).'.html.twig'))
            return $this->render($this->entityBundle.':List:'.strtolower($this->className).'.html.twig', array('list' =>  $list));

        if ($this->get('templating')->exists($this->entityBundle.':List:list.html.twig'))
            return $this->render($this->entityBundle.':List:list.html.twig', array('list' =>  $list));

        return $this->render('IbtikarShareEconomyDashboardDesignBundle:List:list.html.twig', array('list' =>  $list));
    }
    public function getListQuery(){
        $em = $this->getDoctrine()->getManager();
        return $em->createQueryBuilder()
                ->select('e')
                ->from($this->entityBundle.':'.$this->className, 'e');
    }
    protected function getListParameters($request){
        $em = $this->getDoctrine()->getManager();
        $query = $this->getListQuery();
        $this->setPageTitle();
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
            for($i=0; $i<count($searchKey); $i++){
                if (strpos($searchKey[$i], "."))
                    $andX->add($searchKey[$i]." like '%".$searchValue[$i]."%'");
                else
                    $andX->add("e.".$searchKey[$i]." like '%".$searchValue[$i]."%'");
            }
            $query = $query->andWhere($query->expr()->andX($andX));
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
            'totalNumber' => $totalNumber,
            'pagination'  => $pagination,
            'columns'   => $this->listColumns,
            'datatableColumns'   => json_encode($datatableColumns),
            'columnArray'   => $columnArray,
            'actions'   => $this->listActions,
            'globalActions'   => $this->listGlobalActions,
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

    public function getListJsonData($request, $renderingParams){
        $entityObjects = array();
        foreach ($renderingParams['pagination'] as $entity) {
            foreach ($renderingParams['columnArray'] as $value) {
                if ($value == 'checkBox') {
                    $oneEntity['checkBox'] = '';
                    if(count($this->listBulkActions)){
                        $oneEntity['checkBox'] = '<div class="form-group">
                                                <label class="checkbox-inline">
                                                    <input type="checkbox" class="styled" checked="checked">
                                                </label>
                                            </div>';
                    }
                    continue;
                }
                if ($value == 'actions') {
                    $actionTd = '';
                    foreach ($this->listActions as $action) {
                        if ($action == 'edit') {
                            $actionTd.= '<a class="dev-td-btn btn btn-defualt" href="Role-add.php" data-popup="tooltip" title="تعديل" data-placement="bottom" ><i class="icon-pencil"></i></a>';
                        }elseif($action == 'delete'){
                            $actionTd.= '<button tabindex="0" class="dev-td-btn btn btn-defualt dev-delete-btn" data-toggle="popover" data-trigger="focus" title="انت علي وشك حذف comments هل ترغب في حذفه  "><i class="icon-trash"></i></button>';
                        }elseif($action == 'activation'){
                            $actionTd.= '<a tabindex="0" class="dev-td-btn btn btn-defualt" role="button" data-toggle="popover"  data-popup="popover" data-trigger="focus" title="  هل ترغب في ايقاف الموظف  " data-html="true"
                               data-html="true" data-content=\'
                               <button type="button" class="btn btn-danger">نعم</button>
                               <button type="button" class="btn btn-defualt">الغاء</button>
                               \'> <i class="icon-user-check"></i></a>';
                        }
                    }
                    $oneEntity['actions'] = $actionTd;
                    continue;
                }


                if(isset($value[1]['method']))
                    $getfunction = $value[1]['method'];
                else
                    $getfunction = "get" . ucfirst($value[0]);

                if ($entity->$getfunction() instanceof \DateTime) {
                    $oneEntity[$value[0]] = $entity->$getfunction() ? $entity->$getfunction()->format($this->defaultDateFormat) : null;
                } else {
                    $fieldData=$entity->$getfunction();
                    if(is_object($fieldData))
                        $oneEntity[$value[0]] = $fieldData->__toString();
                    else if(strlen($fieldData)> 50)
                        $oneEntity[$value[0]] = substr($fieldData, 0, 49);
                    else
                        $oneEntity[$value[0]] = $fieldData;
                }
            }
            $entityObjects[] = $oneEntity;
        }
//        $rowsHeader=$this->getColumnHeaderAndSort($request);
        return new JsonResponse(array('status' => 'success','data' => $entityObjects, "draw" => 0, 'sEcho' => 0,'columns'=>$renderingParams['columns'],
            "recordsTotal" => $renderingParams['totalNumber'],
            "recordsFiltered" => $renderingParams['totalNumber']));
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

}
