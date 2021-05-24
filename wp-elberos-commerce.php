<?php
/**
 * Plugin Name: WordPress Commerce
 * Description: Commerce plugin for WordPress
 * Version:     0.1.0
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
				require_once __DIR__ . "/include/admin-css.php";
				require_once __DIR__ . "/include/admin-invoices.php";
				require_once __DIR__ . "/include/admin-product-params.php";
				require_once __DIR__ . "/include/admin-product-params-values.php";
				require_once __DIR__ . "/include/admin-metabox.php";
			}
		);
		add_action('admin_menu', 'Elberos_Commerce_Plugin::register_admin_menu');
		add_action('init', 'Elberos_Commerce_Plugin::register_post_types');
		add_action('add_meta_boxes', 'Elberos_Commerce_Plugin::register_meta_boxes');
		add_action('save_post', '\\Elberos\\Commerce\\Metabox::save_metabox' );
		add_action('admin_head', '\\Elberos\\Commerce\\CSS::show_css');
		add_filter('post_type_link', 'Elberos_Commerce_Plugin::post_type_link', false, true);
		add_filter('manage_products_posts_columns', 'Elberos_Commerce_Plugin::products_columns');
		add_action('manage_products_posts_custom_column', 'Elberos_Commerce_Plugin::products_columns_custom', 0, 2);
		add_action('plugins_loaded', 'Elberos_Commerce_Plugin::rank_math_loaded');
		
		/* Remove plugin updates */
		add_filter( 'site_transient_update_plugins', 'Elberos_Commerce_Plugin::filter_plugin_updates' );
		
		/* Include api */
		include __DIR__ . "/include/api.php";
		\Elberos\Commerce\Api::init();
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
				echo "1";
			},
			'/wp-content/plugins/wp-elberos-commerce/images/commerce.png',
			30
		);
		
		add_submenu_page(
			'elberos-commerce', 
			'Параметры товаров', 'Параметры товаров',
			'manage_options', 'elberos-commerce-product-params',
			function()
			{
				\Elberos\Commerce\ProductParams::show();
			}
		);
		
		add_submenu_page(
			'elberos-commerce', 
			'Заказы в магазине', 'Заказы в магазине', 
			'manage_options', 'elberos-commerce-invoice',
			function()
			{
				\Elberos\Commerce\Invoices::show();
			}
		);
	}
	
	
	
	/**
	 * Register post types
	 */
	public static function register_post_types()
	{
		static::register_products();
		static::register_catalog();
	}
	
	
	
	/**
	 * Register meta boxes
	 */
	public static function register_meta_boxes()
	{
		add_meta_box
		(
			'products_catalog_title',
			'Описание категории',
			'\Elberos\Commerce\Metabox::show_products_catalog_title',
			[
				'products_catalog',
			],
			"normal",
			"high",
		);
		
		add_meta_box
		(
			'product_title',
			'Описание товара',
			'\Elberos\Commerce\Metabox::show_products_title',
			[
				'products',
			],
			"normal",
			"high",
		);
		
		add_meta_box
		(
			'product_categories',
			'Категории',
			'\Elberos\Commerce\Metabox::show_categories',
			[
				'products',
			],
			"side",
			"default",
		);
		
		add_meta_box
		(
			'product_params',
			'Параметры товара или услуги',
			'\Elberos\Commerce\Metabox::show_meta_params',
			[
				'products',
			],
			"normal",
			"default",
		);
		
		add_meta_box
		(
			'product_photos',
			'Фотографии',
			'\Elberos\Commerce\Metabox::show_photos',
			[
				'products',
			],
			"normal",
			"default",
		);
	}
	
	
	
	/**
	 * Register product
	 */
	public static function register_products()
	{
		register_post_type( 'products',
		[
			'label'  => null,
			'labels' => [
				'name'               => 'Товары и услуги', // основное название для типа записи
				'singular_name'      => 'Товары и услуги', // название для одной записи этого типа
				'add_new'            => 'Добавить товар или услугу', // для добавления новой записи
				'add_new_item'       => 'Добавление товара', // заголовка у вновь создаваемой записи в админ-панели.
				'edit_item'          => 'Редактирование товара', // для редактирования типа записи
				'new_item'           => 'Новый товар', // текст новой записи
				'view_item'          => 'Смотреть товар', // для просмотра записи этого типа.
				'search_items'       => 'Искать товар', // для поиска по этим типам записи
				'not_found'          => 'Не найдено', // если в результате поиска ничего не было найдено
				'not_found_in_trash' => 'Не найдено в корзине', // если не было найдено в корзине
				'parent_item_colon'  => '', // для родителей (у древовидных типов)
				'menu_name'          => 'Товары и услуги', // название меню
			],
			'description'         => 'Товара или услуга',
			'public'              => true,
			// 'publicly_queryable'  => true, // зависит от public
			// 'exclude_from_search' => true, // зависит от public
			'show_ui'             => true, // зависит от public
			'show_in_nav_menus'   => true, // зависит от public
			'show_in_menu'        => 'elberos-commerce', // показывать ли в меню адмнки
			// 'show_in_admin_bar'   => true, // зависит от show_in_menu
			// 'show_in_rest'        => true, // добавить в REST API. C WP 4.7
			'rest_base'           => null, // $post_type. C WP 4.7
			'menu_position'       => 30,
			'menu_icon'           => null,
			//'capability_type'   => 'post',
			//'capability_type'   => 'post',
			//'capabilities'      => 'post', // массив дополнительных прав для этого типа записи
			//'map_meta_cap'      => null, // Ставим true чтобы включить дефолтный обработчик специальных прав
			'hierarchical'        => false,
			'supports'            => [ 'title' ],
			//'title', 'editor', 'author', 'thumbnail', 'excerpt', 'trackbacks', 'custom-fields',
			//'comments', 'revisions','post-formats', 'page-attributes'
			'taxonomies'          => [],
			'has_archive'         => 'products',
			'rewrite'             => false,
			'query_var'           => true,
		] );
		
		add_permastruct
		(
			'products',
			"products/%product_id%-%products%",
			[
				'with_front' => false,
				'ep_mask' => EP_NONE,
				'paged' => true,
				'feed' => true,
				'forcomments' => false,
				'walk_dirs' => true,
				'endpoints' => true,
			]
		);
		
		add_rewrite_rule('^(ru|en)/products/([0-9]*)-([^/]*)$', 'index.php?products=$matches[3]', 'top');
	}
	
	
	
	/**
	 * Products columns
	 */
	public static function products_columns($columns)
	{
		return
		[
			"cb" => isset($columns["cb"]) ? $columns["cb"] : "",
			"title" => isset($columns["title"]) ? $columns["title"] : "",
			"price" =>  __('Цена', 'elberos-commerce'),
			"in_catalog" =>  __('В каталоге', 'elberos-commerce'),
			"date" => isset($columns["date"]) ? $columns["date"] : "",
		];
		return $columns;
	}
	
	
	
	/**
	 * Products columns custom
	 */
	public static function products_columns_custom($column, $post_id)
	{
		if ($column == "price")
		{
			$product_price = get_post_meta( $post_id, 'product_price', '' );
			$product_price = isset($product_price[0]) ? $product_price[0] : '';
			echo $product_price;
		}
		if ($column == "in_catalog")
		{
			$product_in_catalog = get_post_meta( $post_id, 'product_in_catalog', '' );
			$product_in_catalog = isset($product_in_catalog[0]) ? $product_in_catalog[0] : '';
			if ($product_in_catalog == 0) echo "Нет";
			else if ($product_in_catalog == 1) echo "Да";
		}
	}
	
	
	
	/**
	 * Register post types
	 */
	public static function register_catalog()
	{
		register_post_type( 'products_catalog',
		[
			'label'  => null,
			'labels' => [
				'name'               => 'Категория товара', // основное название для типа записи
				'singular_name'      => 'Категория товара', // название для одной записи этого типа
				'add_new'            => 'Добавить категорию', // для добавления новой записи
				'add_new_item'       => 'Добавление категории', // заголовка у вновь создаваемой записи в админ-панели.
				'edit_item'          => 'Редактирование категории', // для редактирования типа записи
				'new_item'           => 'Новая категория', // текст новой записи
				'view_item'          => 'Смотреть категории', // для просмотра записи этого типа.
				'search_items'       => 'Искать категорию', // для поиска по этим типам записи
				'not_found'          => 'Не найдено', // если в результате поиска ничего не было найдено
				'not_found_in_trash' => 'Не найдено в корзине', // если не было найдено в корзине
				'parent_item_colon'  => '', // для родителей (у древовидных типов)
				'menu_name'          => 'Категория товаров', // название меню
			],
			'description'         => 'Категория',
			'public'              => true,
			'publicly_queryable'  => true, // зависит от public
			'exclude_from_search' => true, // зависит от public
			'show_ui'             => true, // зависит от public
			'show_in_nav_menus'   => true, // зависит от public
			'show_in_menu'        => 'elberos-commerce', // показывать ли в меню адмнки
			'show_in_admin_bar'   => true, // зависит от show_in_menu
			'show_in_rest'        => true, // добавить в REST API. C WP 4.7
			'rest_base'           => null, // $post_type. C WP 4.7
			'menu_position'       => 30,
			'menu_icon'           => null,
			//'capability_type'   => 'post',
			//'capabilities'      => 'post', // массив дополнительных прав для этого типа записи
			//'map_meta_cap'      => null, // Ставим true чтобы включить дефолтный обработчик специальных прав
			'hierarchical'        => true,
			'supports'            => [ 'title' ],
			//'title', 'editor', 'author', 'thumbnail', 'excerpt', 'trackbacks', 'custom-fields',
			//'comments', 'revisions','post-formats', 'page-attributes'
			'taxonomies'          => [],
			'has_archive'         => 'catalog',
			'rewrite'             => false,
			'query_var'  => true,
		] );
		
		add_permastruct
		(
			'products_catalog',
			"catalog/%products_catalog%",
			[
				'with_front' => false,
				'ep_mask' => EP_NONE,
				'paged' => true,
				'feed' => true,
				'forcomments' => false,
				'walk_dirs' => true,
				'endpoints' => true,
			]
		);
		
		add_rewrite_rule
		(
			'^(ru|en)/catalog/(.+)/?$',
			'index.php?post_type=products_catalog&products_catalog=$matches[2]',
			'top'
		);
	}
	
	
	/**
	 * Post type link
	 */
	static function post_type_link($post_link, $post = 0)
	{
		global $wp_rewrite;
		if ($post->post_type == 'products')
		{
			$post_link = str_replace( '%product_id%', $post->ID, $post_link );
		}
		else if ($post->post_type == 'products_catalog')
		{
			$post_link = str_replace( '%products_catalog_id%', $post->ID, $post_link );
		}
		if ($post->post_type == 'products' or $post->post_type == 'products_catalog')
		{
			$post_link = parse_url($post_link);
			$post_link = isset($post_link["path"]) ? $post_link["path"] : "";
			$post_link = home_url("/ru" . $post_link);
		}
		return $post_link;
	}
	
	
	
	/**
	 * Rank math loaded
	 */
	static function rank_math_loaded()
	{
		if (class_exists(\RankMath::class))
		{
			$rank_math = \RankMath::get();
			$rank_math->settings->set("titles", "pt_products_add_meta_box", false);
			$rank_math->settings->set("titles", "pt_products_link_suggestions", false);
			$rank_math->settings->set("titles", "pt_products_catalog_add_meta_box", false);
			$rank_math->settings->set("titles", "pt_products_catalog_link_suggestions", false);
		}
	}
	
	
	
	/**
	 * Rank Math titles
	 */
	static function rank_math_titles($titles)
	{
		return $titles;
	}
}



Elberos_Commerce_Plugin::init();

}