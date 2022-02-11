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
	var $id = [];
	
	
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
		
		$start = time();
		$tasks = $wpdb->get_results($sql, ARRAY_A);
		foreach ($tasks as $task)
		{
			/* Обновляем статус задачи */
			$sql = \Elberos\wpdb_prepare
			(
				"update $table_name_1c_task " .
				"set status=:status " .
				"where id = :id",
				[
					'id' => $task['id'],
					'status' => Helper::TASK_STATUS_WORK,
				]
			);
			$wpdb->query($sql);
			
			try
			{
				set_time_limit(600);
				$task = $this->runTask($task);
				$this->id[] = $task["id"];
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
			
			if (time() - $start > 10)
			{
				break;
			}
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
		else if ($task["type"] == "product_image")
		{
			$task = $this->importProductImage($task, $xml);
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
		$code_1c = \Elberos\mb_trim( (string)$xml->Ид );
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
				"slug" => sanitize_title($name_ru),
				"xml" => $xml_str,
				"gmtime_1c_change" => gmdate("Y-m-d H:i:s"),
				"is_deleted" => 0,
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
		$code_1c = \Elberos\mb_trim((string)$xml->Ид);
		$vendor_code = \Elberos\mb_trim((string)$xml->Артикул);
		
		/* Получаем название товара */
		$names = Helper::getNamesByXml($xml, 'Наименование');
		$name_ru = isset($names['ru']) ? $names['ru'] : '';
		//var_dump($names);
		
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
				"slug" => sanitize_title($name_ru),
				"xml" => $xml_str,
				"gmtime_1c_change" => gmdate("Y-m-d H:i:s"),
				"is_deleted" => 0,
			]
		);
		$product_update = [];
		
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
		
		/* Группы */
		if ($xml->Группы != null && $xml->Группы->getName() == 'Группы')
		{
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
					
					if (!isset($product_update["main_category_id"]))
					{
						$product_update["main_category_id"] = $category["id"];
					}
				}
			}
		}
		
		/* Помечаем фото на удаление */
		$table_name_products_photos = $wpdb->base_prefix . "elberos_commerce_products_photos";
		$sql = \Elberos\wpdb_prepare
		(
			"update $table_name_products_photos set is_deleted=1 where product_id=:product_id",
			[
				"product_id" => $product["id"],
			]
		);
		$wpdb->query($sql);
		
		/* Помечаем товар, чтобы потом скрыть, если не обновится флаг */
		$product_update["just_show_in_catalog"] = 0;
		
		/* Обновляем данные параметров товара */
		$table_name_products_params = $wpdb->base_prefix . "elberos_commerce_products_params";
		$wpdb->update($table_name_products_params, [ "prepare_delete"=>1 ], [ "product_id" => $product["id"] ]);
		
		/* Параметры товара */
		$product_params = [];
		$items = $xml->ЗначенияСвойств;
		if ($items != null && $items->getName() == 'ЗначенияСвойств')
		{
			foreach ($items->children() as $item)
			{
				if ($item->getName() == 'ЗначенияСвойства')
				{
					$product_param = Helper::findProductParamByCode(
						\Elberos\mb_trim((string)$item->Ид)
					);
					$product_param_value = Helper::findProductParamValueByCode(
						\Elberos\mb_trim((string)$item->Значение)
					);
					
					if ($product_param && $product_param_value)
					{
						\Elberos\wpdb_insert_or_update
						(
							$table_name_products_params,
							[
								"product_id" => $product["id"],
								"type" => "params",
								"param_id" => $product_param["id"],
							],
							[
								"product_id" => $product["id"],
								"type" => "params",
								"param_id" => $product_param["id"],
								"param_code_1c" => $product_param["code_1c"],
								"key" => $product_param["name"],
								"param_value_id" => $product_param_value["id"],
								"param_value_code_1c" => $product_param_value["code_1c"],
								"value" => $product_param_value["name"],
								"prepare_delete" => 0,
							]
						);
					}
				}
			}
		}
		
		/* Реквизиты товара */
		$product_props = [];
		$items = $xml->ЗначенияРеквизитов;
		if ($items != null && $items->getName() == 'ЗначенияРеквизитов')
		{
			foreach ($items->children() as $item)
			{
				if ($item->getName() == 'ЗначениеРеквизита')
				{
					$props_name = \Elberos\mb_trim((string)$item->Наименование);
					$props_value = \Elberos\mb_trim((string)$item->Значение);
					
					\Elberos\wpdb_insert_or_update
					(
						$table_name_products_params,
						[
							"product_id" => $product["id"],
							"type" => "props",
							"key" => $props_name,
						],
						[
							"product_id" => $product["id"],
							"type" => "props",
							"param_id" => null,
							"param_code_1c" => "",
							"key" => $props_name,
							"param_value_id" => null,
							"param_value_code_1c" => "",
							"value" => $props_value,
							"prepare_delete" => 0,
						]
					);
				}
			}
		}
		
		/* Удаляем старые значения */
		$wpdb->delete($table_name_products_params, [ "product_id" => $product["id"], "prepare_delete" => 1 ]);
		
		/* Обновляем текста для поиска */
		$search_text = [];
		foreach ($text as $arr1)
		{
			foreach ($arr1 as $key => $value)
			{
				if ($key != "name") continue;
				$search_text[] = $value;
			}
		}
		if ($vendor_code != "") $search_text[] = $vendor_code;
		
		/* Обновляем текст в базе данных */
		$table_name_products_text = $wpdb->base_prefix . "elberos_commerce_products_text";
		\Elberos\wpdb_insert_or_update
		(
			$table_name_products_text,
			[
				"id" => $product["id"],
			],
			[
				"id" => $product["id"],
				"text" => implode(" ", $search_text),
			]
		);
		
		/* Do filter elberos_commerce_1c_import_product */
		$res = apply_filters
		(
			'elberos_commerce_1c_import_product',
			[
				'xml'=>$xml,
				'product' => $product,
				'product_update' => $product_update,
			]
		);
		$product_update = $res["product_update"];
		
		/* Обновляем данные товара */
		if (count($product_update) > 0)
		{
			$wpdb->update($table_name_products, $product_update, [ "id" => $product["id"] ]);
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
	public function importProductImage($task, $xml)
	{
		global $wpdb;
		
		$session_id = session_id();
		$image_path = (string)$xml;
		$image_path_full = Controller::getFilePath($session_id, $image_path);
		$table_name_products_photos = $wpdb->base_prefix . "elberos_commerce_products_photos";
		$products_photo_term_id = (int)\Elberos\get_option("elberos_commerce_products_photos_term_id");
		
		$photo_pos = (string) ($xml->attributes()->pos);
		$code_1c = (string) ($xml->attributes()->code_1c);
		
		$product = Helper::findProductByCode($code_1c);
		if (!$product)
		{
			/* Отмечаем задачу как обработанную */
			$task["error_code"] = -1;
			$task["error_message"] = "Product not found";
			$task["status"] = Helper::TASK_STATUS_ERROR;
			return $task;
		}
		
		if (is_file($image_path_full))
		{
			$photo_id = \Elberos\upload_file($image_path_full, [
				"title" => $product["name"],
			]);
			
			/* Update term */
			if ($photo_id > 0 && $products_photo_term_id > 0)
			{
				\Elberos\update_term_id($photo_id, $products_photo_term_id);
			}
			
			/* If photo exists */
			if ($photo_id > 0)
			{
				/* Загрузка картинки */
				$sql = \Elberos\wpdb_prepare
				(
					"select * from $table_name_products_photos " .
					"where product_id=:product_id and photo_id=:photo_id limit 1",
					[
						"product_id" => $product["id"],
						"photo_id" => $photo_id,
					]
				);
				/*var_dump($sql);*/
				$item = $wpdb->get_row($sql, ARRAY_A);
				
				if (!$item)
				{
					/*var_dump("Insert");*/
					$wpdb->insert
					(
						$table_name_products_photos,
						[
							"product_id" => $product["id"],
							"photo_id" => $photo_id,
							"pos" => $photo_pos,
							"is_deleted" => 0,
						]
					);
				}
				else
				{
					/*var_dump("Update");*/
					$sql = \Elberos\wpdb_prepare
					(
						"update $table_name_products_photos set pos=:pos, is_deleted=0 " .
						"where product_id=:product_id and photo_id=:photo_id limit 1",
						[
							"pos" => $photo_pos,
							"product_id" => $product["id"],
							"photo_id" => $photo_id,
						]
					);
					/*var_dump($sql);*/
					$wpdb->query($sql);
				}
				
				/* Обновляем id фото */
				if ($photo_pos == 0)
				{
					$product_update =
					[
						"main_photo_id" => $photo_id,
						"just_show_in_catalog" => 1,
					];
					
					/* Do filter elberos_commerce_1c_update_product_main_photo */
					$res = apply_filters
					(
						'elberos_commerce_1c_update_product_main_photo',
						[
							'xml' => $xml,
							'product' => $product,
							'product_update' => $product_update,
							'photo_id' => $photo_id,
						]
					);
					$product_update = $res["product_update"];
					
					$table_name_products = $wpdb->base_prefix . "elberos_commerce_products";
					$wpdb->update($table_name_products, $product_update, [ "id" => $product["id"] ]);
				}
				
				/* Отмечаем задачу как обработанную */
				$task["error_code"] = 1;
				$task["status"] = Helper::TASK_STATUS_DONE;
			}
			else
			{
				/* Отмечаем ошибку загрузка файлов не удалась */
				$task["error_code"] = -1;
				$task["error_message"] = "Image upload error " . $image_path_full;
				$task["status"] = Helper::TASK_STATUS_ERROR;
			}
		}
		
		else
		{
			/* Отмечаем ошибку файл не найден */
			$task["error_code"] = -1;
			$task["error_message"] = "Image not found " . $image_path_full;
			$task["status"] = Helper::TASK_STATUS_ERROR;
		}
		
		return $task;
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
		$table_name_products_params = $wpdb->base_prefix . "elberos_commerce_params";
		$table_name_products_params_values = $wpdb->base_prefix . "elberos_commerce_params_values";
		
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
					$value_id = \Elberos\mb_trim((string)$item->ИдЗначения);
					
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
		$table_name_products_offers_prices = $wpdb->base_prefix . "elberos_commerce_products_offers_prices";
		
		/* Получаем код товара */
		$product_code_1c = "";
		$offer_code_1c = \Elberos\mb_trim((string)$xml->Ид);
		$offer_code_1c_arr = explode("#", $offer_code_1c);
		if (count($offer_code_1c_arr) > 0) $product_code_1c = $offer_code_1c_arr[0];
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
					$product_param = Helper::findProductParamByCode( \Elberos\mb_trim((string)$item->Ид) );
					$product_param_value = Helper::findProductParamValueByCode(
						\Elberos\mb_trim((string)$item->Значение)
					);
					
					if ($product_param && $product_param_value)
					{
						$offer_params[] =
						[
							"param" =>
							[
								"id" => $product_param['id'],
								"code_1c" => $product_param['code_1c'],
								"name" => $product_param["name"],
							],
							"value" =>
							[
								"id" => $product_param_value['id'],
								"code_1c" => $product_param_value['code_1c'],
								"name" => $product_param_value["name"],
							]
						];
					}
				}
			}
		}
		
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
				"offer_params" => json_encode($offer_params),
				"count" => $count,
				"prepare_delete" => 0,
				"gmtime_1c_change" => gmdate("Y-m-d H:i:s"),
			]
		);
		$offer_id = $offer['id'];
		
		/* Цены */
		$items = $xml->Цены;
		if ($items != null && $items->getName() == 'Цены')
		{
			foreach ($items->children() as $item)
			{
				if ($item->getName() == 'Цена')
				{
					$price_name = (string)$item->Представление;
					$price_code_1c = \Elberos\mb_trim((string)$item->ИдТипаЦены);
					$price_unit = \Elberos\mb_trim((string)$item->Единица);
					$price_type = Helper::findPriceTypeByCode( $price_code_1c );
					$price_type_id = null; if ($price_type) $price_type_id = $price_type["id"];
					$price = \Elberos\mb_trim((string)$item->ЦенаЗаЕдиницу);
					$currency = \Elberos\mb_trim((string)$item->Валюта);
					$coefficient = \Elberos\mb_trim((string)$item->Коэффициент);
					$price = (double)preg_replace("/[^0-9\.]/","",$price);
					
					\Elberos\wpdb_insert_or_update
					(
						$table_name_products_offers_prices,
						[
							"offer_id" => $offer_id,
							"price_type_code_1c" => $price_code_1c,
						],
						[
							"offer_id" => $offer_id,
							"price_type_id" => $price_type_id,
							"price_type_code_1c" => $price_code_1c,
							"price" => $price,
							"currency" => $currency,
							"coefficient" => $coefficient,
							"unit" => $price_unit,
							"name" => $price_name,
							"prepare_delete" => 0,
						]
					);
				}
			}
		}
		
		/* Код 1с */
		/* $task["code_1c"] = $offer_code_1c; */
		
		/* Do filter elberos_commerce_1c_update_product_offer */
		$res = apply_filters
		(
			'elberos_commerce_1c_update_product_offer',
			[
				'xml' => $xml,
				'product' => $product,
			]
		);
		
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