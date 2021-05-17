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

if ( !class_exists( Product::class ) ) 
{

class ProductParams
{
	public static function show()
	{
		if (isset($_GET["param_id"]))
		{
			$table = new Product_Params_Values_Table();
			$table->display();
		}
		else
		{
			$table = new Product_Params_Table();
			$table->display();
		}
	}
}


class Product_Params_Table extends \WP_List_Table 
{
	
	function __construct()
	{
		global $status, $page;

		parent::__construct(array(
			'singular' => 'elberos-products',
			'plural' => 'elberos-products',
		));
	}
	
	function get_table_name()
	{
		global $wpdb;
		return $wpdb->prefix . 'elberos_products_params';
	}
	
	
	// Вывод значений по умолчанию
	function get_default()
	{
		return array(
			'id' => 0,
			'alias' => '',
			'type' => '',
		);
	}
	
	// Валидация значений
	function item_validate($item)
	{
		return true;
	}
	
	// Колонки таблицы
	function get_columns()
	{
		$columns = array(
			'cb' => '<input type="checkbox" />',
			'name' => __('Название', 'elberos-commerce'),
			'type' => __('Тип', 'elberos-commerce'),
			'alias' => __('Ярлык', 'elberos-commerce'),
			'buttons' => __('', 'elberos-commerce'),
		);
		return $columns;
	}
	
	// Сортируемые колонки
	function get_sortable_columns()
	{
		$sortable_columns = array(
			'alias' => array('alias', true),
			'name' => array('name', true),
			'type' => array('type', true),
		);
		return $sortable_columns;
	}
	
	// Действия
	function get_bulk_actions()
	{
		$is_deleted = isset($_REQUEST['is_deleted']) ? $_REQUEST['is_deleted'] : "";
		if ($is_deleted != 'true')
		{
			$actions = array(
				'trash' => 'Переместить в корзину',
			);
		}
		else
		{
			$actions = array(
				'notrash' => 'Восстановить из корзины',
				'delete' => 'Удалить навсегда',
			);
		}
		return $actions;
	}
	
	// Вывод каждой ячейки таблицы
	function column_default($item, $column_name)
	{
		return isset($item[$column_name])?$item[$column_name]:'';
	}
	
	// Заполнение колонки cb
	function column_cb($item)
	{
		return sprintf(
			'<input type="checkbox" name="id[]" value="%s" />',
			$item['id']
		);
	}
	
	// Колонка type
	function column_type($item)
	{
		$type = isset($item["type"]) ? $item["type"] : "";
		if ($type == "text") return "Текст";
		if ($type == "list") return "Список";
		/*if ($type == "multilist") return "Мультисписок";*/
		return "";
	}
	
	// Колонка name
	function column_buttons($item)
	{
		$actions = array(
			'edit' => sprintf(
				'<a href="?page=elberos-commerce-product-params&action=edit&id=%s">%s</a>',
				$item['id'], 
				__('Edit', 'elberos-commerce')
			),
			/*
			'delete' => sprintf(
				'<a href="?page=elberos-commerce-product-params&action=show_delete&id=%s">%s</a>',
				$item['id'],
				__('Delete', 'elberos-commerce')
			),*/
		);
		
		if (in_array($item["type"], ["list", "multilist"]))
		{
			$actions["values"] = sprintf(
				'<a href="?page=elberos-commerce-product-params&param_id=%s">%s</a>',
				$item['id'],
				__('Значения', 'elberos-commerce')
			);
		}
		
		return $this->row_actions($actions, true);
	}
	
	// Создает элементы таблицы
	function prepare_items()
	{
		global $wpdb;
		$table_name = $this->get_table_name();
		
		$per_page = 10; 

		$columns = $this->get_columns();
		$hidden = array();
		$sortable = $this->get_sortable_columns();
		
		$this->_column_headers = array($columns, $hidden, $sortable);
	   
		$this->process_bulk_action();

		$total_items = $wpdb->get_var("SELECT COUNT(id) FROM $table_name");

		$is_deleted = isset($_REQUEST['is_deleted']) ? $_REQUEST['is_deleted'] : "";
		$paged = isset($_REQUEST['paged']) ? max(0, intval($_REQUEST['paged']) - 1) : 0;
		$orderby = (isset($_REQUEST['orderby']) && in_array($_REQUEST['orderby'], array_keys($this->get_sortable_columns()))) ? $_REQUEST['orderby'] : '';
		$order = (isset($_REQUEST['order']) && in_array($_REQUEST['order'], array('asc', 'desc'))) ? $_REQUEST['order'] : '';
		
		if ($order == "" && $orderby == ""){ $orderby = "name"; $order = "asc"; }
		if ($orderby == ""){ $orderby = "id"; }
		if ($order == ""){ $order = "asc"; }
		
		$inner_join = [];
		$args = [];
		$where = [];
		if ($is_deleted == "true") $where[] = "is_deleted = 1";
		else $where[] = "is_deleted = 0";
		
		$inner_join = implode(" ", $inner_join);
		$where = implode(" and ", $where);
		if ($where != "") $where = "where " . $where;
		
		$args[] = $per_page;
		$args[] = $paged * $per_page;
		
		$sql = $wpdb->prepare
		(
			"SELECT t.* FROM $table_name as t $where
			ORDER BY $orderby $order LIMIT %d OFFSET %d",
			$args
		);
		
		$this->items = $wpdb->get_results($sql, ARRAY_A);

		$this->set_pagination_args(array(
			'total_items' => $total_items, 
			'per_page' => $per_page,
			'total_pages' => ceil($total_items / $per_page) 
		));
	}
	
	function process_bulk_action()
	{
		global $wpdb;
		$table_name = $this->get_table_name();

		if ($this->current_action() == 'trash')
		{
			$ids = isset($_REQUEST['id']) ? $_REQUEST['id'] : array();
			if (is_array($ids)) $ids = implode(',', $ids);

			if (!empty($ids)) {
				$wpdb->query("update $table_name set is_deleted=1 WHERE id IN($ids)");
			}
		}
		
		if ($this->current_action() == 'notrash')
		{
			$ids = isset($_REQUEST['id']) ? $_REQUEST['id'] : array();
			if (is_array($ids)) $ids = implode(',', $ids);

			if (!empty($ids)) {
				$wpdb->query("update $table_name set is_deleted=0 WHERE id IN($ids)");
			}
		}
		
		if ($this->current_action() == 'delete')
		{
			$ids = isset($_REQUEST['id']) ? $_REQUEST['id'] : array();
			if (is_array($ids)) $ids = implode(',', $ids);

			if (!empty($ids)) {
				$wpdb->query("DELETE FROM $table_name WHERE id IN($ids)");
			}
		}
	}
	
	function process_item($item)
	{
		$item = \Elberos\Update::intersect
		(
			$item,
			[
				"alias",
				"type",
			]
		);
		
		$default_lang = \Elberos\wp_get_default_lang();
		$item["text"] = [];
		$search = [];
		$langs = \Elberos\wp_langs();
		foreach ($langs as $key => $lang)
		{
			$lang_code = $lang["code"];
			$text = isset($_POST["text"][$lang_code]) ? $_POST["text"][$lang_code] : "";
			$item["text"][$lang_code] = $text;
			$search[] = $text;
		}
		$item["search"] = trim(implode(" ", $search));
		$item["text"] = serialize($item["text"]);
		$item["name"] = isset($_POST["text"][$default_lang]) ? $_POST["text"][$default_lang] : "";
		$item["alias"] = \Elberos\wp_get_alias($_POST["text"], $item["alias"]);
		
		return $item;
	}
	
	function after_process_item($action, $success_save, $item)
	{
		global $wpdb;
		
		if ($success_save)
		{
		}
	}
	
	function css()
	{
	}
	
	function display_add_or_edit()
	{
		global $wpdb;
		
		$res = \Elberos\Update::wp_save_or_update($this, basename(__FILE__));
		
		$message = $res['message'];
		$notice = $res['notice'];
		$item = $res['item'];
		
		?>
		
		<div class="wrap elberos-commerce">
			<div class="icon32 icon32-posts-post" id="icon-edit"><br></div>
			<h1><?php _e($item['id'] > 0 ? 'Редактирование параметра' : 'Добавление параметра', 'elberos-commerce')?></h1>
			
			<?php if (!empty($notice)): ?>
				<div id="notice" class="error"><p><?php echo $notice ?></p></div>
			<?php endif;?>
			<?php if (!empty($message)): ?>
				<div id="message" class="updated"><p><?php echo $message ?></p></div>
			<?php endif;?>
			
			<a type="button" class='button-primary' href='?page=elberos-commerce-product-params'> Back </a>
			
			<form id="form" method="POST">
				<input type="hidden" name="nonce" value="<?php echo wp_create_nonce(basename(__FILE__))?>"/>
				<input type="hidden" name="id" value="<?php echo $item['id'] ?>"/>
				<div class="metabox-holder" id="poststuff">
					<div id="post-body">
						<div id="post-body-content">
							<div class="add_or_edit_form"
								style="width: 60%; display: inline-block; vertical-align: topl">
								<?php $this->display_form($item) ?>
							</div>
							<div style='clear: both'></div>
							<input type="submit" class="button-primary" value="<?php _e('Save', 'elberos-commerce')?>" >
						</div>
					</div>
				</div>
			</form>
		</div>
		
		<?php
	}
	
	function display_form($item)
	{
		$type = isset($item['type']) ? $item['type'] : "";
		$text = isset($item['text']) ? $item['text'] : "";
		$text = @unserialize($text);
		?>
		
		<p>
			<label for="type"><?php _e('Тип:', 'elberos-commerce')?></label>
		<br>
			<select id="type" name="type" type="text" style="width: 100%; max-width: 100%;"
				value="<?php echo esc_attr($type)?>"
			>
				<option value="" <?= $type == "" ? "selected" : "" ?>>Укажите тип</option>
				<option value="text" <?= $type == "text" ? "selected" : "" ?>>Текст</option>
				<option value="list" <?= $type == "list" ? "selected" : "" ?>>Список</option>
				<option value="multilist" <?= $type == "multilist" ? "selected" : "" ?>>Мультисписок</option>
			</select>
		</p>
		
		<?php
		
		$langs = \Elberos\wp_langs();
		foreach ($langs as $key => $lang)
		{
			$lang_name = $lang['name'];
			$lang_code = $lang['code'];
			$text_name = isset($text[$lang['code']]) ? $text[$lang['code']] : "";
			
			?>
			<p>
				<label for="text[<?= esc_attr($lang_code) ?>]">
					<?php _e('Название', 'elberos-commerce')?> (<?= esc_attr($lang_name) ?>):
				</label>
			<br>
				<input id="text[<?= esc_attr($lang_code) ?>]" 
					name="text[<?= esc_attr($lang_code) ?>]"
					type="text" style="width: 100%"
					value="<?php echo esc_attr($text_name)?>" >
			</p>
			<?php
		}
		
		?>
		
		<p>
			<label for="alias"><?php _e('Ярлык (необязательно):', 'elberos-commerce')?></label>
		<br>
			<input id="alias" name="alias" type="text" style="width: 100%"
				value="<?php echo esc_attr($item['alias'])?>" >
		</p>
		
		<?php
	}
	
	function display_table()
	{
		$is_deleted = isset($_REQUEST['is_deleted']) ? $_REQUEST['is_deleted'] : "";
		
		$this->prepare_items();
		$message = "";
		?>
		<div class="wrap">
			<h1 class="wp-heading-inline">
				<?php echo get_admin_page_title() ?>
			</h1>
			<a href="<?php echo get_admin_url(get_current_blog_id(),
				'admin.php?page=elberos-commerce-product-params&action=add');?>"
				class="page-title-action"
			>
				<?php _e('Add new', 'template')?>
			</a>
			<hr class="wp-header-end">
			
			<div class="icon32 icon32-posts-post" id="icon-edit"><br></div>
			<?php echo $message; ?>
			
			<ul class="subsubsub">
				<li>
					<a href="admin.php?page=elberos-commerce-product-params"
						class="<?= ($is_deleted != "true" ? "current" : "")?>"  >Все</a> |
				</li>
				<li>
					<a href="admin.php?page=elberos-commerce-product-params&is_deleted=true"
						class="<?= ($is_deleted == "true" ? "current" : "")?>" >Корзина</a>
				</li>
			</ul>
			
			<?php
			// выводим таблицу на экран где нужно
			echo '<form action="" method="POST">';
			parent::display();
			echo '</form>';
			?>

		</div>
		<?php
	}
	
	function display()
	{
		$this->css();
		$action = $this->current_action();
		
		if ($action == 'add' or $action == 'edit')
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