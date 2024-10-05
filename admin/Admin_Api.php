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


/* Check if Wordpress */
if (!defined('ABSPATH')) exit;


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
		$site->add_api("elberos_commerce_admin", "invoice_1c_export_reply",
			"\\Elberos\\Commerce\\Admin_Api::invoice_1c_export_reply");
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
		
		$item = isset($_POST["item"]) ? stripslashes_deep($_POST["item"]) : null;
		if (!$item)
		{
			return
			[
				"message" => "Item not found",
				"code" => -1,
			];
		}
		
		require_once dirname(__DIR__) . "/admin/Category_Table.php";
		
		$struct = \Elberos\Commerce\Category_Table::createStruct();
		
		$id = isset($item["id"]) ? $item["id"]: "";
		$classifier_id = isset($item["classifier_id"]) ? $item["classifier_id"]: "";
		$process_item = $struct->update([], $item);
		$process_item = $struct->processItem($process_item);
		$process_item["classifier_id"] = $classifier_id;
		
		if ($process_item["slug"] == "")
		{
			$process_item["slug"] = sanitize_title($process_item["name"]);
		}
		//var_dump($process_item);
		
		$action = "";
		$table_name = $wpdb->base_prefix . 'elberos_commerce_categories';
		
		$classifier_id = isset($item["classifier_id"]) ? $item["classifier_id"] : 0;
		
		/* Update */
		if ($id != "")
		{
			$action = "update";
			$wpdb->update
			(
				$table_name,
				$process_item,
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
				$process_item
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
		$type = isset($_POST["type"]) ? $_POST["type"] : "relative";
		$kind = isset($_POST["kind"]) ? $_POST["kind"] : "";
		$value = isset($_POST["value"]) ? $_POST["value"] : "";
		$product_id = (int)(isset($_POST["product_id"]) ? $_POST["product_id"] : "");
		$message = "Товар добавлен";
		$main_photo = null;
		$main_photo_id = null;
		$table_name = $wpdb->prefix . "elberos_commerce_products";
		
		$table_name_relative = $wpdb->prefix . "elberos_commerce_products_relative";
		if ($type == "komplekt")
		{
			$table_name_relative = $wpdb->prefix . "elberos_commerce_products_komplekt";
		}
		
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
			"type" => $type,
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
		
		$type = isset($_POST["type"]) ? $_POST["type"] : "relative";
		$product_id = isset($_POST["product_id"]) ? $_POST["product_id"] : "";
		$relative_id = isset($_POST["relative_id"]) ? $_POST["relative_id"] : "";
		$table_name_relative = $wpdb->prefix . "elberos_commerce_products_relative";
		if ($type == "komplekt")
		{
			$table_name_relative = $wpdb->prefix . "elberos_commerce_products_komplekt";
		}
		$sql = $wpdb->prepare("delete from " . $table_name_relative .
			" where product_id=%d and relative_id=%d limit 1", [$product_id, $relative_id]);
		$wpdb->query($sql);
		
		return
		[
			"type" => $type,
			"product_id" => $product_id,
			"relative_id" => $relative_id,
			"message" => "OK",
			"code" => 1,
		];
	}
	
	
	/**
	 * Delete relative product
	 */
	public static function invoice_1c_export_reply($site)
	{
		global $wpdb;
		
		/* Check is admin */
		$res = static::checkIsAdmin();
		if ($res) return $res;
		
		$table_name = $wpdb->base_prefix . "elberos_commerce_invoice";
		$invoice_id = isset($_POST["invoice_id"]) ? $_POST["invoice_id"] : "";
		
		$sql = \Elberos\wpdb_prepare
		(
			"update " . $table_name .
			" set export_status=0, gmtime_1c_export=null " .
			" where id=:id",
			[
				"id" => $invoice_id,
			]
		);
		
		$wpdb->query($sql);
		
		return
		[
			"message" => "OK",
			"code" => 1,
		];
	}
	
}

}