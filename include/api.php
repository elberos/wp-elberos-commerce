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


if ( !class_exists( Api::class ) ) 
{

class Api
{
	
	/**
	 * Init api
	 */
	public static function init()
	{
		add_action('elberos_register_routes', '\\Elberos\\Commerce\\Api::register_routes');
	}
	
	
	
	/**
	 * Register API
	 */
	public static function register_routes($site)
	{
		$site->add_api("elberos_commerce", "add_to_basket", "\\Elberos\\Commerce\\Api::api_add_to_basket");
		$site->add_api("elberos_commerce", "remove_from_basket", "\\Elberos\\Commerce\\Api::api_remove_from_basket");
		$site->add_api("elberos_commerce", "clear_basket", "\\Elberos\\Commerce\\Api::api_clear_basket");
		$site->add_api("elberos_commerce", "send_basket", "\\Elberos\\Commerce\\Api::api_send_basket");
	}
	
	
	
	/**
	 * Find basket item
	 */
	public static function findBasketIndex($basket, $product_id, $product_params, $product_count)
	{
		foreach ($basket as $index => $row)
		{
			$basket_product_id = isset($row['product_id']) ? $row['product_id'] : -1;
			$basket_product_params = isset($row['product_params']) ? $row['product_params'] : null;
			$basket_product_count = isset($row['product_count']) ? $row['product_count'] : 1;
			
			if ($product_id != $basket_product_id) continue;
			if (!\Elberos\equalArr($product_params, $basket_product_params)) continue;
			
			return $index;
		}
		return -1;
	}
	
	
	
	/**
	 * Add to basket
	 */
	public static function addToBasket($basket, $product_id, $product_params, $product_count)
	{
		$basket_index = static::findBasketIndex($basket, $product_id, $product_params, $product_count);
		
		/* Add */
		if ($basket_index == -1)
		{
			$basket[] =
			[
				'product_id' => $product_id,
				'product_params' => $product_params,
				'product_count' => $product_count,
			];
		}
		
		/* Change */
		else
		{
			$basket[$basket_index]['product_count'] = $product_count;
		}
		
		return $basket;
	}
	
	
	
	/**
	 * Add to basket
	 */
	public static function api_add_to_basket($site)
	{
		/* Get basket data */
		$basket = static::getBasketData();
		
		/* Get product data */
		$product_id = isset($_POST['product_id']) ? $_POST['product_id'] : -1;
		$product_params = isset($_POST['product_params']) ? $_POST['product_params'] : [];
		$product_count = (int) (isset($_POST['product_count']) ? $_POST['product_count'] : 0);
		
		$basket = static::addToBasket($basket, $product_id, $product_params, $product_count);
		
		/* Set cookie */
		$basket_data = \Elberos\base64_encode_url( json_encode($basket) );
		setcookie('basket', $basket_data, time() + 30*24*60*60, '/');
		
		return
		[
			"message" => "OK",
			"basket" => $basket,
			"code" => 1,
		];
	}
	
	
	
	/**
	 * Remove from basket
	 */
	public static function api_remove_from_basket($site)
	{
		/* Get basket data */
		$basket = static::getBasketData();
		
		/* Get product data */
		$product_id = isset($_POST['product_id']) ? $_POST['product_id'] : -1;
		$product_params = isset($_POST['product_params']) ? $_POST['product_params'] : [];
		$product_count = (int) (isset($_POST['product_count']) ? $_POST['product_count'] : 0);
		
		$basket_index = static::findBasketIndex($basket, $product_id, $product_params, $product_count);
		if ($basket_index != -1)
		{
			unset($basket[$basket_index]);
			
			/* Set cookie */
			$basket_data = \Elberos\base64_encode_url( json_encode($basket) );
			setcookie('basket', $basket_data, time() + 30*24*60*60, '/');
		}
		
		return
		[
			"message" => "OK",
			"basket" => $basket,
			"code" => 1,
		];
	}
	
	
	
	/**
	 * Clear basket
	 */
	public static function api_clear_basket($site)
	{
		/* Set cookie */
		$basket_data = \Elberos\base64_encode_url( json_encode([]) );
		setcookie('basket', $basket_data, 0, '/');
		
		return
		[
			"message" => "OK",
			"code" => 1,
		];
	}
	
	
	
	/**
	 * Send basket
	 */
	public static function api_send_basket($site)
	{
		global $wpdb;
		
		/* Get basket data */
		$basket = static::getBasketData();
		if (count($basket) == 0)
		{
			return
			[
				"message" => "Корзина пустая",
				"code" => -1,
			];
		}
		
		/* Send data */
		$send_data = isset($_POST['data']) ? $_POST['data'] : [];
		
		/* Get utm */
		$utm = isset($_POST["utm"]) ? $_POST["utm"] : [];
		$utm = apply_filters( 'elberos_form_utm', $utm );
		
		/* Create secret code */
		$secret_code = wp_generate_password(12, false, false);
		
		/* Calculate price */
		$price = static::getBasketPrice($basket);
		
		/* Insert data */
		// $wpdb->show_errors();
		$table_invoice = $wpdb->prefix . 'elberos_commerce_invoice';
		$wpdb->insert
		(
			$table_invoice,
			[
				"secret_code" => $secret_code,
				"send_data" => json_encode($send_data),
				"basket" => json_encode($basket),
				"utm" => json_encode($utm),
				"price" => $price,
				"client_id" => null,
				"gmtime_add" => \Elberos\dbtime(),
			]
		);
		
		/* Invoice id */
		$invoice_id = $wpdb->invoice_id;
		
		/* Clear basket */
		static::api_clear_basket($site);
		
		return
		[
			"invoice_id" => $invoice_id,
			"message" => "OK",
			"code" => 1,
		];
	}
	
	
	
	/**
	 * Get basket
	 */
	public static function getBasketData()
	{
		$basket_data = isset($_COOKIE["basket"]) ? $_COOKIE["basket"] : "";
		$basket_data = @\Elberos\base64_decode_url($basket_data);
		$basket = @json_decode($basket_data, true);
		if (!$basket) $basket = [];
		return $basket;
	}
	
	
	
	/**
	 * Get basket products
	 */
	public static function getBasketProducts()
	{
		$basket = \Elberos\Commerce\Api::getBasketData();
		
		/* Get meta values */
		$products_id = array_map
		(
			function($row) { return $row["product_id"]; },
			$basket
		);
		
		/* Get products by id */
		$products_meta = \Elberos\Commerce\Api::getProducts($products_id);
		
		return [$basket, $products_meta];
	}
	
	
	
	/**
	 * Get basket products
	 */
	public static function getBasketPrice()
	{
		list($basket, $products_meta) = \Elberos\Commerce\Api::getBasketProducts();
		
		$price_total = 0;
		foreach ($basket as $basket_data)
		{
			$product_count = intval( isset($basket_data["product_count"]) ? $basket_data["product_count"] : 0 );
			$product_id = isset($basket_data["product_id"]) ? $basket_data["product_id"] : -1;
			$product = isset($products_meta[$product_id]) ? $products_meta[$product_id] : null;
			
			if ($product == null) continue;
			if ($product_count < 0) $product_count = 0;
			
			$price_total = $price_total + $product['price'] * $product_count;
		}
		
		return $price_total;
	}
	
	
	
	/**
	 * Get products by ids
	 */
	public static function getProducts($products_id)
	{
		global $wpdb;
		
		$arr = [];
		$products_count = count($products_id);
		if ($products_count > 0)
		{
			$sql = $wpdb->prepare
			(
				"select * from {$wpdb->prefix}postmeta as postmeta " .
				"where post_id in (" . implode(",", array_fill(0, count($products_id), "%d")) . ") ",
				$products_id
			);
			$arr = $wpdb->get_results($sql, ARRAY_A);
		}
		
		/* Meta */
		$products_meta = [];
		foreach ($arr as $row)
		{
			$post_id = $row["post_id"];
			if (!isset($post_id)) $products_meta[ $post_id ] =
			[
				'meta' => [],
			];
			$products_meta[ $post_id ]['meta'][] = $row;
		}
		
		/* Parse */
		foreach ($products_meta as $post_id => &$products_arr)
		{
			$meta = $products_arr['meta'];
			
			/* Product text */
			$product_text = \Elberos\find_item($meta, "meta_key", "product_text");
			$product_text = @unserialize( isset($product_text["meta_value"]) ? $product_text["meta_value"] : "" );
			
			/* Product price */
			$product_price = \Elberos\find_item($meta, "meta_key", "product_price");
			$product_price = isset($product_price["meta_value"]) ? $product_price["meta_value"] : "";
			
			/* Product photos */
			$product_photos = \Elberos\find_items($meta, "meta_key", "product_photo_id");
			$product_photos = array_map( function($row) { return $row["meta_value"]; }, $product_photos );
			
			$product_photo = isset($product_photos[0]) ? $product_photos[0] : "";
			$product_photo_url = \Elberos\get_image_url($product_photo, "medium_large");
			
			/* Array */
			$products_arr["id"] = $post_id;
			$products_arr["text"] = $product_text;
			$products_arr["price"] = $product_price;
			$products_arr["photos"] = $product_photos;
			$products_arr["photo_id"] = $product_photo;
			$products_arr["photo_url"] = $product_photo_url;
		}
		
		return $products_meta;
	}
}

}