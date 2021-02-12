<?php
/*!
 *  Elberos Commerce
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
		
		if ($pagenow == "post.php" && in_array($post_type, ["products", "products_catalog"]))
		{
			wp_enqueue_media();
			?>
			<style>
			.elberos-commerce .cursor, .elberos-commerce a.cursor
			{
				cursor: pointer;
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
			.elberos-commerce .product_category_name, .product_category_buttons
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