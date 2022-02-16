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
	static $task_run_limits = 20;
	
	
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
		add_action('elberos_commerce_1c_products_update_only', 
			'\\Elberos\\Commerce\\_1C\\Import::elberos_commerce_1c_products_update_only');
		add_action('elberos_commerce_1c_offers_update_only', 
			'\\Elberos\\Commerce\\_1C\\Import::elberos_commerce_1c_offers_update_only');
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
				'enable_locale' => false,
				'render' => function ($site)
				{
					return static::actionIndex($site);
				}
			]
		);
		
		$site->add_route
		(
			"api:1c:1c_exchange.php", "/api/1c_exchange.php",
			null,
			[
				'title' => 'Авторизация',
				'description' => 'Авторизация',
				'enable_locale' => false,
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
		@ini_set( 'upload_max_size' , '512M' );
		@ini_set( 'post_max_size', '512M');
		
		$type = isset($_GET['type']) ? $_GET['type'] : "";
		$mode = isset($_GET['mode']) ? $_GET['mode'] : "";
		if (!defined('DOING_AJAX'))
		{
			define('DOING_AJAX', true);
		}
		
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
		
		/* Sale query */
		else if ($type == 'sale' && $mode == 'query')
		{
			static::actionSaleQuery();
		}
		else if ($type == 'sale' && $mode == 'success')
		{
			static::actionSaleSuccess();
		}
		
		/* Invoice upload */
		else if ($type == 'sale' && $mode == 'file')
		{
			static::actionSaleFile();
		}
		
		/* Success */
		else if ($type == 'success')
		{
		}
		
		return null;
	}
	
	
	
	/**
	 * Аутентификация 1С
	 */
	static function actionAuthenticate()
	{
		$login_1c = static::getKey("elberos_commerce_1c_login", "");
		$password_1c = static::getKey("elberos_commerce_1c_password", "");
		
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
		
		$file_default_size = static::getKey("elberos_commerce_1c_file_default_size", "");
		$res[] = $file_default_size * 1024 * 1024;
		
		echo implode("\n", $res) . "\n";
	}
	
	
	
	/**
	 * Загрузка файлов
	 */
	static function actionCatalogUploadFiled()
	{
		$filefolder = ABSPATH . "wp-content/uploads/1c_uploads";
		$file_max_size = static::getKey("elberos_commerce_1c_file_max_size", "");
		$file_max_size_orig = $file_max_size;
		$file_max_size = $file_max_size * 1024 * 1024;
		
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
		if (strpos($filename, "..") !== false)
		{
			echo "failed upload " . $filename;
		}
		else if ($filename != "")
		{
			$filepath = $filefolder . "/" . $filename;
			$filedir = dirname($filepath);
			if (!file_exists($filedir))
			{
				mkdir($filedir, 0755, true);
			}
			
			/* Загружаем файл */
			$content = file_get_contents("php://input");
			$content_sz = strlen($content);
			
			if ($content_sz >= $file_max_size)
			{
				echo "failed upload " . $filename .
					" (" . round($content_sz / 1024 / 1024) . "Mb)" .
					". Max size ${file_max_size_orig}Mb exceed\n";
				echo "size=" . strlen($content);
				return;
			}
			
			file_put_contents($filepath, $content);
			
			echo "success\n";
			echo "size=" . strlen($content);
		}
		else
		{
			echo "failed upload " . $filename;
		}
		
	}
	
	
	
	/**
	 * Обработка файлов
	 */
	static function actionCatalogImport()
	{
		global $wpdb;
		
		//$session_id = session_id();
		$session_id = "0";
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
			"select * from $table_name_1c_import " .
			"where session_id = :session_id and status in (0,2) and filename = :filename and is_deleted = 0 " .
			"order by id asc",
			[
				'session_id' => $session_id,
				'filename' => $filename,
			]
		);
		
		$item = $wpdb->get_row($sql, ARRAY_A);
		$is_created = false;
		$fastcgi_finish = false;
		
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
			$is_created = true;
		}
		
		$progress = "";
		
		/* Если файл запланирован */
		if ($item['status'] == Helper::IMPORT_STATUS_PLAN)
		{
			if ($is_created)
			{
				if (function_exists( 'fastcgi_finish_request' ))
				{
					echo "progress\n";
					session_write_close();
					fastcgi_finish_request();
					$fastcgi_finish = true;
				}
				$item = static::catalogImportContent($item);
			}
			else
			{
				/* Wait message */
				sleep( mt_rand(10, 20) );
				
				$progress = Helper::getTaskProgress($item['id']);
				$total = Helper::getTaskTotal($item['id']);
				$errors = Helper::getTaskError($item['id']);
				$str = $progress . " / " . $total . ". Errors: " . $errors;
				
				echo "progress\n";
				echo $str . "\n";
				
				return;
			}
		}
		
		/* Выполняем задачи */
		else if ($item['status'] == Helper::IMPORT_STATUS_WORK)
		{
			list($item, $progress) = static::catalogImportWork($item);
		}
		
		/* Обновляем запись в базе данных */
		$wpdb->update
		(
			$table_name_1c_import,
			[
				'status' => $item['status'],
				'progress' => $item['progress'],
				'total' => $item['total'],
				'error' => $item['error'],
				'error_code' => $item['error_code'],
				'error_message' => $item['error_message'],
			],
			[
				'id' => $item['id'],
			]
		);
		
		if (!$fastcgi_finish)
		{
			/* Если ошибка импорта */
			if ($item['status'] == Helper::IMPORT_STATUS_ERROR)
			{
				echo "failed\n";
				echo $item['error_message'];
			}
			
			/* Если закончили */
			else if ($item['status'] == Helper::IMPORT_STATUS_DONE)
			{
				static::actionCatalogImportSuccess();
				echo "success";
			}
			
			/* Продолжаем обработку */
			else
			{
				echo "progress\n";
				echo $progress . "\n";
			}
		}
	}
	
	
	
	/**
	 * Успешная загрузка каталога
	 */
	static function actionCatalogImportSuccess()
	{
		global $wpdb;
		
		/* Показываем товары в каталоге, которые были только что загружены */
		$table_name_products = $wpdb->base_prefix . "elberos_commerce_products";
		$sql = "update " . $table_name_products . " set `show_in_catalog` = `just_show_in_catalog`";
		$wpdb->query($sql);
		
		/* Удаляем предложения */
		$table_name_products_offers = $wpdb->base_prefix . "elberos_commerce_products_offers";
		$sql = "delete from " . $table_name_products_offers . " where `prepare_delete` = 1";
		$wpdb->query($sql);
		$table_name_products_offers_prices = $wpdb->base_prefix . "elberos_commerce_products_offers_prices";
		$sql = "delete from " . $table_name_products_offers_prices . " where `prepare_delete` = 1";
		$wpdb->query($sql);
		
		/* Удаляем фотографии */
		$table_name_products_photos = $wpdb->base_prefix . "elberos_commerce_products_photos";
		$sql = "delete from " . $table_name_products_photos . " where `is_deleted` = 1";
		$wpdb->query($sql);
		
		/* Оставляем последние 100 тысяч тасков */
		Helper::deleteOldTask();
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
			$import["total"] = Helper::getTaskTotal($instance->import["id"]);
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
		// $str .= "\nID: " . implode(",", $instance->id);
		
		$import["progress"] = $progress;
		$import["total"] = $total;
		$import["error"] = $errors;
		
		/* Если все выполнено */
		if ($progress == $total)
		{
			$import['status'] = Helper::IMPORT_STATUS_DONE;
		}
		
		return [$import, $str];
	}
	
	
	
	/**
	 * Выгрузка инвойсов
	 */
	static function actionSaleQuery()
	{
		global $wpdb;
		
		$table_name = $wpdb->prefix . "elberos_commerce_invoice";
		$sql = "select * from " . $table_name . " where export_status=0";
		$results = $wpdb->get_results($sql, ARRAY_A);
		$results = array_map
		(
			function ($item)
			{
				$item["utm"] = @json_decode($item["utm"], true);
				$item["basket"] = @json_decode($item["basket"], true);
				$item["delivery"] = @json_decode($item["delivery"], true);
				$item["form_data"] = @json_decode($item["form_data"], true);
				$item["basket_data"] = @json_decode($item["basket_data"], true);
				return $item;
			},
			$results
		);
		
		$xml = "";
		$xml = static::actionSaleQueryMakeXml($results);
		header('Content-Type: application/xml');
		echo $xml;
	}
	
	
	/**
	 * Copy XML
	 */
	static function copyXML($xml, $append)
    {
		if (strlen(trim((string) $append))==0)
		{
			$item = $xml->addChild($append->getName());
			foreach($append->children() as $child)
			{
				static::copyXML($item, $child);
			}
		}
		else
		{
			$item = $xml->addChild($append->getName(), (string) $append);
		}
		foreach ($append->attributes() as $n => $v)
		{
			$item->addAttribute($n, $v);
		}
    }
	
	
	/**
	 * Copy xml childs
	 */
	static function copyXMLChilds($xml, $append)
	{
		foreach ($append->children() as $child)
		{
			static::copyXML($xml, $child);
		}
	}
	
	
	/**
	 * Copy child by name
	 */
	static function copyXMLChild($xml, $append, $name)
	{
		$item = $append->$name;
		if ($item && $item->getName() == $name)
		{
			static::copyXML($xml, $item);
		}
	}
	
	
	/**
	 * Добавляет key value в xml
	 */
	static function addXmlPropKeyValue($xml, $key, $value)
	{
		$item = $xml->addChild('ЗначениеРеквизита');
		$item->addChild('Наименование', $key);
		$item->addChild('Значение', \Elberos\mb_trim($value));
	}
	
	
	/**
	 *
	 */
	static function addProductsCheckService($invoice, $is_service)
	{
		$basket_data = $invoice['basket_data'];
		foreach ($basket_data as $basket_item)
		{
			$product_is_service = isset($basket_item["product_is_service"]) ? $basket_item["product_is_service"] : false;
			if ($product_is_service == $is_service)
			{
				return true;
			}
		}
		
		return false;
	}
	
	
	
	/**
	 * Добавление налогов
	 */
	static function addNalog($doc, $data_nalog)
	{
		$nalog_xml = $doc->addChild('Налоги');
		foreach ($data_nalog as $data)
		{
			$nalog_xml_item = $nalog_xml->addChild('Налог');
			$nalog_xml_item->addChild('Наименование', $data['name']);
			$nalog_xml_item->addChild('УчтеноВСумме', 'true');
			$nalog_xml_item->addChild('Сумма', $data['amount']);
		}
	}
	
	
	
	/**
	 * Товар
	 */
	static function addProducts($doc, $invoice, $is_service, &$data_nalog)
	{
		global $wpdb;
		
		$flag = static::addProductsCheckService($invoice, $is_service);
		if (!$flag) return;
		
		if ($is_service) $products = $doc->addChild('Услуги');
		else $products = $doc->addChild('Товары');
		
		$basket_data = $invoice['basket_data'];
		foreach ($basket_data as $basket)
		{
			$offer_unit = isset($basket["offer_unit"]) ? $basket["offer_unit"] : "";
			$offer_price_id = isset($basket["offer_price_id"]) ? $basket["offer_price_id"] : "";
			$offer_price = isset($basket["offer_price"]) ? (double)$basket["offer_price"] : "";
			$offer_coefficient = isset($basket["offer_coefficient"]) ? $basket["offer_coefficient"] : "";
			$product_name = isset($basket["product_name"]) ? $basket["product_name"] : "";
			$product_code_1c = isset($basket["product_code_1c"]) ? $basket["product_code_1c"] : "";
			$product_count = isset($basket["count"]) ? (double)$basket["count"] : "";
			$product_main_photo_url = isset($basket["product_main_photo_url"]) ? $basket["product_main_photo_url"] : "";
			$product_vendor_code = isset($basket["product_vendor_code"]) ? $basket["product_vendor_code"] : "";
			$product_is_service = isset($basket["product_is_service"]) ? $basket["product_is_service"] : false;
			$discount_value = isset($basket["discount_value"]) ? (double)$basket["discount_value"] : 0;
			$discount_type = isset($basket["discount_type"]) ? $basket["discount_type"] : "percent";
			
			$product_is_service = isset($product_item['is_service']) ? ((bool)$product_item['is_service']) : false;
			if ($product_is_service != $is_service)
			{
				continue;
			}
			
			$product = null;
			if ($product_is_service) $product = $products->addChild('Услуга');
			else $product = $products->addChild('Товар');
			
			/* Find product */
			$table_name_products = $wpdb->base_prefix . "elberos_commerce_products";
			$sql = \Elberos\wpdb_prepare
			(
				"select * from $table_name_products where code_1c = :code_1c limit 1",
				[
					'code_1c' => $product_code_1c,
				]
			);
			$product_row = $wpdb->get_row($sql, ARRAY_A);
			
			if ($product_row)
			{
				$xml = null;
				try
				{
					@ob_start();
					$xml = new \SimpleXMLElement($product_row['xml']);
					@ob_end_clean();
				}
				catch (\Exception $e)
				{
				}
			}
			
			if ($xml)
			{
				static::copyXMLChild($product, $xml, 'Ид');
				static::copyXMLChild($product, $xml, 'Артикул');
				static::copyXMLChild($product, $xml, 'Наименование');
				static::copyXMLChild($product, $xml, 'БазоваяЕдиница');
				static::copyXMLChild($product, $xml, 'СтавкиНалогов');
				$product->addChild('ЦенаЗаЕдиницу', $offer_price);
				$product->addChild('Количество', $product_count);
				$product->addChild('Единица', $offer_unit);
				$product->addChild('Коэффициент', $offer_coefficient);
				
				/* Скидки */
				if ($discount_type == "percent" && $discount_value > 0 && $discount_value <= 100)
				{
					$product->addChild('Сумма', $offer_price * $product_count * (1 - $discount_value/100));
					$xml_discounts = $product->addChild('Скидки', $offer_price);
					$xml_discount = $xml_discounts->addChild('Скидка', $offer_price);
					$xml_discounts->addChild('Процент', $discount_value);
					$xml_discounts->addChild('УчтеноВСумме', true);
				}
				else
				{
					$product->addChild('Сумма', $offer_price * $product_count);
				}
				
				// Значения реквизитов
				$values = $product->addChild('ЗначенияРеквизитов');
				$props_items = $xml->ЗначенияРеквизитов;
				if ($props_items != null && $props_items->getName() == 'ЗначенияРеквизитов')
				{
					foreach ($props_items->children() as $props_item)
					{
						if ($props_item->getName() == 'ЗначениеРеквизита')
						{
							$props_name = (string)$props_item->Наименование;
							$props_value = (string)$props_item->Значение;
							if (in_array($props_name, ['ВидНоменклатуры', 'ТипНоменклатуры']))
							{
								static::addXmlPropKeyValue($values, $props_name, $props_value);
							}
						}
					}
				}
				
				// СтавкиНалогов
				$nalog_xml = $product->addChild('Налоги');
				$nalog_items = $xml->СтавкиНалогов;
				if ($nalog_items != null && $nalog_items->getName() == 'СтавкиНалогов')
				{
					foreach ($nalog_items->children() as $nalog_item)
					{
						if ($nalog_item->getName() == 'СтавкаНалога')
						{
							$nalog_item_name = (string)$nalog_item->Наименование;
							$nalog_item_value = (int)((string)$nalog_item->Ставка);
							if ($nalog_item_value > 0 && $nalog_item_name != "")
							{
								if (!isset($data_nalog[$nalog_item_name]))
								{
									$data_nalog[$nalog_item_name] =
									[
										"name" => $nalog_item_name,
										"amount" => 0,
									];
								}
								$nalog_amount = $offer_price * $product_count * $nalog_item_value / 100;
								$nalog_xml_item = $nalog_xml->addChild('Налог');
								$nalog_xml_item->addChild('Наименование', $nalog_item_name);
								$nalog_xml_item->addChild('УчтеноВСумме', 'true');
								$nalog_xml_item->addChild('Ставка', $nalog_item_value);
								$nalog_xml_item->addChild('Сумма', $nalog_amount);
								$data_nalog[$nalog_item_name]["amount"] += $nalog_amount;
							}
						}
					}
				}
			}
			else
			{
				$product->addChild('Ид', $product_code_1c);
				$product->addChild('Артикул', $product_vendor_code);
				$product->addChild('Наименование', $product_name);
				$product->addChild('ЦенаЗаЕдиницу', $offer_price);
				$product->addChild('Количество', $product_count);
				$product->addChild('Сумма', $offer_price * $product_count);
			}
			
		}
	}
	
	
	/**
	 * Функция создания xml
	 */
	static function actionSaleQueryMakeXml($results)
	{
		$dt = new \DateTime();
		$dt->setTimezone( new \DateTimeZone( \Elberos\get_wp_timezone() ) );
		$content = new \SimpleXMLElement
		(
			'<?xml version="1.0" encoding="UTF-8"?>'.
			'<КоммерческаяИнформация xmlns="urn:1C.ru:commerceml_210" xmlns:xs="http://www.w3.org/2001/XMLSchema" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"></КоммерческаяИнформация>'
		);
		//$content->addAttribute('xmlns', 'urn:1C.ru:commerceml_210');
		//$content->addAttribute('xmlns:xs', 'http://www.w3.org/2001/XMLSchema');
		//$content->addAttribute('xmlns:xsi', 'http://www.w3.org/2001/XMLSchema-instance');
		$content->addAttribute('ВерсияСхемы', '2.08');
		$content->addAttribute('ДатаФормирования', $dt->format('Y-m-d\TH:i:s'));
		
		
		/* Results */
		foreach ($results as $invoice)
		{
			$doc = $content->addChild('Документ');
			static::makeIvoiceDocument($doc, $invoice);
		}
		
		/* Get XML */
		// $xml_content = $content->asXml();
		
		/* Convert to Windows-1251 */
		/*
		$xml_content = str_replace
		(
			'<?xml version="1.0" encoding="UTF-8"?>',
			'<?xml version="1.0" encoding="Windows-1251"?>',
			$xml_content
		);
		$xml_content = iconv('utf-8','windows-1251',$xml_content);
		*/
		
		$dom = dom_import_simplexml($content)->ownerDocument;
		$dom->formatOutput = true;
		$xml_content = $dom->saveXML();
		
		return $xml_content;
	}
	
	
	/**
	 * Функция создания xml
	 */
	static function makeIvoiceDocument($doc, $invoice)
	{
		$doc->addChild('Ид', $invoice['code_1c']);
		$doc->addChild('Номер', "Z-" . $invoice['id']);
		$doc->addChild('Дата', \Elberos\wp_from_gmtime($invoice['gmtime_add'], 'Y-m-d'));
		$doc->addChild('Время', \Elberos\wp_from_gmtime($invoice['gmtime_add'], 'H:i:s'));
		$doc->addChild('ХозОперация', 'Заказ товара');
		$doc->addChild('Роль', 'Продавец');
		$doc->addChild('Сумма', $invoice['price']);
		$doc->addChild('Валюта', 'KZT');
		$doc->addChild('Курс', '1');
		$doc->addChild('Комментарий', \Elberos\mb_trim($invoice['comment']));
		
		$data_nalog = [];
		$form_data = $invoice['form_data'];
		$form_data_type = isset($form_data['type']) ? $form_data['type'] : 1;
		$name = "";
		if ($form_data_type == 1)
		{
			$name = isset($form_data['name']) ? $form_data['name'] : '';
			$surname = isset($form_data['surname']) ? $form_data['surname'] : '';
			$lastname = isset($form_data['lastname']) ? $form_data['lastname'] : '';
			$name = \Elberos\mb_trim($name . ' ' . $surname . ' ' . $lastname);
		}
		else if ($form_data_type == 2)
		{
			$name = \Elberos\mb_trim
			(
				isset($form_data['company_name']) ? $form_data['company_name'] : ''
			);
		}
		
		/* Контрагент */
		$clients = $doc->addChild('Контрагенты');
		$client = $clients->addChild('Контрагент');
		$client->addChild('Ид', $invoice['client_code_1c']);
		$client->addChild('Роль', 'Покупатель');
		$client->addChild('Наименование', $name);
		$client->addChild('ПолноеНаименование', $name);
		
		/* Тип клиента */
		if ($form_data_type == 1)
		{
			if (isset($form_data['user_identifier']))
			{
				$client->addChild('ИНН', \Elberos\mb_trim($form_data['user_identifier']));
			}
			$node = $client->addChild('РеквизитыФизЛица');
			$node->addChild('ПолноеНаименование', 
				\Elberos\mb_trim
				(
					(isset($form_data['surname']) ? $form_data['surname'] : "") . ' ' .
					(isset($form_data['name']) ? $form_data['name'] : "") . ' ' .
					(isset($form_data['lastname']) ? $form_data['lastname'] : "")
				)
			);
			if (isset($form_data['surname']))
			{
				$node->addChild('Фамилия', \Elberos\mb_trim($form_data['surname']));
			}
			if (isset($form_data['name']))
			{
				$node->addChild('Имя', \Elberos\mb_trim($form_data['name']));
			}
			if (isset($form_data['lastname']))
			{
				$node->addChild('Отчество', \Elberos\mb_trim($form_data['lastname']));
			}
			if (isset($form_data['user_identifier']))
			{
				$node->addChild('ИИН', \Elberos\mb_trim($form_data['user_identifier']));
			}
		}
		else if ($form_data_type == 2)
		{
			if (isset($form_data['company_bin']))
			{
				$client->addChild('ИНН', \Elberos\mb_trim($form_data['company_bin']));
			}
			$node = $client->addChild('РеквизитыЮрЛица');
			if (isset($form_data['company_name']))
			{
				$node->addChild('ОфициальноеНаименование', \Elberos\mb_trim($form_data['company_name']) );
			}
			if (isset($form_data['company_bin']))
			{
				$node->addChild('БИН', \Elberos\mb_trim($form_data['company_bin']) );
			}
			if (isset($form_data['company_address']))
			{
				$node->addChild('ЮридическийАдрес', \Elberos\mb_trim($form_data['company_address']) );
			}
		}
		
		// Контакты
		$contacts = $client->addChild('Контакты');
		if (isset($form_data['phone']) && $form_data['phone'] != '')
		{
			$contact = $contacts->addChild('КонтактнаяИнформация');
			$contact->addChild('КонтактВид', 'Телефон мобильный');
			$contact->addChild('Значение', $form_data['phone']);
			$contact->addChild('Комментарий');
		}
		if (isset($form_data['email']) && $form_data['email'] != '')
		{
			$contact = $contacts->addChild('КонтактнаяИнформация');
			$contact->addChild('КонтактВид', 'E-mail');
			$contact->addChild('Значение', $form_data['email']);
			$contact->addChild('Комментарий');
		}
		
		// Адрес
		/*
		if ($invoice['delivery'])
		{
			$address = $client->addChild('Адрес');
			$address->addChild('Представление', $invoice['delivery']['address']);
			$address->addChild('Комментарий', $invoice['delivery']['comment']);
		}
		*/
		
		// Товары
		static::addProducts($doc, $invoice, false, $data_nalog);
		
		// Услуги
		static::addProducts($doc, $invoice, true, $data_nalog);
		
		// Налог
		static::addNalog($doc, $data_nalog);
		
		// Apply action
		do_action
		(
			'elberos_commerce_1c_make_ivoice_document',
			[
				'xml'=>$doc,
				'invoice'=>$invoice,
			]
		);
	}
	
	
	
	/**
	 * Выгрузка инвойсов
	 */
	static function actionSaleSuccess()
	{
		global $wpdb;
		$table_name = $wpdb->prefix . "elberos_commerce_invoice";
		$sql = $wpdb->prepare
		(
			"update " . $table_name . " set export_status=1 where export_status=0"
		);
		$wpdb->query($sql);
		echo "success";
	}
	
	
	
	/**
	 * Загрузка инвойсов
	 */
	static function actionSaleFile()
	{
		global $wpdb;
		
		/* Оставляем последние 100 тысяч тасков */
		Helper::deleteOldTask();
		
		/* Читаем xml */
		$content = file_get_contents("php://input");
		$error_message = null;
		
		try
		{
			@ob_start();
			$xml = new \SimpleXMLElement($content);
			@ob_end_clean();
		}
		catch (\Exception $e){
			$error_message = $e->getMessage();
		}
		
		if ($error_message != null)
		{
			echo "error" . "\n" . $error_message;
			return;
		}
		
		if ($xml != null)
		{
			$invoices = [];
			foreach ($xml->children() as $item)
			{
				if ($item->getName() == 'Документ')
				{
					$invoices[] = \Elberos\mb_trim( (string)$item->Номер );
				}
			}
			
			$table_name_1c_task = $wpdb->base_prefix . "elberos_commerce_1c_task";
			
			/* Insert task */
			$res = apply_filters
			(
				'elberos_commerce_1c_insert_task',
				[
					'xml'=>$item,
					'data'=>
					[
						"name" => "Invoices: " . implode(", ", $invoices),
						"code_1c" => "",
						"import_id" => null,
						"catalog_id" => 0,
						"classifier_id" => 0,
						"type" => "invoices",
						"data" => (string) $content,
						"status" => Helper::TASK_STATUS_WORK,
						"gmtime_add" => gmdate("Y-m-d H:i:s", time()),
					]
				]
			);
			$insert_data = $res["data"];
			
			$wpdb->insert
			(
				$table_name_1c_task,
				$insert_data
			);
			
			$error_message = "";
			$task_id = $wpdb->insert_id;
			
			/* Try to update invoices */
			try
			{
				foreach ($xml->children() as $item)
				{
					if ($item->getName() == 'Документ')
					{
						$invoice_number = \Elberos\mb_trim( (string)$item->Номер );
						
						/* Find invoice */
						$table_name_invoice = $wpdb->base_prefix . "elberos_commerce_invoice";
						$sql = \Elberos\wpdb_prepare
						(
							"select * from $table_name_invoice where id = :id",
							[
								'id' => $invoice_number,
							]
						);
						$invoice = $wpdb->get_row($sql, ARRAY_A);
						
						if ($invoice)
						{
							$update_data = [];
							
							/* Json decode */
							$invoice["form_data"] = @json_decode($invoice["form_data"], true);
							$invoice["basket_data"] = @json_decode($invoice["basket_data"], true);
							$invoice["utm"] = @json_decode($invoice["utm"], true);
							if (!$invoice["utm"]) $invoice["utm"] = [];
							if (!$invoice["form_data"]) $invoice["form_data"] = [];
							if (!$invoice["basket_data"]) $invoice["basket_data"] = [];
							
							$update_data["form_data"] = $invoice["form_data"];
							$update_data["basket_data"] = $invoice["basket_data"];
							$update_data["utm"] = $invoice["utm"];
							
							list($invoice, $item, $update_data) = static::updateInvoiceClientData
							(
								$invoice, $item, $update_data
							);
							list($invoice, $item, $update_data) = static::updateInvoiceBasketData
							(
								$invoice, $item, $update_data, false
							);
							list($invoice, $item, $update_data) = static::updateInvoiceBasketData
							(
								$invoice, $item, $update_data, true
							);
							
							/* Update invoice */
							$params = apply_filters
							(
								'elberos_commerce_1c_update_ivoice',
								[
									'xml'=>$xml,
									'invoice'=>$invoice,
									'update_data'=>$update_data,
								]
							);
							$update_data = $params["update_data"];
							
							/* Json encode */
							$update_data["form_data"] = json_encode($update_data["form_data"]);
							$update_data["basket_data"] = json_encode($update_data["basket_data"]);
							$update_data["utm"] = json_encode($update_data["utm"]);
							
							$wpdb->update
							(
								$table_name_invoice,
								$update_data,
								[
									'id' => $invoice_number,
								]
							);
						}
						
					}
				}
			}
			
			catch (\Exception $e)
			{
				$error_message = $e->getMessage();
			}
			
			$update_task =
			[
				"error_code" => 1,
				"status" => Helper::TASK_STATUS_DONE,
			];
			
			/* If error */
			if ($error_message != "")
			{
				$update_task['status'] = Helper::TASK_STATUS_ERROR;
				$update_task['error_code'] = -1;
				$update_task['error_message'] = $error_message;
			}
			
			/* Update task */
			$wpdb->update
			(
				$table_name_1c_task,
				$update_task,
				[
					"id" => $task_id
				]
			);
		}
		
		echo "success";
	}
	
	
	
	/**
	 * Update invoice client data
	 */
	static function updateInvoiceClientData($invoice, $xml, $update_data)
	{
		$form_data = $update_data["form_data"];
		$update_data["comment"] = \Elberos\mb_trim( (string) $xml->Комментарий );
		
		// Контрагенты
		$arr = $xml->Контрагенты;
		if ($arr != null && $arr->getName() == 'Контрагенты')
		{
			foreach ($arr->children() as $contragent)
			{
				if ($contragent->getName() != 'Контрагент')
				{
					continue;
				}
				
				// Физ лицо
				$agent = $contragent->РеквизитыФизЛица;
				if ($agent != null && $agent->getName() == 'РеквизитыФизЛица')
				{
					$form_data["type"] = 1;
					$form_data["name"] = \Elberos\mb_trim( (string)$agent->Имя );
					$form_data["surname"] = \Elberos\mb_trim( (string)$agent->Фамилия );
					$form_data["lastname"] = \Elberos\mb_trim( (string)$agent->Отчество );
					
				}
				
				// Юр лицо
				$agent = $contragent->РеквизитыЮрЛица;
				if ($agent != null && $agent->getName() == 'РеквизитыЮрЛица')
				{
					$form_data["type"] = 2;
					$form_data["company_name"] = \Elberos\mb_trim( (string)$agent->ОфициальноеНаименование );
					$form_data["company_bin"] = \Elberos\mb_trim( (string)$agent->БИН );
					$form_data["company_address"] = \Elberos\mb_trim( (string)$agent->ЮридическийАдрес );
				}
				
				// Контакты
				$arr_contact = $contragent->Контакты;
				if ($arr_contact != null && $arr_contact->getName() == 'Контакты')
				{
					foreach ($arr_contact->children() as $contact)
					{
						if ($contact->getName() != 'КонтактнаяИнформация')
						{
							continue;
						}
						
						$name = \Elberos\mb_trim( (string)$contact->КонтактВид );
						$value = \Elberos\mb_trim( (string)$contact->Значение );
						
						if ($name == 'Телефон мобильный')
						{
							$form_data["phone"] = $value;
						}
						
						else if ($name == 'E-mail')
						{
							$form_data["email"] = $value;
						}
						
					}
				}
				
				$client_code_1c = \Elberos\mb_trim( (string)$contragent->Ид );
				$res = apply_filters
				(
					'elberos_commerce_basket_find_client_by_code_1c',
					[
						"client_id" => null,
						"client_code_1c" => $client_code_1c,
					]
				);
				
				$update_data["client_id"] = $res["client_id"];
				$update_data["client_code_1c"] = $client_code_1c;
				$update_data["form_data"] = $form_data;
				
				continue;
			}
		}
		
		return [$invoice, $xml, $update_data];
	}
	
	
	
	/**
	 * Update invoice basket data
	 */
	static function updateInvoiceBasketData($invoice, $xml, $update_data, $is_service)
	{
		/* Цена */
		$update_data["price"] = \Elberos\mb_trim( (string)$xml->Сумма );
		
		$name1 = 'Товары';
		$name2 = 'Товар';
		
		/* Услуга */
		if ($is_service)
		{
			$name1 = 'Услуги';
			$name2 = 'Услуга';
		}
		
		$arr = $xml->$name1;
		if ($arr != null && $arr->getName() == $name1)
		{
			foreach ($arr->children() as $product_xml)
			{
				if ($product_xml->getName() != $name2)
				{
					continue;
				}
				
				$update_data = static::updateInvoiceProduct($invoice, $update_data, $product_xml);
				
			}
		}
		
		return [$invoice, $xml, $update_data];
	}
	
	
	
	/**
	 * Update product
	 */
	static function updateInvoiceProduct($invoice, $update_data, $xml)
	{
		$basket_data = isset($update_data["basket_data"]) ? $update_data["basket_data"] : [];
		$find = false;
		
		foreach ($basket_data as $key => $arr)
		{
			$arr_product_code_1c = $arr["product_code_1c"];
			$xml_product_code_1c = \Elberos\mb_trim( (string)$xml->Ид );
			
			if ($xml_product_code_1c == $arr_product_code_1c)
			{
				$find = true;
				$arr = static::updateInvoiceProductItem($xml, $arr);
				$basket_data[$key] = array_merge($basket_data[$key], $arr);
				//var_dump($basket_data[$key]);
			}
		}
		
		if (!$find)
		{
			$basket_data[] = static::updateInvoiceProductItem($xml, []);
		}
		
		$update_data["basket_data"] = $basket_data;
		
		return $update_data;
	}
	
	
	
	/**
	 * Update product
	 */
	static function updateInvoiceProductItem($xml, $arr)
	{
		global $wpdb;
		
		$arr["product_code_1c"] = \Elberos\mb_trim( (string)$xml->Ид );
		$arr["product_vendor_code"] = \Elberos\mb_trim( (string)$xml->Артикул );
		$arr["product_name"] = \Elberos\mb_trim( (string)$xml->Наименование );
		$arr["offer_price"] = \Elberos\mb_trim( (string)$xml->ЦенаЗаЕдиницу );
		$arr["count"] = \Elberos\mb_trim( (string)$xml->Количество );
		
		/* Find product */
		$table_name_products = $wpdb->base_prefix . "elberos_commerce_products";
		$sql = \Elberos\wpdb_prepare
		(
			"select * from $table_name_products where code_1c = :code_1c limit 1",
			[
				'code_1c' => $arr["product_code_1c"],
			]
		);
		$product_row = $wpdb->get_row($sql, ARRAY_A);
		if ($product_row)
		{
			$arr["product_main_photo_id"] = $product_row["main_photo_id"];
			$arr["product_main_photo_url"] = \Elberos\get_image_url($arr["product_main_photo_id"], "medium");
			$arr["product_vendor_code"] = $product_row["vendor_code"];
			$arr["product_name"] = $product_row["name"];
		}
		else
		{
			$arr["product_main_photo_id"] = 0;
			$arr["product_main_photo_url"] = "";
		}
	
		$res = apply_filters
		(
			'elberos_commerce_1c_update_ivoice_product_item',
			[
				'xml'=>$xml,
				'arr'=>$arr,
			]
		);
		
		$arr = $res["arr"];
		
		return $arr;
	}
}