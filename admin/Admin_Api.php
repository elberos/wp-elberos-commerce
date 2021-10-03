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

if ( !class_exists( Admin_Api::class ) ) 
{

class Admin_Api 
{
	/**
	 * Init api
	 */
	public static function init()
	{
		add_action('elberos_register_routes', '\\Elberos\\Commerce\\Admin_Api::register_routes');
	}
	
	
	
	/**
	 * Register API
	 */
	public static function register_routes($site)
	{
		$site->add_api("elberos_commerce_admin", "categories_load",
			"\\Elberos\\Commerce\\Admin_Api::categories_load");
		$site->add_api("elberos_commerce_admin", "categories_save",
			"\\Elberos\\Commerce\\Admin_Api::categories_save");
		$site->add_api("elberos_commerce_admin", "categories_delete",
			"\\Elberos\\Commerce\\Admin_Api::categories_delete");
		$site->add_api("elberos_commerce_admin", "add_relative_product",
			"\\Elberos\\Commerce\\Admin_Api::add_relative_product");
		$site->add_api("elberos_commerce_admin", "delete_relative_product",
			"\\Elberos\\Commerce\\Admin_Api::delete_relative_product");
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
	public static function categories_load($site)
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
			"order_by" => "name asc",
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
	public static function categories_save($site)
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
		
		$id = isset($item["id"]) ? $item["id"]: "";
		$name = isset($item["name"]) ? $item["name"] : "";
		$code_1c = isset($item["code_1c"]) ? $item["code_1c"] : "";
		$image_file_id = isset($item["image_id"]) ? $item["image_id"] : "";
		$image_file_path = isset($item["image_file_path"]) ? $item["image_file_path"] : "";
		$parent_category_id = isset($item["parent_category_id"]) ? $item["parent_category_id"] : 0;
		$classifier_id = isset($item["classifier_id"]) ? $item["classifier_id"] : 0;
		$show_in_catalog = isset($item["show_in_catalog"]) ? $item["show_in_catalog"] : 0;
		
		$action = "";
		$table_name = $wpdb->base_prefix . 'elberos_commerce_categories';
		
		/* Update */
		if ($id != "")
		{
			$action = "update";
			$wpdb->update
			(
				$table_name,
				[
					"name" => $name,
					"code_1c" => $code_1c,
					"image_id" => $image_file_id,
					"image_file_path" => $image_file_path,
					"show_in_catalog" => $show_in_catalog,
				],
				[
					"id" => $id,
				]
			);
		}
		
		/* Add */
		else
		{
			$action = "add";
			$wpdb->insert
			(
				$table_name,
				[
					"name" => $name,
					"code_1c" => $code_1c,
					"image_id" => $image_file_id,
					"image_file_path" => $image_file_path,
					"classifier_id" => $classifier_id,
					"parent_category_id" => $parent_category_id,
					"show_in_catalog" => $show_in_catalog,
				]
			);
			$id = $wpdb->insert_id;
		}
		
		/* Find item */
		$sql = $wpdb->prepare("select * from " . $table_name . " where id=%d", $id);
		$item = $wpdb->get_row($sql);
		
		return
		[
			"action" => $action,
			"item" => $item,
			"item_id" => $id,
			"message" => "OK",
			"code" => 1,
		];
	}
	
	
	/**
	 * Delete item
	 */
	public static function categories_delete($site)
	{
		global $wpdb;
		
		/* Check is admin */
		$res = static::checkIsAdmin();
		if ($res) return $res;
		
		$table_name = $wpdb->base_prefix . 'elberos_commerce_categories';
		$id = isset($_POST["id"]) ? $_POST["id"] : null;
		$classifier_id = isset($_POST["classifier_id"]) ? $_POST["classifier_id"] : null;
		
		$sql = $wpdb->prepare("delete from " . $table_name . " where id=%d and classifier_id=%d",
			$id, $classifier_id);
		$wpdb->query($sql);
		
		return
		[
			"item_id" => $id,
			"message" => "OK",
			"code" => 1,
		];
	}
	
	
	/**
	 * Add relative product
	 */
	public static function add_relative_product($site)
	{
		global $wpdb;
		
		/* Check is admin */
		$res = static::checkIsAdmin();
		if ($res) return $res;
		
		$code = 1;
		$item = null;
		$kind = isset($_POST["kind"]) ? $_POST["kind"] : "";
		$value = isset($_POST["value"]) ? $_POST["value"] : "";
		$product_id = (int)(isset($_POST["product_id"]) ? $_POST["product_id"] : "");
		$message = "Товар добавлен";
		$main_photo = null;
		$main_photo_id = null;
		$table_name = $wpdb->prefix . "elberos_commerce_products";
		
		if ($kind == "vendor_code" && $value != "")
		{
			$sql = $wpdb->prepare("select * from " . $table_name . " where vendor_code=%s limit 1", [$value]);
			$item = $wpdb->get_row($sql, ARRAY_A);
		}
		if ($kind == "product_id")
		{
			$sql = $wpdb->prepare("select * from " . $table_name . " where id=%d limit 1", [$value]);
			$item = $wpdb->get_row($sql, ARRAY_A);
		}
		
		if ($item)
		{
			$table_name_relative = $wpdb->prefix . "elberos_commerce_products_relative";
			$sql = $wpdb->prepare("select * from " . $table_name_relative .
				" where product_id=%d and relative_id=%d limit 1", [$product_id, $value]);
			$relative_record = $wpdb->get_row($sql, ARRAY_A);
			if (!$relative_record)
			{
				$main_photo_id = $item["main_photo_id"];
				if ($main_photo_id)
				{
					$main_photo = \Elberos\get_image_url($main_photo_id, "thumbnail");
				}
			}
			else
			{
				$item = null;
			}
		}
		
		/* Add relative product */
		if ($item)
		{
			$table_name_relative = $wpdb->prefix . "elberos_commerce_products_relative";
			$wpdb->insert
			(
				$table_name_relative,
				[
					"product_id" => $product_id,
					"relative_id" => $item["id"],
				]
			);
		}
		else
		{
			$code = -1;
			$message = "Товар не найден";
		}
		
		return
		[
			"kind" => $kind,
			"value" => $value,
			"item" => $item,
			"main_photo" => $main_photo,
			"main_photo_id" => $main_photo_id,
			"message" => $message,
			"code" => $code,
		];
	}
	
	
	/**
	 * Delete relative product
	 */
	public static function delete_relative_product($site)
	{
		global $wpdb;
		
		/* Check is admin */
		$res = static::checkIsAdmin();
		if ($res) return $res;
		
		$product_id = isset($_POST["product_id"]) ? $_POST["product_id"] : "";
		$relative_id = isset($_POST["relative_id"]) ? $_POST["relative_id"] : "";
		$table_name_relative = $wpdb->prefix . "elberos_commerce_products_relative";
		$sql = $wpdb->prepare("delete from " . $table_name_relative .
			" where product_id=%d and relative_id=%d limit 1", [$product_id, $relative_id]);
		$wpdb->query($sql);
		
		return
		[
			"product_id" => $product_id,
			"relative_id" => $relative_id,
			"message" => "OK",
			"code" => 1,
		];
	}
}

}