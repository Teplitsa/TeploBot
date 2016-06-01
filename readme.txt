=== TeploBot - Telegram Bot for WP ===
Contributors: gsuvorov, foralien
Author URI: https://te-st.ru
Plugin URI: https://gwptb.te-st.ru
Tags: Telegram, telegram, bot, chatbot, messenger, robot
Requires at least: 4.5
Tested up to: 4.5.2
Stable tag: trunk
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

TeploBot simple Telegram chatbot with green effect.

== Description ==

_Описание на русском языке - ниже._


*TeploBot - Telegram Bot for WP* is the plugin for WordPress that provides basic [Telegram](https://telegram.org/) chatbot functionality for your site. The bot sends search results on your site as replay to chat users. For developers: the behavior of the bot could be customized.

Plugin features:

* webhook support to receive automatic updates from Telegram
* support for global Telegram commands
* messages without command processed as search requests and results returns to chat as list of links
* log of messages and responses
* for developers: customs commands could be defined

**Limitation**. In mean time the plugin supports individual chats only - no support for group chats or inline mode. Follow the development progress or send as pull-requests for improvements.

The plugin developed and supported by [Teplitsa of social technologies](https://te-st.ru/).

Follow the development on [GitHub](https://github.com/Teplitsa/GWPTB)

**Global commands**

* `/start` Greeting on the dialogue start
* `/help` Provide the help text for user

Developers could define own commends through `gwptb_supported_commnds_list` filter (details published in wiki on  [GitHub](https://github.com/Teplitsa/GWPTB)).

Commands should be defined in chat with [@BotFather](https://telegram.me/botfather) to be accepted by plugin: use `/setcommands` command and follow the instructions. 

**Help the project**

We will be very grateful if you will help us to make GWPTB better.

* You can add a bug report or a feature request on [GitHub](https://github.com/Teplitsa/GWPTB/issues).
* Send us your pull request to share a code improvement.
* Translate the plugin in your language

**РУССКИЙ**

*TeploBot - Telegram Bot for WP* - плагин для WordPress, реализующий простого чатбота для [Телеграм](https://telegram.org/). Бот может автоматически отправлять результаты поиска по сайту в ответ на запрос пользователей и позволяет их просматривать. Для разработчиков: состав действий бота может быть расширен. 

Основные функции

* получение и обработка автоматический уведомлений от Телеграм о сообщениях боту
* поддержка стандартных команд Телеграм
* сообщения, не содержащие команд, трактуются как поисковый запрос и в ответ отсылаются результаты поиска по сайту с возможностью пролистывания
* лог сообщений
* для разработчиков - возможность добавлять собственные команды

**Ограничение**. Пока бот обрабатывает сообщения только в индивидуальных чатах. Групповые чаты и инлайновый режим не поддерживается. Следите за обновлениями и присылайте пулл-реквесты.

Плагин разработан и поддерживается [Теплицей социальных технологий](https://te-st.ru/).

Следите за разработкой на [GitHub](https://github.com/Teplitsa/GWPTB)


**Стандартные команды**

* `/start` Начало диалога
* `/help`  Подсказка и описание команд

Разработчики могут определять собственные команды, используя фильтр `gwptb_supported_commnds_list` (подробнее о добавлении собственных команд - в wiki на [GitHub](https://github.com/Teplitsa/GWPTB)).

Чтобы бот распознавал команды, они должны быть установлены в диалоге с [@BotFather](https://telegram.me/botfather): отправьте ему команду `/setcommands` и следуйте инструкциям. 


**Помощь проекту**

Мы очень ждем вашей помощи проекту. Вы можете помочь следующими способами:

* Добавить сообщение об ошибке или предложение по улучшению на [GitHub](https://github.com/Teplitsa/GWPTB/issues/)
* Поделиться улучшениями кода, послав нам Pull Request
* Сделать перевод плагина или оптимизировать его для вашей страны.


== Installation ==

Plugins requires PHP 5.3+ and WordPress 4.5+.

Upload the plugin folder `gwptb` into `wp-content/plugins` using WordPress Dashboard (_Plugins -- Add new_) or by cloning Github-repo.

Activate the plugin through the _Plugins_ menu in WordPress.

Configure the plugin by going to the page _GWPTB -> Settings_ that appears in your admin menu.

To put the plugin into work you need to create a Telegram bot in the dialogue with <a href="https://telegram.me/botfather" target="_blank">BotFather</a> user. Start chat with it and follow a few simple steps. Once you've created a bot you will received your authorization token, that should be saved in plugin settings.

**РУССКИЙ**

Для корректной работы необходим PHP версии 5.3 и выше и WordPress версии 4.5 и выше.

Загрузите папку плагина `gwptb` в директорию `wp-content/plugins`, используя административный интерфейс добавления плагинов (`Плагины -- Добавить новый`)
или клонировав GitHub-репозиторий.

Активируйте плагин в списке плагинов (`Меню - Плагины`).

Настройки плагина доступны в меню _GWPTB -> Настройки_. 

Для начала работы необходимо создать нового бота в чате Телеграм с пользователем [@BotFather](https://telegram.me/botfather). Отправьте команду `/newbot` и следуйте инструкциям.

В случае успешного создания бота вы получите ключ (токен) авторизации. Скопируйте и сохраните его в настройках плагина, после чего установите соединение с Телеграм. В этом режиме бот будет отвечать на запросы пользователей в чате, отправляя результаты поиска по сайту и позволяя их пролистывать. 


== Screenshots ==

1. Страница настроек плагина
2. Успешная настройка бота
3. Лог сообщений
4. Пример ответа с результатами поиска


== Changelog ==
= 1.0 =
* First official release!
