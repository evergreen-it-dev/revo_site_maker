<?php

class CreateSiteFunctions {
    /**
     * @access public
     * @var modX A reference to the modX object.
     */
    public $modx = null;

    public $package_name = null;
    public $package_class = null;
    public $packagepath = null;

    public $message = null;

    public $prefix = null;

    public $langs = array();


    /**
     * Constructor.
     */
    function __construct(modX & $modx, array $config = array()) {

        $this->modx = &$modx;

        $this->langs = array(
            'ua' => 'UA',
            'ru' => 'RU',
            'en' => 'EN'
        );

        $this->packageName = 'create_site';
        $this->packageClass = 'CreateSite';
        $this->packagepath = $this->modx->getOption('core_path') . 'components/' . $this->packageName . '/';
        $modelpath = $this->packagepath . 'model/';
        $prefix = null;
        $this->modx->addPackage($this->packageName, $modelpath, $prefix);

        $this->prefix = $modx->config['table_prefix'];
    }

    //sql
    public function sqlArray($sql){

        $query = $this->executeSql($sql);
        $result = $query->fetchAll(PDO::FETCH_ASSOC);

        return $result;
    }

    public function sqlOne($sql){

        $query = $this->executeSql($sql);
        $result = $query->fetchAll(PDO::FETCH_ASSOC);
        $result = $result[0];

        return $result;
    }

    public function executeSql($sql){
        $modx = &$this->modx;

        $query = $modx->prepare($sql);
        $query->execute();

        return $query;
    }
    //sql

    public function searchContextByKey($context){

        $context = $this->prepareContextName($context);
        $sql = "SELECT * FROM `modx_context` WHERE `key` =  '".$context."'";
        $result = count($this->sqlArray($sql));

        if(!empty($result)){
            return true;
        }else{
            $this->message = "Контекст " . $context . " не знайдено";
            return false;
        }

    }
    public function searchLangContextByKey($context){

        $context = $this->prepareContextName($context);
        $sql = "SELECT * FROM `modx_context` WHERE `key` IN ('".$context."-en', '".$context."-ua', '".$context."-ru')";
        $result = count($this->sqlArray($sql));

        if(!empty($result)){
            return true;
        }else{
            return false;
        }

    }

    public function searchComponentContextByKey($context){

        $context = $this->prepareContextName($context);
        $sql = "SELECT * FROM `modx_create_site` WHERE `context` = '" . $context . "'";
        $result = count($this->sqlArray($sql));

        if(!empty($result)){
            return true;
        }else{
            return false;
        }

    }

    public function prepareContextName($context){
        $context = strtolower(preg_replace('/[^a-z\-]/i', '', $context));
        return $context;
    }

    public function addContext($context){
        $modx = &$this->modx;

        $context = $this->prepareContextName($context);
        if(!$this->searchLangContextByKey($context) && !$this->searchComponentContextByKey($context)){
            $el = $modx->newObject($this->packageClass);
            $el->set('context',$context);
            $el->save();

            return true;
        }
        else{
            return false;
        }
    }

    public function getComponentContext($context){
        $modx = &$this->modx;

        $context = $this->prepareContextName($context);

        $c = $modx->newQuery($this->packageClass);
        $c->where(array('context' => $context));


        $item = $modx->getObject($this->packageClass, $c);

        if(!empty($item)){
            return $item;
        }else{
            return false;
        }
    }

    public function updateComponentContext($context, $name='', $description='', $siteurl=''){

        if($item = $this->getComponentContext($context)){
            $item->set('name', $name);
            $item->set('description', $description);
            $item->set('siteurl', $siteurl);
            $item->save();
            return true;
        }else{
            return false;
        }

    }

    public function createContext($context){
        $modx = &$this->modx;

        if($this->allowCreate($context, 'context')) {

            $item = $this->getComponentContext($context);

            foreach ($this->langs as $k => $v) {

                switch ($k) {
                    case 'ua':
                        $system_context = 'create-ua';
                        //$system_context = 'web';
                        break;
                    case 'ru':
                        $system_context = 'create-ru';
                        //$system_context = 'ru';
                        break;
                    case 'en':
                        $system_context = 'create-en';
                        //$system_context = 'en';
                        break;
                    default:
                        $this->message = '{"status":"error", "message":"Конфігурація не вірна"}';
                        return false;
                }

                if ($this->searchContextByKey($system_context)) {

                    //копирование контекста
                    $data = array(
                        'key' => $system_context,
                        'newkey' => $context . "-" . $k,
                        'preserve_alias' => 'on',
                        'preserve_menuindex' => 'on',
                        'preserve_resources' => 'on'
                    );
                    $response = $modx->runProcessor('context/duplicate', $data);

                    if ($response->isError()) {
                        $this->message = "Помилка при дублюванні контексту (".$system_context.'-'.$context . "-" . $k. ' - '.$response->getMessage().")";
                        return false;
                    }
                    //копирование контекста

                } else {
                    $this->message = "Неможливо знайти системний котекст";
                    return false;
                }

            }

            $item->set('status_context', 1);
            $item->save();
            return true;

        }

        return false;

    }
    public function createContextSettings($context){
        $modx = &$this->modx;

        if($this->allowCreate($context, 'context_settings')) {

            $item = $this->getComponentContext($context);

            foreach ($this->langs as $k => $v) {

                    //Изменение имени и описания контекста
                    $data = array(
                        'key' => $context . "-" . $k,
                        'name' => $item->name . ' ('.$v.')',
                        'description' => $item->description,
                        'settings' => ''
                    );
                    $response = $modx->runProcessor('context/update', $data);

                    if ($response->isError()) {
                        $this->message = "Помилка при спробі перейменування".$response->getMessage();
                        return false;
                    }
                    //Изменение имени и описания контекста


                    //Изменение параметров
                    //получение служебных идентификаторов
                    $res = $this->sqlOne("SELECT id FROM `modx_site_content` WHERE `template` = '2' and context_key = '".$context . "-" . $k."'");
                    if(!empty($res)) {
                        $mainId =  $res['id'];
                    }
                    $res = $this->sqlOne("SELECT id FROM `modx_site_content` WHERE `template` = '108' and context_key = '".$context . "-" . $k."'");
                    if(!empty($res)) {
                        $errorId =  $res['id'];
                    }
                    $res = $this->sqlOne("SELECT id FROM `modx_site_content` WHERE `template` = '80' and context_key = '".$context . "-" . $k."'");
                    if(!empty($res)) {
                        $newsId =  $res['id'];
                    }else{
                        $newsId =  '';
                    }
                    $res = $this->sqlOne("SELECT id FROM `modx_site_content` WHERE `template` = '175' and context_key = '".$context . "-" . $k."'");
                    if(!empty($res)) {
                        $blogsId =  $res['id'];
                    }else{
                        $blogsId =  '';
                    }


                    $res = $this->sqlOne("SELECT id FROM `modx_site_content` WHERE `template` = '103' and context_key = '".$context . "-" . $k."'");
                    if(!empty($res)) {
                        $res_header_texts =  $res['id'];
                    }else{
                        $res_header_texts =  '';
                    }

                    $res = $this->sqlOne("SELECT id FROM `modx_site_content` WHERE `template` = '161' and context_key = '".$context . "-" . $k."'");
                    if(!empty($res)) {
                        $res_menu =  $res['id'];
                    }else{
                        $res_menu =  '';
                    }

                    $res = $this->sqlOne("SELECT id FROM `modx_site_content` WHERE `template` = '97' and context_key = '".$context . "-" . $k."'");
                    if(!empty($res)) {
                        $res_social_id =  $res['id'];
                    }else{
                        $res_social_id =  '';
                    }

                    $res = $this->sqlOne("SELECT id FROM `modx_site_content` WHERE `template` = '118' and context_key = '".$context . "-" . $k."'");
                    if(!empty($res)) {
                        $res_text_vars =  $res['id'];
                    }else{
                        $res_text_vars =  '';
                    }

                    //получение служебных идентификаторов
                    if($k != 'ua'){
                        $conf_site_url = str_replace(array('//',':/'), array('/','://'), $item->siteurl.'/'.$k.'/');
                    }else{
                        $conf_site_url = str_replace(array('//',':/'), array('/','://'), $item->siteurl.'/');
                    }

                    $configs = array(
                        array(
                            'key' => 'news_page_id',
                            'name' => 'Идентифікатор сторінки з новинами',
                            'value' => $newsId,
                            'area' => 'Новини'
                        ),
                        array(
                            'key' => 'news_migx_table',
                            'name' => 'Назва migx таблиці',
                            'value' => 'news_'.$context,
                            'area' => 'Новини'
                        ),
                        array(
                            'key' => 'news_categories_migx_table',
                            'name' => 'Назва migx таблиці категорій',
                            'value' => 'news_categories_'.$context,
                            'area' => 'Категорії новин'
                        ),
                        array(
                            'key' => 'blogs_page_id',
                            'name' => 'Идентифікатор сторінки з матеріалами',
                            'value' => $blogsId,
                            'area' => 'Новини'
                        ),
                        array(
                            'key' => 'blogs_migx_table',
                            'name' => 'Назва migx таблиці матеріалів',
                            'value' => 'blogs_'.$context,
                            'area' => 'Матеріали'
                        ),
                        array(
                            'key' => 'blogs_categories_migx_table',
                            'name' => 'Назва migx таблиці категорій матеріалів',
                            'value' => 'blogs_categories_'.$context,
                            'area' => 'Категорії матеріалів'
                        ),
                        array(
                            'key' => 'error_page',
                            'name' => 'setting_error_page',
                            'value' => $errorId,
                            'area' => 'site'
                        ),
                        array(
                            'key' => 'site_start',
                            'name' => 'site_start',
                            'value' => $mainId,
                            'area' => 'site'
                        ),
                        array(
                            'key' => 'res_text_vars',
                            'name' => 'Ідентифікатор для текстових констант',
                            'value' => $res_text_vars,
                            'area' => 'site'
                        ),
                        array(
                            'key' => 'res_social_id',
                            'name' => 'Ідентифікатор ресурсу соціальних посилань',
                            'value' => $res_social_id,
                            'area' => 'site'
                        ),
                        array(
                            'key' => 'res_menu',
                            'name' => 'Ідентифікатор для меню',
                            'value' => $res_menu,
                            'area' => 'site'
                        ),
                        array(
                            'key' => 'res_header_texts',
                            'name' => 'Ідентифікатор текстів футера\хедера',
                            'value' => $res_header_texts,
                            'area' => 'site'
                        ),
                        array(
                            'key' => 'site_url',
                            'name' => 'site_url',
                            'value' => $conf_site_url,
                            'area' => 'site'
                        )
                    );

                    foreach ($configs as $c) {
                        $data_new = array(
                            'context_key' => $context . "-" . $k,
                            'key' => $c['key'],
                            'value' => $c['value'],
                            'xtype' => 'textfield',
                            'namespace' => 'core',
                            'area' => $c['area'],
                            'editedon' => '2017-11-08 00:00:00'
                        );

                        $Setting = $modx->getObject('modContextSetting', array(
                            'key' => $c['key'],
                            'context_key' => $context . "-" . $k
                        ));

                        if(is_object($Setting)) {
                            if ($Setting->remove() == false) {
                                $this->message = "Error while deleting";
                                return false;
                            }
                        }
                        $newSetting = $modx->newObject('modContextSetting');
                        $newSetting->fromArray($data_new,'',true,true);
                        $newSetting->set('context_key', $context . "-" . $k);
                        if(!$newSetting->save()){
                            $this->message = "Помилка при спробі створення конфігурації котекста";
                            return false;
                        }
                    }
                    //Изменение параметров


            }

            $item->set('status_context_settings', 1);
            $item->save();
            return true;

        }

        return false;

    }

    public function createMedia($context){

        if($this->allowCreate($context, 'media')){

            $item = $this->getComponentContext($context);

            $media_name = 'Content'.ucfirst($item->context);

            $res = $this->sqlOne("SELECT name FROM `modx_media_sources` WHERE `name` = '".$media_name."';");
            if(!empty($res)) {
                $media_name .=  time();
            }
            $item->set('media_name', $media_name);
            $item->save();

            $properties = Array (
                'basePath' =>
                    Array (
                        'name' => 'basePath',
                        'desc' => 'prop_file.basePath_desc',
                        'type' => 'textfield',
                        'options' => Array (),
                        'value' => '/assets/sites/'.$item->context.'/',
                        'lexicon' => 'core:source',
                    ),
                'baseUrl' => Array (
                    'name' => 'baseUrl',
                    'desc' => 'prop_file.baseUrl_desc',
                    'type' => 'textfield',
                    'options' => Array (),
                    'value' => 'assets/sites/'.$item->context.'/',
                    'lexicon' => 'core:source'
                ),
                'imageExtensions' => Array (
                    'name' => 'imageExtensions',
                    'desc' => 'prop_file.imageExtensions_desc',
                    'type' => 'textfield',
                    'options' => Array (),
                    'value' => 'jpg,jpeg,png,gif,svg',
                    'lexicon' => 'core:source'
                )
            );

            $properties = serialize($properties);

            //create media
            if( $media_id = $this->getTableAI('modx_media_sources') ) {
                $sql = "INSERT INTO `modx_media_sources` (`id`, `name`, `description`, `class_key`, `properties`, `is_stream`) VALUES ('".$media_id."', '" . $media_name . "', '', 'sources.modFileMediaSource', '" . $properties . "', '1');";
                $this->executeSql($sql);

                foreach($this->langs as $k => $v) {
                    $sql = "INSERT INTO `modx_media_sources_elements` (`source`, `object_class`, `object`, `context_key`) VALUES ('".$media_id."', 'modTemplateVar', '59', '".$item->context."-".$k."');";
                    $this->executeSql($sql);
                }

                $sql = "INSERT INTO `modx_media_sources_elements` (`source`, `object_class`, `object`, `context_key`) VALUES ('".$media_id."', 'modTemplateVar', '59', '".$item->context."-".$k."');";
                //$this->executeSql($sql);

                $item->set('status_media', 1);
                $item->save();

                return true;
            }else{
                return false;
            }

        }

        return false;

    }

    public function getMediaSrc($item){
        $media_src_res = $this->sqlOne("SELECT id FROM `modx_media_sources` WHERE name = '".$item->media_name."';");
        if(!empty($media_src_res['id'])) {
            $media_src_id = $media_src_res['id'];
            return $media_src_id;
        }else{
            $this->message = "Помилка. Media source id не визначений";
            return false;
        }
    }

    public function getTableAI($table){
        $res = $this->sqlOne("SHOW TABLE STATUS LIKE '".$table."'");
        if(!empty($res['Auto_increment'])){
            $ai = 0 + $res['Auto_increment'];
            return $ai;
        }else{
            $this->message = "Помилка. Номожливо визначити AI";
            return false;
        }
    }

    /**
     * @param $context
     * @return bool
     */
    public function createAccess($context){

        if($this->allowCreate($context, 'access')){

            $item = $this->getComponentContext($context);

            if(!$media_src_id = $this->getMediaSrc($item)){
                return false;
            }

            $res = $this->sqlOne("SHOW TABLE STATUS LIKE ");
            if( $user_group_id = $this->getTableAI('modx_membergroup_names') ){

                //Content Group
                $user_group_id = $user_group_id + 5;

                $sql = "INSERT INTO `modx_membergroup_names` (`id`, `name`, `description`, `parent`, `rank`, `dashboard`) VALUES ('".$user_group_id."', '".$item->media_name."', '', 0, 0, 2);";
                $this->executeSql($sql);

                $sql = "INSERT INTO `modx_access_context` (`id`, `target`, `principal_class`, `principal`, `authority`, `policy`) VALUES
                        (null, 'mgr', 'modUserGroup', '".$user_group_id."', 9999, 13);
                       ";
                $this->executeSql($sql);

                foreach($this->langs as $k => $v) {
                    $sql = "INSERT INTO `modx_access_context` (`id`, `target`, `principal_class`, `principal`, `authority`, `policy`) VALUES
                            (null, '".$item->context."-".$k."', 'modUserGroup', ".$user_group_id.", 9999, 11);
                            ";
                    $this->executeSql($sql);
                }

                $sql = "INSERT INTO `modx_access_media_source` (`id`, `target`, `principal_class`, `principal`, `authority`, `policy`, `context_key`) 
                        VALUES (NULL, '".$media_src_id."', 'modUserGroup', '".$user_group_id."', '9999', '8', 'mgr');";
                $this->executeSql($sql);

                $sql = "INSERT INTO `modx_access_namespace` (`id`, `target`, `principal_class`, `principal`, `authority`, `policy`, `context_key`) VALUES
                        (NULL, 'rebuilder', 'modUserGroup', '".$user_group_id."', 9999, 12, 'mgr'),
                        (NULL, 'elements', 'modUserGroup', '".$user_group_id."', 9999, 12, 'mgr'),
                        (NULL, 'migx', 'modUserGroup', '".$user_group_id."', 9999, 12, 'mgr');";
                $this->executeSql($sql);

                $item->set('user_group_id', $user_group_id);

                //default media src for choosen group
                $sql = "INSERT INTO `modx_user_group_settings` (`group`, `key`, `value`, `xtype`, `namespace`, `area`, `editedon`) VALUES ('".$user_group_id."', 'default_media_source', '".$media_src_id."',	'modx-combo-source',	'core',	'manager',	'2017-01-01 00:00:00');";
                $this->executeSql($sql);

                //News Group
                $user_group_id = $user_group_id + 1;

                $sql = "INSERT INTO `modx_access_context` (`id`, `target`, `principal_class`, `principal`, `authority`, `policy`) VALUES
                        (null, 'mgr', 'modUserGroup', '".$user_group_id."', 9999, 16);
                       ";
                $this->executeSql($sql);

                $sql = "INSERT INTO `modx_membergroup_names` (`id`, `name`, `description`, `parent`, `rank`, `dashboard`) VALUES ('".$user_group_id."', 'News".$item->media_name."', '', 0, 0, 2);";
                $this->executeSql($sql);

                $sql = "INSERT INTO `modx_access_media_source` (`id`, `target`, `principal_class`, `principal`, `authority`, `policy`, `context_key`) 
                        VALUES (NULL, '".$media_src_id."', 'modUserGroup', '".$user_group_id."', '9999', '8', 'mgr');";
                $this->executeSql($sql);

                $sql = "INSERT INTO `modx_access_namespace` (`id`, `target`, `principal_class`, `principal`, `authority`, `policy`, `context_key`) VALUES
                        (NULL, 'migx', 'modUserGroup', '".$user_group_id."', 9999, 12, 'mgr');";
                $this->executeSql($sql);

                //default media src for choosen group
                $sql = "INSERT INTO `modx_user_group_settings` (`group`, `key`, `value`, `xtype`, `namespace`, `area`, `editedon`) VALUES ('".$user_group_id."', 'default_media_source', '".$media_src_id."',	'modx-combo-source',	'core',	'manager',	'2017-01-01 00:00:00');";
                $this->executeSql($sql);

            }else{
                $this->message = "Помилка. Auto increment не визначений";
                return false;
            }

            $item->set('status_access', 1);
            $item->save();

            return true;

        }

        return false;

    }

    public function createUser($context){

        if($this->allowCreate($context, 'user')){

            $item = $this->getComponentContext($context);

            if( $user_id = $this->getTableAI('modx_users') ) {

                $user_id = $user_id +5;

                //Content User
                $sql = "INSERT INTO `modx_users` (`id`, `username`, `password`, `cachepwd`, `class_key`, `active`, `remote_key`, `remote_data`, `hash_class`, `salt`, `primary_group`, `session_stale`, `sudo`, `createdon`) 
VALUES ('".$user_id."', 'content_".$item->context."', 'kBNOdjDToy0R9mFbzHLmBdflQMczkHA6FUjcAYvyMv8=', '', 'modUser', '1', NULL, NULL, 'hashing.modPBKDF2', '235f12e1a4d8c197dac603958d78c0eb', '".$item->user_group_id."', 'a:3:{i:0;s:3:\"mgr\";i:1;s:2:\"ua\";i:2;s:3:\"web\";}', '0', '1465108515');";
                $this->executeSql($sql);

                $sql = "INSERT INTO `modx_user_attributes` (`id`, `internalKey`, `fullname`, `email`, `phone`, `mobilephone`, `blocked`, `blockeduntil`, `blockedafter`, `logincount`, `lastlogin`, `thislogin`, `failedlogincount`, `sessionid`, `dob`, `gender`, `address`, `country`, `city`, `state`, `zip`, `fax`, `photo`, `comment`, `website`, `extended`) 
                    VALUES (NULL, '".$user_id."', '', 'test@test.com', '', '', '0', '0', '0', '0', '1501600645', '1501849762', '0', 'cua2g23p57j8lj0r9k59fi13u6', '0', '0', '', '', '', '', '', '', '', '', '', '[]');";
                $this->executeSql($sql);


                $sql = "INSERT INTO `modx_member_groups` (`id`, `user_group`, `member`, `role`, `rank`) VALUES (NULL, '".$item->user_group_id."', '".$user_id."', '1', '0');";
                $this->executeSql($sql);

                //News User
                $user_id = $user_id + 1;
                $group_id = $item->user_group_id + 1;
                $sql = "INSERT INTO `modx_users` (`id`, `username`, `password`, `cachepwd`, `class_key`, `active`, `remote_key`, `remote_data`, `hash_class`, `salt`, `primary_group`, `session_stale`, `sudo`, `createdon`) 
VALUES ('".$user_id."', 'news_".$item->context."', 'kBNOdjDToy0R9mFbzHLmBdflQMczkHA6FUjcAYvyMv8=', '', 'modUser', '1', NULL, NULL, 'hashing.modPBKDF2', '235f12e1a4d8c197dac603958d78c0eb', '".$group_id."', 'a:3:{i:0;s:3:\"mgr\";i:1;s:2:\"ua\";i:2;s:3:\"web\";}', '0', '1465108515');";
                $this->executeSql($sql);

                $sql = "INSERT INTO `modx_user_attributes` (`id`, `internalKey`, `fullname`, `email`, `phone`, `mobilephone`, `blocked`, `blockeduntil`, `blockedafter`, `logincount`, `lastlogin`, `thislogin`, `failedlogincount`, `sessionid`, `dob`, `gender`, `address`, `country`, `city`, `state`, `zip`, `fax`, `photo`, `comment`, `website`, `extended`) 
                    VALUES (NULL, '".$user_id."', '', 'test@test.com', '', '', '0', '0', '0', '0', '1501600645', '1501849762', '0', 'cua2g23p57j8lj0r9k59fi13u6', '0', '0', '', '', '', '', '', '', '', '', '', '[]');";
                $this->executeSql($sql);


                $sql = "INSERT INTO `modx_member_groups` (`id`, `user_group`, `member`, `role`, `rank`) VALUES (NULL, '".$group_id."', '".$user_id."', '1', '0');";
                $this->executeSql($sql);

            }else{
                return false;
            }

            $item->set('status_user', 1);
            $item->save();

            return true;

        }

        return false;

    }

    public function createReplaceString($key, $find, $replace){

        $find = str_replace("'", "\\'", $find);
        $replace = str_replace("'", "\\'", $replace);
        $str = "REPLACE(".$key.", '".$find."', '".$replace."')";

        return $str;

    }

    public function createReplaceStrings($key, $in = array(), $out = array()){

        $str = $key;
        foreach ($in as $k => $v){
            $str = $this->createReplaceString($str, $v, $out[$k]);
        }

        return $str;

    }

    public function createMigx($context){
        $modx = &$this->modx;

        if($this->allowCreate($context, 'migx')){

            $item = $this->getComponentContext($context);

            $migxdb_name = strtolower($item->context);
            $migxdb_class_name = ucfirst(strtolower($item->context));

            $components = array(
                'news' => array(
                    'class_lower' => 'news',
                    'class' => 'News'

                ),'news_categories' => array(
                    'class_lower' => 'newscategories',
                    'class' => 'NewsCategories'

                ),
                'blogs' => array(
                    'class_lower' => 'blogs',
                    'class' => 'Blogs'

                ),'blogs_categories' => array(
                    'class_lower' => 'blogscategories',
                    'class' => 'BlogsCategories'

                )
            );
            foreach($components as $package => $v) {
                  $formtabs = $this->createReplaceStrings('formtabs',array("'config'=>'".$package."'"),array("'config'=>'".$package."_".$migxdb_name."'"));
                  $extended = $this->createReplaceStrings('extended',array('"packageName":"'.$package.'"','"classname":"'.$v['class'].'"'),array('"packageName":"'.$package.'_'.$migxdb_name.'"','"classname":"'.$v['class'].$migxdb_class_name.'"'));
                  $columns = $this->createReplaceStrings('columns',array('categories_migx_table=`news_categories`', 'categories_migx_table=`blogs_categories`'),array('categories_migx_table=`news_categories_'.$context.'`', 'categories_migx_table=`blogs_categories_'.$context.'`'));

                $sql = "INSERT INTO modx_migx_configs(formtabs, contextmenus, actionbuttons, columnbuttons, filters, extended, columns, createdby, createdon, editedby, editedon, deleted, deletedon, deletedby, published, publishedon, publishedby, id, name)
                    SELECT ".$formtabs.", contextmenus, actionbuttons, columnbuttons, filters, ".$extended.", ".$columns.", createdby, createdon, editedby, editedon, deleted, deletedon, deletedby, published, publishedon, publishedby, null, REPLACE(name, '" . $package . "', '" . $package . "_" . $migxdb_name . "')
                    FROM modx_migx_configs
                    WHERE name = '" . $package . "';";
                $this->executeSql($sql);

                $sql = "CREATE TABLE IF NOT EXISTS modx_" . $package . "_" . $migxdb_name . " LIKE modx_" . $package . ";";
                $this->executeSql($sql);

                if($package == 'blogs_categories'){
                    $sql = "INSERT INTO `modx_blogs_categories_".$context."` (`id`, `published`, `title`, `alias`, `title_ru`, `published_ru`, `title_ua`, `published_ua`, `title_en`, `published_en`) 
SELECT `id`, `published`, `title`, `alias`, `title_ru`, `published_ru`, `title_ua`, `published_ua`, `title_en`, `published_en` FROM `modx_blogs_categories_create`;";
                    $this->executeSql($sql);
                }
                if($package == 'blogs'){
                    $sql = "INSERT INTO `modx_blogs_".$context."` (`id`, `published`, `title`, `category`, `alias`, `date`, `on_main`, `important`, `title_ru`, `content_ru`, `published_ru`, `title_ua`, `content_ua`, `published_ua`, `title_en`, `content_en`, `published_en`, `meta_title_ua`, `meta_title_ru`, `meta_title_en`, `meta_keywords_ua`, `meta_keywords_ru`, `meta_keywords_en`, `meta_description_ua`, `meta_description_ru`, `meta_description_en`, `img_ua`, `img_ru`, `img_en`, `photo_ua`, `photo_ru`, `photo_en`, `video_ua`, `video_ru`, `video_en`, `contacts_ua`, `contacts_ru`, `contacts_en`, `no_link`, `birthday_ua`, `birthday_ru`, `birthday_en`, `vacancy`, `type`) 
SELECT null, `published`, `title`, `category`, `alias`, `date`, `on_main`, `important`, `title_ru`, `content_ru`, `published_ru`, `title_ua`, `content_ua`, `published_ua`, `title_en`, `content_en`, `published_en`, `meta_title_ua`, `meta_title_ru`, `meta_title_en`, `meta_keywords_ua`, `meta_keywords_ru`, `meta_keywords_en`, `meta_description_ua`, `meta_description_ru`, `meta_description_en`, `img_ua`, `img_ru`, `img_en`, `photo_ua`, `photo_ru`, `photo_en`, `video_ua`, `video_ru`, `video_en`, `contacts_ua`, `contacts_ru`, `contacts_en`, `no_link`, `birthday_ua`, `birthday_ru`, `birthday_en`, `vacancy`, `type` FROM `modx_blogs_create`;";
                    $this->executeSql($sql);
                }

                //files
                $file_arr_in = array($package.'.mysql.schema');
                $file_arr_out = array($package.'_'.$context.'.mysql.schema');
                $content_arr_in = array('package="'.$package.'"','class="'.$v['class'].'"', 'table="'.$package.'"');
                $content_arr_out = array('package="'.$package.'_'.$context.'"','class="'.$v['class'].$migxdb_class_name.'"', 'table="'.$package.'_'.$context.'"');


                $this->recurse_copy(
                    $modx->config['base_path'] . 'core/components/'.$package,
                    $modx->config['base_path'] . 'core/components/'.$package.'_' . $migxdb_name,
                    //content
                    $content_arr_in,
                    $content_arr_out,
                    //files
                    $file_arr_in,
                    $file_arr_out
                );

                //code from migx
                $packageName = $package.'_' . $migxdb_name;
                $packagepath = $modx->config['base_path'] . 'core/components/'.$packageName . '/';
                $modelpath = $packagepath . 'model/';
                $schemapath = $modelpath . 'schema/';
                $schemafile = $schemapath . $packageName . '.mysql.schema.xml';

                $xpdo = &$this->modx;
                $manager = $xpdo->getManager();
                $generator = $manager->getGenerator();
                $generator->parseSchema($schemafile, $modelpath);

            }

            $item->set('status_migx', 1);
            $item->save();

            return true;

        }

        return false;

    }

    public function createContent($context){
        $modx = &$this->modx;

        if($this->allowCreate($context, 'content')){

            $item = $this->getComponentContext($context);

            $item->set('status_content', 1);
            $item->save();

            return true;

        }

        return false;

    }

    public function createOther($context){
        $modx = &$this->modx;

        if($this->allowCreate($context, 'other')){

            if($item = $this->getComponentContext($context)) {

                $path = $modx->config['base_path'].'assets/sites/'.$context.'/';

                if (!file_exists($path)) {
                    mkdir($path, 0755, true);
                }

                $folder_src = $this->packagepath.'includes/site-folder-example';
                $folder_dst = $modx->config['base_path'].'sites/'.$context;

                //files
                $file_arr_in = array('site-folder-example');
                $file_arr_out = array($context);
                $content_arr_in = array('example');
                $content_arr_out =  array($context);

                if (file_exists($folder_src)) {
                    $this->recurse_copy(
                        $folder_src,
                        $folder_dst,
                        //content
                        $content_arr_in,
                        $content_arr_out,
                        //files
                        $file_arr_in,
                        $file_arr_out
                    );
                }

                $this->updateBabelTVs($context,'create-ua','create-ru', 'create-en');
                //$this->updateBabelTVs($context,'web','ru', 'en');
                $this->updateBabelSettings($context);

                $item->set('status_other', 1);
                $item->set('status', 1);
                $item->save();

                return true;
            }else{
                return false;
            }

        }

        return false;

    }

    public function allowCreate($context, $status){
        //проверка статуса выполнения операции для выбранного контекста
        $status_full = 'status_'.$status;
        if($item = $this->getComponentContext($context)){

            if(empty($item->name)){
                $this->message = "Поле з назвою не заповнено";
                return false;
            }
            if(empty($item->description)){
                $this->message = "Поле з описом не заповнено";
                return false;
            }
            if(empty($item->siteurl)){
                $this->message = "Поле з посиланням не заповнено";
                return false;
            }

            //если создание подсайта завершено
            if(!empty($item->status)){
                $this->message = "Помилка. Сайт вже створенний";
                return false;
            }
            //если текущая операция завершена
            if(!empty($item->$status_full)){
                $this->message = "Помилка. Операція вже завершена";
                return false;
            }
            //при попытке выполнить операцию, при незавершенной операции создания контекста
            if(empty($item->status_context) && $status != 'context'){
                $this->message = "Помилка. Контексти ще не створенні";
                return false;
            }

        }else{
            $this->message = "Помилка. Налаштування контекста не існують";
            return false;
        }
        //проверка статуса выполнения операции для выбранного контекста

        if($status == 'context' && $this->searchLangContextByKey($context)){
            $this->message = "Помилка. Контексти вже створений";
            return false;
        }
        if( $this->isAdmin() && $this->searchComponentContextByKey($context) ){
            return true;
        }else{
            return false;
        }
    }

    //file fnc
    public function recurse_copy($src, $dst, $in, $out, $in2, $out2) {
        $dir = opendir($src);
        @mkdir($dst);
        while(false !== ( $file = readdir($dir)) ) {
            if (( $file != '.' ) && ( $file != '..' )) {

                if ( is_dir($src . '/' . $file) ) {
                    $new_file = str_replace($in2, $out2, $file);
                    $this->recurse_copy($src . '/' . $file,$dst . '/' . $new_file, $in, $out, $in2, $out2);
                }
                else {
                    copy($src . '/' . $file,$dst . '/' . $file);
                    $this->replace_in_file($dst . '/' . $file, $in, $out);
                    $new_file = str_replace($in2, $out2, $file);
                    rename($dst . '/' . $file, $dst . '/' . $new_file);
                }
            }
        }
        closedir($dir);
    }


    public function replace_in_file($src,$in,$out) {
        $str=file_get_contents($src);
        $str=str_replace($in, $out, $str);
        file_put_contents($src, $str);
    }
    //file fnc

    public function isAdmin(){
        $modx = &$this->modx;

        if ($modx->user && $modx->user->get('sudo')) {
            return true;
        }else{
            return false;
        }
    }

    public function updateBabelSettings($context){
        $modx = &$this->modx;

        $context_arr = array();
        foreach ($this->langs as $k => $v) {
            $context_arr[] = $context.'-'.$k;
        }
        $contexts_string = implode(',', $context_arr).';';

        $settings_obj = $modx->getObject('modSystemSetting', ['key' => 'babel.contextKeys']);
        if (is_object($settings_obj)) {

            $val = $settings_obj->get('value');
            $val_strings = explode(';',$val);
            $val_strings[] = $contexts_string;
            $val_strings = array_unique($val_strings);
            $val = str_replace(';;',';',implode(';', $val_strings).';');

            $settings_obj->set('value', $val);

            $settings_obj->save();
        }else{
            $this->message = 'Помилка. Налаштування babel.contextKeys';
            return false;
        }

        $modx->cacheManager->refresh(array('system_settings' => []));
        return true;
    }

    public function langImplode($start,$val,$symbol,$end,$after_lang=0){
        $arr = array();
        foreach ($this->langs as $k => $v) {
            if(empty($after_lang)){
                $arr[] = $val.$k;
            }else{
                $arr[] = $k.$val;
            }
        }
        $string = $start.implode($symbol, $arr).$end;

        return $string;
    }

    public function getContextsResourcesConnections($context,$ua_context,$ru_context,$en_context){

        $resConnectios = array();
        foreach ($this->langs as $k => $v) {

            switch ($k) {
                case 'ua':
                    $system_context = $ua_context;
                    break;
                case 'ru':
                    $system_context = $ru_context;
                    break;
                case 'en':
                    $system_context = $en_context;
                    break;
                default:
            }

            $arr = $this->sqlArray(
                "SELECT c1.id as new, c2.id as old FROM `modx_site_content` as c1
left join `modx_site_content` as c2 on c1.alias = c2.alias and c1.uri = c2.uri
where c1.context_key = '".$context."-".$k."' and c2.context_key = '".$system_context."'");

            foreach ($arr as $key => $val){
                $resConnectios[$context.'-'.$k][$val['old']] = $val['new'];
            }

        }

        return $resConnectios;
    }

    public function getResourceTVs($context, $tvId){

        $contexts_string = $this->langImplode("'",$context.'-', "', '", "'");
        $result = $this->sqlArray(
            "SELECT v.id, v.contentid, v.value, c.context_key FROM `modx_site_tmplvar_contentvalues` as v
left join `modx_site_content` as c on v.contentid = c.id
WHERE v.tmplvarid = '".$tvId."' and c.context_key in (". $contexts_string .")");

        return $result;
    }

    //Передаем в метод языковой контекст и 3 контекста для сравнения. В итоге получим обновленные значения ТВшек с новыми названиями контекстов и ресурсами
    public function updateBabelTVs($context,$ua_context,$ru_context,$en_context){

        $resConnectios = $this->getContextsResourcesConnections($context,$ua_context,$ru_context,$en_context);

        //babel
        $TVitems = $this->getResourceTVs($context, 1);

        foreach ($TVitems as $key => $item){

            //проверяем было ли обновление ранее
            $updated = stripos($item['value'], $item['context_key'].':');

            $new_val_arr = array();
            $new_val_arr[] = $item['context_key'].':'.$item['contentid'];

            $arr = explode(';', $item['value']);
            foreach ($arr as $k2 => $v2){
                $arr2 = explode(':',$v2);
                if(!empty($arr2[0]) and !empty($arr2[1])){
                    $oldId = 0 + $arr2[1];
                    $new_context = str_replace(array($ua_context, $ru_context, $en_context),array($context.'-ua',$context.'-ru',$context.'-en'),$arr2[0]);
                    if($new_context != $item['context_key'] &&!empty($resConnectios[$new_context]) && !empty($resConnectios[$new_context][$oldId])){
                        $pair = $new_context.':'.$resConnectios[$new_context][$arr2[1]];
                        $new_val_arr[] = $pair;
                    }
                }
            }

            if($updated === false){
                $new_val = implode(';',$new_val_arr);
                $TVitems[$key]['new_val'] = $new_val;
            }else{
                $TVitems[$key]['new_val'] = $TVitems[$key]['value'];
            }

        }

        foreach ($TVitems as $k => $v) {
            $sql = "UPDATE `modx_site_tmplvar_contentvalues` SET `value` = '".$v['new_val']."' WHERE `id` = '".$v['id']."';";
            $this->executeSql($sql);
        }

        //menu_resource_list
        $TVitems = $this->getResourceTVs($context, 156);

        foreach ($TVitems as $key => $item){

            $new_val_arr = array();

            $arr = explode('||', $item['value']);
            foreach ($arr as $k2 => $v2){
                if( !empty($resConnectios[$item['context_key']]) && !empty($resConnectios[$item['context_key']][$v2])){
                    $new_val_arr[] = $resConnectios[$item['context_key']][$v2];
                }
            }

            if( !empty($new_val_arr) ){
                $new_val = implode('||',$new_val_arr);
                $TVitems[$key]['new_val'] = $new_val;
            }else{
                $TVitems[$key]['new_val'] = $TVitems[$key]['value'];
            }

        }

        foreach ($TVitems as $k => $v) {
            $sql = "UPDATE `modx_site_tmplvar_contentvalues` SET `value` = '".$v['new_val']."' WHERE `id` = '".$v['id']."';";
            $this->executeSql($sql);
        }

        return true;
/*
            echo '<pre>';
            print_r($TVitems);
            echo '</pre>';
*/
    }

    public function updateContextRank($contextKey, $rank){
        $modx = &$this->modx;

        $rank = 0 + $rank;

        $context = $modx->getObject('modContext', array('key' => $contextKey));
        if(is_object($context)) {
            $context->set('rank', $rank);
            if(!$context->save()){
                $this->message = "Помилка при спробі оновлення контексту";
                return false;
            }else{
                return true;
            }
        }
        return false;
    }

    public function returnError(){
        $msg = !empty($this->message)? $this->message : 'Помилка';
        $out =  '{"status":"error", "message":"'.$msg.'"}';
        return $out;
    }

    //Удаление всего созданного
    public function deleteContext($context){
        $modx = &$this->modx;

        $context = $this->prepareContextName($context);

        echo $context;
        echo '<br />';
        echo '<br />';

        if(!empty($context)) {

            echo '<b>context block</b><br />';
            foreach ($this->langs as $k => $v) {
                $sql = "DELETE FROM `modx_context` WHERE `key` = '" . $context . "-".$k."';";
                $this->executeSql($sql);
                echo '<b>'.$sql.'</b>';echo '<br />';
                echo 'delete context';echo '<br />';

                $sql = "DELETE FROM `modx_access_context` WHERE `target` = '" . $context . "-".$k."';";
                $this->executeSql($sql);
                echo '<b>'.$sql.'</b>';echo '<br />';
                echo 'delete context accesses';echo '<br />';

                $sql = "DELETE FROM `modx_context_setting` WHERE `context_key` = '" . $context . "-".$k."';";
                $this->executeSql($sql);
                echo '<b>'.$sql.'</b>';echo '<br />';
                echo 'delete context settings';echo '<br />';

                $sql = "DELETE FROM `modx_site_tmplvar_contentvalues` WHERE `contentid` IN ( SELECT `id` FROM `modx_site_content` WHERE `context_key` = '" . $context . "-".$k."');";
                $this->executeSql($sql);
                echo '<b>'.$sql.'</b>';echo '<br />';
                echo 'delete context resourses tv`s';echo '<br />';

                $sql = "DELETE FROM `modx_site_content` WHERE `context_key` = '" . $context . "-".$k."';";
                $this->executeSql($sql);
                echo '<b>'.$sql.'</b>';echo '<br />';
                echo 'delete context resourses';echo '<br />';
            }

            echo '<b>Other</b><br />';
            $media_name = 'Content'.ucfirst($context);
            $sql = "DELETE FROM `modx_access_media_source` WHERE `target` IN (SELECT `id` FROM `modx_media_sources` WHERE `name` = '".$media_name."')";
            $this->executeSql($sql);
            echo '<b>'.$sql.'</b>';echo '<br />';
            echo 'delete media source access';echo '<br />';
            $sql = "DELETE FROM `modx_media_sources` WHERE `name` = '".$media_name."'";
            $this->executeSql($sql);
            echo '<b>'.$sql.'</b>';echo '<br />';
            echo 'delete media source';echo '<br />';

            foreach ($this->langs as $k => $v) {
                $sql = "DELETE FROM `modx_media_sources_elements` WHERE `context_key` = '" . $context . "-".$k."'";
                $this->executeSql($sql);
                echo '<b>'.$sql.'</b>';echo '<br />';
                echo 'delete media_sources_elements';echo '<br />';
            }

            $group_name = 'content'.ucfirst($context);
            $sql = "DELETE FROM `modx_access_namespace` WHERE `principal` IN (SELECT `id` FROM `modx_membergroup_names` WHERE `name` = '".$group_name."')";
            $this->executeSql($sql);
            echo '<b>'.$sql.'</b>';echo '<br />';
            echo 'delete access namespace';echo '<br />';

            $sql = "DELETE FROM `modx_member_groups` WHERE `user_group` IN (SELECT `id` FROM `modx_membergroup_names` WHERE `name` = '".$group_name."')";
            $this->executeSql($sql);
            echo '<b>'.$sql.'</b>';echo '<br />';
            echo 'delete modx_member_groups';echo '<br />';

            $sql = "DELETE FROM `modx_membergroup_names` WHERE `name` = '".$media_name."';";
            $this->executeSql($sql);
            $sql = "DELETE FROM `modx_membergroup_names` WHERE `name` = 'News".$media_name."';";
            $this->executeSql($sql);
            echo '<b>'.$sql.'</b>';echo '<br />';
            echo 'delete modx_membergroup_names';echo '<br />';

            $sql = "DELETE FROM `modx_users` WHERE `username` = 'content_" . $context . "'";
            $this->executeSql($sql);
            $sql = "DELETE FROM `modx_users` WHERE `username` = 'news_" . $context . "'";
            $this->executeSql($sql);
            echo '<b>'.$sql.'</b>';echo '<br />';
            echo 'delete modx_user_attributes';echo '<br />';

            $sql = "DELETE FROM `modx_user_attributes` WHERE `internalKey` IN (SELECT `id` FROM `modx_users` WHERE `username` in ('news_" . $context . "', 'content_" . $context . "'));";
            $this->executeSql($sql);
            echo '<b>'.$sql.'</b>';echo '<br />';
            echo 'delete modx_user_attributes';echo '<br />';

            $sql = "DELETE FROM `modx_users` WHERE `username` = 'content_" . $context . "'";
            $this->executeSql($sql);
            $sql = "DELETE FROM `modx_users` WHERE `username` = 'news_" . $context . "'";
            $this->executeSql($sql);
            echo '<b>'.$sql.'</b>';echo '<br />';
            echo 'delete modx_users';echo '<br />';

            $sql = "DELETE FROM `modx_migx_configs` WHERE `name` = 'news_" . $context . "';";
            $this->executeSql($sql);
            $sql = "DELETE FROM `modx_migx_configs` WHERE `name` = 'news_categories_" . $context . "';";
            $this->executeSql($sql);
            $sql = "DELETE FROM `modx_migx_configs` WHERE `name` = 'blogs_" . $context . "';";
            $this->executeSql($sql);
            $sql = "DELETE FROM `modx_migx_configs` WHERE `name` = 'blogs_categories_" . $context . "';";
            $this->executeSql($sql);
            echo '<b>'.$sql.'</b>';echo '<br />';
            echo 'delete modx_migx_configs';echo '<br />';

            $sql = "DROP TABLE IF EXISTS `modx_blogs_categories_" . $context . "`;";
            $this->executeSql($sql);
            $sql = "DROP TABLE IF EXISTS `modx_blogs_" . $context . "`;";
            $this->executeSql($sql);
            $sql = "DROP TABLE IF EXISTS `modx_news_categories_" . $context . "`;";
            $this->executeSql($sql);
            $sql = "DROP TABLE IF EXISTS `modx_news_" . $context . "`;";
            $this->executeSql($sql);
            echo '<b>'.$sql.'</b>';echo '<br />';
            echo 'delete modx_migx_configs';echo '<br />';


            $sql = "DELETE FROM `modx_create_site` WHERE `context` = '" . $context . "'";
            $this->executeSql($sql);
            echo '<b>'.$sql.'</b>';echo '<br />';
            echo 'delete modx_migx_configs';echo '<br />';

            echo '<b>**********************</b>';
            echo '<br />';


        }






        echo 'work';
    }

}