Smart Apps · Hub

Маленький, быстрый и самодостаточный PHP-хаб для публикации новостей, релизов, документации и каталога интеграций/плагинов с версиями и файлами. Встроены полнотекстовый поиск (SQLite FTS5), i18n (uk/ru/en), админка и лёгкий фронт на Bootstrap 5.3.

✨ Возможности

Каталог плагинов: категории, описание, репозиторий/сайт, версии с каналами (stable/beta/preview), мин. совместимость (Syrve), файлы и чек-суммы.

Новости: WYSIWYG-редактор (TinyMCE), теги, даты/автор.

Документация: Markdown (EasyMDE) или внешний линк + загрузка файла.

Релизы Syrve: канал, дата, флаги «Recommended» и «LT», заметки в HTML.

Поиск по всем сущностям (FTS5, подсветка совпадений, «умные» подсказки).

I18N: 🇺🇦 Украинский / 🇷🇺 Русский / 🇬🇧 English. Простые хелперы __() и t().

UI: Bootstrap 5.3, тёмная/светлая/системная тема, адаптивные таблицы-карточки, верхний прогресс-бар, плавные анимации.

Админка: добавление/редактирование всего контента, загрузка нескольких файлов к версии плагина.

Аутентификация: логин/логаут, роль admin.

🏗️ Технологии

PHP 8.1+

SQLite 3 (FTS5) — единая БД файлами, без внешних сервисов

Bootstrap 5.3, TinyMCE, EasyMDE (CDN)

Без Composer и фреймворков

📁 Структура
/assets/
style.css
/lang/
en.php
ru.php
uk.php
/uploads/
docs/
plugins/<slug>/...
db.php
helpers.php
index.php
search.php
search_suggest.php
login.php, logout.php
admin.php
news.php, news_view.php, news_edit.php
docs.php, docs_view.php, docs_edit.php
plugins.php, plugin_view.php, plugin_edit.php
releases.php, release_view.php, release_edit.php
partials_header.php, partials_footer.php


🌍 Локализация (i18n)

Файлы переводов: /lang/uk.php, /lang/ru.php, /lang/en.php — возвращают array('Key' => 'Value').

Выбор языка: ?lang=uk|ru|en (сохранится в cookie), переключатель — в partials_header.php.

Хелперы:

__('Key') — вернуть перевод или ключ.

t('Hello {name}', ['name'=>'…']) — форматирование с подстановками.

Чтобы добавить язык:

Создайте lang/<code>.php с массивом переводов.

Добавьте код в список поддерживаемых языков в хелпере (см. sa_supported_langs() / i18n_langs()).

🔐 Авторизация

login.php / logout.php

Сессии PHP, пароли захэшированы password_hash()

require_admin() защищает админские разделы

🗂️ Каталог плагинов

Страница списка: plugins.php (поиск, фильтры cat:/category:)

Карточка плагина: plugin_view.php

Админ-редактор: plugin_edit.php + загрузка нескольких файлов к версии

«Лучшая» версия подсвечивается по введённой версии Syrve (сравнение по min_syrve)

🛠️ Frontend

Bootstrap 5.3 с CSS-надстройками (чипы, бейджи, адаптивные таблицы-карточки)

Тема: светлая/тёмная/системная, хранится в localStorage

Верхний прогресс-бар для навигации/fetch (без зависимостей)

Редакторы: TinyMCE (HTML) и EasyMDE (Markdown) через CDN

🧪 Демо-контент

Для быстрого теста можете добавить пару записей в news, docs, plugins и переиндексировать search_all запросами из блока «Поиск».

📌 Дорожная карта

Роли/права по сущностям

Версионирование документов

Веб-хуки/CI для автозаливки релизов

Импорт/экспорт данных (JSON/CSV)

Unit-тесты и миграции схемы