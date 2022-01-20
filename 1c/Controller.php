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
		$res[] = static::$max_size;
		
		echo implode("\n", $res) . "\n";
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
			"where session_id = :session_id and status in (0,2) and filename = :filename " .
			"order by id asc",
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
			static::actionSuccess();
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
	 * Успешная загрузка
	 */
	static function actionSuccess()
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
		$str .= ". ID: " . implode(",", $instance->id);
		
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
	 * Append XML
	 */
	static function appendXML($xml, $append)
    {
		if (strlen(trim((string) $append))==0)
		{
			$item = $xml->addChild($append->getName());
			foreach($append->children() as $child)
			{
				static::appendXML($item, $child);
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
	 * Append xml childs
	 */
	static function appendXMLChilds($xml, $append)
	{
		foreach ($append->children() as $child)
		{
			static::appendXML($xml, $child);
		}
	}
	
	
	/**
	 * Add child by name
	 */
	static function appendXMLChild($xml, $append, $name)
	{
		$item = $append->$name;
		if ($item && $item->getName() == $name)
		{
			static::appendXML($xml, $item);
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
		$flag = static::addProductsCheckService($invoice, $is_service);
		if (!$flag) return;
		
		if ($is_service) $products = $doc->addChild('Услуги');
		else $products = $doc->addChild('Товары');
		
		$basket_data = $invoice['basket_data'];
		foreach ($basket_data as $basket)
		{
			$offer_unit = isset($basket["offer_unit"]) ? $basket["offer_unit"] : "";
			$offer_price_id = isset($basket["offer_price_id"]) ? $basket["offer_price_id"] : "";
			$offer_price = isset($basket["offer_price"]) ? $basket["offer_price"] : "";
			$offer_coefficient = isset($basket["offer_coefficient"]) ? $basket["offer_coefficient"] : "";
			$product_name = isset($basket["product_name"]) ? $basket["product_name"] : "";
			$product_code_1c = isset($basket["product_code_1c"]) ? $basket["product_code_1c"] : "";
			$product_count = isset($basket["count"]) ? $basket["count"] : "";
			$product_main_photo_url = isset($basket["product_main_photo_url"]) ? $basket["product_main_photo_url"] : "";
			$product_vendor_code = isset($basket["product_vendor_code"]) ? $basket["product_vendor_code"] : "";
			$product_is_service = isset($basket["product_is_service"]) ? $basket["product_is_service"] : false;
			
			$product_is_service = isset($product_item['is_service']) ? ((bool)$product_item['is_service']) : false;
			if ($product_is_service != $is_service)
			{
				continue;
			}
			
			$product = null;
			if ($product_is_service) $product = $products->addChild('Услуга');
			else $product = $products->addChild('Товар');
			
			$xml = null;
			try
			{
				@ob_start();
				$xml = new \SimpleXMLElement($basket['product_xml']);
				@ob_end_clean();
			}
			catch (\Exception $e)
			{
			}
			
			if ($xml)
			{
				static::appendXMLChild($product, $xml, 'Ид');
				static::appendXMLChild($product, $xml, 'Артикул');
				static::appendXMLChild($product, $xml, 'Наименование');
				static::appendXMLChild($product, $xml, 'БазоваяЕдиница');
				static::appendXMLChild($product, $xml, 'СтавкиНалогов');
				$product->addChild('ЦенаЗаЕдиницу', $offer_price);
				$product->addChild('Количество', $product_count);
				$product->addChild('Сумма', $offer_price * $product_count);
				$product->addChild('Единица', $offer_unit);
				$product->addChild('Коэффициент', $offer_coefficient);
				
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
		$content = new \SimpleXMLElement
		(
			'<?xml version="1.0" encoding="UTF-8"?>'.
			'<КоммерческаяИнформация xmlns="urn:1C.ru:commerceml_210" xmlns:xs="http://www.w3.org/2001/XMLSchema" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"></КоммерческаяИнформация>'
		);
		//$content->addAttribute('xmlns', 'urn:1C.ru:commerceml_210');
		//$content->addAttribute('xmlns:xs', 'http://www.w3.org/2001/XMLSchema');
		//$content->addAttribute('xmlns:xsi', 'http://www.w3.org/2001/XMLSchema-instance');
		$content->addAttribute('ВерсияСхемы', '2.08');
		$content->addAttribute('ДатаФормирования', ( new \DateTime() )->format('c'));
		
		
		/* Results */
		foreach ($results as $invoice)
		{
			$doc = $content->addChild('Документ');
			$doc->addChild('Ид', $invoice['code_1c']);
			$doc->addChild('Номер', $invoice['id']);
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
			$name = "";
			if ($form_data['type'] == 1)
			{
				$name = isset($form_data['name']) ? $form_data['name'] : '';
				$surname = isset($form_data['surname']) ? $form_data['surname'] : '';
				$lastname = isset($form_data['lastname']) ? $form_data['lastname'] : '';
				$name = \Elberos\mb_trim($name . ' ' . $surname . ' ' . $lastname);
			}
			else if ($form_data['type'] == 2)
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
			if ($form_data['type'] == 1)
			{
				$client->addChild('ИНН', \Elberos\mb_trim($form_data['user_identifier']));
				$node = $client->addChild('РеквизитыФизЛица');
				$node->addChild('ПолноеНаименование', 
					\Elberos\mb_trim
					(
						$form_data['surname'] . ' ' .
						$form_data['name'] . ' ' .
						$form_data['lastname']
					)
				);
				$node->addChild('Фамилия', \Elberos\mb_trim($form_data['surname']));
				$node->addChild('Имя', \Elberos\mb_trim($form_data['name']));
				$node->addChild('Отчество', \Elberos\mb_trim($form_data['lastname']));
				$node->addChild('ИИН', \Elberos\mb_trim($form_data['user_identifier']));
			}
			else if ($form_data['type'] == 2)
			{
				$client->addChild('ИНН', \Elberos\mb_trim($form_data['company_bin']));
				$node = $client->addChild('РеквизитыЮрЛица');
				$node->addChild('ОфициальноеНаименование', \Elberos\mb_trim($form_data['company_name']) );
				$node->addChild('БИН', \Elberos\mb_trim($form_data['company_bin']) );
				$node->addChild('ЮридическийАдрес', \Elberos\mb_trim($form_data['company_address']) );
			}
			
			// Контакты
			$contacts = $client->addChild('Контакты');
			if ($form_data['phone'] != '')
			{
				$contact = $contacts->addChild('КонтактнаяИнформация');
				$contact->addChild('КонтактВид', 'Телефон мобильный');
				$contact->addChild('Значение', $form_data['phone']);
				$contact->addChild('Комментарий');
			}
			if ($form_data['email'] != '')
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
			
			// Значения реквизитов
			static::addPropsUF($doc, $invoice);
		}
		
		/* Get XML */
		$xml_content = $content->asXml();
		
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
		
		return $xml_content;
	}
	
	
	
	/**
	 * Реквизиты для управления фирмой
	 */
	static function addPropsUF($doc, $invoice)
	{
		$values = $doc->addChild('ЗначенияРеквизитов');
		
		// Оплата заказа
		if (mb_strtolower($invoice['status_pay']) == 'paid')
		{
			static::addXmlPropKeyValue($values, 'Оплачен', 'true');
			$dt = \Elberos\create_date_from_string($invoice['gmtime_pay']);
			static::addXmlPropKeyValue($values, 'Дата оплаты', $dt ? $dt->format('c') : '' );
			static::addXmlPropKeyValue($values, 'Тип оплаты', $invoice['method_pay_text']);
			
			// Метод оплаты
			if ($invoice['method_pay_text'] == '') static::addXmlPropKeyValue($values, 'Метод оплаты', '');
			else static::addXmlPropKeyValue($values, 'Метод оплаты', $invoice['method_pay_text']);
		}
		else static::addXmlPropKeyValue($values, 'Оплачен', 'false');
		
		// Дата по 1С
		$dt = \Elberos\create_date_from_string($invoice['gmtime_add']);
		static::addXmlPropKeyValue($values, 'Дата по 1С', $dt ? $dt->format('c') : '');
		
		// Дата оплаты по 1С
		$dt = \Elberos\create_date_from_string($invoice['gmtime_pay']);
		static::addXmlPropKeyValue($values, 'Дата оплаты по 1С', $dt ? $dt->format('c') : 'T');
		
		// Параметры инвойса
		static::addXmlPropKeyValue($values, 'ПометкаУдаления', 'false');
		static::addXmlPropKeyValue($values, 'Проведен', 'true');
		static::addXmlPropKeyValue($values, 'Отгружен', 'false');
	}
	
	
	/**
	 * Реквизиты для управления торговлей
	 */
	static function addPropsUT($doc, $invoice)
	{
		$values = $doc->addChild('ЗначенияРеквизитов');
		
		// Оплата заказа
		if ($invoice['status_pay'] == 'paid')
		{
			static::addXmlPropKeyValue($values, 'Заказ оплачен', 'true');
			$dt = \Elberos\create_date_from_string($invoice['gmtime_pay']);
			static::addXmlPropKeyValue($values, 'Дата оплаты', $dt ? $dt->format('c') : '' );
			static::addXmlPropKeyValue($values, 'Тип оплаты', $invoice['method_pay_text']);
			
			// Метод оплаты
			if ($invoice['method_pay_text'] == '') static::addXmlPropKeyValue($values, 'Метод оплаты', '');
			else static::addXmlPropKeyValue($values, 'Метод оплаты', $invoice['method_pay_text']);
		}
		else static::addXmlPropKeyValue($values, 'Заказ оплачен', 'false');
		
		// Статус заказа
		if ($invoice['status'] == 'final') static::addXmlPropKeyValue($values, 'Финальный статус', 'true');
		else static::addXmlPropKeyValue($values, 'Финальный статус', 'false');
		
		$status = mb_strtolower($invoice['status']);
		if ($status == 'new') static::addXmlPropKeyValue($values, 'Статус заказа', 'Новый');
		else if ($status == 'accepted') static::addXmlPropKeyValue($values, 'Статус заказа', 'Акцептован');
		else if ($status == 'shipped') static::addXmlPropKeyValue($values, 'Статус заказа', 'Отгружен');
		else if ($status == 'delivered') static::addXmlPropKeyValue($values, 'Статус заказа', 'Доставлен');
		else if ($status == 'final') static::addXmlPropKeyValue($values, 'Статус заказа', 'Завершен');
		else if ($status == 'cancel') static::addXmlPropKeyValue($values, 'Статус заказа', 'Отменен');
		else static::addXmlPropKeyValue($values, 'Статус заказа', 'Неверный статус'); 
		
		// Отменен
		if ($invoice['status'] == 'cancel') static::addXmlPropKeyValue($values, 'Отменен', 'true');
		else static::addXmlPropKeyValue($values, 'Отменен', 'false');
		
		// Доставка
		static::addXmlPropKeyValue($values, 'Доставка разрешена', 'false');
		static::addXmlPropKeyValue($values, 'Тип доставки', $invoice['delivery']['type']);
		static::addXmlPropKeyValue($values, 'Адрес доставки', $invoice['delivery']['address']);
		
		$dt = \Elberos\create_date_from_string($invoice['gmtime_change']);
		static::addXmlPropKeyValue($values, 'Дата изменения статуса', $dt ? $dt->format('c') : '');
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
		$content = file_get_contents("php://input");
		
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
			foreach ($xml->children() as $item)
			{
				if ($item->getName() == 'Документ')
				{
					$invoice_number = \Elberos\mb_trim( (string)$item->Номер );
				}
			}
		}
		
		echo "success";
	}
}