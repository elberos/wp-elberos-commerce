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

if ( !class_exists( Classifier_Table::class ) && class_exists( \Elberos\Table::class ) ) 
{

class Classifier_Table extends \Elberos\Table 
{
	
	/**
	 * Table name
	 */
	function get_table_name()
	{
		global $wpdb;
		return $wpdb->base_prefix . 'elberos_commerce_classifiers';
	}
	
	
	
	/**
	 * Page name
	 */
	function get_page_name()
	{
		return "elberos-commerce-classifiers";
	}
	
	
	
	/**
	 * Create struct
	 */
	static function createStruct()
	{
		$struct = \Elberos\Commerce\Classifier::create
		(
			"admin_table",
			function ($struct)
			{
				/*
				$struct
					->addField
					([
						"api_name" => "id",
						"label" => "ID Партнера",
						"type" => "input",
						"virtual" => true,
					])
				;
				*/
				
				$struct->table_fields =
				[
					"id",
					"name",
					"code_1c",
				];
				
				$struct->form_fields =
				[
					"name",
					"code_1c",
				];
				
				return $struct;
			}
		);
		
		return $struct;
	}
	
	
	
	/**
	 * Init struct
	 */
	function initStruct()
	{
		parent::initStruct();
	}
	
	
	
	/**
	 * Column buttons
	 */
	function column_buttons($item)
	{
		$page_name = $this->get_page_name();
		return sprintf
		(
			'<a href="?page=' . $page_name . '&action=catalog&id=%s">%s</a>',
			$item['id'], 
			__('Открыть', 'elberos-commerce')
		);
	}
	
	
	
	/**
	 * Process bulk action
	 */
	function process_bulk_action()
	{
		$action = $this->current_action();
		
		/* Edit items */
		if (in_array($action, ['add', 'edit']))
		{
			parent::process_bulk_action();
		}
		
		/* Info item */
		else if (in_array($action, ['catalog', 'categories', 'price_types', 'products_params',
			'products_params_values', 'warehouses']))
		{
			$this->do_get_item();
		}
	}
	
	
	
	/**
	 * Get item
	 */
	function do_get_item()
	{
		parent::do_get_item();
	}
	
	
	
	/**
	 * Process item
	 */
	function process_item($item, $old_item)
	{
		return $item;
	}
	
	
	
	/**
	 * Item validate
	 */
	function item_validate($item)
	{
		return "";
	}
	
	
	
	/**
	 * Prepare table items
	 */
	function prepare_table_items()
	{
		$args = [];
		$where = [];
		
		$per_page = $this->per_page();
		list($items, $total_items, $pages, $page) = \Elberos\wpdb_query
		([
			"table_name" => $this->get_table_name(),
			"where" => implode(" and ", $where),
			"args" => $args,
			"page" => (int) isset($_GET["paged"]) ? ($_GET["paged"] - 1) : 0,
			"per_page" => $per_page,
		]);
		
		$this->items = $items;
		$this->set_pagination_args(array(
			'total_items' => $total_items, 
			'per_page' => $per_page,
			'total_pages' => ceil($total_items / $per_page) 
		));
	}
	
	
	
	/**
	 * CSS
	 */
	function display_css()
	{
		parent::display_css();
	}
	
	
	
	/**
	 * Display table sub
	 */
	function display_table_sub()
	{
	}
	
	
	
	/**
	 * Display form sub
	 */
	function display_form_sub()
	{
		$page_name = $this->get_page_name();
		$action = isset($_GET['action']) ? $_GET['action'] : "edit";
		$item = $this->form_item;
		$item_id = $this->form_item_id;
		?>
		
		<br/>
		<a type="button" class='button-primary' href='?page=<?= $page_name ?>'> Back </a>
		<br/>
		<br/>
		
		<div class="subsub_table">
			<table>
				<tr>
					<td class="subsub_table_left">Имя:</td>
					<td class="subsub_table_right"><?= esc_html($item["name"]); ?></td>
				</tr>
				<tr>
					<td class="subsub_table_left">Код 1С:</td>
					<td class="subsub_table_right"><?= esc_html($item["code_1c"]); ?></td>
				</tr>
			</table>
			
			<ul class="subsubsub">
				<li>
					<a href="admin.php?page=<?= $page_name ?>&action=catalog&id=<?= $item_id ?>"
						class="<?= ($action == "catalog" ? "current" : "")?>"  >Каталог</a> |
				</li>
				<li>
					<a href="admin.php?page=<?= $page_name ?>&action=categories&id=<?= $item_id ?>"
						class="<?= ($action == "categories" ? "current" : "")?>" >Категории</a> |
				</li>
				<li>
					<a href="admin.php?page=<?= $page_name ?>&action=price_types&id=<?= $item_id ?>"
						class="<?= ($action == "price_types" ? "current" : "")?>" >Типы цен</a> |
				</li>
				<li>
					<a href="admin.php?page=<?= $page_name ?>&action=products_params&id=<?= $item_id ?>"
						class="<?= (in_array($action, ["products_params", "products_params_values"]) ? "current" : "")?>" >Параметры товаров</a> |
				</li>
				<li>
					<a href="admin.php?page=<?= $page_name ?>&action=warehouses&id=<?= $item_id ?>"
						class="<?= ($action == "warehouses" ? "current" : "")?>" >Склады</a> |
				</li>
				<li>
					<a href="admin.php?page=<?= $page_name ?>&action=edit&id=<?= $item_id ?>"
						class="<?= ($action == "edit" ? "current" : "")?>" >Редактировать</a>
				</li>
			</ul>
		</div>
		
		<div style="clear: both;"></div>
		
		<?php
	}
	
	
	
	/**
	 * Returns form title
	 */
	function get_form_title($item)
	{
		return _e($item['id'] > 0 ? 'Редактировать классификатор' : 'Добавить классификатор', 'elberos-commerce');
	}
	
	
	
	/**
	 * Display action
	 */
	function display_action()
	{
		$action = $this->current_action();
		if ($action == "catalog")
		{
			$this->display_form_sub();
			$table = new \Elberos\Commerce\Catalog_Table();
			$table->display();
		}
		else if ($action == "categories")
		{
			$this->display_form_sub();
			$table = new \Elberos\Commerce\Category_Table();
			$table->display();
		}
		else if ($action == "price_types")
		{
			$this->display_form_sub();
			$table = new \Elberos\Commerce\PriceType_Table();
			$table->display();
		}
		else if ($action == "products_params")
		{
			$this->display_form_sub();
			$table = new \Elberos\Commerce\ProductParam_Table();
			$table->display();
		}
		else if ($action == "products_params_values")
		{
			$this->display_form_sub();
			$table = new \Elberos\Commerce\ProductParamValue_Table();
			$table->display();
		}
		else if ($action == "warehouses")
		{
			$this->display_form_sub();
			$table = new \Elberos\Commerce\Warehouse_Table();
			$table->display();
		}
		else
		{
			parent::display_action();
		}
	}
	
	
}

}