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

if ( !class_exists( Category_Table::class ) ) 
{

class Category_Table extends \Elberos\Table 
{
	
	/**
	 * Table name
	 */
	function get_table_name()
	{
		global $wpdb;
		return $wpdb->base_prefix . 'elberos_commerce_categories';
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
		$struct = \Elberos\Commerce\Category::create
		(
			"admin_table",
			function ($struct)
			{
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
	 * Get current action
	 */
	public function current_action()
	{
		if ( isset( $_REQUEST['sub'] ) && -1 != $_REQUEST['sub'] )
		{
			return $_REQUEST['sub'];
		}
		return false;
	}
	
	
	
	/**
	 * Get bulk action name
	 */
	function get_bulk_action_name()
	{
		return "sub";
	}
	
	
	
	/**
	 * Get form id
	 */
	function get_form_id($default = 0)
	{
		return (isset($_REQUEST['sub_id']) ? $_REQUEST['sub_id'] : $default);
	}
	
	
	
	/**
	 * Get bulk id
	 */
	function get_bulk_id($default = [])
	{
		return (isset($_REQUEST['sub_id']) ? $_REQUEST['sub_id'] : $default);
	}
	
	
	
	/* Заполнение колонки cb */
	function column_cb($item)
    {
        return sprintf(
            '<input type="checkbox" name="sub_id[]" value="%s" />',
            $item['id']
        );
    }
	
	
	
	/**
	 * Column buttons
	 */
	function column_buttons($item)
	{
		$page_name = $this->get_page_name();
		$id = isset($_GET['id']) ? $_GET['id'] : '';
		return
			sprintf
			(
				'<a href="?page=' . $page_name . '&action=categories&id=%s&sub_id=%s">%s</a>', 
				$id, $item['id'], __('Подкатегории', 'elberos-commerce')
			) .
			"&nbsp;&nbsp;" .
			sprintf
			(
				'<a href="?page=' . $page_name . '&action=categories&id=%s&sub=edit&sub_id=%s">%s</a>',
				$id, $item['id'], __('Редактировать', 'elberos-commerce')
			)
		;
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
		if (in_array($action, ['add', 'edit']))
		{
			parent::process_bulk_action();
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
		if ($this->form_item_id == 0)
		{
			$item["classifier_id"] = isset($_GET["id"]) ? $_GET["id"] : 0;
		}
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
		$args =
		[
			"classifier_id" => isset($_GET["id"]) ? $_GET["id"] : 0,
		];
		$where =
		[
			"classifier_id=:classifier_id",
		];
		
		/* Is deleted */
		if (isset($_GET["is_deleted"]) && $_GET["is_deleted"] == "true")
		{
			$where[] = "is_deleted=1";
		}
		else
		{
			/* Add parent category */
			$where[] = "parent_category_id=:parent_category_id";
			if (isset($_GET["sub_id"])) $args["parent_category_id"] = $_GET["sub_id"];
			else $args["parent_category_id"] = 0;
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
		.admin_breadcrumbs{
			padding: 0; margin: 0;
			padding-top: 10px;
		}
		.admin_breadcrumbs li{
			display: inline-block;
			vertical-align: top;
			padding: 0; margin: 0;
		}
		.admin_breadcrumbs li a{
			text-decoration: none;
		}
		</style>
		<?php
	}
	
	
	
	/**
	 * Display table sub
	 */
	function display_table_sub()
	{
		global $wpdb;
		
		$page_name = $this->get_page_name();
		$id = isset($_GET['id']) ? $_GET['id'] : "";
		$is_deleted = isset($_GET['is_deleted']) ? $_GET['is_deleted'] : "";
		$url = "admin.php?page=" . $page_name . "&action=categories&id=" . $id;
		?>
		<?php
		
		$items = [];
		$table_name = $this->get_table_name();
		$sub_id = (int) (isset($_GET['sub_id']) ? $_GET['sub_id'] : 0);
		while ($sub_id != 0)
		{
			$sql = \Elberos\wpdb_prepare
			(
				"select * from " . $table_name . " where id=:id limit 1",
				[
					"id" => $sub_id,
				]
			);
			$row = $wpdb->get_row($sql, ARRAY_A);
			if ($row)
			{
				$sub_id = $row["parent_category_id"];
				$items[] = $row;
			}
			else
			{
				break;
			}
		}
		$items[] =
		[
			"id" => 0,
			"name" => "Главная",
		];
		$items = array_reverse($items);
		?>
		<ul class="admin_breadcrumbs">
			<?php
				$count = count($items);
				foreach ($items as $i => $item)
				{
					echo "<li><a href='" . esc_attr($url . "&sub_id=" . $item["id"]) . "'>" . $item["name"] . "</a></li>";
					if ($i < $count - 1)
					{
						echo "<li>&nbsp;/&nbsp;</li>";
					}
				}
			?>
		</ul>
		<ul class="subsubsub">
			<li>
				<a href="<?= esc_attr($url) ?>"
					class="<?= ($is_deleted != "true" ? "current" : "")?>"  >Все</a> |
			</li>
			<li>
				<a href="<?= esc_attr($url) . "&is_deleted=true" ?>"
					class="<?= ($is_deleted == "true" ? "current" : "")?>" >Корзина</a>
			</li>
		</ul>
		<?php
	}
	
	
	
	/**
	 * Display form sub
	 */
	function display_form_sub()
	{
		$page_name = $this->get_page_name();
		$item = $this->form_item;
		?>
		
		<div style="clear: both;"></div>
		
		<?php
	}
	
	
	
	/**
	 * Returns form title
	 */
	function get_form_title($item)
	{
		return _e($item['id'] > 0 ? 'Редактировать категорию' : 'Добавить категорию', 'elberos-commerce');
	}
	
	
	
	/**
	 * Returns table title
	 */
	function get_table_title()
	{
		return "Категории";
	}
	
	
	
	/**
	 * Display table add button
	 */
	function display_table_add_button()
	{
		$page_name = $this->get_page_name();
		$id = isset($_GET['id']) ? $_GET['id'] : '';
		?>
		<a href="<?php echo get_admin_url(get_current_blog_id(), 'admin.php?page=' . $page_name . '&action=categories&id=' . $id . '&sub=add');?>"
			class="page-title-action"
		>
			<?php _e('Add new', 'elberos-core')?>
		</a>
		<?php
	}
	
	
	
	/**
	 * Display action
	 */
	function display_action()
	{
		$action = $this->current_action();
		parent::display_action();
	}
	
	
}

}