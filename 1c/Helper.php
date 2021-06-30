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

class Helper
{
	const IMPORT_STATUS_PLAN = 0;
	const IMPORT_STATUS_DONE = 1;
	const IMPORT_STATUS_WORK = 2;
	const IMPORT_STATUS_ERROR = -1;
	const TASK_STATUS_PLAN = 0;
	const TASK_STATUS_DONE = 1;
	const TASK_STATUS_WORK = 2;
	const TASK_STATUS_ERROR = -1;
	
	static $catalogs = [];
	static $classifiers = [];
	static $categories = [];
	static $product_params = [];
	
	
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
	static function findClassifierByCode($classifier_code_1c)
	{
		global $wpdb;
		
		if (!array_key_exists($classifier_code_1c, static::$classifiers))
		{
			$table_name_catalogs = $wpdb->base_prefix . "elberos_commerce_classifiers";
			$sql = \Elberos\wpdb_prepare
			(
				"select * from $table_name_catalogs " .
				"where code_1c = :code_1c limit 1",
				[
					"code_1c" => $classifier_code_1c,
				]
			);
			$classifier = $wpdb->get_row($sql, ARRAY_A);
			static::$classifiers[$classifier_code_1c] = $classifier;
		}
		
		$classifier = static::$classifiers[$classifier_code_1c];
		return $classifier;
	}
	
	
	
	/**
	 * Поиск классификатора по коду 1с
	 */
	static function findCatalogByCode($catalog_code_1c)
	{
		global $wpdb;
		
		if (!array_key_exists($catalog_code_1c, static::$catalogs))
		{
			$table_name_catalogs = $wpdb->base_prefix . "elberos_commerce_catalogs";
			$sql = \Elberos\wpdb_prepare
			(
				"select * from $table_name_catalogs " .
				"where code_1c = :code_1c limit 1",
				[
					"code_1c" => $catalog_code_1c,
				]
			);
			$catalog = $wpdb->get_row($sql, ARRAY_A);
			static::$catalogs[$catalog_code_1c] = $catalog;
		}
		
		$catalog = static::$catalogs[$catalog_code_1c];
		return $catalog;
	}
	
	
	
	/**
	 * Find category by 1c code
	 */
	static function findCategoryByCode($code_1c)
	{
		global $wpdb;
		
		if (!array_key_exists($code_1c, static::$categories))
		{
			$table_name_categories = $wpdb->base_prefix . "elberos_commerce_categories";
			$sql = \Elberos\wpdb_prepare
			(
				"select * from $table_name_categories " .
				"where code_1c = :code_1c limit 1",
				[
					"code_1c" => $code_1c,
				]
			);
			$category = $wpdb->get_row($sql, ARRAY_A);
			static::$categories[$classifier_code_1c] = $category;
		}
		
		$category = static::$categories[$code_1c];
		return $category;
	}
	
	
	
	/**
	 * Find product by 1c code
	 */
	static function findProductByCode($code_1c)
	{
		global $wpdb;
		
		$table_name_products = $wpdb->base_prefix . "elberos_commerce_products";
		
		$sql = \Elberos\wpdb_prepare
		(
			"select * from $table_name_products " .
			"where code_1c = :code_1c limit 1",
			[
				'code_1c' => $code_1c,
			]
		);
		$product = $wpdb->get_row($sql, ARRAY_A);
		if ($product)
		{
			return $product;
		}
		
		return null;
	}
	
	
	
	/**
	 * Find product param by 1c code
	 */
	static function findProductParamByCode($code_1c)
	{
		global $wpdb;
		
		$table_name_products_params = $wpdb->base_prefix . "elberos_commerce_products_params";
		$sql = \Elberos\wpdb_prepare
		(
			"select * from $table_name_products " .
			"where code_1c = :code_1c limit 1",
			[
				'code_1c' => $code_1c,
			]
		);
		$product_param = $wpdb->get_row($sql, ARRAY_A);
		if ($product_param)
		{
			return $product_param;
		}
		
		return null;
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
	 * Get total
	 */
	static function getTaskError($import_id)
	{
		global $wpdb;
		
		$table_name_1c_task = $wpdb->base_prefix . "elberos_commerce_1c_task";
		$sql = \Elberos\wpdb_prepare
		(
			"select count(*) as c from $table_name_1c_task " .
			"where import_id = :import_id and status=:status",
			[
				'import_id' => $import_id,
				'status' => Helper::TASK_STATUS_ERROR,
			]
		);
		$c = $wpdb->get_var($sql);
		
		return $c;
	}
	
}