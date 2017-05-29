# Инструкция по составлению cron-скрипта

**Скрипт запускайте только после полной настройки модуля!**

### 1) Отключите выполнение агентов на "Событиях"
Для этого необходимо запустить следующую команду в php консоли:

```php
	COption::SetOptionString("main", "agents_use_crontab", "N");
	echo COption::GetOptionString("main", "agents_use_crontab", "N"); 
 
	COption::SetOptionString("main", "check_agents", "N"); 
	echo COption::GetOptionString("main", "check_agents", "Y"); 
```

### 2) Уберите любые упоминания констант
 Для этого необходимо перейти в __/bitrix/php_interface/dbconn.php__ и удалить:

```php
	BX_CRONTABD
	BX_CRONTAB_SUPPORT
	NO_AGENT_CHECK 
	DisableEventsCheck
```

### 3) Добавьте запись в файл __/bitrix/php_interface/dbconn.php__

```php
	if(!(defined("CHK_EVENT") && CHK_EVENT===true))    
    		define("BX_CRONTAB_SUPPORT", true); 
```


### 4) Создайте файл __cron_events.php__ 
  Разместите его в __/bitrix/php_interface/__ и добавьте в него содержимое ниже:


```php
	<?php 
	$_SERVER["DOCUMENT_ROOT"] = realpath(dirname(__FILE__)."/../..");
	$DOCUMENT_ROOT = $_SERVER["DOCUMENT_ROOT"];

	define("NO_KEEP_STATISTIC", true);
	define("NOT_CHECK_PERMISSIONS",true);
	define('BX_NO_ACCELERATOR_RESET', true);
	define('CHK_EVENT', true);

	require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");

	@set_time_limit(0);
	@ignore_user_abort(true);

	CAgent::CheckAgents();
	define("BX_CRONTAB_SUPPORT", true);
	define("BX_CRONTAB", true);
	CEvent::CheckEvents();

	if(CModule::IncludeModule('sender'))
	{
		\Bitrix\Sender\MailingManager::checkPeriod(false);
		\Bitrix\Sender\MailingManager::checkSend();
	}

	//require($_SERVER['DOCUMENT_ROOT']."/bitrix/modules/main/tools/backup.php");
```

### 5) Добавте скрипт в cron для пользователя bitrix.
Для того что бы это сделать необходимо зайти по ssh в косоль CentOS и выполнить команду __crontab -u bitrix -e__ 

```php
	*/3 * * * * /usr/bin/php -f /home/bitrix/www/bitrix/php_interface/cron_events.php
	
```

В [*/3] – число 3 указывает на периодичность работы крона.  
После всех выполненных действий отправка системных событий и все агенты будут обрабатывается из под cron раз в 3 минуты.
