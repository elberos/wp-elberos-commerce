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
		
		/* Find client */
		add_filter
		(
			'elberos_commerce_basket_find_client', '\\Elberos\\Commerce\\Api::elberos_commerce_basket_find_client',
			10, 4
		);
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
		if ($product_id == -1) return $basket;
		
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
		
		/* Get products data */
		$products_meta = \Elberos\Commerce\Api::getBasketProducts($basket);
		
		/* Send data */
		$send_data = isset($_POST['data']) ? $_POST['data'] : [];
		
		/* Validation */
		$validation = apply_filters
		(
			'elberos_commerce_basket_validation',
			null, $send_data, $basket, $products_meta
		);
		if ($validation != null)
		{
			return
			[
				"message" => "Ошибка валидации",
				"fields" => isset($validation["fields"]) ? $validation["fields"] : [],
				"code" => -1,
			];
		}
		
		/* Get utm */
		$utm = isset($_POST["utm"]) ? $_POST["utm"] : [];
		$utm = apply_filters( 'elberos_form_utm', $utm );
		
		/* Create secret code */
		$secret_code = wp_generate_password(12, false, false);
		
		/* Calculate price */
		$price = static::getBasketPrice($basket, $products_meta);
		
		/* Remove meta param */
		foreach ($products_meta as &$arr)
		{
			if (isset($arr['meta'])) unset( $arr['meta'] );
		}
		
		/* Find client */
		$find_client_res =
		[
			'code' => 0,
			'message' => '',
			'register' => false,
			'client_id' => null,
			'item' => null,
		];
		$find_client_res = apply_filters
		(
			'elberos_commerce_basket_find_client',
			$find_client_res, $send_data, $basket, $products_meta
		);
		$client_id = isset($find_client_res['client_id']) ? $find_client_res['client_id'] : null;
		$client_register = isset($find_client_res['register']) ? $find_client_res['register'] : false;
		
		/* Error */
		if ($find_client_res['code'] < 0)
		{
			return
			[
				"message" => $find_client_res["message"],
				"code" => $find_client_res["code"],
			];
		}
		
		/* Client not found */
		if ($client_id == null)
		{
			return
			[
				"message" => "Клиент не найден",
				"code" => -1,
			];
		}
		
		/* Insert data */
		// $wpdb->show_errors();
		$table_invoice = $wpdb->prefix . 'elberos_commerce_invoice';
		$wpdb->insert
		(
			$table_invoice,
			[
				"secret_code" => $secret_code,
				"send_data" => json_encode($send_data),
				"products_meta" => json_encode($products_meta),
				"basket" => json_encode( array_values($basket) ),
				"utm" => json_encode($utm),
				"price" => $price,
				"client_id" => $client_id,
				"gmtime_add" => \Elberos\dbtime(),
			]
		);
		
		/* Invoice id */
		$invoice_id = $wpdb->insert_id;
		
		/* Clear basket */
		static::api_clear_basket($site);
		
		/* Auth client if need */
		if ($client_register == true && isset($find_client_res['item']))
		{
			\Elberos\UserCabinet\Api::create_session($find_client_res['item']);
		}
		
		return
		[
			"invoice_id" => $invoice_id,
			"secret_code" => $secret_code,
			"message" => "OK",
			"code" => 1,
		];
	}
	
	
	
	/**
	 * Find client
	 */
	public static function elberos_commerce_basket_find_client($client_res, $send_data, $basket, $products_meta)
	{
		global $wpdb;
		
		if ($client_res['client_id'] != null) return $client_res;
		
		$email = isset($send_data['email']) ? $send_data['email'] : '';
		
		/* Find client */
		$table_clients = $wpdb->prefix . 'elberos_clients';
		$sql = $wpdb->prepare
		(
			"SELECT * FROM $table_clients WHERE email = %s", $email
		);
		$row = $wpdb->get_row($sql, ARRAY_A);
		if ($row)
		{
			$client_res['register'] = false;
			$client_res['client_id'] = $row['id'];
			$client_res['item'] = $row;
		}
		
		/* Register client */
		else
		{
			$res = \Elberos\UserCabinet\Api::user_register($send_data);
			$client_res['code'] = $res['code'];
			$client_res['message'] = $res['message'];
			
			if ($res['code'] == 1)
			{
				$client_res['register'] = true;
				$client_res['client_id'] = $res['item']['id'];
				$client_res['item'] = $res['item'];
			}
		}
		
		return $client_res;
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
	public static function getBasketProducts($basket)
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
		return $products_meta;
	}
	
	
	
	/**
	 * Get basket products
	 */
	public static function getBasketPrice($basket, $products_meta)
	{
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
	 * Get photos
	 */
	public static function getPhotos($photo_ids)
	{
		$photos = [];
		foreach ($photo_ids as $photo_id)
		{
			$photos[$photo_id] =
			[
				"id" => $photo_id,
				"url" => \Elberos\get_image_url($photo_id, "medium_large"),
			];
		}
		return $photos;
	}
	
	
	
	/**
	 * Get photos
	 */
	public static function getMainPhoto($product_item, $photos)
	{
		$main_photo = null;
		if (isset($photos[ $product_item["main_photo_id"] ]))
		{
			$main_photo = $photos[ $product_item["main_photo_id"] ];
		}
		else
		{
			$main_photo = array_values($photos); $main_photo = array_shift($main_photo);
		}
		return $main_photo;
	}
	
	
	
	/**
	 * Get products by ids
	 */
	public static function getProducts($products_id, $load_images = false)
	{
		global $wpdb;
		
		$items = [];
		if (count($products_id) > 0)
		{
			$sql = $wpdb->prepare
			(
				"select * from " . $wpdb->base_prefix . "elberos_commerce_products " .
				"where id in (" . implode(",", array_fill(0, count($products_id), "%d")) . ") ",
				$products_id
			);
			$items = $wpdb->get_results($sql, ARRAY_A);
		}
		
		/* Параметры товара */
		$items = array_map
		(
			function ($item)
			{
				$item["text"] = @json_decode($item["text"], true);
				unset($item["xml"]);
				return $item;
			},
			$items
		);
		
		/* Список фотографий */
		$photo_ids = array_map
		(
			function ($item)
			{
				return $item["main_photo_id"];
			},
			$items
		);
		
		$photos = static::getPhotos($photo_ids);
		
		return
		[
			"items" => $items,
			"photos" => $photos,
		];
	}
	
	
	
	/**
	 * Get product from meta
	 */
	public static function getProductFromMeta($products_meta, $product_id)
	{
		$items = $products_meta["items"];
		foreach ($items as $item)
		{
			if ($item["id"] == $product_id)
			{
				return $item;
			}
		}
		return null;
	}
	
	
	
	/**
	 * Поиск классификатора по коду 1с
	 */
	static function findCatalogByCode($code_1c)
	{
		global $wpdb;
		
		$table_name = $wpdb->base_prefix . "elberos_commerce_catalogs";
		$sql = \Elberos\wpdb_prepare
		(
			"select * from $table_name " .
			"where code_1c = :code_1c limit 1",
			[
				"code_1c" => $code_1c,
			]
		);
		
		return $wpdb->get_row($sql, ARRAY_A);
	}
	
	
	
	/**
	 * Возращает параметры товара
	 */
	static function getProductsParams($classifier_id)
	{
		global $wpdb;
		
		/* Получаем параметры товара */
		$table_name = $wpdb->base_prefix . "elberos_commerce_products_params";
		$table_name_classifier = $wpdb->base_prefix . "elberos_commerce_classifiers";
		$sql = \Elberos\wpdb_prepare
		(
			"select t1.* from $table_name as t1 " .
			"inner join $table_name_classifier as t2 on (t1.classifier_id = t2.id) " .
			"where t2.code_1c = :classifier_id",
			[
				"classifier_id" => $classifier_id,
			]
		);
		$params = $wpdb->get_results($sql, ARRAY_A);
		
		/* Значения параметров товара */
		$table_name = $wpdb->base_prefix . "elberos_commerce_products_params_values";
		$sql = \Elberos\wpdb_prepare
		(
			"select * from $table_name ",
			[
			]
		);
		$params_values = $wpdb->get_results($sql, ARRAY_A);
		foreach ($params as &$param)
		{
			foreach ($params_values as $param_value)
			{
				if ($param_value['param_id'] == $param['id'])
				{
					if (!isset($param['values'])) $param['values'] = [];
					$param['values'][] = $param_value;
				}
			}
		}
		
		return $params;
	}
}

}