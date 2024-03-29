<?php
/**
 * Plugin Name: WordPress Commerce
 * Description: Commerce plugin for WordPress
 * Version:     0.2.0
 * Author:      Elberos Team <support@elberos.org>
 * License:     Apache License 2.0
 *
 * Elberos Framework
 *
 * (c) Copyright 2019-2021 "Ildar Bikmamatov" <support@elberos.org>
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     https://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

/* Check if Wordpress */
if (!defined('ABSPATH')) exit;


if ( !class_exists( 'Elberos_Commerce_Plugin' ) ) 
{


class Elberos_Commerce_Plugin
{
	
	/**
	 * Init Plugin
	 */
	public static function init()
	{
		add_action
		(
			'admin_init', 
			function()
			{
				require_once __DIR__ . "/1c/Import_Table.php";
				require_once __DIR__ . "/1c/Task_Table.php";
				require_once __DIR__ . "/admin/Catalog_Table.php";
				require_once __DIR__ . "/admin/Category_Table.php";
				require_once __DIR__ . "/admin/Classifier_Table.php";
				require_once __DIR__ . "/admin/Invoice_Table.php";
				require_once __DIR__ . "/admin/PriceType_Table.php";
				require_once __DIR__ . "/admin/Product_Table.php";
				require_once __DIR__ . "/admin/ProductParam_Table.php";
				require_once __DIR__ . "/admin/ProductParamValue_Table.php";
				require_once __DIR__ . "/admin/Settings.php";
				require_once __DIR__ . "/admin/Warehouse_Table.php";
			}
		);
		add_action('admin_menu', 'Elberos_Commerce_Plugin::register_admin_menu');
		
		/* Load entities */
		add_action(
			'plugins_loaded',
			function()
			{
				include __DIR__ . "/1c/Import_Struct.php";
				include __DIR__ . "/1c/Task_Struct.php";
				include __DIR__ . "/entity/Catalog.php";
				include __DIR__ . "/entity/Category.php";
				include __DIR__ . "/entity/Classifier.php";
				include __DIR__ . "/entity/Invoice.php";
				include __DIR__ . "/entity/PriceType.php";
				include __DIR__ . "/entity/Product.php";
				include __DIR__ . "/entity/ProductParam.php";
				include __DIR__ . "/entity/ProductParamValue.php";
				include __DIR__ . "/entity/Warehouse.php";
			},
		);
		
		/* Remove plugin updates */
		add_filter( 'site_transient_update_plugins', 'Elberos_Commerce_Plugin::filter_plugin_updates' );
		
		/* Include api */
		include __DIR__ . "/include/api.php";
		\Elberos\Commerce\Api::init();
		
		/* Include admin api */
		include __DIR__ . "/admin/Admin_Api.php";
		\Elberos\Commerce\Admin_Api::init();
		
		/* Include 1C */
		include __DIR__ . "/1c/Controller.php";
		include __DIR__ . "/1c/Helper.php";
		include __DIR__ . "/1c/Import.php";
		include __DIR__ . "/1c/Task.php";
		\Elberos\Commerce\_1C\Controller::init();
		
		/* Add cron twicedaily task */
		if ( !wp_next_scheduled( 'elberos_commerce_twicedaily_event' ) )
		{
			wp_schedule_event( time() + 60, 'twicedaily', 'elberos_commerce_twicedaily_event' );
		}
		add_action( 'elberos_commerce_twicedaily_event',
			'Elberos_Commerce_Plugin::cron_twicedaily_event' );
		
		/* Product updated */
		add_action('elberos_commerce_product_updated', 'Elberos_Commerce_Plugin::elberos_commerce_product_updated');
	}
	
	
	
	/**
	 * Cron twicedaily event
	 */
	public static function cron_twicedaily_event()
	{
		global $wpdb;
		
		/**
		 * Удаляем старый лог 1С
		 */
		$table_1c_import = $wpdb->base_prefix . 'elberos_commerce_1c_import';
		$sql = $wpdb->prepare
		(
			"delete from `$table_1c_import` where `gmtime_add` < %s",
			[ gmdate("Y-m-d h:i:s", time() - 30*24*60*60 ) ]
		);
		$wpdb->query($sql, ARRAY_A);
	}
	
	
	
	/**
	 * Remove plugin updates
	 */
	public static function filter_plugin_updates($value)
	{
		$name = plugin_basename(__FILE__);
		if (isset($value->response[$name]))
		{
			unset($value->response[$name]);
		}
		return $value;
	}
	
	
	
	/**
	 * Редирект на блог
	 */
	static function get_first_blog_redirect_url()
	{
		$url = get_blogaddress_by_id(1);
		$url .= "wp-admin/admin.php";
		$flag = "?";
		
		foreach ($_GET as $key => $value)
		{
			$url .= $flag . $key . "=" . urlencode($value);
			$flag = "&";
		}
		
		return $url;
	}
	
	
	
	/**
	 * Register Admin Menu
	 */
	public static function register_admin_menu()
	{
		add_menu_page
		(
			'Магазин', 'Магазин',
			'manage_options', 'elberos-commerce',
			function ()
			{
				$blog_id = get_current_blog_id();
				if ($blog_id != 1)
				{
					$url = static::get_first_blog_redirect_url();
					echo "<br/>";
					echo "<br/>";
					echo "<a href='" . esc_attr($url) . "'>Перейдите по ссылке, чтобы открыть список товаров</a>";
				}
				else
				{
					$table = new \Elberos\Commerce\Product_Table();
					$table->display();
				}
			},
			'/wp-content/plugins/wp-elberos-commerce/images/commerce.png',
			30
		);
		
		add_submenu_page
		(
			'elberos-commerce', 
			'Классификатор', 'Классификатор',
			'manage_options', 'elberos-commerce-classifiers',
			function()
			{
				$blog_id = get_current_blog_id();
				if ($blog_id != 1)
				{
					$url = static::get_first_blog_redirect_url();
					echo "<br/>";
					echo "<br/>";
					echo "<a href='" . esc_attr($url) . "'>Перейдите по ссылке, чтобы открыть классификатор</a>";
				}
				else
				{
					$table = new \Elberos\Commerce\Classifier_Table();
					$table->display();
				}
			}
		);
		
		add_submenu_page
		(
			'elberos-commerce', 
			'Заказы в магазине', 'Заказы в магазине', 
			'manage_options', 'elberos-commerce-invoice',
			function()
			{
				$blog_id = get_current_blog_id();
				if ($blog_id != 1)
				{
					$url = static::get_first_blog_redirect_url();
					echo "<br/>";
					echo "<br/>";
					echo "<a href='" . esc_attr($url) . "'>Перейдите по ссылке, чтобы открыть заказ в магазине</a>";
				}
				else
				{
					$table = new \Elberos\Commerce\Invoice_Table();
					$table->display();
				}
			}
		);
		
		add_submenu_page
		(
			'elberos-commerce', 
			'Настройки магазина', 'Настройки магазина', 
			'manage_options', 'elberos-commerce-settings',
			function()
			{
				$blog_id = get_current_blog_id();
				if ($blog_id != 1)
				{
					$url = static::get_first_blog_redirect_url();
					echo "<br/>";
					echo "<br/>";
					echo "<a href='" . esc_attr($url) . "'>Перейдите по ссылке, чтобы открыть настройки 1С</a>";
				}
				else
				{
					\Elberos\Commerce\Settings::show();
				}
			}
		);
		
		add_submenu_page
		(
			'elberos-commerce', 
			'Импорт 1С', 'Импорт 1С', 
			'manage_options', 'elberos-commerce-1c-import',
			function()
			{
				$blog_id = get_current_blog_id();
				if ($blog_id != 1)
				{
					$url = static::get_first_blog_redirect_url();
					echo "<br/>";
					echo "<br/>";
					echo "<a href='" . esc_attr($url) . "'>Перейдите по ссылке, чтобы открыть импорт 1С</a>";
				}
				else
				{
					$table = new \Elberos\Commerce\_1C\Import_Table();
					$table->display();
				}
			}
		);
		
		add_submenu_page
		(
			'elberos-commerce', 
			'Лог 1С', 'Лог 1С', 
			'manage_options', 'elberos-commerce-1c-task',
			function()
			{
				$blog_id = get_current_blog_id();
				if ($blog_id != 1)
				{
					$url = static::get_first_blog_redirect_url();
					echo "<br/>";
					echo "<br/>";
					echo "<a href='" . esc_attr($url) . "'>Перейдите по ссылке, чтобы открыть лог 1С</a>";
				}
				else
				{
					$table = new \Elberos\Commerce\_1C\Task_Table();
					$table->display();
				}
			}
		);
	}
	
	
	/**
	 * Product updated
	 */
	public static function elberos_commerce_product_updated($product)
	{
		global $wpdb;
		
		if ($product == null) return;
		
		$name = isset($product["name"]) ? $product["name"] : "";
		$text = @json_decode(isset($product["text"]) ? $product["text"] : null, true);
		$vendor_code = isset($product["vendor_code"]) ? $product["vendor_code"] : "";
		
		/* Обновляем текста для поиска */
		$search_text = [];
		if ($name != "") $search_text[] = $name;
		if ($vendor_code != "") $search_text[] = $vendor_code;
		if ($text and gettype($text) == "array")
		{
			foreach ($text as $arr1)
			{
				foreach ($arr1 as $key => $value)
				{
					if ($key != "name") continue;
					$search_text[] = $value;
				}
			}
		}

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
	}
	
}



Elberos_Commerce_Plugin::init();

}