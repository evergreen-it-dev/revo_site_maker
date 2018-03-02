# revo_site_maker

Решение представляет компонент, позволяющий создавать мультиязычные сайты на базе основного шаблонного сайта.
Как это работает. Есть мультиязычный сайт, созданный на трех связанных контекстах(3 языка). К нему в админку есть доступ у администратора и контентщика. При клонировании сайта появятся три новых контекста, новый контент пользователь. Политики доступа настроены так что контент менеджер каждого сайта видит только три своих контекста и у него своя папка для медиа. Доступ ко всем сайтам есть только у главного администратора.

Базовые требования:
1) modx revo 2.6.0
2) установленные дополнения: MIGX, Babel

Базовая сборка сайта:
1) Три контекста для каждого из языков, связанных между собой с помощью babel компонента. Контексты: create-ua, create-ru, create-en
2) Собранные на migx компоненты: news, news_categories, blogs, blogs_categories

Компонент. Структура:
1) Index файл: core/components/create_site/index.php
2) Модель: core/components/create_site/model/create_site/
3) Логика компонента находится в core/components/create_site/includes/

Описание работы компонента - компонент состоит из модулей, каждый из которых отвечает за копирование/создание отдельных сущностей сайта. Модули используют стандартные процессоры modx`а где это возможно, в остальном - это кастомный код и запросы.
Модули:
1) Дублирование контекстов
2) Создание настроек контекстов
3) Создание медиа источников
4) Создание пользователей
5) Создание migx компонентов
6) Общие настройки