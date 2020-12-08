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
	
	function get_table_name_categories()
	{
		global $wpdb;
		return $wpdb->prefix . 'elberos_products_categories';
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
	
	function extra_tablenav( $which )
	{
		global $wpdb;
		if ( $which == "top" )
		{
			$categories = $wpdb->get_results
			(
				$wpdb->prepare
				(
					"SELECT * FROM {$wpdb->prefix}posts WHERE post_type = 'catalog' and post_status='publish'"
				),
				ARRAY_A
			);
			
			$selected_category = isset($_POST['category']) ? $_POST['category'] : "";
			$in_catalog = isset($_POST['in_catalog']) ? $_POST['in_catalog'] : "";
			?>
			
			<input type="text" name='product_name' style='vertical-align: middle; display: inline-block;'
				value='<?=esc_attr(isset($_POST['product_name']) ? $_POST['product_name'] : "");?>'
				placeholder='Название товара'
			/>
			
			<select class='product_select_category' name='category' value='<?= esc_attr($selected_category) ?>'
				style='vertical-align: middle; display: inline-block;'
			>
				<option value="">Все категории</option>
				<?php foreach ($categories as $cat) {
					$selected = "";
					if ($selected_category == $cat['ID'])
					{
						$selected = "selected='selected'";
					}
				?>
					<option value="<?= esc_attr($cat['ID']) ?>" <?= $selected ?>>
						<?= esc_html($cat['post_title']) ?>
					</option>
				<?php } ?>
			</select>
			
			<select name="in_catalog" value="<?php echo esc_attr($in_catalog)?>">
				<option value="">В каталоге</option>
				<option value="0" <?= $in_catalog == "0" ? "selected" : "" ?>>Нет</option>
				<option value="1" <?= $in_catalog == "1" ? "selected" : "" ?>>Да</option>
			</select>
			
			<input type="submit" name="filter_action" id="post-query-submit" class="button" value="Фильтр">
			<?php
		}
	}
	
	// Создает элементы таблицы
	function prepare_items()
	{
		global $wpdb;
		$table_name = $this->get_table_name();
		$table_name_categories = $this->get_table_name_categories();
		
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
		
		/* Add category filter */
		$selected_category = isset($_POST['category']) ? $_POST['category'] : "";
		if ($selected_category)
		{
			$inner_join[] = "inner join $table_name_categories as cat on (cat.product_id=t.id)";
			$where[] = "cat.category_id = %d";
			$args[] = $selected_category;
		}
		
		/* Add search filter */
		$product_name = isset($_POST['product_name']) ? $_POST['product_name'] : "";
		if ($product_name)
		{
			$where[] = "search LIKE %s";
			$args[] = "%" . $product_name . "%";
		}
		
		/* Add in_catalog filter */
		$in_catalog = isset($_POST['in_catalog']) ? $_POST['in_catalog'] : "";
		if ($in_catalog != "")
		{
			$where[] = "in_catalog=%d";
			$args[] = $in_catalog;
		}
		
		$inner_join = implode(" ", $inner_join);
		$where = implode(" and ", $where);
		if ($where != "") $where = "where " . $where;
		
		$args[] = $per_page;
		$args[] = $paged * $per_page;
		
		$sql = $wpdb->prepare
		(
			"SELECT t.* FROM $table_name as t $inner_join $where
			ORDER BY $orderby $order LIMIT %d OFFSET %d",
			$args
		);
		
		$this->items = $wpdb->get_results(
			$sql,
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
		
		$search = [];
		$langs = \Elberos\wp_langs();
		foreach ($langs as $key => $lang)
		{
			$locale = $lang['locale'];
			$name = isset($_POST["text"]["name"][$locale]) ? $_POST["text"]["name"][$locale] : "";
			$search[] = $name;
		}
		$item["search"] = trim(implode(" ", $search));
		
		return $item;
	}
	
	function after_process_item($action, $success_save, $item)
	{
		global $wpdb;
		
		if ($success_save)
		{
			$table_name_text = $this->get_table_name_text();
			
			/* Save langs */
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
			
			/* Save category */
			$table_name_categories = $this->get_table_name_categories();
			$cat = $_POST["cat"];
			$cat = gettype($cat) == 'array' ? array_keys($cat) : [];
			
			$categories = $wpdb->get_results
			(
				$wpdb->prepare("SELECT * FROM $table_name_categories WHERE product_id = %d", $item['id']), ARRAY_A
			);
			
			/* Add */
			foreach ($cat as $cat_id)
			{
				$find = false;
				foreach ($categories as $c)
				{
					if ($c['category_id'] == $cat_id)
					{
						$find = true;
						break;
					}
				}
				if (!$find)
				{
					$wpdb->insert($table_name_categories, ['product_id'=>$item['id'], 'category_id' => $cat_id]);
				}
			}
			
			/* Delete */
			foreach ($categories as $c)
			{
				$cat_id = $c['category_id'];
				if (!in_array($cat_id, $cat))
				{
					$wpdb->delete($table_name_categories, ['product_id'=>$item['id'], 'category_id' => $cat_id]);
				}
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
		
		/* Read categories */
		if ($item['id'] > 0)
		{
			$table_name_categories = $this->get_table_name_categories();
			$categories = $wpdb->get_results
			(
				$wpdb->prepare("SELECT * FROM $table_name_categories WHERE product_id = %d", $item['id']), ARRAY_A
			);
			$item['categories'] = $categories;
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
							<div class="add_or_edit_form"
								style="width: 60%; display: inline-block; vertical-align: topl">
								<? $this->display_form($item) ?>
							</div>
							<div style="width: calc(40% - 5px); display: inline-block; vertical-align: top;">
								<? $this->display_form_category($item) ?>
								<? $this->display_form_photos($item) ?>
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
					break;
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
			<label for="alias"><?php _e('Ярлык (необязательно):', 'elberos-commerce')?></label>
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
			<select id="in_catalog" name="in_catalog" style="width: 100%"
				value="<?php echo esc_attr($item['in_catalog'])?>">
				<option value="0" <?= $item['in_catalog'] == 0 ? "selected" : "" ?>>Нет</option>
				<option value="1" <?= $item['in_catalog'] == 1 ? "selected" : "" ?>>Да</option>
			</select>
		</p>
		
		<?php
	}
	
	function display_form_category($item)
	{
		global $wpdb;
		$categories = $wpdb->get_results
		(
			$wpdb->prepare
			(
				"SELECT * FROM {$wpdb->prefix}posts WHERE post_type = 'catalog' and post_status='publish'"
			),
			ARRAY_A
		);
		?>
		
		<style>
		.product_category
		{
			font-size: 0;
			padding-bottom: 5px;
		}
		.product_category_name, .product_category_buttons
		{
			display: inline-block;
			vertical-align: top;
			font-size: 14px;
		}
		.product_category_name
		{
			width: calc(100% - 65px);
		}
		.product_category_buttons
		{
			width: 60px;
		}
		.product_category_buttons button
		{
			cursor: pointer;
		}
		</style>
		
		<h2>Категории</h2>
		<div style='padding: 8px 12px;'>
			
			<div class='product_categories'>
				<?php 
					if (isset($item['categories'])) foreach ($item['categories'] as $row)
					{
						$find_category = null;
						foreach ($categories as $cat)
						{
							if ($cat['ID'] == $row['category_id'])
							{
								$find_category = $cat;
							}
						}
						if ($find_category)
						{
				?>
				
				<div class='product_category' data-id='<?= esc_attr($find_category['ID']) ?>'>
					<div class='product_category_name'><?= esc_html($find_category['post_title']) ?></div>
					<div class='product_category_buttons'>
						<button data-id='<?= esc_attr($find_category['ID']) ?>' type='button'>
							Delete
						</button>
					</div>
					<input type='hidden' name='cat[<?= esc_attr($find_category['ID']) ?>]' value='1'>
				</div>
				
				<?php 
						}
					}
				?>
			</div>
			
			<p>
				<select class='product_select_category' style="width: 100%">
					<option value="">Выберите категорию</option>
					<?php foreach ($categories as $cat) { ?>
						<option value="<?= esc_attr($cat['ID']) ?>"><?= esc_html($cat['post_title']) ?></option>
					<?php } ?>
				</select>
			</p>
			
			<script>
				jQuery('.product_select_category').change(function(){
					var value = jQuery(this).val();
					var value_name = jQuery(this).find('option[value='+value+']').text();
					
					var find = false;
					var $items = jQuery('.product_category');
					for (var i=0; i<$items.length; i++)
					{
						var $item = jQuery($items[i]);
						var item_data_id = jQuery($item).attr('data-id');
						if (item_data_id == value)
						{
							find = true;
							break;
						}
					}
					
					if (!find)
					{
						var div = jQuery(document.createElement('div'))
						.addClass('product_category')
						.attr('data-id', value)
						.append
						(
							jQuery(document.createElement('div'))
							.addClass('product_category_name')
							.text(value_name)
						)
						.append
						(
							jQuery(document.createElement('div'))
							.addClass('product_category_buttons')
							.append
							(
								jQuery(document.createElement('button'))
								.attr('type', 'button')
								.attr('data-id', value)
								.text('Delete')
							)
						)
						.append
						(
							jQuery(document.createElement('input'))
							.attr('type', 'hidden')
							.attr('name', 'cat[' + value + ']')
							.attr('value', '1')
						)
						jQuery('.product_categories').append(div);
					}
					
					jQuery(this).val("");
				});
				jQuery(document).on('click', '.product_category button', '', function(){
					var data_id = jQuery(this).attr('data-id');
					var $items = jQuery('.product_category');
					for (var i=0; i<$items.length; i++)
					{
						var $item = jQuery($items[i]);
						var item_data_id = jQuery($item).attr('data-id');
						if (item_data_id == data_id)
						{
							$item.remove();
						}
					}
				});
			</script>
			
		</div>
		<?php
	}
	
	function display_form_photos($item)
	{
		?>
		<div>
			<h2>Фотографии</h2>
		</div>	
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