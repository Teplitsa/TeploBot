=== TeploBot - Telegram Bot for WP ===
Contributors: gsuvorov, foralien, denis.cherniatev
Author URI: https://te-st.ru
Plugin URI: https://teplobot.te-st.ru
Tags: Telegram, telegram, bot, chatbot, messenger, robot
Requires at least: 4.5
Tested up to: 4.5.2
Stable tag: trunk
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

TeploBot simple Telegram chatbot with green effect.

== Description ==

_Описание на русском языке - ниже._

*TeploBot - Telegram Bot for WP* is the plugin for WordPress that provides basic [Telegram](https://telegram.org/) chatbot functionality for your site. The bot sends search results from your site as reply to chat users. For developers: the behavior of the bot could be customized.

Plugin features:

* webhook support to receive automatic updates from Telegram
* support for global Telegram commands
* send list of serach results into group and privte chats as a response to search requst
* support up to 5 custom commands with lists or recent posts or custom post types
* in private chats: messages without command processed as search requests 
* log of messages and responses
* posting from Telegram 
* subscription to notifications about new posts
* for developers: commands with custom logic could be defined
* for developers: API for sending notifications to subscribers

**Limitation**. In mean time the plugin doesn't support inline mode. Follow the development progress or send as pull-requests for improvements.

The plugin developed and supported by [Teplitsa. Technologies for Social Good](https://te-st.ru/).

Follow the progress at [GitHub](https://github.com/Teplitsa/TeploBot)

**Default commands**

* `/start` Greeting on the dialogue start
* `/help` Provide the help text for user
* `/s` Provide search results as list of posts' link
* `/post` Submit article to WP site
* `/sub` Subscribe to notifications about new posts (or any other content types)
* `/unsub` Unsubscribe from notifications about new posts (or any other content types)

Admins could add up to 5 custom commands that send list or posts or CPTs to chats. Developers could alter the commands logic through `gwptb_supported_commnds_list` filter (details published at [GitHub wiki](https://github.com/Teplitsa/TeploBot)).

Set "Active subscriptions" option to activate subscriptions to notifications.

Commands should be defined in chat with [@BotFather](https://telegram.me/botfather) to be accepted by plugin: use `/setcommands` command and follow the instructions. 

**Help the project**

We will be very grateful if you help us to make TeploBot better.

* Submit a bug report or feature request at [GitHub](https://github.com/Teplitsa/TeploBot/issues).
* Send us pull-request to share a code improvement.
* Translate the plugin into your language


**РУССКИЙ**

*TeploBot - Telegram Bot for WP* - плагин для WordPress, реализующий простого чатбота для [Телеграм](https://telegram.org/). Бот может автоматически отправлять результаты поиска по сайту в ответ на запрос пользователей и позволяет их просматривать. Для разработчиков: состав действий бота может быть расширен. 

Основные функции

* получение и обработка автоматических уведомлений от Телеграм о сообщениях боту
* поддержка стандартных команд Телеграм - /start и /help
* отправка результатов поиска по сайту в ответ на поисковый запрос (команду)
* поддержка до 5 собственных команд, отправляющих список последних записей или пользовательских типов записей
* в индивидуальных чатах сообщения, не содержащие команд, трактуются как поисковый запрос 
* лог сообщений
* написание постов из Telegram 
* подписка на уведомления о новых публикациях
* для разработчиков - возможность добавлять собственные команды или менять логику существующих
* для разработчиков - API для рассылки уведомлений подписчикам

**Ограничение**. В настоящее время инлайновый режим не поддерживается плагином. Следите за обновлениями и присылайте пулл-реквесты.

Плагин разработан и поддерживается [Теплицей социальных технологий](https://te-st.ru/).

Следите за разработкой на [GitHub](https://github.com/Teplitsa/TeploBot)


**Стандартные команды**

* `/start` Начало диалога
* `/help`  Подсказка и описание команд
* `/s`  Результаты поиска
* `/post` Отправка публикации на сайт
* `/sub` Подписка на уведомления о новых публикациях
* `/unsub` Отписка от уведомлений о новых публикациях

Администраторы сайта могут добавить до 5 собственных команд, отправляющих список последних публикаций в чат. Разработчики могут определять собственные команды или менять логику существующих, используя фильтр `gwptb_supported_commnds_list` (подробнее в wiki на [GitHub](https://github.com/Teplitsa/TeploBot)).

Заполните поле "Активные подписки" в настройках, чтобы активировать возможность подписки на уведомления.

Чтобы бот распознавал команды, они должны быть установлены в диалоге с [@BotFather](https://telegram.me/botfather): отправьте ему команду `/setcommands` и следуйте инструкциям. 


**Помощь проекту**

Мы очень ждем вашей помощи проекту. Вы можете помочь следующими способами:

* Добавить сообщение об ошибке или предложение по улучшению на [GitHub](https://github.com/Teplitsa/TeploBot/issues/)
* Поделиться улучшениями кода, послав нам Pull Request
* Сделать перевод плагина или оптимизировать его для вашей страны.


== Installation ==

Plugins requires PHP 5.3+ and WordPress 4.5+.

Website should work with https protocol for connecting with Telegram, self-signed certificate allowed.

1. Upload the plugin folder into `wp-content/plugins` using WordPress Dashboard (_Plugins -- Add new_) or by cloning GitHub-repo.

2. Activate the plugin through the _Plugins_ menu in WordPress.

3. Configure the plugin by going to the page _TeploBot -> Settings_ that appears in your admin menu.

To set the plugin into work you need to create a Telegram bot in the dialogue with <a href="https://telegram.me/botfather" target="_blank">BotFather</a> user. Start chat with it and follow a few simple steps. Once you've created a bot you will received your authorization token, that should be saved in plugin settings.

**РУССКИЙ**

Для корректной работы необходим PHP версии 5.3 и выше и WordPress версии 4.5 и выше.

Сайт должен поддерживать протокол https для взаимодействия с Telegram, допускается использование самоподписанного сертификата.

1. Загрузите папку плагина в директорию `wp-content/plugins`, используя административный интерфейс добавления плагинов (`Плагины -- Добавить новый`)
или клонировав GitHub-репозиторий.

2. Активируйте плагин в списке плагинов (`Меню - Плагины`).

3. Настройки плагина доступны в меню _TeploBot -> Настройки_. 

Для начала работы необходимо создать нового бота в чате Телеграм с пользователем [@BotFather](https://telegram.me/botfather). Отправьте команду `/newbot` и следуйте инструкциям.

В случае успешного создания бота вы получите ключ (токен) авторизации. Скопируйте и сохраните его в настройках плагина, после чего установите соединение с Телеграм. В этом режиме бот будет отвечать на запросы пользователей в чате, отправляя результаты поиска по сайту и позволяя их пролистывать. 


== Screenshots ==

1. Страница настроек плагина
2. Успешная настройка бота
3. Лог сообщений
4. Пример ответа с результатами поиска


== Changelog ==
= 1.2 =
* New: Submit posts to WP site rigth from Telegram
* New: Subscribe to notifications about new posts
* New: API for sending notifications to subscribers

= 1.1 =
* New: Support for group chats
* New: Support for custom commands
* Fix: correct naming of the bot
* Fix: some search request provide an incorrect results without notification

= 1.0 =
* First official release!
