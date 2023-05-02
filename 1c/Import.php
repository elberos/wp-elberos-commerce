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


class Import
{
	var $file_path = null;
	var $import = null;
	var $xml = null;
	var $count_category = 0;
	var $count_products = 0;
	
	
	/**
	 * Возвращает True если это импорт каталога
	 */
	function isCatalog()
	{
		$catalog = $this->xml->Каталог;
		if ($catalog && $catalog->getName() == 'Каталог')
		{
			return true;
		}
		return false;
	}
	
	
	
	/**
	 * Возвращает True если это импорт предложений
	 */
	function isOffer()
	{
		return false;
	}
	
	
	
	/**
	 * Products update only
	 */
	static function elberos_commerce_1c_products_update_only($res)
	{
		$xml = $res["xml"];
		$update_only = $res["update_only"];
		if ($update_only)
		{
			return $res;
		}
		
		$products_update_only = mb_strtolower((string) ($xml->Каталог->attributes()->СодержитТолькоИзменения));
		if ($products_update_only === "нет" ||
			$products_update_only === "false" ||
			$products_update_only === "0")
		{
			$update_only = false;
		}
		
		if ($products_update_only === "да" ||
			$products_update_only === "true" ||
			$products_update_only === "1")
		{
			$update_only = true;
		}
		
		$res["update_only"] = $update_only;
		return $res;
	}
	
	
	
	/**
	 * Offers update only
	 */
	static function elberos_commerce_1c_offers_update_only($res)
	{
		$xml = $res["xml"];
		$update_only = $res["update_only"];
		if ($update_only)
		{
			return $res;
		}
		
		$offers_update_only = mb_strtolower((string) ($xml->ПакетПредложений->attributes()->СодержитТолькоИзменения));
		if ($offers_update_only === "нет" ||
			$offers_update_only === "false" ||
			$offers_update_only === "0")
		{
			$update_only = false;
		}
		
		if ($offers_update_only === "да" ||
			$offers_update_only === "true" ||
			$offers_update_only === "1")
		{
			$update_only = true;
		}
		
		$res["update_only"] = $update_only;
		return $res;
	}
	
	
	
	/**
	 * Загрузка xml контента в базу
	 */
	function loadContent()
	{
		$this->loadInit();
		$this->loadGroups();
		$this->loadPriceTypes();
		$this->loadProductParams();
		$this->loadWarehouses();
		$this->loadProducts();
		$this->loadOffers();
	}
	
	
	
	/**
	 * Загрузка xml контента в базу
	 */
	function loadInit()
	{
		global $wpdb;
		
		/* Таблицы */
		$table_name_classifiers = $wpdb->base_prefix . "elberos_commerce_classifiers";
		$table_name_catalogs = $wpdb->base_prefix . "elberos_commerce_catalogs";
		
		$classifier_id = 0;
		$catalog_id = 0;
		
		/* Создаем классификатор */
		$xml = $this->xml->Классификатор;
		if ($this->xml->Классификатор != null && $this->xml->Классификатор->getName() == 'Классификатор')
		{
			/* Получаем название */
			$name = [];
			if ($this->xml->Классификатор->Наименование != null)
			{
				$name[] = (string)$this->xml->Классификатор->Наименование;
			}
			if ($this->xml->Классификатор->Владелец->Наименование != null)
			{
				$name[] = (string)$this->xml->Классификатор->Владелец->Наименование;
			}
			$name = implode(" ", $name);
			
			/* Ищем классификатор */
			$classifier_code_1c = (string)$this->xml->Классификатор->Ид;
			if ($classifier_code_1c != "")
			{
				$classifier = \Elberos\wpdb_insert_or_update
				(
					$table_name_classifiers,
					[
						"code_1c" => $classifier_code_1c,
					],
					[
						"code_1c" => $classifier_code_1c,
						"name" => $name,
					]
				);
				$classifier_id = $classifier['id'];
			}
		}
		
		/* Создаем каталог */
		$xml = $this->xml->Каталог;
		if ($this->xml->Каталог != null && $this->xml->Каталог->getName() == 'Каталог')
		{
			/* Получаем название */
			$name = [];
			if ($this->xml->Каталог->Наименование != null)
			{
				$name[] = (string)$this->xml->Каталог->Наименование;
			}
			if ($this->xml->Каталог->Владелец->Наименование != null)
			{
				$name[] = (string)$this->xml->Каталог->Владелец->Наименование;
			}
			$name = implode(" ", $name);
			
			$classifier = Helper::findClassifierByCode( (string)$this->xml->Каталог->ИдКлассификатора );
			$catalog = Helper::findCatalogByCode( (string)$this->xml->Каталог->Ид );
			
			/* Создаем каталог */
			if ($catalog == null)
			{
				$catalog_code_1c = (string)$this->xml->Каталог->Ид;
				$catalog = \Elberos\wpdb_insert_or_update
				(
					$table_name_catalogs,
					[
						"code_1c" => $catalog_code_1c,
					],
					[
						"code_1c" => $catalog_code_1c,
						"classifier_id" => $classifier["id"],
						"name" => $name,
					]
				);
			}
			$catalog_id = $catalog ? $catalog['id'] : 0;
			
			/* Флаг не содержит только изменения */
			$res = apply_filters
			(
				'elberos_commerce_1c_products_update_only',
				[
					'xml'=>$this->xml,
					'update_only' => false,
				]
			);
			$update_only = $res["update_only"];
			
			/* Сбрасываем флаг just_show_in_catalog */
			if (!$update_only)
			{
				$table_name_products = $wpdb->base_prefix . "elberos_commerce_products";
				$sql = "update " . $table_name_products .
					" set `just_show_in_catalog` = 0 " .
					" where `catalog_id` = '" . (int)($catalog_id) . "'";
				$wpdb->query($sql);
				
				$this->saveCurrentXML();
			}
		}
		
		/* Предложения */
		$xml = $this->xml->ПакетПредложений;
		if ($xml != null && $xml->getName() == 'ПакетПредложений')
		{
			$catalog = Helper::findCatalogByCode( (string)$this->xml->ПакетПредложений->ИдКаталога );
			$catalog_id = $catalog ? $catalog['id'] : 0;
			
			/* Флаг не содержит только изменения */
			$res = apply_filters
			(
				'elberos_commerce_1c_offers_update_only',
				[
					'xml'=>$this->xml,
					'update_only' => false,
				]
			);
			
			/* Сбрасываем флаг prepare_delete у предложений */
			$update_only = $res["update_only"];
			if (!$update_only)
			{
				$table_name_products = $wpdb->base_prefix . "elberos_commerce_products";
				$table_name_products_offers = $wpdb->base_prefix . "elberos_commerce_products_offers";
				$table_name_products_offers_prices = $wpdb->base_prefix . "elberos_commerce_products_offers_prices";
				
				$sql = "update " . $table_name_products_offers . " as offers " .
					"inner join " . $table_name_products . " as products " .
					" on ( products.id = offers.product_id ) " .
					" set `offers`.`prepare_delete` = 1 " .
					"where `products`.`catalog_id`='" . (int)($catalog_id) . "'"
				;
				$wpdb->query($sql);
				
				$sql = "update " . $table_name_products_offers_prices . " as offers_prices " .
					"inner join " . $table_name_products_offers . " as offers " .
					" on ( offers.id = offers_prices.offer_id) " .
					"inner join " . $table_name_products . " as products " .
					" on ( products.id = offers.product_id)"  .
					" set `offers_prices`.`prepare_delete` = 1 " .
					"where `products`.`catalog_id`='" . (int)($catalog_id) . "'"
				;
				$wpdb->query($sql);
				
				$this->saveCurrentXML();
			}
		}
	}
	
	
	/**
	 * Save current xml
	 */
	public function saveCurrentXML()
	{
		$file_folder = ABSPATH . "wp-content/uploads/1c_uploads/save";
		
		/* Создаем папку если не была создана */
		if (!file_exists($file_folder))
		{
			try
			{
				mkdir($file_folder, 0775);
			}
			catch (\Exception $e)
			{
			}
		}
		
		$file_name = basename($this->file_path);
		$save_file_path = $file_folder . "/" . $file_name;
		
		if (file_exists($this->file_path) && is_file($this->file_path))
		{
			$content = file_get_contents($this->file_path);
			file_put_contents($save_file_path, $content);
		}
	}
	
	
	/**
	 * Create task for groups by group items
	 */
	public function loadGroupsByItems($arr, $parent_id='')
	{
		global $wpdb;
		
		$classifier_code_1c = (string)$this->xml->Классификатор->Ид;
		$classifier = Helper::findClassifierByCode( $classifier_code_1c );
		$classifier_id = ($classifier != null) ? $classifier["id"] : 0;
		$task_count = 0;
		
		/* Таблица */
		$table_name_1c_task = $wpdb->base_prefix . "elberos_commerce_1c_task";
		if ($arr != null && $arr->getName() == 'Группы')
		{
			foreach ($arr->children() as $item)
			{
				if ($item->getName() == 'Группа')
				{
					$item_id = (string)$item->Ид;
					
					$sub_item = new \SimpleXMLElement("<Группа></Группа>");
					$sub_item->addChild("Ид", $item->Ид);
					$sub_item->addChild("Классификатор_Ид", $classifier_id);
					$sub_item->addChild("Классификатор_Код", $classifier_code_1c);
					$names = $item->Наименование;
					foreach ($names as $name)
					{
						$lang = (string) ($name->attributes()->lang);
						if ($lang == null) $lang = "ru";
						$node = $sub_item->addChild("Наименование", (string)$name);
						$node->addAttribute("lang", $lang);
					}
					if ((string)$item->Картинка != "")
					{
						$sub_item->addChild("Картинка", (string)$item->Картинка);
					}
					$sub_item->addChild("ParentID", $parent_id);
					
					$task_xml = (string) $sub_item->asXML();
					
					/* Получаем название */
					$names = Helper::getNamesByXml($sub_item, 'Наименование');
					$name_ru = isset($names['ru']) ? $names['ru'] : '';
					
					/* Insert task */
					$res = apply_filters
					(
						'elberos_commerce_1c_insert_task',
						[
							'xml'=>$sub_item,
							'data'=>
							[
								"name" => $name_ru,
								"code_1c" => $item_id,
								"import_id" => $this->import["id"],
								"classifier_id" => $classifier_id,
								"type" => "category",
								"data" => $task_xml,
								"status" => Helper::TASK_STATUS_PLAN,
								"gmtime_add" => gmdate("Y-m-d H:i:s", time()),
							]
						]
					);
					$insert_data = $res["data"];
					
					/* Добавление задания в базу данных */
					$wpdb->insert
					(
						$table_name_1c_task,
						$insert_data
					);
					
					$this->count_category += 1;
					
					$subarr = $item->Группы;
					$this->loadGroupsByItems($subarr, $item_id);
					
					/* Set time limit */
					set_time_limit(600);
					
					/* Update task progress */
					if ($task_count % 20 == 0)
					{
						Helper::updateTaskTotal($this->import["id"]);
					}
					$task_count++;
				}
				
			}
			
		}
	}
	
	
	
	/**
	 * Создание заданий для импорта групп
	 */
	public function loadGroups()
	{
		$arr = $this->xml->Классификатор->Группы;
		if ($arr != null && $arr->getName() == 'Группы')
		{
			$this->loadGroupsByItems($arr);
		}
	}
	
	
	
	/**
	 * Создание заданий для импорта товаров
	 */
	public function loadProducts()
	{
		global $wpdb;
		
		if (!$this->isCatalog())
		{
			return;
		}
		
		$arr = $this->xml->Каталог->Товары;
		if ($arr == null) return;
		if ($arr->getName() != 'Товары') return;
		
		$classifier_code_1c = (string)$this->xml->Каталог->ИдКлассификатора;
		$classifier = Helper::findClassifierByCode( $classifier_code_1c );
		$classifier_id = ($classifier != null) ? $classifier["id"] : 0;
		
		$catalog_code_1c = (string)$this->xml->Каталог->Ид;
		$catalog = Helper::findCatalogByCode( $catalog_code_1c );
		$catalog_id = ($catalog != null) ? $catalog["id"] : 0;
		$task_count = 0;
		
		/* Таблица */
		$table_name_1c_task = $wpdb->base_prefix . "elberos_commerce_1c_task";
		foreach ($arr->children() as $item)
		{
			if ($item->getName() == 'Товар')
			{
				$item_id = (string)$item->Ид;
				
				/* Получаем название */
				$names = Helper::getNamesByXml($item, 'Наименование');
				$name_ru = isset($names['ru']) ? $names['ru'] : '';
				
				/* Insert task */
				$res = apply_filters
				(
					'elberos_commerce_1c_insert_task',
					[
						'xml'=>$item,
						'data'=>
						[
							"name" => $name_ru,
							"code_1c" => $item_id,
							"import_id" => $this->import["id"],
							"catalog_id" => $catalog_id,
							"classifier_id" => $classifier_id,
							"type" => "product",
							"data" => (string) $item->asXML(),
							"status" => Helper::TASK_STATUS_PLAN,
							"gmtime_add" => gmdate("Y-m-d H:i:s", time()),
						]
					]
				);
				$insert_data = $res["data"];

				/* Добавление задания в базу данных */
				$wpdb->insert
				(
					$table_name_1c_task,
					$insert_data
				);
				
				/* Update task progress */
				if ($task_count % 20 == 0)
				{
					Helper::updateTaskTotal($this->import["id"]);
				}
				$task_count++;
				
				$this->count_products += 1;
				
				/* Картинка */
				$images = $item->Картинка;
				$image_pos = 0;
				foreach ($images as $image)
				{
					$image_path = (string)$image;
					$image->addAttribute('pos', $image_pos);
					$image->addAttribute('code_1c', $item_id);
					
					if (strlen($image_path) > 0)
					{
						/* Insert task */
						$res = apply_filters
						(
							'elberos_commerce_1c_insert_task',
							[
								'xml'=>$image,
								'data'=>
								[
									"name" => "Картинка " . $image_path,
									"code_1c" => $item_id,
									"import_id" => $this->import["id"],
									"catalog_id" => $catalog_id,
									"classifier_id" => $classifier_id,
									"type" => "product_image",
									"data" => (string) $image->asXML(),
									"status" => Helper::TASK_STATUS_PLAN,
									"gmtime_add" => gmdate("Y-m-d H:i:s", time()),
								]
							]
						);
						$insert_data = $res["data"];
						
						$wpdb->insert
						(
							$table_name_1c_task,
							$insert_data
						);
						
						$image_pos++;
						
						/* Set time limit */
						set_time_limit(600);
						
						/* Update task progress */
						if ($task_count % 20 == 0)
						{
							Helper::updateTaskTotal($this->import["id"]);
						}
						$task_count++;
					}
				}
			}
		}
	}
	
	
	
	/**
	 * Загрузка типов цен
	 */
	public function loadPriceTypes()
	{
		global $wpdb;
		
		/* Таблица */
		$table_name_1c_task = $wpdb->base_prefix . "elberos_commerce_1c_task";
		$task_count = 0;
		
		$xml = $this->xml->ПакетПредложений;
		if ($xml != null && $xml->getName() == 'ПакетПредложений')
		{
			$classifier_code_1c = (string)$this->xml->ПакетПредложений->ИдКлассификатора;
			$classifier = Helper::findClassifierByCode( $classifier_code_1c );
			$classifier_id = ($classifier != null) ? $classifier["id"] : 0;
			
			$catalog_code_1c = (string)$this->xml->ПакетПредложений->ИдКаталога;
			$catalog = Helper::findCatalogByCode( $catalog_code_1c );
			$catalog_id = ($catalog != null) ? $catalog["id"] : 0;
			
			$items = $xml->ТипыЦен;
			if ($items != null && $items->getName() == 'ТипыЦен')
			{
				foreach ($items->children() as $item)
				{
					if ($item->getName() == 'ТипЦены')
					{
						$item_id = (string)$item->Ид;
						
						/* Получаем название */
						$names = Helper::getNamesByXml($item, 'Наименование');
						$name_ru = isset($names['ru']) ? $names['ru'] : '';
						
						/* Insert task */
						$res = apply_filters
						(
							'elberos_commerce_1c_insert_task',
							[
								'xml'=>$item,
								'data'=>
								[
									"name" => $name_ru,
									"code_1c" => $item_id,
									"import_id" => $this->import["id"],
									"catalog_id" => $catalog_id,
									"classifier_id" => $classifier_id,
									"type" => "price_type",
									"data" => (string) $item->asXML(),
									"status" => Helper::TASK_STATUS_PLAN,
									"gmtime_add" => gmdate("Y-m-d H:i:s", time()),
								]
							]
						);
						$insert_data = $res["data"];
						
						$wpdb->insert
						(
							$table_name_1c_task,
							$insert_data
						);
						
						/* Set time limit */
						set_time_limit(600);
						
						/* Update task progress */
						if ($task_count % 20 == 0)
						{
							Helper::updateTaskTotal($this->import["id"]);
						}
						$task_count++;
					}
				}
			}
		}
	}
	
	
	
	/**
	 * Загрузка параметров товаров
	 */
	public function loadProductParams()
	{
		global $wpdb;
		
		/* Таблица */
		$table_name_1c_task = $wpdb->base_prefix . "elberos_commerce_1c_task";
		$task_count = 0;
		
		$xml = $this->xml->Классификатор;
		if ($xml != null && $xml->getName() == 'Классификатор')
		{
			$classifier_code_1c = (string)$this->xml->Классификатор->Ид;
			$classifier = Helper::findClassifierByCode( $classifier_code_1c );
			$classifier_id = ($classifier != null) ? $classifier["id"] : 0;
			
			$items = $xml->Свойства;
			if ($items != null && $items->getName() == 'Свойства')
			{
				foreach ($items->children() as $item)
				{
					if ($item->getName() == 'Свойство')
					{
						$item_id = (string)$item->Ид;
						
						/* Получаем название */
						$names = Helper::getNamesByXml($item, 'Наименование');
						$name_ru = isset($names['ru']) ? $names['ru'] : '';
						
						/* Insert task */
						$res = apply_filters
						(
							'elberos_commerce_1c_insert_task',
							[
								'xml'=>$item,
								'data'=>
								[
									"name" => $name_ru,
									"code_1c" => $item_id,
									"import_id" => $this->import["id"],
									"classifier_id" => $classifier_id,
									"type" => "product_param",
									"data" => (string) $item->asXML(),
									"status" => Helper::TASK_STATUS_PLAN,
									"gmtime_add" => gmdate("Y-m-d H:i:s", time()),
								]
							]
						);
						$insert_data = $res["data"];
						
						$wpdb->insert
						(
							$table_name_1c_task,
							$insert_data
						);
						
						/* Set time limit */
						set_time_limit(600);
						
						/* Update task progress */
						if ($task_count % 20 == 0)
						{
							Helper::updateTaskTotal($this->import["id"]);
						}
						$task_count++;
					}
				}
			}
		}
	}
	
	
	
	/**
	 * Загрузка складов
	 */
	public function loadWarehouses()
	{
		global $wpdb;
		
		/* Таблица */
		$table_name_1c_task = $wpdb->base_prefix . "elberos_commerce_1c_task";
		$task_count = 0;
		
		$xml = $this->xml->ПакетПредложений;
		if ($xml != null && $xml->getName() == 'ПакетПредложений')
		{
			$classifier_code_1c = (string)$this->xml->ПакетПредложений->ИдКлассификатора;
			$classifier = Helper::findClassifierByCode( $classifier_code_1c );
			$classifier_id = ($classifier != null) ? $classifier["id"] : 0;
			
			$catalog_code_1c = (string)$this->xml->ПакетПредложений->ИдКаталога;
			$catalog = Helper::findCatalogByCode( $catalog_code_1c );
			$catalog_id = ($catalog != null) ? $catalog["id"] : 0;
			
			$items = $xml->Склады;
			if ($items != null && $items->getName() == 'Склады')
			{
				foreach ($items->children() as $item)
				{
					if ($item->getName() == 'Склад')
					{
						$item_id = (string)$item->Ид;
						
						/* Получаем название */
						$names = Helper::getNamesByXml($item, 'Наименование');
						$name_ru = isset($names['ru']) ? $names['ru'] : '';
						
						/* Insert task */
						$res = apply_filters
						(
							'elberos_commerce_1c_insert_task',
							[
								'xml'=>$item,
								'data'=>
								[
									"name" => $name_ru,
									"code_1c" => $item_id,
									"import_id" => $this->import["id"],
									"catalog_id" => $catalog_id,
									"classifier_id" => $classifier_id,
									"type" => "warehouse",
									"data" => (string) $item->asXML(),
									"status" => Helper::TASK_STATUS_PLAN,
									"gmtime_add" => gmdate("Y-m-d H:i:s", time()),
								]
							]
						);
						$insert_data = $res["data"];
						
						$wpdb->insert
						(
							$table_name_1c_task,
							$insert_data
						);
						
						/* Set time limit */
						set_time_limit(600);
						
						/* Update task progress */
						if ($task_count % 20 == 0)
						{
							Helper::updateTaskTotal($this->import["id"]);
						}
						$task_count++;
					}
				}
			}
		}
	}
	
	
	
	/**
	 * Загрузка предложений
	 */
	public function loadOffers()
	{
		global $wpdb;
		
		/* Таблица */
		$table_name_1c_task = $wpdb->base_prefix . "elberos_commerce_1c_task";
		$task_count = 0;
		
		$xml = $this->xml->ПакетПредложений;
		if ($xml != null && $xml->getName() == 'ПакетПредложений')
		{
			$classifier_code_1c = (string)$this->xml->ПакетПредложений->ИдКлассификатора;
			$classifier = Helper::findClassifierByCode( $classifier_code_1c );
			$classifier_id = ($classifier != null) ? $classifier["id"] : 0;
			
			$catalog_code_1c = (string)$this->xml->ПакетПредложений->ИдКаталога;
			$catalog = Helper::findCatalogByCode( $catalog_code_1c );
			$catalog_id = ($catalog != null) ? $catalog["id"] : 0;
			
			$items = $xml->Предложения;
			if ($items != null && $items->getName() == 'Предложения')
			{
				foreach ($items->children() as $item)
				{
					if ($item->getName() == 'Предложение')
					{
						$item_id = (string)$item->Ид;
						
						/* Получаем название */
						$names = Helper::getNamesByXml($item, 'Наименование');
						$name_ru = isset($names['ru']) ? $names['ru'] : '';
						
						/* Insert task */
						$res = apply_filters
						(
							'elberos_commerce_1c_insert_task',
							[
								'xml'=>$item,
								'data'=>
								[
									"name" => $name_ru,
									"code_1c" => $item_id,
									"import_id" => $this->import["id"],
									"catalog_id" => $catalog_id,
									"classifier_id" => $classifier_id,
									"type" => "offer",
									"data" => (string) $item->asXML(),
									"status" => Helper::TASK_STATUS_PLAN,
									"gmtime_add" => gmdate("Y-m-d H:i:s", time()),
								]
							]
						);
						$insert_data = $res["data"];
						
						$wpdb->insert
						(
							$table_name_1c_task,
							$insert_data
						);
						
						/* Set time limit */
						set_time_limit(600);
						
						/* Update task progress */
						if ($task_count % 20 == 0)
						{
							Helper::updateTaskTotal($this->import["id"]);
						}
						$task_count++;
					}
				}
			}
		}
	}
	
	
}