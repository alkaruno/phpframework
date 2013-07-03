phpframework
============

Велосипед собственной сборки для быстрого создания веб-приложений. PHP >= 5.2

Структура проекта
-----------------

  app
		config
			app.php
			env.php
		controllers
		models
		views
	public
		css
		im
		js
		.htaccess
		index.php

_.htaccess_

  AddDefaultCharset UTF-8
	
	Options +FollowSymLinks +ExecCGI
	
	php_flag    display_errors          on
	php_flag    display_startup_errors  on
	php_value   error_reporting         2047
	php_flag    magic_quotes_gpc        off
	
	<IfModule mod_rewrite.c>
	
	  RewriteEngine On
	
	  RewriteCond %{REQUEST_FILENAME} !-f
	  RewriteRule ^(.*)$ index.php [QSA,L]
	
	</IfModule>
  
_index.php_

  <?php
	
	require '../../phpframework/autoload.php';
	new Dispatcher();
  
Конфигурационные настройки хранятся в папке `config`. `env.php` содержит настройки, привязанные к конкретному окружению (настройки БД и пр.), `app.php` содержит общие настройки приложения.

Каждый конфигурационный файл должен возвращать массив настроек. Например:

  <?php
	
	return array(
	    'db' => array(
	        'dsn' => 'mysql:dbname=dbname;host=localhost;charset=utf8',
	        'username' => 'root',
	        'password' => 'password'
	    )
	);
