<?php

$action = !empty($_POST['action'])? $_POST['action'] : '';
$name = !empty($_POST['name'])? $_POST['name'] : '';
$description = !empty($_POST['description'])? $_POST['description'] : '';
$siteurl = !empty($_POST['siteurl'])? $_POST['siteurl'] : '';
$context_search = !empty($_POST['context_search'])? $_POST['context_search'] : '';
$context = !empty($_POST['context'])? $_POST['context'] : '';
$config = !empty($_POST['config'])? $_POST['config'] : '';


if(!empty($action) || !empty($_REQUEST['extra-action'])){
    require_once './../init_modx.php';
}

if(!is_object($obj) || !$obj->isAdmin()){
    echo '{"status":"error", "message":"Недостатньо прав!"}';
    exit();
}

if(!empty($_REQUEST['context'] && !empty($_REQUEST['delete-context']))){

    $obj->deleteContext($_REQUEST['context']);

    exit();

    /*
    $context = 'lv';
    $obj->updateBabelTVs($context,'web','ru', 'en');
    //$obj->updateBabelTVs($context,'create-ua','create-ru', 'create-en');
    */

    exit();
}

if( $action == 'search' && !empty($context_search) ){

    if(empty($obj->prepareContextName($context_search))){
        echo '{"status":"error", "message":"Введите название контекста"}';
        exit();
    }

    if(!$obj->searchLangContextByKey($context_search) && !$obj->searchComponentContextByKey($context_search)){
        $obj->addContext($context_search);
    }elseif($obj->searchLangContextByKey($context_search) && !$obj->searchComponentContextByKey($context_search)){
        echo '{"status":"error", "message":"Контексти вже існують"}';
        exit();
    }

    $context_obj = $obj->getComponentContext($context_search);
    if(is_object($context_obj)){
        $data = array(
            'status' => 'success',
            'name' => !empty($context_obj->name) ? $context_obj->name : '',
            'context' => !empty($context_obj->context) ? $context_obj->context : '',
            'description' => !empty($context_obj->description) ? $context_obj->description : '',
            'siteurl' => !empty($context_obj->siteurl) ? $context_obj->siteurl : ''
        );

        $statuses = array(
            'context',
            'context_settings',
            'media',
            'access',
            'user',
            'migx',
            'other'
        );
        foreach($statuses as $status) {
            $status_full = 'status_'.$status;
            $data['statuses'][$status] = !empty($context_obj->$status_full) ? $context_obj->$status_full : '0';
        }

        print_r(json_encode($data));
    }

}elseif( $action == 'update' && !empty($context) ){

    if($obj->searchComponentContextByKey($context)){
        if($obj->updateComponentContext($context,$name, $description, $siteurl)){
            echo '{"status":"success", "message":"Збережено!"}';
        }
    }else {
        echo '{"status":"error", "message":"Контекст не знайдений"}';
        exit();
    }

}elseif( $action == 'create' && !empty($config) && !empty($context) ){
        switch ($config){
            case 'context':
                ob_start();
                if($obj->createContext($context)){
                    ob_end_clean();
                    echo '{"status":"success", "message":"Контексти створені!", "next":"context_settings"}';
                }else{
                    ob_end_clean();
                    echo $obj->returnError();
                }
                break;
            case 'context_settings':
                ob_start();
                if($obj->createContextSettings($context)){
                    ob_end_clean();
                    echo '{"status":"success", "message":"Налаштування створені!", "next":"media"}';
                }else{
                    ob_end_clean();
                    echo $obj->returnError();
                }
                break;
            case 'media':
                if($obj->createMedia($context)){
                    echo '{"status":"success", "message":"Медіа джерела створені!", "next":"access"}';
                }else{
                    echo $obj->returnError();
                }
                break;
            case 'access':
                if($obj->createAccess($context)){
                    echo '{"status":"success", "message":"Налаштування доступу створені!", "next":"user"}';
                }else{
                    echo $obj->returnError();
                }
                break;
            case 'user':
                if($obj->createUser($context)){
                    echo '{"status":"success", "message":"Користувач створений!", "next":"migx"}';
                }else{
                    echo $obj->returnError();
                }
                break;
            case 'migx':
                ob_start();
                if($obj->createMigx($context)){
                    ob_end_clean();
                    echo '{"status":"success", "message":"Migx компоненти створені!", "next":"other"}';
                }else{
                    ob_end_clean();
                    echo $obj->returnError();
                }
                break;
            case 'other':
                if($obj->createOther($context)){
                    echo '{"status":"success", "message":"Налаштування виконані!", "next":""}';
                }else{
                    echo $obj->returnError();
                }
                break;
            default:
                echo '{"status":"error", "message":"Конфігурація не вірна"}';
        }
}else{
    echo '{"status":"error", "message":"Невірні данні"}';
}

?>