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

class Task
{
	var $import = null;
	var $task_count = 0;
	var $task_run_limits = 10;
	
	
	/**
	 * Возвращает guid
	 */
	static function getGUID($post_type, $id)
	{
		$guid_prefix = site_url("/");
		return $guid_prefix . "?post_type=" . $post_type . "&p=" .$id;
	}
	
	
	
	/**
	 * Выполняем задачи
	 */
	function run()
	{
		global $wpdb;
		
		$table_name_1c_task = $wpdb->base_prefix . "elberos_commerce_1c_task";
		$sql = \Elberos\wpdb_prepare
		(
			"select * from $table_name_1c_task " .
			"where import_id = :import_id and status = :status order by id asc limit " . $this->task_run_limits,
			[
				'import_id' => $this->import['id'],
				'status' => Helper::TASK_STATUS_PLAN,
			]
		);
		$tasks = $wpdb->get_results($sql, ARRAY_A);
		foreach ($tasks as $task)
		{
			try
			{
				$task = $this->runTask($task);
			}
			catch (\Exception $e)
			{
				$task['status'] = Helper::TASK_STATUS_ERROR;
				$task['error_code'] = -1;
				$task['error_message'] = $e->getMessage();
			}
			
			/* Обновляем статус задачи */
			$sql = \Elberos\wpdb_prepare
			(
				"update $table_name_1c_task " .
				"set status=:status, error_code=:error_code, error_message=:error_message, gmtime_end=:gmtime_end " .
				"where id = :id",
				[
					'id' => $task['id'],
					'status' => $task['status'],
					'error_code' => $task['error_code'],
					'error_message' => $task['error_message'],
					'gmtime_end' => $task['gmtime_end'],
				]
			);
			$wpdb->query($sql);
			
		}
	}
	
	
	
	/**
	 * Выполняем задачу
	 */
	function runTask($task)
	{
		$xml = null;
		$error_message = '';
		try
		{
			@ob_start();
			$xml = new \SimpleXMLElement($task['data']);
			$error_message = ob_get_contents();
			@ob_end_clean();
		}
		catch (\Exception $e)
		{
			$error_message = $e->getMessage();
		}
		
		if ($error_message != "")
		{
			$task['status'] = Helper::TASK_STATUS_ERROR;
			$task['error_code'] = -1;
			$task['error_message'] = $error_message;
		}
		else if ($task["type"] == "category")
		{
			$task = $this->importCategory($task, $xml);
		}
		else if ($task["type"] == "product")
		{
			$task = $this->importProduct($task, $xml);
		}
		
		else if ($task["type"] == "product_param")
		{
			$task = $this->importProductParam($task, $xml);
		}
		
		else if ($task["type"] == "price_type")
		{
			$task = $this->importPriceType($task, $xml);
		}
		
		else if ($task["type"] == "warehouse")
		{
			$task = $this->importWarehouse($task, $xml);
		}
		
		else if ($task["type"] == "offer")
		{
			$task = $this->importOffer($task, $xml);
		}
		
		else
		{
			$task['status'] = Helper::TASK_STATUS_ERROR;
			$task['error_code'] = -1;
			$task['error_message'] = "Unknown type " . $task["type"];
		}
		
		$task['gmtime_end'] = gmdate("Y-m-d H:i:s", time());
		
		return $task;
	}
	
	
	
	/* --------------------------------- Категории --------------------------------- */
	
	
	/**
	 * Загружаем категорию в базу
	 */
	function importCategory($task, $xml)
	{
		global $wpdb;
		
		$table_name_categories = $wpdb->base_prefix . "elberos_commerce_categories";
		$xml_str = $task['data'];
		$code_1c = (string)$xml->Ид;
		$parent_category_code_1c = (string)$xml->ParentID;
		$parent_category_id = 0;
		
		/* Ищем parent категорию */
		if ($parent_category_code_1c != "")
		{
			$sql = \Elberos\wpdb_prepare
			(
				"select * from $table_name_categories " .
				"where code_1c = :code_1c limit 1",
				[
					"code_1c" => $parent_category_code_1c,
				]
			);
			$parent_category = $wpdb->get_row($sql, ARRAY_A);
			if ($parent_category)
			{
				$parent_category_id = $parent_category['id'];
			}
		}
		
		/* Получаем название категории */
		$names = Helper::getNamesByXml($xml, 'Наименование');
		$name_ru = isset($names['ru']) ? $names['ru'] : '';
		
		/* Вставляем категорию в базу данных */
		$category = \Elberos\wpdb_insert_or_update
		(
			$table_name_categories,
			[
				"code_1c" => $code_1c,
			],
			[
				"parent_category_id" => $parent_category_id,
				"classifier_id" => $task["classifier_id"],
				"code_1c" => $code_1c,
				"name" => $name_ru,
				"xml" => $xml_str,
				"gmtime_1c_change" => gmdate("Y-m-d H:i:s"),
			]
		);
		
		/* Код 1с */
		/* $task["code_1c"] = $code_1c; */
		
		/* Отмечаем задачу как обработанную */
		$task["error_code"] = 1;
		$task["status"] = Helper::TASK_STATUS_DONE;
		
		return $task;
	}
	
	
	
	/**
	 * Вызов функции после обновления категории
	 */
	public function importCategoryAfter($task, $category, $xml)
	{
		return [$task, $category];
	}
	
	
	
	/* ----------------------------------- Товары ---------------------------------- */
	
	
	/**
	 * Загружаем товар в базу
	 */
	function importProduct($task, $xml)
	{
		global $wpdb;
		
		$xml_str = $task['data'];
		$code_1c = (string)$xml->Ид;
		$vendor_code = (string)$xml->Артикул;
		
		/* Получаем название товара */
		$names = Helper::getNamesByXml($xml, 'Наименование');
		$name_ru = isset($names['ru']) ? $names['ru'] : '';
		
		/* Получаем описание товара */
		$descriptions = Helper::getNamesByXml($xml, 'Описание');
		$description_ru = isset($descriptions['ru']) ? $descriptions['ru'] : '';
		
		/* Текст */
		$text =
		[
			"ru_RU" =>
			[
				"name" => $name_ru,
				"description" => $description_ru,
			],
		];
		
		/* Вставляем товар в базу данных */
		$table_name_products = $wpdb->base_prefix . "elberos_commerce_products";
		$product = \Elberos\wpdb_insert_or_update
		(
			$table_name_products,
			[
				"code_1c" => $code_1c,
			],
			[
				"catalog_id" => $task["catalog_id"],
				"code_1c" => $code_1c,
				"vendor_code" => $vendor_code,
				"text" => json_encode($text),
				"name" => $name_ru,
				"xml" => $xml_str,
				"gmtime_1c_change" => gmdate("Y-m-d H:i:s"),
			]
		);
		
		/* Вставка групп */
		$table_name_categories = $wpdb->base_prefix . "elberos_commerce_products_categories";
		$sql = \Elberos\wpdb_prepare
		(
			"delete from $table_name_categories where product_id=:product_id",
			[
				"product_id" => $product["id"],
			]
		);
		$wpdb->query($sql);
		
		$groups = $xml->Группы;
		foreach ($groups->children() as $group)
		{
			$group_code_1c = (string)$group;
			$category = Helper::findCategoryByCode($group_code_1c);
			if ($category)
			{
				$wpdb->insert
				(
					$table_name_categories,
					[
						"product_id" => $product["id"],
						"category_id" => $category["id"],
					]
				);
			}
		}
		
		/* Загрузка фото */
		$pos = 0;
		$main_photo_id = null;
		$images = $xml->Картинка;
		foreach ($images as $image)
		{
			$photo_id = $this->importProductImage($product, $image, $pos);
			if ($photo_id)
			{
				$main_photo_id = $photo_id;
			}
			$pos++;
		}
		
		/* Обновляем id фото */
		if ($main_photo_id)
		{
			$wpdb->update
			(
				$table_name_products,
				[
					"main_photo_id" => $main_photo_id,
				],
				[ "id" => $product["id"] ]
			);
		}
		else
		{
			$wpdb->update
			(
				$table_name_products,
				[
					"main_photo_id" => $main_photo_id,
				],
				[ "id" => $product["id"] ]
			);
		}
		
		/* Код 1с */
		/* $task["code_1c"] = $code_1c; */
		
		/* Отмечаем задачу как обработанную */
		$task["error_code"] = 1;
		$task["status"] = Helper::TASK_STATUS_DONE;
		
		return $task;
	}
	
	
	
	/**
	 * Загрузка картинки
	 */
	public function importProductImage($product, $xml, $pos)
	{
		global $wpdb;
		
		$session_id = session_id();
		$image_path = (string)$xml;
		$image_path_full = Controller::getFilePath($session_id, $image_path);
		
		/* Удаление фото */
		$table_name_products_photos = $wpdb->base_prefix . "elberos_commerce_products_photos";
		$sql = \Elberos\wpdb_prepare
		(
			"delete from $table_name_products_photos where product_id=:product_id",
			[
				"product_id" => $product["id"],
			]
		);
		$wpdb->query($sql);
		
		if (is_file($image_path_full))
		{
			$sha1 = sha1_file($image_path_full);
			$sql = \Elberos\wpdb_prepare
			(
				"select * from " . $wpdb->base_prefix . "postmeta " .
				"where meta_key='file_sha1' and meta_value=:meta_value limit 1",
				[
					"meta_value" => $sha1,
				]
			);
			$row = $wpdb->get_row($sql, ARRAY_A);
			$photo_id = 0;
			
			/* Найден файл */
			if ($row)
			{
				$photo_id = $row["post_id"];
			}
			
			/* Загружаем файл, если не найден */
			else
			{
				$file_content = file_get_contents($image_path_full);
				$new_file_name = basename($image_path_full);
				$wp_filetype = wp_check_filetype($new_file_name, null );
				$upload = wp_upload_bits( $new_file_name, null, $file_content );
				
				/* Если успешно загружен */
				if ( !$upload['error'] )
				{
					$file_url = $upload['url'];
					$attachment = array
					(
						'post_date' => date('Y-m-d H:i:s'),
						'post_date_gmt' => gmdate('Y-m-d H:i:s'),
						'post_title' => $new_file_name,
						'post_status' => 'inherit',
						'comment_status' => 'closed',
						'ping_status' => 'closed',
						'post_name' => $new_file_name,
						'post_modified' => date('Y-m-d H:i:s'),
						'post_modified_gmt' => gmdate('Y-m-d H:i:s'),
						'post_type' => 'attachment',
						'guid' => $file_url,
						'post_mime_type' => $wp_filetype['type'],
						'post_excerpt' => '',
						'post_content' => ''
					);
					
					$photo_id = wp_insert_attachment( $attachment, $filename );
					update_post_meta( $photo_id, 'file_sha1', $sha1 );
					
					require_once ABSPATH . 'wp-admin/includes/image.php';
					
					/* Обновляем метаданные */
					update_attached_file( $photo_id, $upload['file'] );
					\wp_update_attachment_metadata
					(
						$photo_id, \wp_generate_attachment_metadata( $photo_id, $upload['file'] )
					);
				}
			}
			
			/* Загрузка картинки */
			$wpdb->insert
			(
				$table_name_products_photos,
				[
					"product_id" => $product["id"],
					"photo_id" => $photo_id,
					"pos" => $pos,
				]
			);
		}
		
		return $photo_id;
	}
	
	
	
	/**
	 * Вызов функции после обновления товара
	 */
	public function importProductAfter($task, $product, $xml)
	{
		return [$task, $product];
	}
	
	
	
	/* ------------------------------ Параметры товаров ---------------------------- */
	
	
	/**
	 * Загружаем параметр товара в базу
	 */
	public function importProductParam($task, $xml)
	{
		global $wpdb;
		
		$xml_str = $task['data'];
		$classifier_id = $task['classifier_id'];
		$code_1c = (string)$xml->Ид;
		
		/* Название таблиц */
		$table_name_products_params = $wpdb->base_prefix . "elberos_commerce_products_params";
		$table_name_products_params_values = $wpdb->base_prefix . "elberos_commerce_products_params_values";
		
		/* Получаем название параметра */
		$names = Helper::getNamesByXml($xml, 'Наименование');
		$name_ru = isset($names['ru']) ? $names['ru'] : '';
		
		/* Вставляем параметр в базу данных */
		$product_param = \Elberos\wpdb_insert_or_update
		(
			$table_name_products_params,
			[
				"code_1c" => $code_1c,
			],
			[
				"classifier_id" => $classifier_id,
				"code_1c" => $code_1c,
				"alias" => sanitize_title($name_ru),
				"name" => $name_ru,
				"xml" => $xml_str,
				"gmtime_1c_change" => gmdate("Y-m-d H:i:s"),
				"is_deleted" => 0,
			]
		);
		
		/* Вставляем значения в базу данных */
		$values = $xml->ВариантыЗначений;
		if ($values != null && $values->getName() == 'ВариантыЗначений')
		{
			foreach ($values->children() as $item)
			{
				if ($item->getName() == 'Справочник')
				{
					$value_id = (string)$item->ИдЗначения;
					
					/* Получаем значение параметра */
					$names = Helper::getNamesByXml($item, 'Значение');
					$name_ru = isset($names['ru']) ? $names['ru'] : '';
					
					/* Вставляем значение параметра в базу данных */
					$product_param_value = \Elberos\wpdb_insert_or_update
					(
						$table_name_products_params_values,
						[
							"code_1c" => $value_id,
						],
						[
							"param_id" => $product_param["id"],
							"code_1c" => $value_id,
							"alias" => sanitize_title($name_ru),
							"name" => $name_ru,
							"xml" => (string)$item->asXml(),
							"gmtime_1c_change" => gmdate("Y-m-d H:i:s"),
							"is_deleted" => 0,
						]
					);
					
				}
			}
		}
		
		/* Код 1с */
		/* $task["code_1c"] = $code_1c; */
		
		/* Отмечаем задачу как обработанную */
		$task["error_code"] = 1;
		$task["status"] = Helper::TASK_STATUS_DONE;
		
		return $task;
	}
	
	
	/**
	 * Вызов функции после обновления категории
	 */
	public function importProductParamAfter($task, $product, $xml)
	{
		return [$task, $product];
	}
	
	
	
	/* ----------------------------------- Типы цен -------------------------------- */
	
	
	/**
	 * Загружаем параметр товара в базу
	 */
	public function importPriceType($task, $xml)
	{
		global $wpdb;
		
		$xml_str = $task['data'];
		$classifier_id = $task['classifier_id'];
		$code_1c = (string)$xml->Ид;
		
		/* Название таблиц */
		$table_name_price_types = $wpdb->base_prefix . "elberos_commerce_price_types";
		
		/* Получаем название параметра */
		$names = Helper::getNamesByXml($xml, 'Наименование');
		$name_ru = isset($names['ru']) ? $names['ru'] : '';
		
		/* Вставляем параметр в базу данных */
		$product_param = \Elberos\wpdb_insert_or_update
		(
			$table_name_price_types,
			[
				"code_1c" => $code_1c,
			],
			[
				"classifier_id" => $classifier_id,
				"code_1c" => $code_1c,
				"name" => $name_ru,
				"xml" => $xml_str,
				"gmtime_1c_change" => gmdate("Y-m-d H:i:s"),
			]
		);
		
		/* Код 1с */
		/* $task["code_1c"] = $code_1c; */
		
		/* Отмечаем задачу как обработанную */
		$task["error_code"] = 1;
		$task["status"] = Helper::TASK_STATUS_DONE;
		
		return $task;
	}
	
	
	/**
	 * Вызов функции после обновления категории
	 */
	public function importPriceTypeAfter($task, $product, $xml)
	{
		return [$task, $product];
	}
	
	
	
	/* ------------------------------------ Склады --------------------------------- */
	
	
	/**
	 * Загружаем параметр товара в базу
	 */
	public function importWarehouse($task, $xml)
	{
		global $wpdb;
		
		$xml_str = $task['data'];
		$classifier_id = $task['classifier_id'];
		$code_1c = (string)$xml->Ид;
		
		/* Название таблиц */
		$table_name_warehouses = $wpdb->base_prefix . "elberos_commerce_warehouses";
		
		/* Получаем название параметра */
		$names = Helper::getNamesByXml($xml, 'Наименование');
		$name_ru = isset($names['ru']) ? $names['ru'] : '';
		
		/* Вставляем параметр в базу данных */
		$product_param = \Elberos\wpdb_insert_or_update
		(
			$table_name_warehouses,
			[
				"code_1c" => $code_1c,
			],
			[
				"classifier_id" => $classifier_id,
				"code_1c" => $code_1c,
				"name" => $name_ru,
				"xml" => $xml_str,
				"gmtime_1c_change" => gmdate("Y-m-d H:i:s"),
			]
		);
		
		/* Код 1с */
		/* $task["code_1c"] = $code_1c; */
		
		/* Отмечаем задачу как обработанную */
		$task["error_code"] = 1;
		$task["status"] = Helper::TASK_STATUS_DONE;
		
		return $task;
	}
	
	
	
	/**
	 * Вызов функции после обновления категории
	 */
	public function importWarehouseAfter($task, $product, $xml)
	{
		return [$task, $product];
	}
	
	
	
	/* ---------------------------------- Предложение ------------------------------ */
	
	
	/**
	 * Загружаем предложение в базу
	 */
	public function importOffer($task, $xml)
	{
		global $wpdb;
		
		$xml_str = $task['data'];
		$catalog_id = $task['catalog_id'];
		$classifier_id = $task['classifier_id'];
		
		/* Название таблиц */
		$table_name_products_offers = $wpdb->base_prefix . "elberos_commerce_products_offers";
		
		/* Получаем код товара */
		$product_code_1c = "";
		$offer_code_1c = (string)$xml->Ид;
		$offer_code_1c_arr = explode("#", $offer_code_1c);
		if (count($offer_code_1c_arr) > 0) $product_code_1c = $offer_code_1c_arr[0];
		//var_dump($product_code_1c);
		if ($product_code_1c == "")
		{
			$task["status"] = Helper::TASK_STATUS_ERROR;
			$task["error_code"] = -1;
			$task["error_message"] = "Product 1c code is empty";
			return $task;
		}
		
		/* Поиск товара по коду 1с */
		$product = Helper::findProductByCode($product_code_1c);
		if ($product == null)
		{
			$task["status"] = Helper::TASK_STATUS_ERROR;
			$task["error_code"] = -1;
			$task["error_message"] = "Product not found";
			return $task;
		}
		
		/* Получаем название */
		$names = Helper::getNamesByXml($xml, 'Наименование');
		$name_ru = isset($names['ru']) ? $names['ru'] : '';
		
		/* Количество товара */
		$count = (string)$xml->Количество;
		
		/* ЗначенияСвойств */
		$offer_params = [];
		$items = $xml->ЗначенияСвойств;
		if ($items != null && $items->getName() == 'ЗначенияСвойств')
		{
			foreach ($items->children() as $item)
			{
				if ($item->getName() == 'ЗначенияСвойства')
				{
					$product_param = Helper::findProductParamByCode( (string)$item->Ид );
					$product_param_value = Helper::findProductParamValueByCode( (string)$item->Значение );
					
					if ($product_param && $product_param_value)
					{
						$offer_params[] =
						[
							"param" =>
							[
								"id" => $product_param['id'],
								"code_1c" => $product_param['code_1c'],
							],
							"value" =>
							[
								"id" => $product_param_value['id'],
								"code_1c" => $product_param_value['code_1c'],
							]
						];
					}
				}
			}
		}
		
		/* Цены */
		$prices = [];
		$items = $xml->Цены;
		if ($items != null && $items->getName() == 'Цены')
		{
			foreach ($items->children() as $item)
			{
				if ($item->getName() == 'Цена')
				{
					$price_type = Helper::findPriceTypeByCode( (string)$item->ИдТипаЦены );
					$price = (string)$item->ЦенаЗаЕдиницу;
					$currency = (string)$item->Валюта;
					$coefficient = (string)$item->Коэффициент;
					
					if ($price_type)
					{
						$prices[] =
						[
							"id" => $price_type['id'],
							"code_1c" => $price_type['code_1c'],
							"price" => $price,
							"currency" => $currency,
							"coefficient" => $coefficient,
						];
					}
				}
			}
		}
		//Склад
		
		/* Вставляем параметр в базу данных */
		$offer = \Elberos\wpdb_insert_or_update
		(
			$table_name_products_offers,
			[
				"code_1c" => $offer_code_1c,
			],
			[
				"product_id" => $product['id'],
				"code_1c" => $offer_code_1c,
				"name" => $name_ru,
				"xml" => $xml_str,
				"prices" => json_encode($prices),
				"offer_params" => json_encode($offer_params),
				"count" => $count,
				"gmtime_1c_change" => gmdate("Y-m-d H:i:s"),
			]
		);
		
		/* Код 1с */
		/* $task["code_1c"] = $offer_code_1c; */
		
		/* Отмечаем задачу как обработанную */
		$task["error_code"] = 1;
		$task["status"] = Helper::TASK_STATUS_DONE;
		
		return $task;
	}
	
	
	
	/**
	 * Вызов функции после обновления предложения
	 */
	public function importOfferAfter($task, $product, $xml)
	{
		return [$task, $product];
	}
}