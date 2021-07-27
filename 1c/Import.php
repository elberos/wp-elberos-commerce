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

class Import
{
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
	 * Возращает true если каталог содержит только изменения
	 */
	function isOnlyChange()
	{
		$catalog = $this->xml->Каталог;
		if ($catalog && $catalog->getName() == 'Каталог')
		{
			$products_update_only = mb_strtolower((string) ($catalog->attributes()->СодержитТолькоИзменения));
			if ($products_update_only == "да" || 
				$products_update_only == "true" || 
				$products_update_only === true || 
				$products_update_only === 1)
			{
				return true;
			}
		}
		return false;
	}
	
	
	
	/**
	 * Загрузка xml контента в базу
	 */
	function loadContent()
	{
		$this->loadInit();
		$this->loadGroups();
		$this->loadProducts();
		$this->loadPriceTypes();
		$this->loadProductParams();
		$this->loadWarehouses();
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
		if ($this->xml->Классификатор != null)
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
		if ($this->xml->Каталог != null)
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
			
			/* Ищем каталог */
			$catalog_code_1c = (string)$this->xml->Каталог->Ид;
			if ($catalog_code_1c != "")
			{
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
				$catalog_id = $catalog['id'];
			}
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
					
					/* Добавление задания в базу данных */
					$wpdb->insert
					(
						$table_name_1c_task,
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
					);
					
					$this->count_category += 1;
					
					$subarr = $item->Группы;
					$this->loadGroupsByItems($subarr, $item_id);
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
				
				$wpdb->insert
				(
					$table_name_1c_task,
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
				);
				$this->count_products += 1;
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
						
						$wpdb->insert
						(
							$table_name_1c_task,
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
						);
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
						
						$wpdb->insert
						(
							$table_name_1c_task,
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
						);
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
						
						$wpdb->insert
						(
							$table_name_1c_task,
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
						);
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
						
						$wpdb->insert
						(
							$table_name_1c_task,
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
						);
					}
				}
			}
		}
	}
	
	
}