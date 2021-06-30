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
	var $task_run_limits = 3;
	
	
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
				"set status=:status, error_code=:error_code, error_message=:error_message " .
				"where id = :id",
				[
					'id' => $task['id'],
					'status' => $task['status'],
					'error_code' => $task['error_code'],
					'error_message' => $task['error_message'],
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
		
		else
		{
			$task['status'] = Helper::TASK_STATUS_ERROR;
			$task['error_code'] = -1;
			$task['error_message'] = "Unknown type " . $task["type"];
		}
		
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
		$category = \Elberos\insert_or_update
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
			]
		);
		
		/* Отмечаем задачу как обработанную */
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
		
		/* Вставляем товар в базу данных */
		$table_name_products = $wpdb->base_prefix . "elberos_commerce_products";
		$product = \Elberos\insert_or_update
		(
			$table_name_products,
			[
				"code_1c" => $code_1c,
			],
			[
				"catalog_id" => $task["catalog_id"],
				"code_1c" => $code_1c,
				"name" => $name_ru,
				"xml" => $xml_str,
			]
		);
		
		/* Отмечаем задачу как обработанную */
		$task["status"] = Helper::TASK_STATUS_DONE;
		
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
		$table_name_products_params = $wpdb->base_prefix . "elberos_commerce_products_params";
		$table_name_products_params_values = $wpdb->base_prefix . "elberos_commerce_products_params_values";
		
		/* Получаем название параметра */
		$names = Helper::getNamesByXml($xml, 'Наименование');
		$name_ru = isset($names['ru']) ? $names['ru'] : '';
		
		/* Вставляем параметр в базу данных */
		$product_param = \Elberos\insert_or_update
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
					$product_param_value = \Elberos\insert_or_update
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
							"is_deleted" => 0,
						]
					);
					
				}
			}
		}
		
		/* Отмечаем задачу как обработанную */
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
		$product_param = \Elberos\insert_or_update
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
			]
		);
		
		/* Отмечаем задачу как обработанную */
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
	public function importWarehouseParam($task, $xml)
	{
		return $task;
	}
	
	
	/**
	 * Вызов функции после обновления категории
	 */
	public function importWarehouseAfter($task, $product, $xml)
	{
		return [$task, $product];
	}
	
	
	
	/* ------------------------------------- Old ----------------------------------- */
	
	/**
	 * Импорт категории в таблицу posts
	 */
	public function importCategoryPost($task, $category, $xml)
	{
		global $wpdb;
		
		$table_name_posts = $wpdb->prefix . "posts";
		
		/* Ищем категорию родителя */
		$parent_category_id = $category['parent_category_id'];
		$parent_category_id_post = 0;
		if ($parent_category_id > 0)
		{
			$sql = \Elberos\wpdb_prepare
			(
				"select * from $table_name_posts " .
				"where post_type=:post_type and the_guid=:the_guid limit 1",
				[
					"the_guid" => "elberos_category_" . $parent_category_id,
					"post_type" => "products_catalog",
				]
			);
			$parent_post = $wpdb->get_row($sql, ARRAY_A);
			if ($parent_post)
			{
				$parent_category_id_post = $parent_post["ID"];
			}
		}
		
		//var_dump($parent_category_id_post);
		
		/* Ищем запись в таблице posts */
		$sql = \Elberos\wpdb_prepare
		(
			"select * from $table_name_posts " .
			"where post_type=:post_type and the_guid=:the_guid limit 1",
			[
				"the_guid" => "elberos_category_" . $category["id"],
				"post_type" => "products_catalog",
			]
		);
		$post = $wpdb->get_row($sql, ARRAY_A);
		
		/* Вставляем запись */
		if (!$post)
		{
			$wpdb->insert
			(
				$table_name_posts,
				[
					"post_author" => 1,
					"post_title" => $category["name"],
					"post_name" => sanitize_title($category["name"]),
					"post_parent" => $parent_category_id_post,
					"post_status" => "publish",
					"post_date" => gmdate("Y-m-d H:i:s"),
					"post_date_gmt" => gmdate("Y-m-d H:i:s"),
					"post_modified" => gmdate("Y-m-d H:i:s"),
					"post_modified_gmt" => gmdate("Y-m-d H:i:s"),
					"comment_status" => "closed",
					"ping_status" => "closed",
					"the_guid" => "elberos_category_" . $category["id"],
					"post_type" => "products_catalog",
				]
			);
		}
		
		/* Изменяем запись */
		else
		{
			$sql = \Elberos\wpdb_prepare
			(
				"update $table_name_posts " .
				"set
					post_title=:post_title,
					post_name=:post_name,
					post_parent=:post_parent,
					post_status=:post_status,
					comment_status=:comment_status,
					ping_status=:ping_status,
					post_modified=:post_modified,
					post_modified_gmt=:post_modified_gmt
				where id = :id",
				[
					"id" => $post["ID"],
					"post_title" => $category["name"],
					"post_name" => sanitize_title($category["name"]),
					"post_parent" => $parent_category_id_post,
					"post_modified" => gmdate("Y-m-d H:i:s"),
					"post_modified_gmt" => gmdate("Y-m-d H:i:s"),
					"post_status" => "publish",
					"comment_status" => "closed",
					"ping_status" => "closed",
				]
			);
			$wpdb->query($sql);
		}
		
		return $task;
	}
	
	
	
	/**
	 * Импорт категории в таблицу posts
	 */
	public function importProductPost($task, $product, $xml)
	{
		global $wpdb;
		
		$table_name_posts = $wpdb->prefix . "posts";
		
		/* Ищем запись в таблице posts */
		$sql = \Elberos\wpdb_prepare
		(
			"select * from $table_name_posts " .
			"where post_type=:post_type and guid=:guid limit 1",
			[
				"guid" => static::getGUID("products", $product["id"]),
				"post_type" => "products",
			]
		);
		$post = $wpdb->get_row($sql, ARRAY_A);
		
		/* Вставляем запись */
		if (!$post)
		{
			$wpdb->insert
			(
				$table_name_posts,
				[
					"post_author" => 1,
					"post_title" => $product["name"],
					"post_name" => sanitize_title($product["name"]),
					"post_parent" => $parent_category_id_post,
					"post_status" => "publish",
					"post_date" => gmdate("Y-m-d H:i:s"),
					"post_date_gmt" => gmdate("Y-m-d H:i:s"),
					"post_modified" => gmdate("Y-m-d H:i:s"),
					"post_modified_gmt" => gmdate("Y-m-d H:i:s"),
					"comment_status" => "closed",
					"ping_status" => "closed",
					"guid" => static::getGUID("products", $product["id"]),
					"post_type" => "products",
				]
			);
		}
		
		/* Изменяем запись */
		else
		{
			$sql = \Elberos\wpdb_prepare
			(
				"update $table_name_posts " .
				"set
					post_title=:post_title,
					post_name=:post_name,
					post_parent=:post_parent,
					post_status=:post_status,
					comment_status=:comment_status,
					ping_status=:ping_status,
					post_modified=:post_modified,
					post_modified_gmt=:post_modified_gmt
				where id = :id",
				[
					"id" => $post["ID"],
					"post_title" => $product["name"],
					"post_name" => sanitize_title($product["name"]),
					"post_parent" => $parent_category_id_post,
					"post_modified" => gmdate("Y-m-d H:i:s"),
					"post_modified_gmt" => gmdate("Y-m-d H:i:s"),
					"post_status" => "publish",
					"comment_status" => "closed",
					"ping_status" => "closed",
				]
			);
			$wpdb->query($sql);
		}
		
		return $task;
	}
	
}