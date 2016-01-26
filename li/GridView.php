<?php
namespace Li;
// how to use
// $gridview = GridView::init($this, $model, 
//     array(
//         'columns'=>array(
//             array(
//                 'name'=>'id',
//                 'alias'=>'id',
//                 'filter'=>true,
//             ),
//             array(
//                 'name'=>'created',
//                 'alias'=>'created',
//                 'filter'=>array(
//                     '1'=>1,
//                     '2'=>2),
//                 'value'=>'date("Y-m-d",$data["created"])'
//             ),
//             array(
//                 'name'=>'updated',
//                 'alias'=>'updated',
//                 'filter'=>true,
//                 'options'=>array(
//                     'class'=>'gridview-datepicker',
//                 ),
//                 'value'=>'date("Y-m-d",$data["updated"])'
//             ),
//             array(
//                 'alias'=>'操作',
//                 'buttons'=>array(
//                     array(
//                         'name'=>'编辑',
//                         'action'=>'update',
//                     ),
//                     array(
//                         'name'=>'删除',
//                         'action'=>'delete',
//                     ),
//                     array(
//                         'name'=>'test',
//                         'action'=>'test',
//                         'options'=>array(
//                             // 'class'=>'btn btn-default btn-xm',
//                             'style'=>'color:red;',
//                         ),
//                     ),
//                 )
//             ),
//         ),
//         'criteria'=>array(
//             'pageSize'=>2,
//             'order'=>'id desc'
//         )
//     )
// );

class GridView {
    public $controller;
    public $model;
    public $columns;
    public $criteria;

    public $pagination;
    // public $order;
    public $reset;
    public $params=array(
        'filter'=>true,
        'columns'=>array(),
        'criteria'=>array(),
    );


    // 全局控制筛选
    public $filter=true;

    /**
     * init gridview
     * @param $controller mixed current controller instance
     * @param $model mixed current model instance
     * @param $params mixed gridview params
     *
     * @return mixed the gridview
     */
    static public function init($controller, $model, $params=array())
    {
        return new self($controller, $model, $params);
    }

    public function __construct($controller, $model, $params=null)
    {
        $this->params=array_merge($this->params,$params);

        $this->reset=$controller->url();
        $this->controller = $controller;
        $this->model = $model;
        $this->pagination =  new Pagination($this->model,$this->params['criteria']);

        if(empty($this->params['columns']))
        {
            $cols = $model->getColumns();
            foreach($cols as $value)
            {
                $tmp['name'] = $value['Field'];
                $tmp['alias'] = (isset($model->alias()[$value['Field']])) ? $model->alias()[$value['Field']] : $value['Field'];
                $tmp['filter'] = true;

                $this->params['columns'][] = $tmp;
                unset($tmp);
            }
            $this->params['columns'][] = array(
                'alias'=>'操作',
                'buttons'=>array(
                    array(
                        'name'=>'编辑',
                        'action'=>'update',
                    ),
                    array(
                        'name'=>'删除',
                        'action'=>'delete',
                        'htmlOptions'=>array(
                            'class'=>'gridview-delete',
                        ),
                    ),
                )
            );
        }
        else
        {
            unset($value);
            foreach($this->params['columns'] as &$value)
            {
                if(!isset($value['alias']))
                {
                    if(isset($model->alias()[$value['name']]))
                        $value['alias'] = $model->alias()[$value['name']];
                    else
                        $value['alias'] = $value['name'];
                }

                $value['htmlOptions']=$this->_getOptions(isset($value['options'])?$value['options']:null);
                if(!isset($value['buttons']) && !isset($value['filter']))
                {
                    $value['filter']=true;
                }
            }
        }

        $this->columns = $this->params['columns'];
        $this->data = $this->getData();
    }

    /**
     * 获得criteria并且合并已知条件
     * @param $criteria array 
     * @return array
     */
    // private function _getCriteria($criteria=array())
    // {
    //     if(!empty($_GET))
    //     {
    //         if(empty($criteria))
    //         {
    //             foreach($_GET as $key=>$value)
    //             {
    //                 if(!empty($value) && $key != $this->pagination->pageVar)
    //                 {
    //                     $criteria['condition'][$key]=$value;
    //                 }
    //             }
    //         }
    //         else
    //         {
    //             foreach($_GET as $key=>$value)
    //             {
    //                 if(!empty($value) && $key != $this->pagination->pageVar && !isset($criteria['condition'][$key]))
    //                 {
    //                     $criteria['condition'][$key]=$value;
    //                 }
    //             }            
    //         }
    //     }

    //     return $criteria;
    // }

    /**
     * get Pagination
     * @return mixed the Pagination instance
     */
    // public function getPagination()
    // {
    //     return new Pagination($this->model,$this->criteria);
    // }

    /**
     * get data
     * @return array the gridview data
     */

    public function getData()
    {
        if(empty($this->criteria['order']))
        {
            $this->criteria['order'] = $this->model->pk.' DESC';
        }

        $this->criteria['limit'] = ($this->pagination->currentPage-1)*$this->pagination->pageSize.','.$this->pagination->pageSize;


        list($data,$criteria) = $this->model->search($this->criteria);
        $this->pagination->init($criteria);

        unset($value);
        foreach($data as &$value)
        {
            foreach($this->columns as $column)
            {
                if(isset($column['value']))
                {
                    $value[$column['name']] = $this->evaluateExpression($column['value'],array('data'=>$value));
                }
                elseif(isset($column['buttons']) && is_array($column['buttons']))
                {
                    $value['__BUTTONS_HTML__'] = $this->_getButtons($column['buttons'],$value);
                }
            }
        }


        return $data;
    }

    /**
     * get button html code
     * @param $buttons array the button array
     * @param $row array current row data
     * @return string the html code of button
     */
    private function _getButtons($buttons,$row)
    {
        $html = '';
        foreach($buttons as $value)
        {
            $value['options']['class']='gridview-'.$value['action'];
            $tmp='<a  href="';

            $tmp.=url().$this->controller->id.'/'.$value['action'].'?id='.$row['id'].'&from='.urlencode($_SERVER['REQUEST_URI']).'"';

            if(is_array($value['options']) && !empty($value['options']))
            {
                $tmp.=$this->_getOptions($value['options']);
            }
            $tmp.='>'.$value['name'].'</a>&nbsp;&nbsp;';

            $html.=$tmp;
        }

        return $html;
    }

    /**
     * get item option html code
     * @param $options array html options
     * @return string the html code of options
     */
    private function _getOptions($options)
    {
        $optionsHtml='';
        if(!empty($options))
        {
            if(is_array($options))
            {
                foreach($options as $key => $value)
                {
                    $optionsHtml.=' '.$key.'="'.$value.'"';
                }
            }
            else
            {
                $optionsHtml=' class="'.$options.'"';
            }
        }
        return $optionsHtml;
    }

    /**
     * Evaluates a PHP expression.
     *
     * @param mixed $_expression_ a PHP expression or PHP callback to be evaluated.
     * @param array $_data_ additional parameters to be passed to the above expression/callback.
     * @return mixed the expression result
     */
    public function evaluateExpression($_expression_,$_data_)
    {
        if(is_string($_expression_))
        {
            extract($_data_);
            return eval('return '.$_expression_.';');
        }
        else
        {
            $_data_[]=$this;
            return call_user_func_array($_expression_, $_data_);
        }
    }
}
