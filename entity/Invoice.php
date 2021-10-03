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

if ( !class_exists( Invoice::class ) && class_exists( \Elberos\StructBuilder::class ) ) 
{

class Invoice extends \Elberos\StructBuilder
{
	
	/**
	 * Get entity name
	 */
	public static function getEntityName()
	{
		return "elberos_commerce_invoice";
	}
	
	
	/**
	 * Init struct
	 */
	public function init()
	{
		$this
			->addField
			([
				"api_name" => "id",
				"label" => "Номер",
				"type" => "input",
			])
			
			->addField
			([
				"api_name" => "price",
				"label" => "Цена",
				"type" => "input",
				"column_value" => function($struct, $item)
				{
					return \Elberos\formatMoney( $item["price"] );
				},
			])
			
			->addField
			([
				"api_name" => "client_data",
			])
			
			->addField
			([
				"api_name" => "basket",
			])
			
			->addField
			([
				"api_name" => "products_meta",
			])
			
			->addField
			([
				"api_name" => "utm",
			])
			
			->addField
			([
				"api_name" => "gmtime_add",
				"label" => "Дата создания",
				"column_value" => function($struct, $item)
				{
					return \Elberos\wp_from_gmtime( $item["gmtime_add"] );
				},
			])
		;
	}
	
}

}