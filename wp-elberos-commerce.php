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
				require_once __DIR__ . "/1c/Settings.php";
				require_once __DIR__ . "/1c/Task_Table.php";
				require_once __DIR__ . "/admin/Catalog_Table.php";
				require_once __DIR__ . "/admin/Category_Table.php";
				require_once __DIR__ . "/admin/Classifier_Table.php";
				require_once __DIR__ . "/admin/Invoice_Table.php";
				require_once __DIR__ . "/admin/PriceType_Table.php";
				require_once __DIR__ . "/admin/Product_Table.php";
				require_once __DIR__ . "/admin/ProductParam_Table.php";
				require_once __DIR__ . "/admin/ProductParamValue_Table.php";
				require_once __DIR__ . "/admin/Warehouse_Table.php";
			}
		);
		add_action('admin_menu', 'Elberos_Commerce_Plugin::register_admin_menu');
		
		/* Load entities */
		add_action(
			'plugins_loaded',
			function()
			{
				include __DIR__ . "/1c/Task_Struct.php";
				include __DIR__ . "/entity/Catalog.php";
				include __DIR__ . "/entity/Category.php";
				include __DIR__ . "/entity/Classifier.php";
				include __DIR__ . "/entity/Invoice.php";
				include __DIR__ . "/entity/Offer.php";
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
		
		/* Include 1C */
		include __DIR__ . "/1c/Controller.php";
		include __DIR__ . "/1c/Helper.php";
		include __DIR__ . "/1c/Import.php";
		include __DIR__ . "/1c/Task.php";
		\Elberos\Commerce\_1C\Controller::init();
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
				$table = new \Elberos\Commerce\Product_Table();
				$table->display();
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
				$table = new \Elberos\Commerce\Classifier_Table();
				$table->display();
			}
		);
		
		add_submenu_page
		(
			'elberos-commerce', 
			'Заказы в магазине', 'Заказы в магазине', 
			'manage_options', 'elberos-commerce-invoice',
			function()
			{
				$table = new \Elberos\Commerce\Invoice_Table();
				$table->display();
			}
		);
		
		add_submenu_page
		(
			'elberos-commerce', 
			'Настройки 1С', 'Настройки 1С', 
			'manage_options', 'elberos-commerce-1c-settings',
			function()
			{
				\Elberos\Commerce\_1C\Settings::show();
			}
		);
		
		add_submenu_page
		(
			'elberos-commerce', 
			'Импорт 1С', 'Лог 1С', 
			'manage_options', 'elberos-commerce-1c-task',
			function()
			{
				$table = new \Elberos\Commerce\_1C\Task_Table();
				$table->display();
			}
		);
	}
	
}



Elberos_Commerce_Plugin::init();

}