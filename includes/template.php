<div class="container create-site-container" >
<form id="context_form">

		<div class="col-md-12 center f18 mb15">
			<label for="resource_name">Створення нового обласного сайту чи департаменту</label>
		</div>

        <div class="col-md-12 center ajax-preloader">
            <img class="ajax-preloader-img" src="<?php echo $images_url; ?>preloader.gif">
        </div>

        <div class="col-md-12 mb15">
            <div class="col-md-12">
            <label for="context_name">Назва контексту (тільки англійські літери у нижньому регістрі, наприклад, kyiv)</label>
            </div>
            <div class="col-md-8">
                <input type="text" name="context_search" class="form-control" id="context_name">
            </div>
            <div class="col-md-4">
                <button type="button" class="btn btn-success btn-sm search-context">Додати / знайти</button>
            </div>
		</div>

        <div class="col-md-12 mb15 mt25 context-block">
            <div class="col-md-12">
                <label for="tpl_name">Назва сайту</label>
                <input type="text" name="name" class="form-control" id="tpl_name">
            </div>
            <div class="col-md-12">
                <label for="tpl_context">Контекст</label>
                <input type="text" name="context_text" class="form-control" id="tpl_context" disabled="disabled">
                <input type="hidden" name="context" class="form-control" id="hidden_context">
            </div>

            <div class="col-md-12">
                <label for="tpl_description">Опис контексту</label>
                <input type="text" name="description" class="form-control" id="tpl_description">
            </div>

            <div class="col-md-12">
                <label for="tpl_siteurl">Посилання на сайт (приклад: http://site.ua/)</label>
                <input type="text" name="siteurl" class="form-control" id="tpl_siteurl">
            </div>

            <div class="col-md-12 mt15 center">
                <button type="button" class="btn btn-success save-settings">Зберегти данні контексту</button>
            </div>
		</div>

        <div class="col-md-12 mb25">
            <div class="col-md-12 mt15">
            <button type="button" class="btn btn-sm btn-success btn-start create-task" data-config="context">Запустити завдання</button>
            </div>
        </div>

        <?php
        $blocks = array(
            array(
                'label_undone' => 'Контексти',
                'label_done' => 'Контексти створенні',
                'config' => 'context'
            ),
            array(
                'label_undone' => 'Налаштування контекстів',
                'label_done' => 'Налаштування контекстів створенні',
                'config' => 'context_settings'
            ),array(
                'label_undone' => 'Джерела',
                'label_done' => 'Джерела медіа створенні',
                'config' => 'media'
            ),array(
                'label_undone' => 'Налаштування доступів',
                'label_done' => 'Налаштування доступів створенні',
                'config' => 'access'
            ),array(
                'label_undone' => 'Користувач',
                'label_done' => 'Користувачі content_<span class="user_name">{назва контексту}</span> та news_<span class="user_name">{назва контексту}</span> створенні (пароль: 123456789)',
                'config' => 'user'
            ),array(
                'label_undone' => 'Migx компоненти і таблиці',
                'label_done' => 'Migx компоненти і таблиці створенні',
                'config' => 'migx'
            ),
            array(
                    'label_undone' => 'Додаткові налаштування',
                    'label_done' => 'Завершено',
                    'config' => 'other'
                )

        );

        foreach($blocks as $block){
            echo '
                <div class="col-md-12 mb15 mt25 context-block create-'.$block['config'].'">
                <div class="col-md-12 mb25 undone">
                    <img src="/assets/components/migx/style/images/cross.png">
                    <label for="tpl_name">'.$block['label_undone'].'</label>
                    <button type="button" class="btn btn-sm btn-success create-task" data-config="'.$block['config'].'">Створити</button>
                </div>
    
                <div class="col-md-12 mb25 done">
                    <img src="/assets/components/migx/style/images/tick.png">
                    <label for="tpl_name">'.$block['label_done'].'</label>
                </div>
            </div>
            ';
        }
        ?>
            <div class="col-md-12 mb15 mt25 show-config">
                <div class="col-md-12 mb25">
                    <label class="col-md-12 center">Приклад конфігурації для nginx`у</label>
                    <label class="col-md-12 center">(УВАГА - ssl сертифікат загальний для всіх піддоменів)</label>
                    <div class="config-text"><?php
                        $config = file_get_contents($includes.'config/config-example.txt');
                        if(!empty($config)){
                            $config = str_replace('ПОДДОМЕН', '<span class="context_name">ПОДДОМЕН</span>', $config);
                            echo $config;
                        }
                        ?>
                    </div>
                    <textarea class="col-md-12 mb25 config">
                    </textarea>
                </div>
            </div>


</form>
</div>