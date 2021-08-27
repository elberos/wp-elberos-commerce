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

namespace Elberos\Commerce;

if ( !class_exists( Category_Api::class ) ) 
{

class Category_Api 
{
	/**
	 * Init api
	 */
	public static function init()
	{
		add_action('elberos_register_routes', '\\Elberos\\Commerce\\Category_Api::register_routes');
	}
	
	
	
	/**
	 * Register API
	 */
	public static function register_routes($site)
	{
		$site->add_api("elberos_commerce_admin", "categories_load", "\\Elberos\\Commerce\\Category_Api::load");
		$site->add_api("elberos_commerce_admin", "categories_save", "\\Elberos\\Commerce\\Category_Api::save");
	}
	
	
	
	/**
	 * Check is admin
	 */
	public static function checkIsAdmin()
	{
		if (current_user_can('administrator'))
		{
			return null;
		}
		
		return
		[
			"message" => "Error",
			"code" => -1,
		];
	}
	
	
	
	/**
	 * Load items
	 */
	public static function load($site)
	{
		global $wpdb;
		
		$items = [];
		
		/* Check is admin */
		$res = static::checkIsAdmin();
		if ($res) return $res;
		
		$classifier_id = (int)(isset($_POST["classifier_id"]) ? $_POST["classifier_id"] : 0);
		$parent_category_id = (int)(isset($_POST["parent_category_id"]) ? $_POST["parent_category_id"] : 0);
		$args =
		[
			"classifier_id" => $classifier_id,
			"parent_category_id" => $parent_category_id,
		];
		$where =
		[
			"classifier_id=:classifier_id",
			"parent_category_id=:parent_category_id",
			"is_deleted=0",
		];
		
		$table_name = $wpdb->base_prefix . 'elberos_commerce_categories';
		list($items, $total_items, $pages, $page) = \Elberos\wpdb_query
		([
			"table_name" => $table_name,
			"where" => implode(" and ", $where),
			"args" => $args,
			"page" => 0,
			"per_page" => -1,
			//"log"=>true,
		]);
		
		return
		[
			"message" => "OK",
			"code" => 1,
			"items" => $items,
		];
	}
	
	
	
	/**
	 * Save item
	 */
	public static function save($site)
	{
		global $wpdb;
		
		/* Check is admin */
		$res = static::checkIsAdmin();
		if ($res) return $res;
		
		$item = isset($_POST["item"]) ? $_POST["item"] : null;
		if (!$item)
		{
			return
			[
				"message" => "Item not found",
				"code" => -1,
			];
		}
		
		$id = $item["id"];
		$name = $item["name"];
		$code_1c = $item["code_1c"];
		$image_file_path = $item["image_file_path"];
		$parent_category_id = $item["parent_category_id"];
		$classifier_id = $item["classifier_id"];
		
		$table_name = $wpdb->base_prefix . 'elberos_commerce_categories';
		
		/* Update */
		if ($id != "")
		{
			$wpdb->update
			(
				$table_name,
				[
					"name" => $name,
					"code_1c" => $code_1c,
					"image_file_path" => $image_file_path,
				],
				[
					"id" => $id,
				]
			);
		}
		
		/* Add */
		else
		{
			$wpdb->insert
			(
				$table_name,
				[
					"name" => $name,
					"code_1c" => $code_1c,
					"image_file_path" => $image_file_path,
					"classifier_id" => $classifier_id,
					"parent_category_id" => $parent_category_id,
				]
			);
			$id = $wpdb->insert_id;
		}
		
		return
		[
			"item_id" => $id,
			"message" => "OK",
			"code" => 1,
		];
	}
}

}