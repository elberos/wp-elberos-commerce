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


/* Check if Wordpress */
if (!defined('ABSPATH')) exit;


class Helper
{
	const IMPORT_STATUS_PLAN = 0;
	const IMPORT_STATUS_DONE = 1;
	const IMPORT_STATUS_WORK = 2;
	const IMPORT_STATUS_ERROR = -1;
	const IMPORT_STATUS_UPLOAD = -2;
	const TASK_STATUS_PLAN = 0;
	const TASK_STATUS_DONE = 1;
	const TASK_STATUS_WORK = 2;
	const TASK_STATUS_ERROR = -1;
	
	static $catalogs = [];
	static $classifiers = [];
	static $categories = [];
	static $price_types = [];
	static $product_params = [];
	static $product_params_values = [];
	
	
	/**
	 * Get names
	 */
	static function getNamesByXml($xml, $api_name)
	{
		$names = [];
		$arr = $xml->$api_name;
		foreach ($arr as $name)
		{
			$lang = (string) ($name->attributes()->lang);
			if ($lang == null)
			{
				$lang = "ru";
			}
			$names[$lang] = (string)$name;
		}
		return $names;
	}
	
	
	
	/**
	 * Поиск классификатора по коду 1с
	 */
	static function findClassifierByCode($code_1c)
	{
		global $wpdb;
		
		if ($code_1c == "") return null;
		
		if (!array_key_exists($code_1c, static::$classifiers))
		{
			$table_name = $wpdb->base_prefix . "elberos_commerce_classifiers";
			$sql = \Elberos\wpdb_prepare
			(
				"select * from $table_name " .
				"where code_1c = :code_1c limit 1",
				[
					"code_1c" => $code_1c,
				]
			);
			$item = $wpdb->get_row($sql, ARRAY_A);
			static::$classifiers[$code_1c] = $item;
		}
		
		$item = static::$classifiers[$code_1c];
		return $item;
	}
	
	
	
	/**
	 * Поиск классификатора по коду 1с
	 */
	static function findCatalogByCode($code_1c)
	{
		global $wpdb;
		
		if ($code_1c == "") return null;
		
		if (!array_key_exists($code_1c, static::$catalogs))
		{
			$table_name = $wpdb->base_prefix . "elberos_commerce_catalogs";
			$sql = \Elberos\wpdb_prepare
			(
				"select * from $table_name " .
				"where code_1c = :code_1c limit 1",
				[
					"code_1c" => $code_1c,
				]
			);
			$item = $wpdb->get_row($sql, ARRAY_A);
			static::$catalogs[$code_1c] = $item;
		}
		
		$item = static::$catalogs[$code_1c];
		return $item;
	}
	
	
	
	/**
	 * Find category by 1c code
	 */
	static function findCategoryByCode($code_1c)
	{
		global $wpdb;
		
		if ($code_1c == "") return null;
		
		if (!array_key_exists($code_1c, static::$categories))
		{
			$table_name = $wpdb->base_prefix . "elberos_commerce_categories";
			$sql = \Elberos\wpdb_prepare
			(
				"select * from $table_name " .
				"where code_1c = :code_1c limit 1",
				[
					"code_1c" => $code_1c,
				]
			);
			$item = $wpdb->get_row($sql, ARRAY_A);
			static::$categories[$code_1c] = $item;
		}
		
		$item = static::$categories[$code_1c];
		return $item;
	}
	
	
	
	/**
	 * Find product by 1c code
	 */
	static function findProductByCode($code_1c)
	{
		global $wpdb;
		
		if ($code_1c == "") return null;
		
		$table_name = $wpdb->base_prefix . "elberos_commerce_products";
		
		$sql = \Elberos\wpdb_prepare
		(
			"select * from $table_name " .
			"where code_1c = :code_1c limit 1",
			[
				'code_1c' => $code_1c,
			]
		);
		$item = $wpdb->get_row($sql, ARRAY_A);
		if ($item)
		{
			return $item;
		}
		
		return null;
	}
	
	
	
	/**
	 * Find product by id
	 */
	static function findProductByID($id)
	{
		global $wpdb;
		
		$table_name = $wpdb->base_prefix . "elberos_commerce_products";
		
		$sql = \Elberos\wpdb_prepare
		(
			"select * from $table_name " .
			"where id = :id limit 1",
			[
				'id' => $id,
			]
		);
		$item = $wpdb->get_row($sql, ARRAY_A);
		return $item;
	}
	
	
	
	/**
	 * Find product param by 1c code
	 */
	static function findProductParamByCode($code_1c)
	{
		global $wpdb;
		
		if ($code_1c == "") return null;
		
		if (!array_key_exists($code_1c, static::$product_params))
		{
			$table_name = $wpdb->base_prefix . "elberos_commerce_params";
			$sql = \Elberos\wpdb_prepare
			(
				"select * from $table_name " .
				"where code_1c = :code_1c limit 1",
				[
					"code_1c" => $code_1c,
				]
			);
			$item = $wpdb->get_row($sql, ARRAY_A);
			static::$product_params[$code_1c] = $item;
		}
		
		$item = static::$product_params[$code_1c];
		return $item;
	}
	
	
	
	/**
	 * Find product param by 1c code
	 */
	static function findProductParamValueByCode($code_1c)
	{
		global $wpdb;
		
		if ($code_1c == "") return null;
		
		if (!array_key_exists($code_1c, static::$product_params_values))
		{
			$table_name = $wpdb->base_prefix . "elberos_commerce_params_values";
			$sql = \Elberos\wpdb_prepare
			(
				"select * from $table_name " .
				"where code_1c = :code_1c limit 1",
				[
					"code_1c" => $code_1c,
				]
			);
			$item = $wpdb->get_row($sql, ARRAY_A);
			static::$product_params_values[$code_1c] = $item;
		}
		
		$item = static::$product_params_values[$code_1c];
		return $item;
	}
	
	
	
	/**
	 * Find price type by 1c code
	 */
	static function findPriceTypeByCode($code_1c)
	{
		global $wpdb;
		
		if ($code_1c == "") return null;
		
		if (!array_key_exists($code_1c, static::$price_types))
		{
			$table_name = $wpdb->base_prefix . "elberos_commerce_price_types";
			$sql = \Elberos\wpdb_prepare
			(
				"select * from $table_name " .
				"where code_1c = :code_1c limit 1",
				[
					"code_1c" => $code_1c,
				]
			);
			$item = $wpdb->get_row($sql, ARRAY_A);
			static::$price_types[$code_1c] = $item;
		}
		
		$item = static::$price_types[$code_1c];
		return $item;
	}
	
	
	
	/**
	 * Get progress
	 */
	static function getTaskProgress($import_id)
	{
		global $wpdb;
		
		$table_name_1c_task = $wpdb->base_prefix . "elberos_commerce_1c_task";
		$sql = \Elberos\wpdb_prepare
		(
			"select count(*) as c from $table_name_1c_task " .
			"where import_id = :import_id and status != :status",
			[
				'import_id' => $import_id,
				'status' => Helper::TASK_STATUS_PLAN,
			]
		);
		$c = $wpdb->get_var($sql);
		
		return $c;
	}
	
	
	
	/**
	 * Get total
	 */
	static function getTaskTotal($import_id)
	{
		global $wpdb;
		
		$table_name_1c_task = $wpdb->base_prefix . "elberos_commerce_1c_task";
		$sql = \Elberos\wpdb_prepare
		(
			"select count(*) as c from $table_name_1c_task " .
			"where import_id = :import_id",
			[
				'import_id' => $import_id,
			]
		);
		$c = $wpdb->get_var($sql);
		
		return $c;
	}
	
	
	
	/**
	 * Update task progress
	 */
	static function updateTaskTotal($import_id)
	{
		global $wpdb;
		
		$total = static::getTaskTotal($import_id);
		$table_name_1c_import = $wpdb->base_prefix . "elberos_commerce_1c_import";
		
		$wpdb->update
		(
			$table_name_1c_import,
			[
				"total" => $total,
			],
			[
				"id" => $import_id,
			]
		);
	}
	
	
	
	/**
	 * Get total
	 */
	static function getTaskError($import_id)
	{
		global $wpdb;
		
		$table_name_1c_task = $wpdb->base_prefix . "elberos_commerce_1c_task";
		$sql = \Elberos\wpdb_prepare
		(
			"select count(*) as c from $table_name_1c_task " .
			"where import_id = :import_id and status<0",
			[
				'import_id' => $import_id,
			]
		);
		$c = $wpdb->get_var($sql);
		
		return $c;
	}
	
	
	
	/**
	 * Delete old task
	 */
	static function deleteOldTask()
	{
		global $wpdb;
		
		$max_task = 100000;
		$table_name_1c_task = $wpdb->base_prefix . "elberos_commerce_1c_task";
		$table_name_1c_import = $wpdb->base_prefix . "elberos_commerce_1c_import";
		
		$sql = "select max(task.id) from ${table_name_1c_task} as task where task.status=1";
		//var_dump( $sql );
		
		$max_task_id = $wpdb->get_var($sql);
		//var_dump( $max_task_id );
		
		if ($max_task_id != null)
		{
			$sql = \Elberos\wpdb_prepare
			(
				"delete from $table_name_1c_task " .
				"where id < :task_id and status=1",
				[
					'task_id' => $max_task_id - $max_task,
				]
			);
			//var_dump( $sql );
			$wpdb->query($sql);
		}
	}
	
	
	
	/**
	 * Add product photo
	 */
	static function addProductPhoto($product_id, $photo_id)
	{
		global $wpdb;
		
		$table_name_products_photos = $wpdb->base_prefix . "elberos_commerce_products_photos";
		
		$sql = \Elberos\wpdb_prepare
		(
			"select * from " . $table_name_products_photos . " " .
			"where product_id=:product_id and photo_id=:photo_id limit 1",
			[
				"product_id" => $product_id,
				"photo_id" => $photo_id,
			]
		);
		$product_photo_item = $wpdb->get_row($sql, ARRAY_A);
		if (!$product_photo_item)
		{
			$wpdb->insert
			(
				$table_name_products_photos,
				[
					"product_id" => $product_id,
					"photo_id" => $photo_id,
					"pos" => 0,
					"is_deleted" => 0,
				]
			);
		}
	}
}