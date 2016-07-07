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
    public $params = [
        'filter'=>true,
        'columns'=>[],
        'criteria'=>[],
    ];

    public $dataProvider;


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
    static public function init($controller, $dataProvider, $params=array()) {
        return new self($controller, $dataProvider, $params);
    }

    public function __construct($controller, $dataProvider, $params=null) {
        $this->params=array_merge($this->params,$params);

        $this->criteria=$this->params['criteria'];
        $this->reset = $controller->url();
        $this->dataProvider = $dataProvider;
        $this->controller = $controller;
        $this->columns = $this->getColumns();
        $this->data = $this->getData();
        $this->pagination =  $this->dataProvider->getPagination();

    }


    public function getColumns() {
        $columns = [];
        if(empty($this->params['columns'])) {
            $cols = $this->dataProvider->model->alias();
            foreach($cols as $key => $value) {
                $tmp['name'] = $key;
                $tmp['alias'] = $value;
                $tmp['filter'] = true;

                $columns[] = $tmp;
            }

            $columns[] = array(
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
        } else {
            foreach($this->params['columns'] as $value) {
                $column = [];
                $column['filter'] = true;
                if(isset($value['value'])) {
                    $column['value'] = $value['value'];
                }
                if (isset($value['buttons'])) {
                    $column['name'] = '';
                    $column['type'] = 'buttons';
                    $column['buttons'] = $value['buttons'];
                    $column['alias'] = $value['alias'];
                }
                if (!is_array($value)) {
                    $column['name'] = $value;
                } else if( isset($value['name']) ) {
                    $column['name'] = $value['name'];
                    if(isset($value['filter'])) {
                        $column['filter'] = $value['filter'];
                    } 
                }

                if (!isset($value['alias'])) {
                    // debug($this->dataProvider);die;
                    if($this->dataProvider instanceof DataProvider && isset($this->dataProvider->model->alias()[$column['name']]))
                        $column['alias'] = $this->dataProvider->model->alias()[$column['name']];
                    else
                        $column['alias'] = $column['name'];
                } else {
                    $column['alias'] = '';
                }

                $column['htmlOptions']=$this->_getOptions(isset($value['options'])?$value['options']:null);
                $columns[] = $column;
            }
        }

        return $columns;
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

    public function getData() {
        $data = $this->dataProvider->getData();
        unset($value);
        foreach($data as &$value) {
            foreach($this->columns as $column) {
                // 自定义value
                if(isset($column['value'])) {
                    $value->$column['name'] = $this->evaluateExpression($column['value'],array('data'=>$value));
                } elseif(isset($column['buttons']) && is_array($column['buttons'])) {
                    $value->{'__BUTTONS_HTML__'} = $this->_getButtons($column['buttons'],$value);
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
    private function _getButtons($buttons, $row) {
        $html = '';
        foreach($buttons as $value) {
            $tmp='<a  href="';
            if(isset($value['url'])) {
                $tmp .= $this->evaluateExpression($value['url'], array('data'=>$row));
            } else {
                $value['options']['class']='gridview-'.$value['action'];
                $tmp.=url().$this->controller->id.'/'.$value['action'].'?id='.$row['id'].'&from='.urlencode($_SERVER['REQUEST_URI']);
            }

            if (isset($value['options']) && is_array($value['options']) && !empty($value['options'])) {
                $tmp.=$this->_getOptions($value['options']);
            }
            $tmp.='">'.$value['name'].'</a>&nbsp;&nbsp;';

            $html.=$tmp;
        }

        return $html;
    }

    /**
     * get item option html code
     * @param $options array html options
     * @return string the html code of options
     */
    private function _getOptions($options) {
        $optionsHtml='';
        if (!empty($options)) {
            if(is_array($options)) {
                foreach($options as $key => $value) {
                    $optionsHtml.=' '.$key.'="'.$value.'"';
                }
            } else {
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
    public function evaluateExpression($_expression_, $_data_) {
        if (is_string($_expression_)) {
            extract($_data_);
            return eval('return '.$_expression_.';');
        } else {
            $_data_[]=$this;
            return call_user_func_array($_expression_, $_data_);
        }
    }
}
