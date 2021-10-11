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
	
	static $price_types = [];
	static $product_params_filter = [];
	
	
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
	public static function findBasketIndex($basket, $offer_price_id)
	{
		foreach ($basket as $index => $row)
		{
			$basket_offer_id = isset($row['offer_price_id']) ? $row['offer_price_id'] : -1;
			if ($offer_price_id != $basket_offer_id) continue;
			return $index;
		}
		return -1;
	}
	
	
	
	/**
	 * Add to basket
	 */
	public static function addToBasket($basket, $offer_price_id, $count)
	{
		if ($offer_price_id == -1) return $basket;
		
		$basket_index = static::findBasketIndex($basket, $offer_price_id);
		
		/* Add */
		if ($basket_index == -1)
		{
			$basket[] =
			[
				'offer_price_id' => $offer_price_id,
				'count' => $count,
			];
		}
		
		/* Change */
		else
		{
			$basket[$basket_index]['count'] = $count;
		}
		
		return $basket;
	}
	
	
	
	/**
	 * Add to basket
	 */
	public static function api_add_to_basket($site)
	{
		global $wpdb;
		
		/* Get basket data */
		$basket = static::getBasketData();
		
		/* Get product data */
		$offer_price_id = isset($_POST['offer_price_id']) ? $_POST['offer_price_id'] : -1;
		$count = (int) (isset($_POST['count']) ? $_POST['count'] : 0);
		if ($count < 1) $count = 1;
		
		/* Получаем offer */
		$offers = static::getOffersByPriceId([$offer_price_id]);
		$offer = \Elberos\find_item($offers, "price_id", $offer_price_id);
		if ($offer == null)
		{
			return
			[
				"message" => "Offer not found",
				"code" => -1,
			];
		}
		
		/* Add to basket */
		$basket = static::addToBasket($basket, $offer_price_id, $count);
		
		/* Set cookie */
		$basket_data = \Elberos\base64_encode_url( json_encode($basket) );
		setcookie('basket', $basket_data, time() + 30*24*60*60, '/');
		
		/* Basket products */
		$product_id = $offer["product_id"];
		$products_items = \Elberos\Commerce\Api::getProducts([$product_id]);
		$product_item = \Elberos\find_item($products_items, "id", $product_id);
		if ($product_item == null)
		{
			return
			[
				"message" => "Product not found",
				"code" => -1,
			];
		}
		
		/* Чистим мета информацию */
		unset($product_item["offers"]);
		unset($product_item["photos"]);
		unset($product_item["params"]);
		unset($product_item["prices"]);
		unset($product_item["xml"]);
		unset($offer["offer_xml"]);
		
		return
		[
			"message" => "OK",
			"code" => 1,
			"basket" => array_values($basket),
			"product_id" => $product_id,
			"product_item" => $product_item,
			"offer" => $offer,
			"offer_price_id" => $offer_price_id,
			"count" => $count,
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
		$offer_price_id = isset($_POST['offer_price_id']) ? $_POST['offer_price_id'] : -1;
		$basket_index = static::findBasketIndex($basket, $offer_price_id);
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
			"basket" => array_values($basket),
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
		$basket_data = \Elberos\Commerce\Api::getBasketProducts($basket);
		
		/* Send data */
		$form_data = isset($_POST['data']) ? $_POST['data'] : [];
		
		/* Validation */
		$res = apply_filters
		(
			'elberos_commerce_basket_validation',
			[
				'validation'=>[],
				'validation_error'=>null,
				'form_data'=>$form_data,
				'basket_data'=>$basket_data
			]
		);
		$form_data = $res['form_data'];
		$basket_data = $res['basket_data'];
		
		/* If error */
		$validation = $res['validation'];
		$validation_error = $res['validation_error'];
		if ($validation != null && count($validation) > 0)
		{
			return
			[
				"message" => ($validation_error != null) ? $validation_error : "Ошибка валидации",
				"fields" => isset($validation["fields"]) ? $validation["fields"] : [],
				"code" => -1,
			];
		}
		
		/* Get utm */
		$utm = isset($_POST["utm"]) ? $_POST["utm"] : [];
		$utm = apply_filters( 'elberos_form_utm', $utm );
		
		/* Generate code 1c */
		$code_1c = \Elberos\uid();
		
		/* Create secret code */
		$secret_code = wp_generate_password(12, false, false);
		
		/* Calculate price */
		$basket_price = static::getBasketPrice($basket_data);
		
		/* Find client */
		$find_client_res =
		[
			'code' => 0,
			'message' => '',
			'register' => false,
			'client_id' => null,
			'form_data' => $form_data,
			'products_meta' => $products_meta,
			'basket' => $basket,
			'item' => null,
		];
		$find_client_res = apply_filters('elberos_commerce_basket_find_client', $find_client_res);
		
		$client = isset($find_client_res['client']) ? $find_client_res['client'] : [];
		$client_id = isset($find_client_res['client_id']) ? $find_client_res['client_id'] : null;
		$client_register = isset($find_client_res['register']) ? $find_client_res['register'] : false;
		$client_code_1c = isset($client['code_1c']) ? $client['code_1c'] : '';
		$form_data = isset($find_client_res['form_data']) ? $find_client_res['form_data'] : null;
		
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
		
		$comment = isset($form_data["comment"]) ? $form_data["comment"] : "";
		if (isset($form_data["comment"]))
		{
			unset($form_data["comment"]);
		}
		
		/* Insert data */
		// $wpdb->show_errors();
		$table_invoice = $wpdb->prefix . 'elberos_commerce_invoice';
		$wpdb->insert
		(
			$table_invoice,
			[
				"code_1c" => $code_1c,
				"secret_code" => $secret_code,
				"form_data" => json_encode($form_data),
				"basket_data" => json_encode($basket_data),
				"comment" => $comment,
				"utm" => json_encode($utm),
				"price" => $basket_price,
				"client_id" => $client_id,
				"client_code_1c" => $client_code_1c,
				"gmtime_add" => \Elberos\dbtime(),
				"gmtime_change" => \Elberos\dbtime(),
			]
		);
		
		/* Invoice id */
		$invoice_id = $wpdb->insert_id;
		
		/* Get invoice */
		$invoice = $wpdb->get_row
		(
			$wpdb->prepare("SELECT * FROM ".$wpdb->prefix."elberos_commerce_invoice WHERE id = %d", $invoice_id),
			ARRAY_A
		);
		if ($invoice)
		{
			$invoice['utm'] = json_decode($invoice['utm'], true);
			$invoice['form_data'] = json_decode($invoice['form_data'], true);
			$invoice['basket_data'] = json_decode($invoice['basket_data'], true);
		}
		
		/* Clear basket */
		static::api_clear_basket($site);
		
		/* Basket after */
		do_action
		(
			'elberos_commerce_basket_after',
			[
				'invoice'=>$invoice
			]
		);
		
		/* Auth client if need */
		/*
		if ($client_register == true && isset($find_client_res['item']))
		{
			\Elberos\UserCabinet\Api::create_session($find_client_res['item']);
		}
		*/
		
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
	public static function elberos_commerce_basket_find_client($client_res)
	{
		global $wpdb;
		
		if ($client_res['client_id'] != null) return $client_res;
		
		$form_data = isset($client_res['form_data']) ? $client_res['form_data'] : [];
		$email = isset($form_data['email']) ? $form_data['email'] : '';
		
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
			$client_res['client'] = $row;
		}
		
		/* Register client */
		else
		{
			$res = \Elberos\UserCabinet\Api::user_register($form_data);
			$client_res['code'] = $res['code'];
			$client_res['message'] = $res['message'];
			
			if ($res['code'] == 1)
			{
				$client_res['register'] = true;
				$client_res['client_id'] = $res['item']['id'];
				$client_res['client'] = $res['item'];
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
	public static function getBasketProducts($basket_data)
	{
		global $wpdb;
		
		$offer_price_ids = array_map(function($item){ return $item["offer_price_id"]; }, $basket_data);
		$offer_prices = static::getOffersByPriceId($offer_price_ids);
		$products_id = array_map(function($item){ return $item["product_id"]; }, $offer_prices);
		$products_items = static::getProducts($products_id);
		
		$basket = [];
		foreach ($basket_data as $data)
		{
			$count = $data["count"];
			$offer_price_id = $data["offer_price_id"];
			$offer_item = \Elberos\find_item($offer_prices, "price_id", $offer_price_id);
			if (!$offer_item)
			{
				continue;
			}
			
			$product_item = \Elberos\find_item($products_items, "id", $offer_item["product_id"]);
			if (!$product_item)
			{
				continue;
			}
			
			$basket[] =
			[
				"count" => $count,
				"offer_id" => (int) (isset($offer_item["offer_id"]) ? $offer_item["offer_id"] : 0),
				"offer_price_id" => (int) (isset($offer_item["price_id"]) ? $offer_item["price_id"] : 0),
				"offer_price" => (int) (isset($offer_item["price"]) ? $offer_item["price"] : 0),
				"offer_currency" => isset($offer_item["currency"]) ? $offer_item["currency"] : "",
				"offer_unit" => isset($offer_item["unit"]) ? $offer_item["unit"] : "",
				"offer_coefficient" => isset($offer_item["coefficient"]) ? $offer_item["coefficient"] : "",
				"offer_code_1c" => isset($offer_item["offer_code_1c"]) ? $offer_item["offer_code_1c"] : "",
				"offer_price_type_id" => (int) (isset($offer_item["price_type_id"]) ? $offer_item["price_type_id"] : 0),
				"offer_price_code_1c" =>
					isset($offer_item["price_type_code_1c"]) ? $offer_item["price_type_code_1c"] : "",
				"offer_xml" => isset($offer_item["offer_xml"]) ? $offer_item["offer_xml"] : "",
				"product_id" => isset($product_item["id"]) ? $product_item["id"] : "",
				"product_code_1c" => isset($product_item["code_1c"]) ? $product_item["code_1c"] : "",
				"product_name" => isset($product_item["name"]) ? $product_item["name"] : "",
				"product_params" => isset($product_item["params"]) ? $product_item["params"] : [],
				"product_main_photo_id" => isset($product_item["main_photo_id"]) ? $product_item["main_photo_id"] : 0,
				"product_main_photo_url" => isset($product_item["main_photo_url"]) ? $product_item["main_photo_url"] : 0,
				"product_text" => isset($product_item["text"]) ? $product_item["text"] : [],
				"product_vendor_code" => isset($product_item["vendor_code"]) ? $product_item["vendor_code"] : "",
				"product_xml" => isset($product_item["xml"]) ? $product_item["xml"] : "",
			];
		}
		
		return $basket;
	}
	
	
	
	/**
	 * Get basket products
	 */
	public static function getBasketPrice($basket_data)
	{
		$price_total = 0;
		foreach ($basket_data as $basket)
		{
			$count = $basket["count"];
			$offer_price = $basket["offer_price"];
			$price_total = $price_total + $offer_price * $count;
		}
		return $price_total;
	}
	
	
	
	/**
	 * Get products by ids
	 */
	public static function getProducts($products_id, $settings = [])
	{
		global $wpdb;
		
		/* Remove duplicates */
		if (gettype($products_id) == "array" && count($products_id) > 0)
		{
			$res = [];
			foreach ($products_id as $id)
			{
				if (!in_array($id, $res)) $res[] = $id;
			}
			$products_id = $res;
		}
		
		$items = [];
		$params = [];
		$photos = [];
		$offers = [];
		$photo_size = isset($settings["photo_size"]) ? $settings["photo_size"] : "medium";
		if (gettype($products_id) == "array" && count($products_id) > 0)
		{
			/* Items */
			$sql = $wpdb->prepare
			(
				"select * from " . $wpdb->base_prefix . "elberos_commerce_products as p " .
				"where id in (" . implode(",", array_fill(0, count($products_id), "%d")) . ") ",
				$products_id
			);
			$items = $wpdb->get_results($sql, ARRAY_A);
			
			/* Sort items by $products_id */
			$res = [];
			foreach ($products_id as $id)
			{
				$item = \Elberos\find_item($items, "id", $id);
				if ($item) $res[] = $item;
			}
			$items = $res;
			
			/* Params */
			$sql = $wpdb->prepare
			(
				"select * from " . $wpdb->base_prefix . "elberos_commerce_products_params as p " .
				"where product_id in (" . implode(",", array_fill(0, count($products_id), "%d")) . ") ",
				$products_id
			);
			$params = $wpdb->get_results($sql, ARRAY_A);
			
			/* Photos */
			$sql = $wpdb->prepare
			(
				"select * from " . $wpdb->base_prefix . "elberos_commerce_products_photos as p " .
				"where product_id in (" . implode(",", array_fill(0, count($products_id), "%d")) . ") ",
				$products_id
			);
			$photos = $wpdb->get_results($sql, ARRAY_A);
			
			/* Offers */
			$sql = $wpdb->prepare
			(
				"select
					t1.id as price_id,
					t1.price_type_id,
					t1.price_type_code_1c,
					t1.price,
					t1.currency,
					t1.unit,
					t1.coefficient,
					t1.name as offer_price_name,
					t2.id as offer_id,
					t2.product_id as product_id,
					t2.code_1c as offer_code_1c
				from {$wpdb->base_prefix}elberos_commerce_products_offers_prices as t1
				inner join {$wpdb->base_prefix}elberos_commerce_products_offers as t2
					on (t2.id = t1.offer_id)
				where t2.product_id in (" . implode(",", array_fill(0, count($products_id), "%d")) . ") ",
				$products_id
			);
			$offers = $wpdb->get_results($sql, ARRAY_A);
		}
		
		/* Обработка элементов */
		$items = array_map
		(
			function ($item)
			{
				$item["text"] = @json_decode($item["text"], true);
				return $item;
			},
			$items
		);
		
		/* Параметры товара */
		foreach ($items as &$item)
		{
			$item['params'] = array_filter
			(
				$params,
				function ($param) use ($item)
				{
					return $param["product_id"] == $item["id"];
				}
			);
			$item['photos'] = array_filter
			(
				$photos,
				function ($photo) use ($item)
				{
					return $photo["product_id"] == $item["id"];
				}
			);
			$item['offers'] = array_filter
			(
				$offers,
				function ($offer) use ($item)
				{
					return $offer["product_id"] == $item["id"];
				}
			);
			$photo_id = $item["main_photo_id"];
			$item["main_photo_url"] = \Elberos\get_image_url($photo_id, $photo_size);
		}
		
		return $items;
	}
	
	
	
	/**
	 * Возвращает список оферов у товара
	 */
	static function getProductOffers($product_id)
	{
		global $wpdb;
		$sql = \Elberos\wpdb_prepare
		(
			"SELECT
				t1.id as price_id,
				t1.price_type_id,
				t1.price_type_code_1c,
				t1.price,
				t1.currency,
				t1.unit,
				t1.coefficient,
				t1.name as offer_price_name,
				t2.id as offer_id,
				t2.product_id as product_id,
				t2.code_1c as offer_code_1c,
				t2.xml as offer_xml
			FROM {$wpdb->base_prefix}elberos_commerce_products_offers as t1
			INNER JOIN {$wpdb->base_prefix}elberos_commerce_products_offers_prices as t2
				on (t1.id = t2.offer_id)
			LEFT JOIN {$wpdb->base_prefix}elberos_commerce_price_types as t3
				on (t3.id = t2.price_type_id)
			WHERE t1.product_id=:product_id
			order by t1.id asc",
			[
				"product_id" => $product_id,
			]
		);
		$product_offers = $wpdb->get_results($sql, ARRAY_A);
		return $product_offers;
	}
	
	
	
	/**
	 * Get basket products
	 */
	public static function getOffersByPriceId($price_ids)
	{
		global $wpdb;
		
		$offers = [];
		if (count($price_ids) > 0)
		{
			$sql = $wpdb->prepare
			(
				"select
					t1.id as price_id,
					t1.price_type_id,
					t1.price_type_code_1c,
					t1.price,
					t1.currency,
					t1.unit,
					t1.coefficient,
					t1.name as offer_price_name,
					t2.id as offer_id,
					t2.product_id as product_id,
					t2.code_1c as offer_code_1c,
					t2.xml as offer_xml
				from {$wpdb->base_prefix}elberos_commerce_products_offers_prices as t1
				inner join {$wpdb->base_prefix}elberos_commerce_products_offers as t2
					on (t2.id = t1.offer_id)
				where t1.id in (" . implode(",", array_fill(0, count($price_ids), "%d")) . ") ",
				$price_ids
			);
			$offers = $wpdb->get_results($sql, ARRAY_A);
		}
		
		return $offers;
	}
	
	
	
	/**
	 * Возращает параметры товаров
	 */
	static function getProductsParams($classifier_id)
	{
		global $wpdb;
		
		/* Получаем параметры товара */
		$table_name = $wpdb->base_prefix . "elberos_commerce_params";
		$table_name_classifier = $wpdb->base_prefix . "elberos_commerce_classifiers";
		$sql = \Elberos\wpdb_prepare
		(
			"select t1.* from $table_name as t1 " .
			"inner join $table_name_classifier as t2 on (t1.classifier_id = t2.id) " .
			"where t2.code_1c = :classifier_id and is_deleted = 0",
			[
				"classifier_id" => $classifier_id,
			]
		);
		$params = $wpdb->get_results($sql, ARRAY_A);
		
		/* Значения параметров товара */
		$table_name = $wpdb->base_prefix . "elberos_commerce_params_values";
		$sql = \Elberos\wpdb_prepare
		(
			"select * from $table_name where is_deleted = 0 order by name asc",
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
	 * Find price type by 1c code
	 */
	static function findPriceTypeByCode($code_1c)
	{
		global $wpdb;
		
		if ($code_1c == "") return null;
		
		if (!array_key_exists($code_1c, static::$price_types))
		{
			$table_name = $wpdb->base_prefix . "elberos_commerce_price_types";
			$sql = \Elberos\wpdb_prepare
			(
				"select * from $table_name " .
				"where code_1c = :code_1c limit 1",
				[
					"code_1c" => $code_1c,
				]
			);
			$item = $wpdb->get_row($sql, ARRAY_A);
			static::$price_types[$code_1c] = $item;
		}
		
		$item = static::$price_types[$code_1c];
		return $item;
	}
	
	
}

}