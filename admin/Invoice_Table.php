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

if ( !class_exists( Invoice_Table::class ) ) 
{

class Invoice_Table extends \Elberos\Table 
{
	
	/**
	 * Table name
	 */
	function get_table_name()
	{
		global $wpdb;
		return $wpdb->base_prefix . 'elberos_commerce_invoice';
	}
	
	
	
	/**
	 * Page name
	 */
	function get_page_name()
	{
		return "elberos-commerce-invoice";
	}
	
	
	
	/**
	 * Create struct
	 */
	static function createStruct()
	{
		$struct = \Elberos\Commerce\Invoice::create
		(
			"admin_table",
			function ($struct)
			{
				
				$struct
					->addField
					([
						"api_name" => "email",
						"label" => "Email",
						"type" => "input",
						"virtual" => true,
						"column_value" => function ($struct, $item)
						{
							$client_data = @json_decode($item["client_data"], true);
							return $client_data["email"];
						}
					])
					->addField
					([
						"api_name" => "name",
						"label" => "Имя",
						"type" => "input",
						"virtual" => true,
						"column_value" => function ($struct, $item)
						{
							$client_data = @json_decode($item["client_data"], true);
							return $client_data["name"];
						}
					])
				;
				
				$struct->table_fields =
				[
					"id",
					"email",
					"name",
					"price",
					"gmtime_add",
				];
				
				$struct->form_fields =
				[
					"email",
					"name",
					"price",
					"gmtime_add",
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
			'<a href="?page=' . $page_name . '&action=edit&id=%s">%s</a>',
			$item['id'], 
			__('Открыть', 'elberos-commerce')
		);
	}
	
	
	
	/**
	 * Column buttons
	 */
	function column_email($item)
	{
		$send_data = @json_decode($item["send_data"], true);
		$email = isset($send_data["email"]) ? $send_data["email"] : "";
		return esc_html($email);
	}
	
	
	
	/**
	 * Column buttons
	 */
	function column_name($item)
	{
		$send_data = @json_decode($item["send_data"], true);
		$name = isset($send_data["name"]) ? $send_data["name"] : "";
		$surname = isset($send_data["surname"]) ? $send_data["surname"] : "";
		$company_name = isset($send_data["company_name"]) ? $send_data["company_name"] : "";
		return esc_html($name . " " . $surname . " " . $company_name);
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
			$this->do_get_item();
			/* parent::process_bulk_action(); */
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
		?>
		<style>
		.invoice_table *{
			box-sizing: border-box;
		}
		.invoice_table_row {
			padding-top: 10px;
			padding-bottom: 10px;
		}
		.invoice_table_label{
			display: inline-block;
			vertical-align: top;
			font-weight: bold;
			text-align: right;
			padding-right: 10px;
			width: 150px;
		}
		.invoice_table_content{
			display: inline-block;
			vertical-align: top;
			width: calc(100% - 155px);
			padding-left: 10px;
		}
		.invoice_table_products_header{
			padding-top: 20px;
			padding-bottom: 20px;
			text-align: center;
			font-weight: bold;
			font-size: 20px;
		}
		.invoice_table_products table{
			width: 100%;
		}
		.invoice_table_product_row td{
			padding: 10px;
			text-align: center;
		}
		td.invoice_table_product_title_td{
			text-align: left;
		}
		.invoice_table_product_image, .invoice_table_product_title {
			display: inline-block;
			vertical-align: middle;
		}
		.invoice_table_product_image img {
			height: 100px;
		}
		.invoice_table_product_title{
			padding-left: 10px;
		}
		.invoice_table_product_row_total td {
			padding: 10px;
		}
		.invoice_table_product_row_total_2 {
			text-align: right;
			padding-right: 20px;
		}
		.invoice_table_product_row_total_3 {
			text-align: center;
		}
		</style>
		<?php
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
		
		<div style="clear: both;"></div>
		
		<?php
	}
	
	
	
	/**
	 * Returns form title
	 */
	function get_form_title($item)
	{
		return _e($item['id'] > 0 ? 'Инвойс ' . $item['id'] : 'Инвойс', 'elberos-commerce');
	}
	
	
	
	/**
	 * Display form content
	 */
	function display_form_content()
	{
		if ($this->form_item == null)
		{
			return;
		}
		$invoice = $this->form_item;
		$client_data = @json_decode($invoice["client_data"], true);
		
		echo "<div class='invoice_table'>";
		foreach ($this->struct->form_fields as $field_name)
		{
			$field = $this->struct->getField($field_name);
			if (!$field) continue;
			echo "<div class='invoice_table_row'>";
			echo "<div class='invoice_table_label'>" . esc_html( isset($field["label"]) ? $field["label"] : "" ) .
				":</div>";
			echo "<div class='invoice_table_content'>";
			echo esc_html( $this->struct->getColumnValue($this->form_item, $field_name) );
			echo "</div>";
			echo "</div>";
		}
		echo "</div>";
		
		
		echo "<div class='invoice_table_products'>";
		/* echo "<div class='invoice_table_products_header'>Товары</div>"; */
		
		echo "<table>";
		echo "<tr class='invoice_table_product_header'>";
		echo "<th>Наименование</th>";
		echo "<th>Цена</th>";
		echo "<th>Ед. изм.</th>";
		echo "<th>Количество</th>";
		echo "<th>Сумма</th>";
		echo "</tr>";
		
		$basket_data = @json_decode($invoice["basket_data"], true);
		$basket_sum_total = 0;
		if (gettype($basket_data) == 'array') foreach ($basket_data["items"] as $basket)
		{
			$offer_price_id = $basket["offer_price_id"];
			$offer_item = \Elberos\find_item($basket_data["offers"], "offer_price_id", $offer_price_id);
			if (!$offer_item) continue;
			
			$product_item = \Elberos\find_item($basket_data["products"]["items"], "id", $offer_item["product_id"]);
			$product_main_photo = \Elberos\Commerce\Api::getMainPhoto($product_item, $basket_data["products"]["photos"]);
			$product_main_photo_id = isset($product_main_photo["id"]) ? $product_main_photo["id"] : "";
			$product_main_photo_url = isset($product_main_photo["url"]) ? $product_main_photo["url"] : "";
			
			$product_name = isset($product_item["name"]) ? $product_item["name"] : "";
			$product_price = (int) isset($offer_item["price"]) ? $offer_item["price"] : 0;
			$product_count = $basket["count"];
			
			echo "<tr class='invoice_table_product_row'>";
			
			echo "<td class='invoice_table_product_title_td'>";
				echo "<div class='invoice_table_product_image'>";
					echo "<img src='" . esc_attr( $product_main_photo_url ) . "' />";
				echo "</div>";
				echo "<div class='invoice_table_product_title'>";
					echo "<div class='invoice_table_product_title_row'>";
						echo esc_html( $product_name );
					echo "</div>";
					echo "<div class='invoice_table_product_title_row'>";
						echo "Артикул: <span class='value'>" . esc_html($product_item["vendor_code"]) . "</span>";
					echo "</div>";
					echo "<div class='invoice_table_product_title_row'>";
						echo "";
					echo "</div>";
				echo "</div>";
			echo "</td>";
			echo "<td>";
				echo "<div class='invoice_table_product_price'>";
					echo esc_html( \Elberos\formatMoney($product_price) );
				echo "</div>";
			echo "</td>";
			echo "<td>";
				echo esc_html($offer_item["unit"]);
			echo "</td>";
			echo "<td>";
				echo esc_html($product_count);
			echo "</td>";
			echo "<td>";
				echo "<div class='invoice_table_product_price'>";
					echo esc_html( \Elberos\formatMoney($product_price * $product_count) );
				echo "</div>";
			echo "</td>";
			
			echo "</tr>";
			
			$basket_sum_total += $product_price * $product_count;
		}
		
		echo "<tr class='invoice_table_product_row_total'>";
			echo "<td class='invoice_table_product_row_total_2' colspan='4'>";
				echo "Итого:";
			echo "</td>";
			echo "<td class='invoice_table_product_row_total_3' colspan='2'>";
				echo "<span class='invoice_table_product_row_total_value'>";
					echo esc_html( \Elberos\formatMoney($basket_sum_total) );
				echo "</span>";
			echo "</td>";
		echo "</tr>";
		
		echo "</table>";
		
		echo "</div>";
	}
	
	
	
	/**
	 * Display form buttons
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
		parent::display_action();
	}
	
	
}

}