<?php

//get resources
$sql = "SELECT * FROM `modx_site_content` ORDER BY id";
$query = $modx->prepare($sql);
$query->execute();

$result = $query->fetchAll(PDO::FETCH_ASSOC);

$resource_tpl = '<select name="resource_parent" class="form-control" id="tpl">';
$resource_tpl .= '<option value="0">Выберите родительский ресурс</option>';
foreach($result as $k => $row){
    $resource_tpl .= '<option value="'.$row['id'].'">'.$row['pagetitle'].' ('.$row['id'].')</option>';
}
$resource_tpl .= '</select>';
//get resources

//*

//get templates
$sql = "SELECT * FROM  `modx_site_templates` ORDER BY templatename";
$query = $modx->prepare($sql);
$query->execute();
$result = $query->fetchAll(PDO::FETCH_ASSOC);

$template_tpl = '<select name="template" class="form-control" id="tpl">';
$template_tpl .= '<option value="0">Выберите шаблон</option>';
foreach($result as $k => $row){
    $template_tpl .= '<option value="'.$row['id'].'">'.$row['templatename'].'</option>';
}
$template_tpl .= '</select>';
//get templates

//get categories
$sql = "SELECT * FROM  `modx_categories` ORDER BY category";
$query = $modx->prepare($sql);
$query->execute();
$result = $query->fetchAll(PDO::FETCH_ASSOC);
$cat_tpl = '';
$cat_tpl .= '<option value="0">Выберите категорию</option>';
foreach($result as $k => $row){
    $cat_tpl .= '<option value="'.$row['id'].'">'.$row['category'].'</option>';
}
$cat_tpl .= '</select>';
//get categories

//get lang fields
$lang_fields = '';
foreach($yams_lang_array as $k => $v){
    $lang_fields .= '
		<div class="col-md-12">
			<label for="tpl_name">'.$v.'</label>
			<input type="text" name="langtv['.$v.'][]" class="form-control" id="tpl_name">
		</div>
	';

}
//get lang fields

//get TV list
$sql = "SELECT id, name FROM `modx_site_tmplvars` WHERE category = '4' ORDER BY name,id";
$query = $modx->prepare($sql);
$query->execute();
$result = $query->fetchAll(PDO::FETCH_ASSOC);
$tv_array = array();
foreach($result as $k => $row){

        $name = $row['name'];
        $tv_array[$name]['id'][] = $row['id'];

}

$tv_tpl = '';
$tv_tpl .= '<option value="0">Выберите дополнительное поле</option>';
foreach($tv_array as $name => $v){
    $tv_tpl .= '<option value="'.implode(',',$v['id']).'">'.$name.'</option>';
}
$tv_tpl .= '</select>';
/*
echo '|||<pre>';
print_r($tv_array);
echo '</pre>|||';
*/
//get TV list

?>