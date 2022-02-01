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

if ( !class_exists( Task_Table::class ) && class_exists( \Elberos\Table::class ) ) 
{

class Task_Table extends \Elberos\Table 
{
	
	/**
	 * Table name
	 */
	function get_table_name()
	{
		global $wpdb;
		return $wpdb->base_prefix . 'elberos_commerce_1c_task';
	}
	
	
	
	/**
	 * Page name
	 */
	function get_page_name()
	{
		return "elberos-commerce-1c-task";
	}
	
	
	
	/**
	 * Create struct
	 */
	static function createStruct()
	{
		$struct = \Elberos\Commerce\_1C\Task_Struct::create
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
					"import_id",
					"type",
					"name",
					"code_1c",
					"status",
					"error",
					"gmtime",
				];
				
				$struct->form_fields =
				[
					"type",
					"name",
					"code_1c",
					"status",
					"data",
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
		unset( $res["cb"] );
		return $res;
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
			'<a href="?page=' . $page_name . '&action=show&id=%s">%s</a>',
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
		if (in_array($action, ['show']))
		{
			$this->do_get_item();
			$this->struct->editField("type", ["virtual"=>true, "readonly"=>true, "type"=>"select_input_value"]);
			$this->struct->editField("name", ["virtual"=>true, "readonly"=>true]);
			$this->struct->editField("code_1c", ["virtual"=>true, "readonly"=>true]);
			$this->struct->editField("status", ["virtual"=>true, "readonly"=>true, "type"=>"select_input_value"]);
			$this->struct->editField("data", ["virtual"=>true, "readonly"=>true]);
			$this->struct->editField("error_code", ["virtual"=>true, "readonly"=>true]);
			$this->struct->editField("error_message", ["virtual"=>true, "readonly"=>true]);
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
	 * Фильтр
	 */
	function extra_tablenav( $which )
	{
		$field_type = $this->struct->getField("type");
		$field_type_options = $field_type["options"];
		
		$catalog_field = $this->struct->getField("catalog_id");
		$catalog_options = isset($catalog_field["options"]) ? $catalog_field["options"] : [];
		if ( $which == "top" )
		{
			$catalog_id = isset($_GET["catalog_id"]) ? $_GET["catalog_id"] : "";
			$name = isset($_GET["name"]) ? $_GET["name"] : "";
			$order = isset($_GET["order"]) ? $_GET["order"] : "";
			$orderby = isset($_GET["orderby"]) ? $_GET["orderby"] : "";
			$is_deleted = isset($_GET["is_deleted"]) ? $_GET["is_deleted"] : "";
			?>
			<div class="table_filter" style="padding-bottom: 10px;">
				<input type="text" name="import_id" class="web_form_value" placeholder="Импорт 1С"
					value="<?= esc_attr( isset($_GET["import_id"]) ? $_GET["import_id"] : "" ) ?>">
				<select name="type" class="web_form_value">
					<option value="">Выберите тип</option>
					<?php
						foreach ($field_type_options as $option)
						{
							$checked = \Elberos\is_get_selected("type", $option["id"]);
							echo '<option value="'.
								esc_attr($option['id']) . '"' . $checked . '>' .
								esc_html($option['value']) .
							'</option>';
						}
					?>
				</select>
				<input type="text" name="name" class="web_form_value" placeholder="Название"
					value="<?= esc_attr( isset($_GET["name"]) ? $_GET["name"] : "" ) ?>">
				<input type="text" name="code_1c" class="web_form_value" placeholder="Код 1С"
					value="<?= esc_attr( isset($_GET["code_1c"]) ? $_GET["code_1c"] : "" ) ?>">
				<select name="status" class="web_form_value">
					<option value="">Статус</option>
					<option value="0" <?= \Elberos\is_get_selected("status", "0") ?>>Запланировано</option>
					<option value="1" <?= \Elberos\is_get_selected("status", "1") ?>>Выполнено</option>
					<option value="2" <?= \Elberos\is_get_selected("status", "2") ?>>В процессе</option>
				</select>
				<select name="is_error" class="web_form_value">
					<option value="">Ошибка?</option>
					<option value="yes" <?= \Elberos\is_get_selected("is_error", "yes") ?>>Есть ошибки</option>
					<option value="no" <?= \Elberos\is_get_selected("is_error", "no") ?>>Без ошибок</option>
				</select>
				<input type="button" class="button dosearch" value="Поиск">
			</div>
			<script>
			jQuery(".dosearch").click(function(){
				var filter = [];
				<?= $is_deleted == "true" ? "filter.push('is_deleted=true');" : "" ?>
				<?= $order != "" ? "filter.push('order='+" . json_encode($order) . ");" : "" ?>
				<?= $orderby != "" ? "filter.push('orderby='+" . json_encode($orderby) . ");" : "" ?>
				jQuery(".table_filter .web_form_value").each(function(){
					var name = jQuery(this).attr("name");
					var value = jQuery(this).val();
					if (value != "") filter.push(name + "=" + encodeURIComponent(value));
				});
				filter = filter.join("&");
				if (filter != "") filter = "&" + filter;
				document.location.href = 'admin.php?page=elberos-commerce-1c-task'+filter;
			});
			</script>
			<?php
		}
	}
	
	
	
	/**
	 * Prepare table items
	 */
	function prepare_table_items()
	{
		global $wpdb;
		
		$args = [];
		$where = [];
		
		/* Import id */
		if (isset($_GET["import_id"]))
		{
			$where[] = "import_id=:import_id";
			$args["import_id"] = $_GET["import_id"];
		}
		
		/* Catalog id */
		if (isset($_GET["type"]))
		{
			$where[] = "type=:type";
			$args["type"] = $_GET["type"];
		}
		
		/* Status */
		if (isset($_GET["status"]))
		{
			$where[] = "status=:status";
			$args["status"] = (int)$_GET["status"];
		}
		
		/* Error */
		if (isset($_GET["is_error"]))
		{
			$is_error = $_GET["is_error"];
			if ($is_error == "no")
			{
				$where[] = "error_code=1";
			}
			else if ($is_error == "yes")
			{
				$where[] = "error_code < 0";
			}
		}
		
		/* Code 1C */
		if (isset($_GET["code_1c"]))
		{
			$where[] = "code_1c=:code_1c";
			$args["code_1c"] = $_GET["code_1c"];
		}
		
		/* Name */
		if (isset($_GET["name"]))
		{
			$where[] = "name like :name";
			$args["name"] = "%" . $wpdb->esc_like($_GET["name"]) . "%";
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
		?>
		<style>
		textarea[data-name=data]{
			min-height: 500px !important;
		}
		</style>
		<?php
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
	
	
	
	/**
	 * Display table sub
	 */
	function display_table_sub()
	{
	}
}

}