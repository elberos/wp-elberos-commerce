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
	 * Show filter wrap
	 */
	function show_filter_wrap()
	{
		?>
		<style>
		.tablenav .extra_tablenav_filter_wrap{
			display: inline-block;
			vertical-align: top;
			position: relative;
			top: -1px;
		}
		.tablenav .extra_tablenav_filter{
			display: flex;
			padding-top: 0px;
			padding-bottom: 0px;
		}
		.tablenav .dosearch{
			margin-left: 5px;
		}
		</style>
		<div class="extra_tablenav_filter_wrap">
			<div class="extra_tablenav_filter">
				<?php $this->extra_tablenav_before() ?>
				<?php $this->show_filter() ?>
				<?php $this->extra_tablenav_after() ?>
				<input type="button" class="button dosearch" value="Поиск">
			</div>
		</div>
		<?php
	}
	
	
	
	/**
	 * Returns true if show filter
	 */
	function is_show_filter()
	{
		list($_,$result) = apply_filters("elberos_table_is_show_filter_" . get_called_class(), [$this,true]);
		return $result;
	}
	
	
	
	/**
	 * Returns filter elements
	 */
	function get_filter()
	{
		return [
			"is_error",
		];
	}
	
	
	
	/**
	 * Show filter item
	 */
	function show_filter_item($item_name)
	{
		if ($item_name == "is_error")
		{
			?>
			<select name="is_error" class="web_form_value">
				<option value="">Ошибка?</option>
				<option value="1" <?= \Elberos\is_get_selected("is_error", "1") ?>>Есть ошибки</option>
				<option value="0" <?= \Elberos\is_get_selected("is_error", "0") ?>>Без ошибок</option>
			</select>
			<?php
		}
	}
	
	
	
	/**
	 * Process items params
	 */
	function prepare_table_items_filter($params)
	{
		global $wpdb;
		
		$params = parent::prepare_table_items_filter($params);
		
		/* Is error */
		if (isset($_GET["is_error"]))
		{
			if ($_GET["is_error"] == "1")
			{
				$params["where"][] = "error > 0";
			}
			else
			{
				$params["where"][] = "error = 0";
			}
		}
		
		return $params;
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