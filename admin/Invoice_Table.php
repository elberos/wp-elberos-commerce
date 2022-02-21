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


/* Check if Wordpress */
if (!defined('ABSPATH')) exit;


if ( !class_exists( Invoice_Table::class ) && class_exists( \Elberos\Table::class ) ) 
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
				$struct->table_fields =
				[
					"id",
					"status",
					"status_pay",
					"email",
					"client_id",
					"name",
					"price",
					"gmtime_add",
				];
				
				$struct->form_fields =
				[
					"status",
					"client_id",
					"type",
					"name",
					"surname",
					"user_identifier",
					"company_name",
					"company_bin",
					"email",
					"phone",
					"comment",
					"price",
					"price_pay",
					"status_pay",
					"gmtime_add",
					"gmtime_pay",
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
		$form_data = @json_decode($item["form_data"], true);
		$email = isset($form_data["email"]) ? $form_data["email"] : "";
		return esc_html($email);
	}
	
	
	
	/**
	 * Column buttons
	 */
	function column_name($item)
	{
		$form_data = @json_decode($item["form_data"], true);
		$name = isset($form_data["name"]) ? $form_data["name"] : "";
		$surname = isset($form_data["surname"]) ? $form_data["surname"] : "";
		$company_name = isset($form_data["company_name"]) ? $form_data["company_name"] : "";
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
			"invoice_id",
			"status",
			"status_pay",
			"client_email",
			"client_id",
		];
	}
	
	
	
	/**
	 * Show filter item
	 */
	function show_filter_item($item_name)
	{
		if ($item_name == "invoice_id")
		{
			?>
			<input type="text" name="invoice_id" class="web_form_value" placeholder="ID инвойса"
				value="<?= esc_attr( isset($_GET["invoice_id"]) ? $_GET["invoice_id"] : "" ) ?>">
			<?php
		}
		else if ($item_name == "client_id")
		{
			?>
			<input type="text" name="client_id" class="web_form_value" placeholder="ID клиента"
				value="<?= esc_attr( isset($_GET["client_id"]) ? $_GET["client_id"] : "" ) ?>">
			<?php
		}
		else if ($item_name == "client_email")
		{
			?>
			<input type="text" name="client_email" class="web_form_value" placeholder="E-mail клиента"
				value="<?= esc_attr( isset($_GET["client_email"]) ? $_GET["client_email"] : "" ) ?>">
			<?php
		}
		else if ($item_name == "status")
		{
			$field = $this->struct->getField("status");
			$options = isset($field["options"]) ? $field["options"] : [];
			//var_dump($options);
			//var_dump($_GET["status"]);
			?>
			<select name="status" class="web_form_value">
				<option value="">Выберите статус</option>
				<?php
					foreach ($options as $option)
					{
						$checked = \Elberos\is_get_selected("status", $option["id"]);
						echo '<option value="'.
							esc_attr($option['id']) . '"' . $checked . '>' .
							esc_html($option['value']) .
						'</option>';
					}
				?>
			</select>
			<?php
		}
		else if ($item_name == "status_pay")
		{
			$field = $this->struct->getField("status_pay");
			$options = isset($field["options"]) ? $field["options"] : [];
			?>
			<select name="status_pay" class="web_form_value">
				<option value="">Выберите статус оплаты</option>
				<?php
					foreach ($options as $option)
					{
						$checked = \Elberos\is_get_selected("status_pay", $option["id"]);
						echo '<option value="'.
							esc_attr($option['id']) . '"' . $checked . '>' .
							esc_html($option['value']) .
						'</option>';
					}
				?>
			</select>
			<?php
		}
		else
		{
			parent::show_filter_item($item_name);
		}
	}
	
	
	
	/**
	 * Process items params
	 */
	function prepare_table_items_filter($params)
	{
		global $wpdb;
		
		list($_,$params) = apply_filters("elberos_table_prepare_items_params_" . get_called_class(), [$this,$params]);
		
		/* invoice_id */
		if (isset($_GET["invoice_id"]))
		{
			$params["where"][] = "id = :invoice_id";
			$params["args"]["invoice_id"] = (int)$_GET["invoice_id"];
		}
		
		/* client_id */
		if (isset($_GET["client_id"]))
		{
			$params["where"][] = "client_id = :client_id";
			$params["args"]["client_id"] = (int)$_GET["client_id"];
		}
		
		/* client_email */
		if (isset($_GET["client_email"]))
		{
			$res = apply_filters
			(
				'elberos_commerce_find_client_by_email',
				[
					"client_id" => null,
					"client_email" => $_GET["client_email"],
				]
			);
			$client_id = $res["client_id"];
			if ($client_id > 0)
			{
				$params["where"][] = "client_id = :client_id";
				$params["args"]["client_id"] = (int)$client_id;
			}
		}
		
		/* status */
		if (isset($_GET["status"]))
		{
			$params["where"][] = "status = :status";
			$params["args"]["status"] = (int)$_GET["status"];
		}
		
		/* status_pay */
		if (isset($_GET["status_pay"]))
		{
			$params["where"][] = "status_pay = :status_pay";
			$params["args"]["status_pay"] = (int)$_GET["status_pay"];
		}
		
		return $params;
	}
	
	
	
	/**
	 * Prepare table items
	 */
	function prepare_table_items()
	{
		parent::prepare_table_items();
	}
	
	
	
	/**
	 * CSS
	 */
	function display_css()
	{
		parent::display_css();
		?>
		<style>
		.invoice_table{
			padding-top: 10px;
		}
		.invoice_table *{
			box-sizing: border-box;
		}
		.invoice_table_row {
			padding-top: 0px;
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
		.invoice_table_product_title_row{
			padding-bottom: 5px;
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
		$form_data = @json_decode($invoice["form_data"], true);
		
		echo "<div class='invoice_table'>";
		foreach ($this->struct->form_fields as $field_name)
		{
			$field = $this->struct->getField($field_name);
			$value = $this->struct->getColumnValue($this->form_item, $field_name);
			if (!$field) continue;
			if (!$value) continue;
			//var_dump($field);
			echo "<div class='invoice_table_row'>";
			echo "<div class='invoice_table_label'>" . esc_html( isset($field["label"]) ? $field["label"] : "" ) .
				":</div>";
			echo "<div class='invoice_table_content'>";
			echo esc_html( $value );
			echo "</div>";
			echo "</div>";
		}
		echo "</div>";
		
		
		echo "<div class='invoice_table_products'>";
		/* echo "<div class='invoice_table_products_header'>Товары</div>"; */
		
		echo "<table>";
		echo "<tr class='invoice_table_product_header'>";
		echo "<th>Наименование</th>";
		echo "<th>Цена за ед</th>";
		echo "<th>Ед. изм.</th>";
		echo "<th>Количество</th>";
		echo "<th>Скидка</th>";
		echo "<th>Сумма</th>";
		echo "</tr>";
		
		$basket_data = @json_decode($invoice["basket_data"], true);
		$basket_sum_total = 0;
		if (gettype($basket_data) == 'array') foreach ($basket_data as $basket)
		{
			$offer_unit = isset($basket["offer_unit"]) ? $basket["offer_unit"] : "";
			$offer_price_id = isset($basket["offer_price_id"]) ? $basket["offer_price_id"] : "";
			$offer_price = isset($basket["offer_price"]) ? $basket["offer_price"] : "";
			$product_name = isset($basket["product_name"]) ? $basket["product_name"] : "";
			$product_count = isset($basket["count"]) ? $basket["count"] : "";
			$product_main_photo_url = isset($basket["product_main_photo_url"]) ? $basket["product_main_photo_url"] : "";
			$product_vendor_code = isset($basket["product_vendor_code"]) ? $basket["product_vendor_code"] : "";
			$discount_value = isset($basket["discount_value"]) ? $basket["discount_value"] : "";
			
			$info_ammount = $offer_price * $product_count;
			if ($discount_value > 0 && $discount_value <= 100)
			{
				$info_ammount = $info_ammount * (1 - $discount_value / 100);
			}
			
			echo "<tr class='invoice_table_product_row'>";
			
			/* Title td */
			echo "<td class='invoice_table_product_title_td'>";
				
				$text = "";
				$text .= "<div class='invoice_table_product_image'>";
					$text .= "<img src='" . esc_attr( $product_main_photo_url ) . "' />";
				$text .= "</div>";
				$text .= "<div class='invoice_table_product_title'>";
					$text .= "<div class='invoice_table_product_title_row'>";
						$text .= esc_html( $product_name );
					$text .= "</div>";
					$text .= "<div class='invoice_table_product_title_row'>";
						$text .= "Артикул: <span class='value'>" . esc_html($product_vendor_code) . "</span>";
					$text .= "</div>";
					$text .= "<div class='invoice_table_product_title_row'>";
						$text .= "";
					$text .= "</div>";
				$text .= "</div>";
				
				$res = apply_filters
				(
					'elberos_commerce_admin_invoice_table_item_td',
					[
						"basket" => $basket,
						"text" => $text,
						"row" => "title_td",
					]
				);
				echo $res["text"];
				
			echo "</td>";
			
			/* product_price */
			echo "<td>";
				$text = "";
				$text .= "<div class='invoice_table_product_price'>";
					$text .= esc_html( \Elberos\formatMoney($offer_price) );
				$text .= "</div>";
				$res = apply_filters
				(
					'elberos_commerce_admin_invoice_table_item_td',
					[
						"basket" => $basket,
						"text" => $text,
						"row" => "product_price",
					]
				);
				echo $res["text"];
			echo "</td>";
			
			/* offer_unit */
			echo "<td>";
				$text = esc_html($offer_unit);
				$res = apply_filters
				(
					'elberos_commerce_admin_invoice_table_item_td',
					[
						"basket" => $basket,
						"text" => $text,
						"row" => "offer_unit",
					]
				);
				echo $res["text"];
			echo "</td>";
			
			/* product_count */
			echo "<td>";
				$text = esc_html($product_count);
				$res = apply_filters
				(
					'elberos_commerce_admin_invoice_table_item_td',
					[
						"basket" => $basket,
						"text" => $text,
						"row" => "product_count",
					]
				);
				echo $res["text"];
			echo "</td>";
			
			/* discount_value */
			echo "<td>";
				$text = esc_html($discount_value) . "%";
				$res = apply_filters
				(
					'elberos_commerce_admin_invoice_table_item_td',
					[
						"basket" => $basket,
						"text" => $text,
						"row" => "discount_value",
					]
				);
				echo $res["text"];
			echo "</td>";
			
			/* product_price */
			echo "<td>";
				$text = "";
				$text .= "<div class='invoice_table_product_price'>";
					$text .= esc_html( \Elberos\formatMoney($info_ammount) );
				$text .= "</div>";
				$res = apply_filters
				(
					'elberos_commerce_admin_invoice_table_item_td',
					[
						"basket" => $basket,
						"text" => $text,
						"row" => "product_price",
					]
				);
				echo $res["text"];
			echo "</td>";
			
			echo "</tr>";
			
			$basket_sum_total += $info_ammount;
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