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

if ( !class_exists( Invoices_Table::class ) ) 
{

class Invoices
{
	public static function show()
	{
		$table = new Invoices_Table();
		$table->display();		
	}
}


class Invoices_Table extends \WP_List_Table 
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
		return $wpdb->prefix . 'elberos_commerce_invoice';
	}
	
	
	// Вывод значений по умолчанию
	function get_default()
	{
		return array
		(
			'id' => 0,
			'utm' => '',
			'price' => '',
			'basket' => '',
			'send_data' => '',
			'secret_code' => '',
			'products_meta' => '',
			'gmtime_add' => '',
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
			'id' => __('Инвойс', 'elberos-commerce'),
			'client' => __('Клиент', 'elberos-commerce'),
			'price' => __('Цена', 'elberos-commerce'),
			'gmtime_add' => __('Дата инвойса', 'elberos-commerce'),
			'buttons' => __('', 'elberos-commerce'),
		);
		return $columns;
	}
	
	// Сортируемые колонки
	function get_sortable_columns()
	{
		$sortable_columns = array
		(
		);
		return $sortable_columns;
	}
	
	// Действия
	function get_bulk_actions()
	{
		return [];
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
	
	// Колонка client
	function column_client($item)
	{
		$arr =
		[
			"name" => "Имя",
			"surname" => "Фамилия",
			"email" => "E-mail",
			"phone" => "Телефон",
		];
		$res = [];
		$send_data = json_decode($item["send_data"], true);
		foreach ($arr as $key => $title)
		{
			$value = isset($send_data[$key]) ? $send_data[$key] : "";
			if ($value == "") continue;
			$value = esc_html($value);
			$res[] = esc_html($title) . ": ". mb_substr($value, 0, 30);
		}
		return implode("<br/>\n", $res);
	}
	
	// Колонка дата
	function column_gmtime_add($item)
	{
		return \Elberos\wp_from_gmtime($item['gmtime_add']);
	}
	
	// Колонка name
	function column_buttons($item)
	{
		$actions = array(
			'view' => sprintf(
				'<a href="?page=elberos-commerce-invoice&action=view&id=%s">%s</a>',
				$item['id'], 
				__('View', 'elberos-commerce')
			),
		);
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
		
		$paged = isset($_REQUEST['paged']) ? max(0, intval($_REQUEST['paged']) - 1) : 0;
		$orderby = (isset($_REQUEST['orderby']) && in_array($_REQUEST['orderby'], array_keys($this->get_sortable_columns()))) ? $_REQUEST['orderby'] : '';
		$order = (isset($_REQUEST['order']) && in_array($_REQUEST['order'], array('asc', 'desc'))) ? $_REQUEST['order'] : '';
		
		if ($order == "" && $orderby == ""){ $orderby = "id"; $order = "desc"; }
		if ($orderby == ""){ $orderby = "id"; }
		if ($order == ""){ $order = "desc"; }
		
		$inner_join = [];
		$args = [];
		$where = [];
		
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
	}
	
	function process_item($item)
	{
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
	
	function display_view()
	{
		global $wpdb;
		
		$item = [];
		$item_id = (int) (isset($_REQUEST['id']) ? $_REQUEST['id'] : 0);
		if ($item_id > 0)
		{
			$table_name = $this->get_table_name();
			$sql = $wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $item_id);
			$item = $wpdb->get_row($sql, ARRAY_A);
		}
		
		$default = $this->get_default();
		$item = shortcode_atts($default, $item);
		
		$message = '';
		$notice = '';
		
		?>
		
		<div class="wrap elberos-commerce">
			<div class="icon32 icon32-posts-post" id="icon-edit"><br></div>
			<h1>Инвойс <?= $item['id'] ?></h1>
			
			<?php if (!empty($notice)): ?>
				<div id="notice" class="error"><p><?php echo $notice ?></p></div>
			<?php endif;?>
			<?php if (!empty($message)): ?>
				<div id="message" class="updated"><p><?php echo $message ?></p></div>
			<?php endif;?>
			
			<a type="button" class='button-primary' href='?page=elberos-commerce-invoice'> Back </a>
			
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
						</div>
					</div>
				</div>
			</form>
		</div>
		
		<?php
	}
	
	function display_form($item)
	{
		$basket = json_decode($item["basket"], true);
		$send_data = json_decode($item["send_data"], true);
		$products_meta = json_decode($item["products_meta"], true);
		
		?>
		
		<style>
		.page_basket_table_row td {
			padding-top: 10px;
			padding-bottom: 10px;
			border-bottom: 1px #e0d3c0 solid;
		}
		.page_basket_product_image, .page_basket_product_title {
			display: inline-block;
			vertical-align: middle;
		}
		.page_basket_product_image img {
			height: 100px;
		}
		.page_basket_product_price {
			font-weight: bold;
		}
		.page_basket_table_row_total td {
			padding-top: 20px;
			padding-bottom: 10px;
		}
		.page_basket_table_row_total_2 {
			text-align: right;
			padding-right: 20px;
		}
		</style>
		
		<p>
			<?= $this->column_client($item); ?>
		</p>
		
		<p>
			<label for="type"><?php _e('Цена:', 'elberos-commerce')?></label>
		<br>
			<?= $item['price'] ?>
		</p>
		
		<p>
			<label for="type"><?php _e('Комментарий:', 'elberos-commerce')?></label>
		<br>
			<?= esc_html(isset($send_data['comment']) ? $send_data['comment'] : '') ?>
		</p>
		
		<p>
			<label for="type"><?php _e('Дата:', 'elberos-commerce')?></label>
		<br>
			<?= $this->column_gmtime_add($item); ?>
		</p>
		
		<p>
			<label for="type"><?php _e('Товары:', 'elberos-commerce')?></label>
		<br>
		
		<table>
			<tr class='page_basket_table_header'>
				<th>Наименование</th>
				<th>Цена</th>
				<th>Ед. изм.</th>
				<th>Количество</th>
				<th>Сумма</th>
			</tr>
			
			<?php
				$basket_sum_total = 0;
				foreach ($basket as $basket_data)
				{
					$product_id = $basket_data['product_id'];
					$product_count = intval($basket_data['product_count']);
					$product = isset($products_meta[$product_id]) ? $products_meta[$product_id] : [];
					$photo_url = isset($product['photo_url']) ? $product['photo_url'] : '';
					$basket_sum_total = $basket_sum_total + $product['price'] * $product_count;
					
					?>
					<tr class='page_basket_table_header'>
						<td>
							<div class='page_basket_product_image'>
								<img src='<?= esc_attr($photo_url) ?>' />
							</div>
							<div class='page_basket_product_title'>
								<div class='page_basket_product_title_row'>
									<?= esc_html($product['text']['ru_RU']['name']) ?>
								</div>
								<div class='page_basket_product_title_row'>Артикул: <span class='value'>646464</span></div>
								<div class='page_basket_product_title_row'><span class='value'>340 шт/кор</span></div>
							</div>
						</td>
						<td>
							<div class='page_basket_product_price'>
								<span><?= esc_html(\Elberos\formatMoney($product['price'])) ?></span> тг
							</div>
						</td>
						<td>
							<center>коробка</center>
						</td>
						<td>
							<center><?= esc_html($product_count) ?></center>
						</td>
						<td>
							<div class='page_basket_product_price'>
								<span><?= esc_html(\Elberos\formatMoney($product['price'] * $product_count)) ?></span> тг
							</div>
						</td>
					</tr>
					<?php
				}
			?>
			
			<tr class='page_basket_table_row_total'>
				<td class='page_basket_table_row_total_2' colspan='4'>
					Итого:
				</td>
				<td class='page_basket_table_row_total_3' colspan='2'>
					<span class='page_basket_table_row_total_value'>
						<?= esc_html( \Elberos\formatMoney($basket_sum_total) ) ?>
					</span> тг
				</td>
			</tr>
			
		</table>
		
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
				'admin.php?page=elberos-commerce-invoice&action=add');?>"
				class="page-title-action"
			>
				<?php _e('Add new', 'template')?>
			</a>
			<hr class="wp-header-end">
			
			<div class="icon32 icon32-posts-post" id="icon-edit"><br></div>
			<?php echo $message; ?>
			
			<ul class="subsubsub">
				<li>
					<a href="admin.php?page=elberos-commerce-invoice"
						class="<?= ($is_deleted != "true" ? "current" : "")?>"  >Все</a> |
				</li>
				<li>
					<a href="admin.php?page=elberos-commerce-invoice&is_deleted=true"
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
		
		if ($action == 'view')
		{
			$this->display_view();
		}
		else
		{
			$this->display_table();
		}
	}
	
}

}