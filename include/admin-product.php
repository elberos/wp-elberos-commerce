<?php

/*!
 *  Elberos Commerce
 *
 *  (c) Copyright 2019-2020 "Ildar Bikmamatov" <support@elberos.org>
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

class Product
{
	public static function show()
	{
		$table = new Product_Table();
		$table->display();		
	}
}


class Product_Table extends \WP_List_Table 
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
		return $wpdb->prefix . 'elberos_products';
	}
	
	function get_table_name_text()
	{
		global $wpdb;
		return $wpdb->prefix . 'elberos_products_text';
	}
	
	// Вывод значений по умолчанию
	function get_default()
	{
		return array(
			'id' => 0,
			'name' => '',
			'alias' => '',
			'price' => 0,
			'in_catalog' => 0,
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
			'name' => __('Имя', 'elberos-commerce'),
			'price' => __('Цена', 'elberos-commerce'),
			'in_catalog' => __('В каталоге', 'elberos-commerce'),
			'buttons' => __('', 'elberos-commerce'),
		);
		return $columns;
	}
	
	// Сортируемые колонки
	function get_sortable_columns()
	{
		$sortable_columns = array(
			'name' => array('name', true),
			'api_name' => array('api_name', true),
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
	
	// Колонка name
	function column_buttons($item)
	{
		$actions = array(
			'edit' => sprintf(
				'<a href="?page=elberos-commerce&action=edit&id=%s">%s</a>',
				$item['id'], 
				__('Edit', 'elberos-commerce')
			),
			/*
			'delete' => sprintf(
				'<a href="?page=elberos-commerce&action=show_delete&id=%s">%s</a>',
				$item['id'],
				__('Delete', 'elberos-commerce')
			),*/
		);
		
		return $this->row_actions($actions, true);
	}
	
	// В каталоге
	function column_in_catalog($item)
	{
		if ($item["in_catalog"] == 1) return "Да";
		return "Нет";
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
		
		$where = "";
		if ($is_deleted == "true") $where = "where is_deleted = 1";
		else $where = "where is_deleted = 0";
		
		$this->items = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT t.* FROM $table_name as t $where
				ORDER BY $orderby $order LIMIT %d OFFSET %d",
				$per_page, $paged * $per_page
			),
			ARRAY_A
		);

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
				"name",
				"alias",
				"price",
				"in_catalog",
			]
		);
		
		$item["name"] = isset($_POST["text"]["name"]["ru_RU"]) ? $_POST["text"]["name"]["ru_RU"] : "";
		if ($item["alias"] == "")
		{
			$item["alias"] = sanitize_title($item["name"]);
		}
		
		return $item;
	}
	
	function after_process_item($action, $success_save, $item)
	{
		global $wpdb;
		
		if ($success_save)
		{
			$table_name_text = $this->get_table_name_text();
			
			$langs = \Elberos\wp_langs();
			foreach ($langs as $key => $lang)
			{
				$locale = $lang['locale'];
				
				$name = isset($_POST["text"]["name"][$locale]) ? $_POST["text"]["name"][$locale] : "";
				$description = isset($_POST["text"]["description"][$locale]) ?
					$_POST["text"]["description"][$locale] : ""
				;
				
				$arr =
				[
					"name" => $name,
					"description" => $description,
				];
				
				$sql = "INSERT INTO {$table_name_text} (product_id,locale,name,description) VALUES (%d,%s,%s,%s) ON DUPLICATE KEY UPDATE name = %s, description = %s";
				$sql = $wpdb->prepare($sql,$item['id'],$locale,$name,$description,$name,$description);
				$wpdb->query($sql);
				
			}
		}
	}
	
	function css()
	{
		?>
		<style>
		.cursor, a.cursor
		{
			cursor: pointer;
		}
		.nav-tab-data
		{
			display: none;
		}
		.nav-tab-data.nav-tab-data-active
		{
			display: block;
		}
		</style>
		<?php
	}
	
	function display_add_or_edit()
	{
		global $wpdb;
		
		$res = \Elberos\Update::wp_save_or_update($this, basename(__FILE__));
		
		$message = $res['message'];
		$notice = $res['notice'];
		$item = $res['item'];
		
		/* Read lang */
		if ($item['id'] > 0)
		{
			$table_name_text = $this->get_table_name_text();
			$text = $wpdb->get_results
			(
				$wpdb->prepare("SELECT * FROM $table_name_text WHERE product_id = %d", $item['id']), ARRAY_A
			);
			$item['text'] = $text;
		}
		
		$settings = isset($item['settings']) ? $item['settings'] : "";
		if ($settings != "")
		{
			$obj = json_decode($settings);
			if ($obj == null)
			{
				$notice = __('Settings json error', 'elberos-commerce');
			}
		}
		
		?>
		
		<div class="wrap">
			<div class="icon32 icon32-posts-post" id="icon-edit"><br></div>
			<h1><?php _e($item['id'] > 0 ? 'Редактирование товара' : 'Добавление товара', 'elberos-commerce')?></h1>
			
			<?php if (!empty($notice)): ?>
				<div id="notice" class="error"><p><?php echo $notice ?></p></div>
			<?php endif;?>
			<?php if (!empty($message)): ?>
				<div id="message" class="updated"><p><?php echo $message ?></p></div>
			<?php endif;?>
			
			<a type="button" class='button-primary' href='?page=elberos-commerce'> Back </a>
			
			<form id="form" method="POST">
				<input type="hidden" name="nonce" value="<?php echo wp_create_nonce(basename(__FILE__))?>"/>
				<input type="hidden" name="id" value="<?php echo $item['id'] ?>"/>
				<div class="metabox-holder" id="poststuff">
					<div id="post-body">
						<div id="post-body-content">
							<div class="add_or_edit_form" style="width: 60%">
								<? $this->display_form($item) ?>
							</div>
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
		?>
		<p>
			<nav class="nav-tab-wrapper">
				<?php
				$langs = \Elberos\wp_langs();
				foreach ($langs as $key => $lang)
				{
					?><a class="nav-tab cursor <?= $key == 0 ? "nav-tab-active" : "" ?>"
						data-tab="elberos_commerce_<?= esc_attr($lang['locale']) ?>"
						data-key="elberos_commerce"
					>
						<?= esc_html($lang['name']) ?>
					</a><?php
				}
				?>
			</nav>
		</p>
		
		<?php
		$langs = \Elberos\wp_langs();
		foreach ($langs as $key => $lang)
		{
			$item_text = null;
			foreach ($item['text'] as $arr)
			{
				if ($arr['locale'] == $lang['locale'])
				{
					$item_text = $arr;
				}
			}
			
			$text_name = (isset($item_text) && isset($item_text['name'])) ? $item_text['name'] : "";
			$text_description = (isset($item_text) && isset($item_text['description'])) ?
				$item_text['description'] : "";
			
			?>
			<p class='nav-tab-data-wrapper'>
				<div class='nav-tab-data <?= $key == 0 ? "nav-tab-data-active" : "" ?>'
					data-tab="elberos_commerce_<?= esc_attr($lang['locale']) ?>"
					data-key="elberos_commerce"
				>
					<p>
						<label for="name[<?= esc_attr($lang['locale']) ?>]">
							<?php _e('Название', 'elberos-commerce')?> <?= esc_attr($lang['locale']) ?>:
						</label>
					<br>
						<input id="name[<?= esc_attr($lang['locale']) ?>]" 
							name="text[name][<?= esc_attr($lang['locale']) ?>]"
							type="text" style="width: 100%"
							value="<?php echo esc_attr($text_name)?>" >
					</p>
					
					<p>
						<label for="description[<?= esc_attr($lang['locale']) ?>]">
							<?php _e('Описание', 'elberos-commerce')?> <?= esc_attr($lang['locale']) ?>:
						</label>
					<br>
						<textarea id="description[<?= esc_attr($lang['locale']) ?>]"
							name="text[description][<?= esc_attr($lang['locale']) ?>]"
							type="text" style="width: 100%; height: 300px;"><?= esc_html($text_description) ?></textarea>
					</p>
					
				</div>
			</p>
			<?php
		}
		
		?>
		<script>
		jQuery('.nav-tab').click(function(){
			var data_key = jQuery(this).attr('data-key');
			var data_tab = jQuery(this).attr('data-tab');
			jQuery(this).parent('.nav-tab-wrapper').find('.nav-tab').removeClass('nav-tab-active');
			jQuery(this).addClass('nav-tab-active');
			
			var $items = jQuery('.nav-tab-data');
			for (var i=0; i<$items.length; i++)
			{
				var $item = jQuery($items[i]);
				var item_data_key = jQuery($item).attr('data-key');
				var item_data_tab = jQuery($item).attr('data-tab');
				if (data_key == item_data_key)
				{
					$item.removeClass('nav-tab-data-active');
					if (data_tab == item_data_tab)
					{
						$item.addClass('nav-tab-data-active');
					}
				}
			}
			
		});
		</script>
		
		<p>
			<label for="alias"><?php _e('Синоним (необязательно):', 'elberos-commerce')?></label>
		<br>
			<input id="alias" name="alias" type="text" style="width: 100%"
				value="<?php echo esc_attr($item['alias'])?>" >
		</p>
		<p>
			<label for="price"><?php _e('Цена:', 'elberos-commerce')?></label>
		<br>
			<input id="price" name="price" type="text" style="width: 100%"
				value="<?php echo esc_attr($item['price'])?>" >
		</p>
		<p>
			<label for="in_catalog"><?php _e('Разместить в каталоге:', 'elberos-commerce')?></label>
		<br>
			<select id="in_catalog" name="in_catalog" type="text" style="width: 100%"
				value="<?php echo esc_attr($item['in_catalog'])?>">
				<option value="0" <?= $item['in_catalog'] == 0 ? "selected" : "" ?>>Нет</option>
				<option value="1" <?= $item['in_catalog'] == 1 ? "selected" : "" ?>>Да</option>
			</select>
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
			<a href="<?php echo get_admin_url(get_current_blog_id(), 'admin.php?page=elberos-commerce&action=add');?>"
				class="page-title-action"
			>
				<?php _e('Add new', 'template')?>
			</a>
			<hr class="wp-header-end">
			
			<div class="icon32 icon32-posts-post" id="icon-edit"><br></div>
			<?php echo $message; ?>
			
			<ul class="subsubsub">
				<li>
					<a href="admin.php?page=elberos-commerce"
						class="<?= ($is_deleted != "true" ? "current" : "")?>"  >Все</a> |
				</li>
				<li>
					<a href="admin.php?page=elberos-commerce&is_deleted=true"
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