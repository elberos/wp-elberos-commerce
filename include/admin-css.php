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

if ( !class_exists( CSS::class ) ) 
{
class CSS
{
	static function show_css($hook_suffix)
	{
		global $pagenow;
		$screen = get_current_screen();
		$post_type = $screen ? $screen->post_type : "";
		
		if (
			($pagenow == "post.php" or $pagenow == "post-new.php") &&
				in_array($post_type, ["products", "products_catalog"]) or
			in_array($pagenow, ["elberos-commerce-product-params"])
		)
		{
			wp_enqueue_media();
			wp_enqueue_script( 'vue', '/wp-content/plugins/wp-elberos-core/assets/vue.min.js', false );
			// wp_enqueue_script( 'vue', '/wp-content/plugins/wp-elberos-core/assets/vue/vue.runtime.global.prod.js', false );
			?>
			<style>
			.elberos-commerce .cursor, .elberos-commerce a.cursor
			{
				cursor: pointer;
			}
			.elberos-commerce .nav-tab-wrapper
			{
				padding-top: 0;
			}
			.elberos-commerce .nav-tab-data
			{
				display: none;
			}
			.elberos-commerce .nav-tab-data.nav-tab-data-active
			{
				display: block;
			}
			.elberos-commerce .product_category
			{
				font-size: 0;
				padding-bottom: 5px;
			}
			.elberos-commerce .product_category_name, .elberos-commerce .product_category_buttons
			{
				display: inline-block;
				vertical-align: top;
				font-size: 14px;
			}
			.elberos-commerce .product_category_name
			{
				width: calc(100% - 65px);
			}
			.elberos-commerce .product_category_buttons
			{
				width: 60px;
			}
			.elberos-commerce .product_category_buttons button
			{
				cursor: pointer;
			}
			.elberos-commerce .product_param
			{
				position: relative;
				font-size: 0;
				padding-bottom: 5px;
			}
			.elberos-commerce .product_param_name,
			.elberos-commerce .product_param_value,
			.elberos-commerce .product_param_buttons
			{
				position: relative;
				display: inline-block;
				vertical-align: middle;
				font-size: 14px;
			}
			.elberos-commerce .product_param_name
			{
				width: 100px;
				text-align: right;
				padding-bottom: 5px;
			}
			.elberos-commerce .product_param_value
			{
				width: calc(100% - 170px);
				font-size: 14px;
				padding-left: 5px;
				padding-right: 5px;
			}
			.elberos-commerce .product_param_value input,
			.elberos-commerce .product_param_value select
			{
				width: 100%;
				max-width: 100%;
			}
			.elberos-commerce .product_param_buttons
			{
				width: 60px;
			}
			.elberos-commerce .product_param_buttons button
			{
				cursor: pointer;
			}
			.elberos-commerce .product_photos
			{
				font-size: 0;
			}
			.elberos-commerce .product_photo
			{
				position: relative;
				display: inline-block;
				vertical-align: top;
				margin: 5px;
			}
			.elberos-commerce .product_photo .button-delete
			{
				position: absolute;
				cursor: pointer;
				top: 0px;
				right: 0px;
				font-size: 30px;
				width: 30px;
				height: 30px;
				background-color: white;
				color: red;
				border: 1px #ccc solid;
				border-radius: 2px;
			}
			</style>
			<?php
		}
	}
}
}