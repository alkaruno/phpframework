phpframework
============

Велосипед собственной сборки для быстрого создания веб-приложений. PHP >= 5.4


Структура проекта и конфигурация
--------------------------------

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
    
    require '../vendor/autoload.php';
    new \Xplosio\PhpFramework\App();

Конфигурационные настройки хранятся в папке `config`. `env.php` содержит настройки, привязанные к конкретному окружению (настройки БД и пр.), `app.php` содержит общие настройки приложения.

Каждый конфигурационный файл должен возвращать массив настроек. Например:

	<?php

	return [
	    'db' => [
	        'dsn' => 'mysql:dbname=dbname;host=localhost;charset=utf8',
	        'username' => 'root',
	        'password' => 'password'
	    ]
	];


Роутинг
-------

Для этого в конфигурационном файле `app.php` описываются правила. Например:

	<?php

	return [
	    'routes' => [
	        ['^/$', 'HomeController'],
	        ['^/profile', 'ProfileController']
	    ]
	];

Во втором случае на контроллер переправляются все запросы, которые начинаются на `/profile`. Дополнительный роутинг может осуществляться в самом контроллере `ProfileController`.


Контроллеры
-----------

В фреймворке имеется базовый класс `Controller`, с абстрактым методом `abstract public function handle();`. Названия классов контроллеров должны заканчиваться на `Controller`.

	<?php

	class HomeController extends Controller
	{
	    public function handle()
	    {
	        $this->request->set('user', 'Вася Пупкин');

	        return 'profile.php'; // для PHP представлений

	        return 'profile.tpl'; // для Smarty представлений

	        return array('profile.tpl', 'user', 'Вася Пупкин'); // для передачи в представление одной переменной

	        return array('profile.tpl', array(
	            'user' => 'Вася Пупкин',
	            'city' => 'Дефолт сити'
	        )); // для передачи в представление массива переменных

	        return 'redirect:/success'; // для редиректа на другой URL
	    }
	}

Также имеется контроллер с дополнительным роутингом внутри `RouterController` с абстрактным методом `abstract protected function getRoutes();`. Роутинговые правила задаются аналогично правилам в `app.php`, только вместо имен контроллеров задаются имена методов.

	class ProfileController extends RouterController
	{
	    protected function getRoutes()
	    {
	        return array(
	            array('^/profile', 'index'),
	            array('^/profile/(\d+)$', 'details')
	        );
	    }

	    public function index()
	    {
	        return array('profile/index.tpl', ProfileModel::getAll());
	    }

	    public function details($id)
	    {
	        return array('profile/details.tpl', ProfileModel::get($id));
	    }
	}


Модели и работа с БД
--------------------

Имя класса модели должно заканчиваться на `Model`. Для работы с БД имеется класс `Db` со следующими методами:

	public static function query($sql, $values = null);

	public static function getValue($sql, $values = null);

	public static function getRow($sql, $values = null);

	public static function getRows($sql, $values = null);

	public static function getPairs($sql, $values = null, $keyColumn = 'id', $valueColumn = 'name');

	public static function update($sql, $values = null);

	public static function insert($sql, $values = null);

	public static function begin();

	public static function commit();

	public static function rollback();

	public static function getLogs();

Все методы, кроме `getPairs` могут вызываться следующими образами:

	<?php

	$user = Db::getRow('SELECT * FROM user WHERE id = ?', 5);

	$users = Db::getRows('SELECT * FROM user WHERE age BETWEEN ? AND ?', array(21, 35));

	или

	$users = Db::getRows('SELECT * FROM user WHERE age BETWEEN ? AND ?', 21, 35);

	или

	$users = Db::getRows('SELECT * FROM user WHERE age BETWEEN :from AND :to', array('from' => 21, 'to' => 35));

Для простых запросов к таблицам есть класс `Entity` с методами:

	public static function getRow($table, $id);

	public static function getRows($table, $where = null, $order = null, $from = null, $count = null);

	public static function save($table, $data, $idColumn = 'id');

	public static function delete($table, $id);

Примеры использования:

	$user = Entity::getRow('user', 5); // равносильно $user = Db::getRow('SELECT * FROM user WHERE id = ?', 5);

	$id = Entity::save('user', array('first_name' => 'Вася', 'last_name' => 'Пупкин'));

	// равносильно
	$id = Db::insert('INSERT user SET first_name = :first_name, last_name = :last_name', array('first_name' => 'Вася', 'last_name' => 'Пупкин'));

Если в метод save передать в значениях id, то происходит UPDATE записи.


Формы и валидация
-----------------

Для валидации форм, а также любых других ассоциативных массивов, имеется класс `Validator`. Пример использования:

	<?php

	if ($this->request->getMethod() == 'POST') {

	    $validator = new Validator();
	    $validator->add('username')->required('Please, enter value');
	    $validator->add('password')->required('Please, enter value');

	    if ($validator->validate()) {
	        $data = $validator->getData(); // отвалидированные данные
	    } else {
	        $this->request->set('errors', $validator->getErrors()); // передача ошибок в отображение
	    }
	}


Отправка почты
--------------


Работа с изображениями
----------------------


Кеш
---
