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
				"api_name" => "status",
				"label" => "Статус",
				"type" => "select",
				"options" =>
				[
					["id"=>"0", "value"=>"Новый"],
					["id"=>"1", "value"=>"Акцептован"],
				],
			])
			
			->addField
			([
				"api_name" => "status_pay",
				"label" => "Статус оплаты",
				"type" => "select",
				"options" =>
				[
					["id"=>"0", "value"=>"Не оплачен"],
					["id"=>"1", "value"=>"Оплачен"],
				],
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
			
			->addField
			([
				"api_name" => "type",
				"label" => "Тип",
				"type" => "select",
				"virtual" => true,
				"options" =>
				[
					["id"=>"1", "value"=>"Физ лицо"],
					["id"=>"2", "value"=>"Юр лицо"],
				],
				"column_value" => function ($struct, $item)
				{
					$form_data = @json_decode($item["form_data"], true);
					$value = isset($form_data["type"]) ? $form_data["type"] : "";					
					$option = $struct->getSelectOption("type", $value);
					if ($option) $value = $option['value'];
					return $value;
				}
			])
			->addField
			([
				"api_name" => "email",
				"label" => "Email",
				"type" => "input",
				"virtual" => true,
				"column_value" => function ($struct, $item)
				{
					$form_data = @json_decode($item["form_data"], true);
					return isset($form_data["email"]) ? $form_data["email"] : "";
				}
			])
			->addField
			([
				"api_name" => "phone",
				"label" => "Телефон",
				"type" => "input",
				"virtual" => true,
				"column_value" => function ($struct, $item)
				{
					$form_data = @json_decode($item["form_data"], true);
					return isset($form_data["phone"]) ? $form_data["phone"] : "";
				}
			])
			->addField
			([
				"api_name" => "name",
				"label" => "Имя",
				"type" => "input",
				"virtual" => true,
				"column_value" => function ($struct, $item)
				{
					$form_data = @json_decode($item["form_data"], true);
					return isset($form_data["name"]) ? $form_data["name"] : "";
				}
			])
			->addField
			([
				"api_name" => "surname",
				"label" => "Фамилия",
				"type" => "input",
				"virtual" => true,
				"column_value" => function ($struct, $item)
				{
					$form_data = @json_decode($item["form_data"], true);
					return isset($form_data["surname"]) ? $form_data["surname"] : "";
				}
			])
			->addField
			([
				"api_name" => "user_identifier",
				"label" => "ИИН",
				"type" => "input",
				"virtual" => true,
				"column_value" => function ($struct, $item)
				{
					$form_data = @json_decode($item["form_data"], true);
					return isset($form_data["user_identifier"]) ? $form_data["user_identifier"] : "";
				}
			])
			->addField
			([
				"api_name" => "company_name",
				"label" => "Название компании",
				"type" => "input",
				"virtual" => true,
				"column_value" => function ($struct, $item)
				{
					$form_data = @json_decode($item["form_data"], true);
					return isset($form_data["company_name"]) ? $form_data["company_name"] : "";
				}
			])
			->addField
			([
				"api_name" => "company_bin",
				"label" => "БИН",
				"type" => "input",
				"virtual" => true,
				"column_value" => function ($struct, $item)
				{
					$form_data = @json_decode($item["form_data"], true);
					return isset($form_data["company_bin"]) ? $form_data["company_bin"] : "";
				}
			])
			->addField
			([
				"api_name" => "comment",
				"label" => "Комментарий",
				"type" => "input",
			])
			->addField
			([
				"api_name" => "gmtime_add",
				"label" => "Дата создания",
				"type" => "input",
				"column_value" => function ($struct, $item)
				{
					return \Elberos\wp_from_gmtime($item["gmtime_add"]);
				}
			])
			->addField
			([
				"api_name" => "gmtime_pay",
				"label" => "Дата оплаты",
				"type" => "input",
				"column_value" => function ($struct, $item)
				{
					return \Elberos\wp_from_gmtime($item["gmtime_pay"]);
				}
			])
			->addField
			([
				"api_name" => "price",
				"label" => "Сумма",
				"type" => "input",
			])
			->addField
			([
				"api_name" => "price_pay",
				"label" => "Сумма оплаты",
				"type" => "input",
			])
			->addField
			([
				"api_name" => "client_id",
				"label" => "ID клиента",
				"type" => "input",
			])
		;
	}
	
}

}