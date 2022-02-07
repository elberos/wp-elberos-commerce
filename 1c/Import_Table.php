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

if ( !class_exists( Import_Table::class ) && class_exists( \Elberos\Table::class ) ) 
{

class Import_Table extends \Elberos\Table 
{
	
	/**
	 * Table name
	 */
	function get_table_name()
	{
		global $wpdb;
		return $wpdb->base_prefix . 'elberos_commerce_1c_import';
	}
	
	
	
	/**
	 * Page name
	 */
	function get_page_name()
	{
		return "elberos-commerce-1c-import";
	}
	
	
	
	/**
	 * Create struct
	 */
	static function createStruct()
	{
		$struct = \Elberos\Commerce\_1C\Import_Struct::create
		(
			"admin_table",
			function ($struct)
			{
				$struct
					->addField
					([
						"api_name" => "gmtime",
						"label" => "Время",
						"type" => "input",
						"virtual" => true,
						"column_value" => function($struct, $item)
						{
							if ($item["gmtime_end"]) return \Elberos\wp_from_gmtime( $item["gmtime_end"] );
							return \Elberos\wp_from_gmtime( $item["gmtime_add"] );
						},
					])
				;
				
				$struct
					->addField
					([
						"api_name" => "result",
						"label" => "Результат",
						"type" => "input",
						"virtual" => true,
						"column_value" => function($struct, $item)
						{
							return $item["progress"] . " / " . $item["total"] . ". Errors: " . $item["error"];
						},
					])
				;
				
				$struct
					->addField
					([
						"api_name" => "error",
						"label" => "Ошибка",
						"type" => "input",
						"virtual" => true,
						"column_value" => function($struct, $item)
						{
							return $item["error_message"];
						},
					])
				;
				
				$struct->table_fields =
				[
					"id",
					//"session_id",
					"filename",
					"status",
					"result",
					//"error",
					"gmtime",
				];
				
				$struct->form_fields =
				[
					"id",
					//"session_id",
					"filename",
					"status",
					"result",
					"error_code",
					"error_message",
				];
				
				return $struct;
			}
		);
		
		return $struct;
	}
	
	
	
	/**
	 * Returns columns
	 */
	function get_columns()
	{
		$res = parent::get_columns();
		//unset( $res["cb"] );
		return $res;
	}
	
	
	
	/**
	 * Init struct
	 */
	function initStruct()
	{
		parent::initStruct();
	}
	
	
	
	/* Заполнение колонки cb */
	function column_cb($item)
    {
        return sprintf(
            '<input type="checkbox" name="id[]" value="%s" />',
            $item['id']
        );
    }
	
	
	
	/**
	 * Column buttons
	 */
	function column_buttons($item)
	{
		$page_name = $this->get_page_name();
		return sprintf
		(
			'<a href="?page=' . $page_name . '&action=show&id=%s">%s</a>',
			$item['id'], 
			__('Открыть', 'elberos-commerce')
		);
	}
	
	
	
	/**
	 * Действия
	 */
	function get_bulk_actions()
	{
		$is_deleted = isset($_REQUEST['is_deleted']) ? $_REQUEST['is_deleted'] : "";
		if ($is_deleted != 'true')
		{
			$actions = array
			(
				'trash' => 'Переместить в корзину',
			);
		}
		else
		{
			$actions = array
			(
				'notrash' => 'Восстановить из корзины',
				'delete' => 'Удалить навсегда',
			);
		}
		return $actions;
	}
	
	
	
	/**
	 * Process bulk action
	 */
	function process_bulk_action()
	{
		$action = $this->current_action();
		
		/* Edit items */
		if (in_array($action, ['show']))
		{
			$this->do_get_item();
			$this->struct->editField("id", ["virtual"=>true, "readonly"=>true]);
			$this->struct->editField("session_id", ["virtual"=>true, "readonly"=>true]);
			$this->struct->editField("filename", ["virtual"=>true, "readonly"=>true]);
			$this->struct->editField("status", ["virtual"=>true, "readonly"=>true, "type"=>"select_input_value"]);
			$this->struct->editField("error_code", ["virtual"=>true, "readonly"=>true]);
			$this->struct->editField("error_message", ["virtual"=>true, "readonly"=>true]);
		}
		
		/* Move to trash items */
		else if (in_array($action, ['trash', 'notrash', 'delete']))
		{
			parent::process_bulk_action();
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
		global $wpdb;
		
		$args = [];
		$where = [];
		
		/* Is deleted */
		if (isset($_GET["is_deleted"]) && $_GET["is_deleted"] == "true")
		{
			$where[] = "is_deleted=1";
		}
		else
		{
			$where[] = "is_deleted=0";
		}
		
		$per_page = $this->per_page();
		list($items, $total_items, $pages, $page) = \Elberos\wpdb_query
		([
			"table_name" => $this->get_table_name(),
			"where" => implode(" and ", $where),
			"args" => $args,
			"page" => (int) isset($_GET["paged"]) ? ($_GET["paged"] - 1) : 0,
			"per_page" => $per_page,
			//"log" => true,
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
	 * Display buttons
	 */
	function display_form_buttons()
	{
		
	}
	
	
	
	/**
	 * Display action
	 */
	function display_action()
	{
		$action = $this->current_action();
		if ($action == 'show')
		{
			$this->display_add_or_edit();
		}
		else
		{
			$this->display_table();
		}
	}
	
}

}