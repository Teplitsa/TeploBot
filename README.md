# TeploBot - Telegram Bot for WP

**Описание на русском языке - ниже.**

*TeploBot* is the plugin for WordPress that provide basic [Telegram](https://telegram.org/) chatbot functionality for your site. The bot send search results on your site as replay to chat users. The behavior of the bot could be customized.

Plugin features:

* webhook support for automatic updates from Telegram
* support for global Telegram commands
* messages without command processed as search request and search results returns to chat
* log of messages and responses
* for developers: customs commands could be defined

**Limitation**. In mean time the plugin supports individual chats only - no support for group chats or inline mode. Follow the development progress or send as pull-requests for improvements.

The plugin developed and supported by [Teplitsa of social technologies](https://te-st.ru/).

###How to install###

Plugins requires PHP 5.3+ and WordPress 4.5+.

1. Upload the plugin folder `gwptb` into `wp-content/plugins` using WordPress Dashboard (_Plugins -- Add new_) or by cloning GitHub-repo.

2. Activate the plugin through the _Plugins_ menu in WordPress.

3. Configure the plugin by going to the page _TeploBot -> Settings_ that appears in your admin menu.

**Global commands**

`/start` - Greeting on the dialogue start

`/help` - Provide the help text for user

Developers could define own commends through `gwptb_supported_commnds_list` filter (details published in wiki on  [GitHub](//github.com/Teplitsa/GWPTB)).

Commands should be defined in chat with [@BotFather](https://telegram.me/botfather) to be accepted by plugin: use `/setcommands` command and follow the instructions.

### Screenshots ###

Plugin Settings

![Plugin Settings](https://itv.te-st.ru/wp-content/uploads/gwptb-screenshot-1.png)

Connection setup for the bot

![Connection setup for the bot](https://itv.te-st.ru/wp-content/uploads/gwptb-screenshot-2.png)

Log screen

![Log screen](https://itv.te-st.ru/wp-content/uploads/gwptb-screenshot-3.png)

Search results in chat

![Search results in chat](https://itv.te-st.ru/wp-content/uploads/gwptb-screenshot-4.png)


###Help the project###

We will be very grateful if you will help us to make TeploBot better.

* You can add a bug report or a feature request on [GitHub](https://github.com/Teplitsa/GWPTB/issues).
* Send us your pull request to share a code improvement.
* Translate the plugin into your language


##Описание на русском языке##

*TeploBot - Telegram Bot for WP* - плагин для WordPress, реализующий простого чатбота для [Телеграм](https://telegram.org/). Бот может автоматически отправлять результаты поиска по сайту в ответ на запрос пользователей и позволяет их просматривать. Состав действий бота может быть расширен. 

Основные функции

* получение и обработка автоматический уведомлений от Телеграм о сообщениях боту
* поддержка стандартных команд Телеграм
* сообщения, не содержащие команд, трактуются как поисковый запрос и в ответ отсылаются результаты поиска по сайту с возможностью пролистывания
* лог сообщений
* для разработчиков - возможность добавлять собственные команды

**Ограничение**. Пока бот обрабатывает сообщения только в индивидуальных чатах. Групповые чаты и инлайновый режим не поддерживается. Следите за обновлениями и присылайте пулл-реквесты.

Плагин разработан и поддерживается [Теплицей социальных технологий](https://te-st.ru/).

### Установка и использование ###

Для корректной работы необходим PHP версии 5.3 и выше и WordPress версии 4.5 и выше.

1. Загрузите папку плагина `gwptb` в директорию `wp-content/plugins`, используя административный интерфейс добавления плагинов (_Плагины -- Добавить новый_)
или клонировав GitHub-репозиторий.

2. Активируйте плагин в списке плагинов (_Меню - Плагины_).

3. Настройки плагина доступны в меню _TeploBot -> Настройки_. 

Для начала работы необходимо создать нового бота в чате Телеграм с пользователем [@BotFather](https://telegram.me/botfather). Отправьте команду `/newbot` и следуйте инструкциям.

В случае успешного создания бота вы получите ключ (токен) авторизации. Скопируйте и сохраните его в настройках плагина, после чего установите соединение с Телеграм. В этом режиме бот будет отвечать на запросы пользователей в чате, отправляя результаты поиска по сайту и позволяя их пролистывать. 

**Стандартные команды**

`/start` - Начало диалога

`/help` - Подсказка и описание команд

Разработчики могут определять собственные команды, используя фильтр `gwptb_supported_commnds_list` (подробнее о добавлении собственных команд - в wiki).

Чтобы бот распознавал команды, они должны быть установлены в диалоге с [@BotFather](https://telegram.me/botfather): отправьте ему команду `/setcommands` и следуйте инструкциям. 

### Помощь проекту ###

Мы очень ждем вашей помощи проекту. Вы можете помочь следующими способами:

* Добавить сообщение об ошибке или предложение по улучшению на GitHub
* Поделиться улучшениями кода, прислав нам Pull Request
* Сделать перевод плагина или оптимизировать его для вашей страны.
