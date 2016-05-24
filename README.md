# Green WP Telegram Bot

**Scroll down for English description, please.**

*Green WP Telegram Bot* - плагин для WordPress, реализующий простого чатбота для [Телеграм](https://telegram.org/). Бот может автоматически отправлять результаты поиска по сайту в ответ на запрос пользователей и позволяет их просматривать. Состав действий бота может быть расширен. 

Основные функции

* получение и обработка автоматический уведомлений от Телеграм о сообщениях боту
* поддержка стандартных команд Телеграм
* сообщения, не содержашие команд, трактуются как поисковый запрос и в ответ отсылаются результаты поиска по сайту с возможностью пролистывания
* лог сообщений
* для разработчиков - возможность добавлять собственные команды

Плагин разработан и поддерживается [Теплицей социальных технологий](https://te-st.ru/).

## Установка и использование ##

Для корректной работы необходим PHP версии 5.3 и выше и WordPress версии 4.5 и выше.

Загрузите папку плагина `gwptb` в директорию `wp-content/plugins`, используя административный интерфейс добавления плагинов (_Плагины -- Добавить новый_)
или клонировав GitHub-репозиторий.

Активируйте плагин в списке плагинов (_Меню - Плагины_).

Настройки плагина доступны в меню _GWPTB -> Настройки_. 

Для начала работы необходимо создать нового бота в чате Телеграм с пользователем [@BotFather](https://telegram.me/botfather). Отправьте команду `/newbot` и следуйте инструкциям.

В случае успешного создания бота вы получите ключ (токен) авторизации. Скопируйте и сохраните его в настройках плагина, после чего установите соединение с Телеграм. В этом режиме бот будет отвечать на запросы пользователей в чате, отправляя результаты поиска по сайту и позволяя их пролистывать. 


**Стандартные команды**

`/start` - Начало диалога

`/help` - Подсказка и описание команд

Разработчики могут определять собственные команды, используя фильтр `gwptb_supported_commnds_list` (подробнее о добавлении собственных команд - в wiki).

Чтобы бот распознавал команды, они должны быть установлены в диалоге с [@BotFather](https://telegram.me/botfather): отправьте ему команду `/setcommands` и следуйте инструкциям. 


## Как все выглядит ##

Страница настроек плагина

![Страница настроек плагина](https://itv.te-st.ru/wp-content/uploads/gwptb-screenshot-1.png)

Успешная настройка бота

![Успешная настройка бота](https://itv.te-st.ru/wp-content/uploads/gwptb-screenshot-2.png)

Лог сообщений

![Лог сообщений](https://itv.te-st.ru/wp-content/uploads/gwptb-screenshot-3.png)

Пример ответа с результатами поиска

![Пример ответа с результатами поиска](https://itv.te-st.ru/wp-content/uploads/gwptb-screenshot-4.png)
 

## Помощь проекту ##

Мы очень ждем вашей помощи проекту. Вы можете помочь следующими способами:

* Добавить сообщение об ошибке или предложение по улучшению на GitHub
* Поделиться улучшениями кода, прислав нам Pull Request
* Сделать перевод плагина или оптимизировать его для вашей страны.


## In English ##

*Green WP Telegram Bot* is the plugin for WordPress that provide basic [Telegram](https://telegram.org/) chatbot functionality for your site. The bot send search results on your site as replay to chat users. The behavior of the bot could be customized.

Plugin features:

* webhook support for automatic updates from Telegram
* support for global Telegram commands
* messages without command proccessed as search request and search results returns to chat
* log of messages and responses
* for developers: customs commands could be defined

The plugin developed and supported by [Teplitsa of social technologies](https://te-st.ru/).

Follow the development on [GitHub](https://github.com/Teplitsa/GWPTB)

**How to install**

Plugins requires PHP 5.3+ and WordPress 4.5+.

Upload the plugin folder `gwptb` into `wp-content/plugins` using WordPress Dashboard (_Plugins -- Add new_) or by cloning Github-repo.

Activate the plugin through the _Plugins_ menu in WordPress.

Configure the plugin by going to the page _GWPTB -> Settings_ that appears in your admin menu.


**Global commands**

`/start` - Greeting on the dialogue start

`/help` - Provide the help text for user

Developers could define own commends through `gwptb_supported_commnds_list` filter (details published in wiki on  [GitHub](//github.com/Teplitsa/GWPTB)).

Commands should be defined in chat with [@BotFather](https://telegram.me/botfather) to be accepted by plugin: use `/setcommands` command and follow the instructions. 

**Help the project**

We will be very grateful if you will help us to make GWPTB better.

* You can add a bugreport or a feature request on [GitHub](https://github.com/Teplitsa/GWPTB/issues).
* Send us your pull request to share a code improvement.
* Translate the plugin into your language
