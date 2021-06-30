<?php

/*!
 *  Elberos Framework
 *
 *  (c) Copyright 2019-2021 "Ildar Bikmamatov" <support@elberos.org>
 *
 *  Licensed under the Apache License, Version 2.0 (the "License");
 *  you may not use this file except in compliance with the License.
 *  You may obtain a copy of the License at
 *
 *      https://www.apache.org/licenses/LICENSE-2.0
 *
 *  Unless required by applicable law or agreed to in writing, software
 *  distributed under the License is distributed on an "AS IS" BASIS,
 *  WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 *  See the License for the specific language governing permissions and
 *  limitations under the License.
 */


namespace Elberos\Commerce\_1C;


class Controller
{
	static $max_size = 8 * 1024 * 1024;
	static $task_run_limits = 10;
	
	
	/**
	 * Create import
	 */
	static function createImport()
	{
		return new \Elberos\Commerce\_1C\Import();
	}
	
	
	
	/**
	 * Create task
	 */
	static function createTask()
	{
		$item = new \Elberos\Commerce\_1C\Task();
		$item->task_run_limits = static::$task_run_limits;
		return $item;
	}
	
	
	
	/**
	 * Обновить ключ
	 */
	static function updateKey($key, $value)
	{
		if ( ! is_multisite() )
		{
			if (!add_option($key, $value, "", "no"))
			{
				update_option($key, $value);
			}
		}
		else
		{
			if (!add_network_option(1, $key, $value, "", "no"))
			{
				update_network_option(1, $key, $value);
			}
		}
	}
	
	
	
	/**
	 * Получить ключ
	 */
	static function getKey($key, $value)
	{
		if ( ! is_multisite() )
		{
			return get_option($key, $value);
		}
		return get_network_option(1, $key, $value);
	}
	
	
	
	/**
	 * Возвращает имя файла
	 */
	static function getFilePath($session_id, $filename)
	{
		$filefolder = ABSPATH . "wp-content/uploads/1c_uploads";
		$filepath = $filefolder . "/" . $filename;
		return $filepath;
	}
	
	
	
	/**
	 * Инициализация
	 */
	public static function init()
	{
		add_action('elberos_register_routes', '\\Elberos\\Commerce\\_1C\\Controller::elberos_register_routes');
	}
	
	
	
	/**
	 * Register routes
	 */
	public static function elberos_register_routes($site)
	{
		$site->add_route
		(
			"api:1c:1c_exchange", "/api/1c_exchange/",
			null,
			[
				'title' => 'Авторизация',
				'description' => 'Авторизация',
				'render' => function ($site)
				{
					return static::actionIndex($site);
				}
			]
		);
	}
	
	
	
	/**
	 * Главный роут
	 */
	static function actionIndex()
	{
		set_time_limit(600);
		
		$type = isset($_GET['type']) ? $_GET['type'] : "";
		$mode = isset($_GET['mode']) ? $_GET['mode'] : "";
		
		/* Проверка авторизации */
		if ($mode == 'checkauth')
		{
			static::actionAuthenticate();
			return null;
		}
		if (!static::checkAuth())
		{
			echo "Authenticate failed\n";
			return null;
		}
		
		
		/* Инициализация */
		if ($mode == 'init')
		{
			static::actionInit();
		}
		
		/* Загрузка файлов */
		else if ($type == 'catalog' && $mode == 'file')
		{
			static::actionCatalogUploadFiled();
		}
		
		/* Обработка файлов */
		else if ($type == 'catalog' && $mode == 'import')
		{
			static::actionCatalogImport();
		}
		
		return null;
	}
	
	
	
	/**
	 * Аутентификация 1С
	 */
	static function actionAuthenticate()
	{
		$login_1c = static::getKey("elberos_1c_login", "");
		$password_1c = static::getKey("elberos_1c_password", "");
		
		$user = isset($_SERVER['PHP_AUTH_USER']) ? $_SERVER['PHP_AUTH_USER'] : null;
		$password = isset($_SERVER['PHP_AUTH_PW']) ? $_SERVER['PHP_AUTH_PW'] : null;
		if ($user == "" || $password == "" || $user != $login_1c || $password != $password_1c)
		{
			status_header(401);
			header('WWW-Authenticate: Basic realm="My Realm"');
			echo "Authenticate";
			return false;
		}
		
		session_start();
		
		$res = [];
		$res[] = "success";
		$res[] = "PHPSESSID";
		$res[] = session_id();
		
		echo implode("\n", $res) . "\n";
		
		$_SESSION["elberos_1c_login"] = true;
		
		return true;
	}
	
	
	
	/**
	 * Проверка авторизации
	 */
	static function checkAuth()
	{
		session_start();
		
		if (isset($_SESSION["elberos_1c_login"]) && $_SESSION["elberos_1c_login"])
		{
			return true;
		}
		
		return false;
	}
	
	
	
	/**
	 * Инициализация
	 */
	static function actionInit()
	{
		$res = [];
		$res[] = "zip=no";
		$res[] = static::$max_size;
		
		echo implode("\n", $res) . "\n";
		
		/* Обнуляем параметр product_just_imported */
		static::flushJustImportedFlagProducts();
	}
	
	
	
	/**
	 * Загрузка файлов
	 */
	static function actionCatalogUploadFiled()
	{
		$filefolder = ABSPATH . "wp-content/uploads/1c_uploads";
		
		/* Создаем папку если не была создана */
		if (!file_exists($filefolder))
		{
			try
			{
				mkdir($filefolder, 0775);
			}
			catch (\Exception $e)
			{
			}
		}
		
		if (!file_exists($filefolder))
		{
			echo "error " . $filefolder . " does not exists";
			return;
		}
		
		if (!is_dir($filefolder))
		{
			echo "error " . $filefolder . " does not exists";
			return;
		}
		
		$filename = isset($_GET['filename']) ? $_GET['filename'] : "";
		if ($filename != "")
		{
			$filepath = $filefolder . "/" . $filename;
			
			/* Загружаем файл */
			$content = file_get_contents("php://input");
			file_put_contents($filepath, $content);
		}
		
		echo "success";
	}
	
	
	
	/**
	 * Обработка файлов
	 */
	static function actionCatalogImport()
	{
		global $wpdb;
		
		$session_id = session_id();
		$filename = isset($_GET['filename']) ? $_GET['filename'] : "";		
		if ($filename == "")
		{
			echo "success";
			return;
		}
		
		/* Проверяем наличие такого файла */
		$file_path = static::getFilePath($session_id, $filename);
		if (!is_file($file_path))
		{
			echo "failed\n";
			echo "File not found";
			return;
		}
		
		/* Проверяем есть ли такой файл в базе */
		$table_name_1c_import = $wpdb->base_prefix . "elberos_commerce_1c_import";
		$sql = \Elberos\wpdb_prepare
		(
			"select * from $table_name_1c_import ".
			"where session_id = :session_id and status in (0,2) and filename = :filename",
			[
				'session_id' => $session_id,
				'filename' => $filename,
			]
		);
		
		$item = $wpdb->get_row($sql, ARRAY_A);
		
		/* Если элемент не найден */
		if ($item == null)
		{
			$wpdb->insert
			(
				$table_name_1c_import,
				[
					"session_id" => $session_id,
					"filename" => $filename,
					"status" => Helper::IMPORT_STATUS_PLAN, /* Запланировано */
					"gmtime_add" => gmdate("Y-m-d H:i:s", time()),
				]
			);
			
			$sql = \Elberos\wpdb_prepare
			(
				"select * from $table_name_1c_import where id = :id",
				[
					'id' => $wpdb->insert_id,
				]
			);
			$item = $wpdb->get_row($sql, ARRAY_A);
		}
		
		$progress = "";
		
		/* Если файл запланирован */
		if ($item['status'] == Helper::IMPORT_STATUS_PLAN)
		{
			$item = static::catalogImportContent($item);
		}
		
		/* Выполняем задачи */
		else if ($item['status'] == Helper::IMPORT_STATUS_WORK)
		{
			list($item, $progress) = static::catalogImportWork($item);
		}
		
		/* Обновляем запись в базе данных */
		$sql = \Elberos\wpdb_prepare
		(
			"update $table_name_1c_import " .
			"set status=:status, error_code=:error_code, error_message=:error_message " .
			"where id = :id",
			[
				'id' => $item['id'],
				'status' => $item['status'],
				'error_code' => $item['error_code'],
				'error_message' => $item['error_message'],
			]
		);
		$wpdb->query($sql);
		
		/* Если ошибка импорта */
		if ($item['status'] == Helper::IMPORT_STATUS_ERROR)
		{
			echo "failed\n";
			echo $item['error_message'];
		}
		
		/* Если закончили */
		else if ($item['status'] == Helper::IMPORT_STATUS_DONE)
		{
			/* Удаляем файл */
			if (is_file($file_path))
			{
				@unlink($file_path);
			}
			echo "success";
		}
		
		/* Продолжаем обработку */
		else
		{
			echo "progress\n";
			echo $progress . "\n";
		}
	}
	
	
	
	/**
	 * Flush products import flag
	 */
	static function flushJustImportedFlagProducts()
	{
		global $wpdb;
		/*
		$table_name = $wpdb->base_prefix . "postmeta";
		$sql = "update $table_name where meta_key='product_just_imported' and meta_value!='0' set meta_value='0'";
		$wpdb->query($sql);
		*/
	}
	
	
	
	/**
	 * Загрузка файла в базу
	 */
	static function catalogImportContent($import)
	{
		global $wpdb;
		
		$import_id = $import['id'];
		$session_id = $import['session_id'];
		$filename = $import['filename'];
		
		$file_path = static::getFilePath($session_id, $filename);
		if (!is_file($file_path))
		{
			$item['status'] = Helper::IMPORT_STATUS_ERROR;
			$item['error_code'] = -1;
			$item['error_message'] = 'File not found';
			return $item;
		}
		
		/* Таблицы базы данных */
		$table_name_1c_import = $wpdb->base_prefix . "elberos_commerce_1c_import";
		
		$error_message = 'File not found';
		try
		{
			@ob_start();
			$xml = simplexml_load_file($file_path);
			$error_message = ob_get_contents();
			@ob_end_clean();
		}
		catch (\Exception $e)
		{
			$error_message = $e->getMessage();
		}
		
		/* Обрабатываем xml файл */
		if ($xml)
		{
			$instance = static::createImport();
			$instance->import = $import;
			$instance->xml = $xml;
			$instance->loadContent();
			
			/* Меняем статус */
			$import['status'] = Helper::IMPORT_STATUS_WORK;
			$import['error_code'] = 0;
			$import['error_message'] = '';
		}
		
		/* Set error */
		else
		{
			$import['status'] = Helper::IMPORT_STATUS_ERROR;
			$import['error_code'] = -1;
			$import['error_message'] = $error_message;
		}
		
		return $import;
	}
	
	
	
	/**
	 * Выполнение задач из базы
	 */
	static function catalogImportWork($import)
	{
		$instance = static::createTask();
		$instance->import = $import;
		
		/* Выполняем задачи */
		$instance->run();
		
		$progress = Helper::getTaskProgress($import['id']);
		$total = Helper::getTaskTotal($import['id']);
		$errors = Helper::getTaskError($import['id']);
		$str = $progress . " / " . $total . ". Errors: " . $errors;
		
		/* Если все выполнено */
		if ($progress == $total)
		{
			$import['status'] = Helper::IMPORT_STATUS_DONE;
		}
		
		return [$import, $str];
	}
	
}