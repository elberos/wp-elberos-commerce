<?php
/**
 * Plugin Name: Elberos Commerce
 * Description: Commerce plugin for WordPress
 * Version:     0.1.0
 * Author:      Elberos team <support@elberos.org>
 * License:     Apache License 2.0
 *
 *  (c) Copyright 2019-2020 "Ildar Bikmamatov" <support@elberos.org>
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
				require_once __DIR__ . "/include/admin-product.php";
			}
		);
		add_action('admin_menu', 'Elberos_Commerce_Plugin::register_admin_menu');
		add_action('init', 'Elberos_Commerce_Plugin::register_post_types');
		add_filter('post_type_link', 'Elberos_Commerce_Plugin::post_type_link', false, true);
	}
	
	
	
	/**
	 * Register Admin Menu
	 */
	public static function register_admin_menu()
	{
		add_menu_page
		(
			'Товары', 'Товары',
			'manage_options', 'elberos-commerce',
			function ()
			{
				\Elberos\Commerce\Product::show();
			},
			null,
			7
		);
	}
	
	
	
	/**
	 * Register post types
	 */
	public static function register_post_types()
	{
		register_post_type( 'catalog',
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
			'show_in_menu'        => true, // показывать ли в меню адмнки
			'show_in_admin_bar'   => true, // зависит от show_in_menu
			'show_in_rest'        => true, // добавить в REST API. C WP 4.7
			'rest_base'           => null, // $post_type. C WP 4.7
			'menu_position'       => 4,
			'menu_icon'           => null,
			//'capability_type'   => 'post',
			//'capabilities'      => 'post', // массив дополнительных прав для этого типа записи
			//'map_meta_cap'      => null, // Ставим true чтобы включить дефолтный обработчик специальных прав
			'hierarchical'        => true,
			'supports'            => [ 'title', 'editor','thumbnail','page-attributes' ], // 'title','editor','author','thumbnail','excerpt','trackbacks','custom-fields','comments','revisions','page-attributes','post-formats'
			'taxonomies'          => [],
			'has_archive'         => 'catalog',
			'rewrite'             => false,
			'query_var'  => true,
		] );
		
		add_permastruct
		(
			"catalog",
			"catalog/%catalog%",
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
		
		add_rewrite_rule('^(ru|en)/catalog/([^/]*)$', 'index.php?catalog=$matches[2]', 'top');
	}
	
	
	
	static function post_type_link($post_link, $post = 0)
	{
		global $wp_rewrite;
		if ($post->post_type == 'catalog')
		{
			$post_link = str_replace( '%catalog_id%', $post->ID, $post_link );
		}
		return $post_link;
	}
	
}



Elberos_Commerce_Plugin::init();

}