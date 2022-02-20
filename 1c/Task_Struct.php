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


namespace Elberos\Commerce\_1C;


/* Check if Wordpress */
if (!defined('ABSPATH')) exit;


if ( !class_exists( Task_Struct::class ) && class_exists( \Elberos\StructBuilder::class ) ) 
{

class Task_Struct extends \Elberos\StructBuilder
{
	
	/**
	 * Get entity name
	 */
	public static function getEntityName()
	{
		return "elberos_commerce_1c_task";
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
				"api_name" => "import_id",
				"label" => "ID импорта",
				"type" => "input",
			])
			
			->addField
			([
				"api_name" => "classifier_id",
				"label" => "ID классификатора",
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
				"api_name" => "name",
				"label" => "Название",
				"type" => "input",
			])
			
			->addField
			([
				"api_name" => "data",
				"label" => "xml",
				"type" => "textarea",
			])
			
			->addField
			([
				"api_name" => "status",
				"label" => "Статус",
				"type" => "select",
				"options" =>
				[
					["id"=>"0", "value"=>"Запланировано"],
					["id"=>"1", "value"=>"Выполнено"],
					["id"=>"2", "value"=>"В процессе"],
				]
			])
			
			->addField
			([
				"api_name" => "type",
				"label" => "Тип",
				"type" => "select",
				"options" =>
				[
					["id"=>"product_param", "value"=>"Параметр товара"],
					["id"=>"product_image", "value"=>"Картинка товара"],
					["id"=>"product", "value"=>"Товар"],
					["id"=>"category", "value"=>"Категории"],
					["id"=>"price_type", "value"=>"Тип цены"],
					["id"=>"warehouse", "value"=>"Склад"],
					["id"=>"offer", "value"=>"Предложение"],
					["id"=>"invoices", "value"=>"Заказы"],
				]
			])
			
			->addField
			([
				"api_name" => "error_code",
				"label" => "Код ошибки",
				"type" => "input",
			])
			
			->addField
			([
				"api_name" => "error_message",
				"label" => "Сообщение ошибки",
				"type" => "textarea",
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
			
			->addField
			([
				"api_name" => "gmtime_end",
				"label" => "Дата обработки",
				"column_value" => function($struct, $item)
				{
					return \Elberos\wp_from_gmtime( $item["gmtime_add"] );
				},
			])
		;
	}
	
}

}