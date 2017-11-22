<?php 

/**
 * 
 * $params['template']  模板地址，如果不传，默认返回
 * $params['className']  容器css class 名称，默认为page
 * 
 */

function smarty_function_page($params, $smarty)
{
    if (empty($params['page'])) {
        trigger_error('Widget is missing page !', E_USER_ERROR);
    }

    $page = $params['page'];

    if(empty($params['template'])) {
        $html = '<ul class="'.$page->className.'">
        <li>
            <a href="?'.$page->pageVar.'='.$page->prePage.'" aria-label="Previous">
            <span aria-hidden="true">&laquo;</span>
            </a>
        </li>';

        if($page->currentPage >= $page->rangePage*2) {
            $html .= '
            <li><a href="'.$page->preHref.$page->pageVar.'=1">1</a></li>
            <li><a href="'.$page->preHref.$page->pageVar.'=1">...</a></li>
            ';
        }
        
        for($p=$page->startPage;  $p <= $page->endPage; $p++) {
            $html .= '<li';
            if($p == $page->currentPage) {
                $html .= ' class="active"';
            }
            $html .= '><a href="'.$page->preHref.$page->pageVar.'='.$p.'">'.$p.'</a></li>';
        }

        if($page->currentPage <= ($page->pageCount - 10)) {
            $html .= '
            <li><a href="'.$page->preHref.$page->pageVar.'='.$page->pageCount.'">...</a></li>
            <li><a href="'.$page->preHref.$page->pageVar.'='.$page->pageCount.'">'.$page->pageCount.'</a></li>
            ';
        }

        $html .= '
        <li>
            <a href="?'.$page->pageVar.'='.$page->nextPage.'" aria-label="Next">
            <span aria-hidden="true">&raquo;</span>
            </a>
        </li>
        ';

        echo $html;
    } else {
        $plugin = $smarty->createTemplate($template);

        if (!empty($params['service'])) {
            $service = $params['service'];
            $class   = \Li\App::app()->namespace.'\\core\service\\'.ucfirst($service);

            if (!class_exists($class)) {
                trigger_error('Widget is missing service ' . $class, E_USER_ERROR);
            }

            $function = 'getData';
            if (!empty($params['function'])) {
                $function = $params['function'];
            }

            if (!method_exists($class::service(), $function)) {
                trigger_error('Widget is missing function "' . $function . '" of service "'. $service . '"', E_USER_ERROR);
            }

            if (empty($params['params'])) {
                $data = $class::service()->$function();
            } else {
                $data = $class::service()->$function($params['params']);
            }


            $plugin->assign($params['name'], $data);
        }

        $plugin->display();
    }

}
