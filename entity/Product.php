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


/* Check if Wordpress */
if (!defined('ABSPATH')) exit;


if ( !class_exists( Product::class ) && class_exists( \Elberos\StructBuilder::class ) ) 
{

class Product extends \Elberos\StructBuilder
{
	
	/**
	 * Get entity name
	 */
	public static function getEntityName()
	{
		return "elberos_commerce_products";
	}
	
	
	
	/**
	 * Init struct
	 */
	public function init()
	{
		$this
			->addField
			([
				"api_name" => "show_in_catalog",
				"label" => "Разместить в каталоге",
				"type" => "select",
				"options" =>
				[
					["id"=>0, "value"=>"Нет"],
					["id"=>1, "value"=>"Да"],
				]
			])
			
			->addField
			([
				"api_name" => "show_in_top",
				"label" => "Показывать на главной",
				"type" => "select",
				"options" =>
				[
					["id"=>0, "value"=>"Нет"],
					["id"=>1, "value"=>"Да"],
				]
			])
			
			->addField
			([
				"api_name" => "main_page_pos",
				"label" => "Позиция в каталоге",
				"type" => "input",
			])
			
			->addField
			([
				"api_name" => "vendor_code",
				"label" => "Артикул",
				"type" => "input",
			])
			
			->addField
			([
				"api_name" => "code_1c",
				"label" => "Код 1С",
				"type" => "input",
			])
			
			->addField
			([
				"api_name" => "catalog_id",
				"label" => "Каталог",
				"type" => "select",
			])
			
			->addField
			([
				"api_name" => "name",
				"label" => "Название",
				"type" => "input",
			])
		;
	}
	
	
}

}