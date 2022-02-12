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

if ( !class_exists( Product_Table::class ) && class_exists( \Elberos\Table::class ) ) 
{

class Product_Table extends \Elberos\Table 
{
	
	var $items_id = [];
	var $items_categories = [];
	
	
	/**
	 * Table name
	 */
	function get_table_name()
	{
		global $wpdb;
		return $wpdb->base_prefix . 'elberos_commerce_products';
	}
	
	
	
	/**
	 * Page name
	 */
	function get_page_name()
	{
		return "elberos-commerce";
	}
	
	
	
	/**
	 * Create struct
	 */
	static function createStruct()
	{
		$struct = \Elberos\Commerce\Product::create
		(
			"admin_table",
			function ($struct)
			{
				global $wpdb;
				
				$struct->addField([
					"api_name" => "main_photo_id",
					"label" => "Фото",
				]);
				
				$struct->table_fields =
				[
					"id",
					"catalog_id",
					"category_id",
					"main_photo_id",
					"vendor_code",
					"name",
					"code_1c",
				];
				
				$struct->form_fields =
				[
					"catalog_id",
					"show_in_catalog",
					"show_in_top",
					"vendor_code",
					"name",
					"code_1c",
				];
				
				/* Запрос каталога */
				$sql = \Elberos\wpdb_prepare
				(
					"select * from " . $wpdb->base_prefix . "elberos_commerce_catalogs",
					[]
				);
				$catalogs = $wpdb->get_results($sql, ARRAY_A);
				$catalog_options = array_map
				(
					function ($item)
					{
						return
						[
							"id" => $item["id"],
							"value" => $item["name"],
							"code_1c" => $item["code_1c"],
						];
					},
					$catalogs
				);
				$struct->editField
				(
					"catalog_id",
					[
						"options" => $catalog_options
					]
				);
				
				/* Запрос категорий */
				$sql = \Elberos\wpdb_prepare
				(
					"select * from " . $wpdb->base_prefix . "elberos_commerce_categories " .
					"where is_deleted = 0 order by name asc",
					[]
				);
				$categories = $wpdb->get_results($sql, ARRAY_A);
				$categories_options = array_map
				(
					function ($item)
					{
						return
						[
							"id" => $item["id"],
							"value" => $item["name"],
							"code_1c" => $item["code_1c"],
						];
					},
					$categories
				);
				$struct->addField
				(
					[
						"api_name" => "category_id",
						"label" => "Категория",
						"options" => $categories_options,
						"virtual" => true,
					]
				);
				
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
	 * Column category
	 */
	function column_category_id($item)
	{
		$product_id = isset($item["id"]) ? $item["id"] : "";
		
		/* Фильтруем категории по product_id */
		$categories = array_filter
		(
			$this->items_categories,
			function ($cat) use ($product_id)
			{
				return $cat["product_id"] == $product_id;
			}
		);
		
		/* Оставляем только имя */
		$categories = array_map
		(
			function ($cat)
			{
				return $cat["name"];
			},
			$categories
		);
		
		return implode("<br/>", $categories);
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
	 * Column main photo id
	 */
	function column_main_photo_id($item)
	{
		$main_photo_id = $item["main_photo_id"];
		$href = \Elberos\get_image_url($main_photo_id, "thumbnail");
		return "<img class='product_table_main_photo_id' src='" . esc_attr($href) . "' />";
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
	 * Get item query
	 */
	function do_get_item_query($item_id)
	{
		global $wpdb;
		$table_name = $this->get_table_name();
		$table_name_catalog = $wpdb->base_prefix . "elberos_commerce_catalogs";
		$sql = $wpdb->prepare
		(
			"SELECT t.*, catalog.classifier_id FROM $table_name as t " .
			"LEFT JOIN $table_name_catalog as catalog on (catalog.id = t.catalog_id) " .
			"WHERE t.id = %d limit 1",
			$item_id
		);
		return $wpdb->get_row($sql, ARRAY_A);
	}
	
	
	
	/**
	 * Process item
	 */
	function process_item($item, $old_item)
	{
		$product_text = stripslashes_deep(isset($_POST['product_text']) ? $_POST['product_text'] : []);
		$item["text"] = json_encode($product_text);
		return $item;
	}
	
	
	
	/**
	 * Process item after
	 */
	function process_item_after($item, $old_item, $action, $success)
	{
		global $wpdb;
		
		/* Добавление категорий */
		if ($success)
		{
			$table_name_categories = $wpdb->base_prefix . "elberos_commerce_products_categories";
			$sql = \Elberos\wpdb_prepare
			(
				"delete from $table_name_categories where product_id=:product_id",
				[
					"product_id" => $item["id"],
				]
			);
			$wpdb->query($sql);
			
			$product_category = isset($_POST['product_category']) ? $_POST['product_category'] : [];
			if (gettype($product_category) == 'array')
			{
				foreach ($product_category as $category)
				{
					$wpdb->insert
					(
						$table_name_categories,
						[
							"product_id" => $item["id"],
							"category_id" => $category,
						]
					);
				}
			}
		}
		
		/* Добавление фото */
		if ($success)
		{
			$table_name_products_photos = $wpdb->base_prefix . "elberos_commerce_products_photos";
			$sql = \Elberos\wpdb_prepare
			(
				"delete from $table_name_products_photos where product_id=:product_id",
				[
					"product_id" => $item["id"],
				]
			);
			$wpdb->query($sql);
			
			$pos = 0;
			$product_photo = isset($_POST['product_photo']) ? $_POST['product_photo'] : [];
			if (gettype($product_photo) == 'array')
			{
				foreach ($product_photo as $photo)
				{
					$wpdb->insert
					(
						$table_name_products_photos,
						[
							"product_id" => $item["id"],
							"photo_id" => $photo["id"],
							"pos" => $pos,
						]
					);
					$pos++;
				}
			}
		}
		
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
			"catalog_id",
			"category_id",
			"vendor_code",
			"name",
			"product_id",
			"code_1c",
		];
	}
	
	
	
	/**
	 * JQ filter sub
	 */
	function jq_filter_sub()
	{
		return
		[
			"is_deleted",
			"order",
			"orderby",
			"show_in_catalog",
			"show_in_top",
		];
	}
	
	
	
	/**
	 * Show filter item
	 */
	function show_filter_item($item_name)
	{
		if ($item_name == "catalog_id")
		{
			$catalog_field = $this->struct->getField("catalog_id");
			$catalog_options = isset($catalog_field["options"]) ? $catalog_field["options"] : [];
			?>
			<select name="catalog_id" class="web_form_value">
				<option value="">Выберите каталог</option>
				<?php
					foreach ($catalog_options as $option)
					{
						$checked = \Elberos\is_get_selected("catalog_id", $option["id"]);
						echo '<option value="'.
							esc_attr($option['id']) . '"' . $checked . '>' .
							esc_html($option['value']) .
						'</option>';
					}
				?>
			</select>
			<?php
		}
		else if ($item_name == "category_id")
		{
			$category_field = $this->struct->getField("category_id");
			$category_options = isset($category_field["options"]) ? $category_field["options"] : [];
			?>
			<select name="category_id" class="web_form_value">
				<option value="">Выберите категорию</option>
				<?php
					foreach ($category_options as $option)
					{
						$checked = \Elberos\is_get_selected("category_id", $option["id"]);
						echo '<option value="'.
							esc_attr($option['id']) . '"' . $checked . '>' .
							esc_html($option['value']) .
						'</option>';
					}
				?>
			</select>
			<?php
		}
		else if ($item_name == "vendor_code")
		{
			?>
			<input type="text" name="vendor_code" class="web_form_value" placeholder="Артикул"
				value="<?= esc_attr( isset($_GET["vendor_code"]) ? $_GET["vendor_code"] : "" ) ?>">
			<?php
		}
		else if ($item_name == "name")
		{
			?>
			<input type="text" name="name" class="web_form_value" placeholder="Название товара"
				value="<?= esc_attr( isset($_GET["name"]) ? $_GET["name"] : "" ) ?>">
			<?php
		}
		else if ($item_name == "product_id")
		{
			?>
			<input type="text" name="product_id" class="web_form_value" placeholder="ID товара"
				value="<?= esc_attr( isset($_GET["product_id"]) ? $_GET["product_id"] : "" ) ?>">
			<?php
		}
		else if ($item_name == "code_1c")
		{
			?>
			<input type="text" name="code_1c" class="web_form_value" placeholder="Код 1С"
				value="<?= esc_attr( isset($_GET["code_1c"]) ? $_GET["code_1c"] : "" ) ?>">
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
		
		$params = parent::prepare_table_items_filter($params);
		
		/* Catalog id */
		if (isset($_GET["catalog_id"]))
		{
			$params["where"][] = "catalog_id=:catalog_id";
			$params["args"]["catalog_id"] = (int)$_GET["catalog_id"];
		}
		
		/* Category id */
		if (isset($_GET["category_id"]))
		{
			$products_categories_table = $wpdb->base_prefix .
				"elberos_commerce_products_categories as products_categories";
			$params["join"][] = "inner join " . $products_categories_table .
				" on (t.id = products_categories.product_id) ";
			$params["where"][] = "products_categories.category_id = :current_category_id";
			$params["args"]["current_category_id"] = (int)$_GET["category_id"];
		}
		
		/* Vendor code */
		if (isset($_GET["vendor_code"]))
		{
			$params["where"][] = "vendor_code like :vendor_code";
			//$params["args"]["vendor_code"] = $_GET["vendor_code"];
			$params["args"]["vendor_code"] = "%" . $wpdb->esc_like(\Elberos\mb_trim($_GET["vendor_code"])) . "%";
		}
		
		/* Name */
		if (isset($_GET["name"]))
		{
			$params["where"][] = "name like :name";
			$params["args"]["name"] = "%" . $wpdb->esc_like(\Elberos\mb_trim($_GET["name"])) . "%";
		}
		
		/* code 1c */
		if (isset($_GET["code_1c"]))
		{
			$params["where"][] = "code_1c like :code_1c";
			//$params["args"]["code_1c"] = $_GET["code_1c"];
			$params["args"]["code_1c"] = "%" . $wpdb->esc_like(\Elberos\mb_trim($_GET["code_1c"])) . "%";
		}
		
		/* code 1c */
		if (isset($_GET["product_id"]))
		{
			$params["where"][] = "id = :product_id";
			$params["args"]["product_id"] = (int)$_GET["product_id"];
		}
		
		/* Show in catalog */
		if (isset($_GET["show_in_catalog"]) && $_GET["show_in_catalog"] == "true")
		{
			$params["where"][] = "show_in_catalog=1";
			$params["where"][] = "is_deleted=0";
		}
		
		/* Show in top */
		if (isset($_GET["show_in_top"]) && $_GET["show_in_top"] == "true")
		{
			$params["where"][] = "show_in_top=1";
			$params["where"][] = "is_deleted=0";
		}
		
		return $params;
	}
	
	
	
	/**
	 * Prepare table items
	 */
	function prepare_table_items()
	{
		global $wpdb;
		
		parent::prepare_table_items();
		
		/* Items id */
		$this->items_id = array_map
		(
			function($item)
			{
				return $item["id"];
			},
			$this->items
		);
		
		/* Список категорий у товара */
		if (count($this->items_id) > 0)
		{
			$sql = $wpdb->prepare
			(
				"SELECT t.product_id, c.* FROM {$wpdb->base_prefix}elberos_commerce_products_categories as t
				inner join {$wpdb->base_prefix}elberos_commerce_categories as c
				  on (c.id = t.category_id)
				WHERE t.product_id in (" . implode(",", array_fill(0, count($this->items_id), "%d")) . ") ",
				$this->items_id
			);
			$this->items_categories = $wpdb->get_results($sql, ARRAY_A);
		}
	}
	
	
	
	/**
	 * CSS
	 */
	function display_css()
	{
		parent::display_css();
		wp_enqueue_media();
		wp_enqueue_script( 'script.js',
			'/wp-content/plugins/wp-elberos-core/assets/script.js', false );
		wp_enqueue_script( 'vue',
			'/wp-content/plugins/wp-elberos-core/assets/vue.min.js', false );
		?>
		<style>
		.elberos-commerce td.main_photo_id{
			/* text-align: center; */
		}
		.elberos-commerce .product_table_main_photo_id{
			height: 100px;
		}
		.elberos-commerce .cursor, .elberos-commerce a.cursor
		{
			cursor: pointer;
		}
		.elberos-commerce .nav-tab-wrapper
		{
			padding-top: 0;
		}
		.elberos-commerce .nav-tab-data
		{
			display: none;
		}
		.elberos-commerce .nav-tab-data.nav-tab-data-active
		{
			display: block;
		}
		.elberos-commerce .product_category
		{
			font-size: 0;
			padding-bottom: 5px;
		}
		.elberos-commerce .product_category_name, .elberos-commerce .product_category_buttons
		{
			display: inline-block;
			vertical-align: top;
			font-size: 14px;
		}
		.elberos-commerce .product_category_name
		{
			width: calc(100% - 65px);
		}
		.elberos-commerce .product_category_buttons
		{
			width: 60px;
		}
		.elberos-commerce .product_category_buttons button
		{
			cursor: pointer;
		}
		.elberos-commerce .product_param
		{
			position: relative;
			font-size: 0;
			padding-bottom: 5px;
		}
		.elberos-commerce .product_param_name,
		.elberos-commerce .product_param_value,
		.elberos-commerce .product_param_buttons
		{
			position: relative;
			display: inline-block;
			vertical-align: middle;
			font-size: 14px;
		}
		.elberos-commerce .product_param_name
		{
			width: 100px;
			text-align: right;
			padding-bottom: 5px;
		}
		.elberos-commerce .product_param_value
		{
			width: calc(100% - 170px);
			font-size: 14px;
			padding-left: 5px;
			padding-right: 5px;
		}
		.elberos-commerce .product_param_value input,
		.elberos-commerce .product_param_value select
		{
			width: 100%;
			max-width: 100%;
		}
		.elberos-commerce .product_param_buttons
		{
			width: 60px;
		}
		.elberos-commerce .product_param_buttons button
		{
			cursor: pointer;
		}
		.elberos-commerce-photos{
			padding-top: 20px;
		}
		.elberos-commerce .product_photos
		{
			font-size: 0;
		}
		.elberos-commerce .product_photo
		{
			position: relative;
			display: inline-block;
			vertical-align: top;
			margin: 5px;
		}
		.elberos-commerce .product_photo .button-delete
		{
			position: absolute;
			cursor: pointer;
			top: 0px;
			right: 0px;
			font-size: 30px;
			width: 30px;
			height: 30px;
			background-color: white;
			color: red;
			border: 1px #ccc solid;
			border-radius: 2px;
		}
		.add_or_edit_form, .add_or_edit_form_right{
			display: inline-block;
			vertical-align: top;
			margin-top: 20px;
		}
		.add_or_edit_form_right{
			width: calc(40% - 25px);
			margin-left: 20px;
		}
		.elberos-commerce-product-params{
			margin-bottom: 20px;
		}
		.elberos-commerce-product-params label{
			display: inline-block;
			font-weight: bold;
			margin-bottom: 10px;
		}
		.elberos-commerce-product-params-item{
			margin-bottom: 5px;
		}
		.elberos-commerce-product-params-item-key, .elberos-commerce-product-params-item-value{
			display: inline-block;
			vertical-align: top;
		}
		.elberos-commerce-product-params-item-key{
			width: 200px;
		}
		.elberos-commerce-product-params-item-value{
			width: calc(100% - 210px);
		}
		.elberos-commerce-product-offers{
			margin-bottom: 20px;
		}
		.elberos-commerce-product-offers label{
			display: inline-block;
			font-weight: bold;
			margin-bottom: 10px;
		}
		.elberos-commerce-product-offers th, .elberos-commerce-product-offers td{
			padding: 5px;
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
		$page_name = $this->get_page_name();
		$is_deleted = isset($_GET['is_deleted']) ? $_GET['is_deleted'] : "";
		$show_in_catalog = isset($_GET['show_in_catalog']) ? $_GET['show_in_catalog'] : "";
		$show_in_top = isset($_GET['show_in_top']) ? $_GET['show_in_top'] : "";
		$url = "admin.php?page=" . $page_name;
		?>
		<ul class="subsubsub">
			<li>
				<a href="admin.php?page=<?= $page_name ?>"
					class="<?= (($show_in_catalog != "true" && $show_in_top != "true" && $is_deleted != "true") ? "current" : "")?>"  >Все товары</a> |
			</li>
			<li>
				<a href="admin.php?page=<?= $page_name ?>&show_in_catalog=true"
					class="<?= ($show_in_catalog == "true" ? "current" : "")?>" >Размещено на сайте</a> |
			</li>
			<li>
				<a href="admin.php?page=<?= $page_name ?>&show_in_top=true"
					class="<?= ($show_in_top == "true" ? "current" : "")?>" >Размещено на главной</a> |
			</li>
			<li>
				<a href="admin.php?page=<?= $page_name ?>&is_deleted=true"
					class="<?= ($is_deleted == "true" ? "current" : "")?>" >На удалении</a>
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
	
	
	function display_add_or_edit()
	{
		parent::display_add_or_edit();
		$this->show_offers();
		$this->show_params();
		$this->show_relative();
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
		echo '<div class="add_or_edit_form">';
		$this->display_form();
		echo '</div>';
		echo '<div class="add_or_edit_form_right">';
		$this->show_categories();
		$this->show_photos();
		echo '</div>';
		echo '<div class="clear"></div>';
	}
	
	
	
	/**
	 * Returns form title
	 */
	function get_form_title($item)
	{
		return _e($item['id'] > 0 ? 'Редактировать товар' : 'Добавить товар', 'elberos-commerce');
	}
	
	
	
	/**
	 * Display form
	 */
	function display_form()
	{
		parent::display_form();
		$this->show_products_title();
	}
	
	
	
	/**
	 * Display action
	 */
	function display_action()
	{
		$action = $this->current_action();
		parent::display_action();
	}
	
	
	
	/**
	 * Products title
	 */
	public function show_products_title()
	{
		// Get langs
		$langs = \Elberos\wp_langs();
		
		// Get products text
		$product_text = json_decode($this->form_item["text"], true);
		
		?>
		<div class="elberos-commerce products_text">
			<nav class="nav-tab-wrapper">
				<?php
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
			
			<?php
			foreach ($langs as $key => $lang)
			{
				$locale = $lang['locale'];
				$item_text = isset($product_text[$locale]) ? $product_text[$locale] : null;
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
								<?php _e('Название', 'elberos-commerce')?> (<?= esc_attr($lang['name']) ?>):
							</label>
						<br>
							<input id="name[<?= esc_attr($lang['locale']) ?>]" 
								name="product_text[<?= esc_attr($lang['locale']) ?>][name]"
								type="text" style="width: 100%"
								value="<?php echo esc_attr($text_name)?>" >
						</p>
						
						<p>
							<label for="description[<?= esc_attr($lang['locale']) ?>]">
								<?php _e('Описание', 'elberos-commerce')?> (<?= esc_attr($lang['name']) ?>):
							</label>
						<br>
							<textarea id="description[<?= esc_attr($lang['locale']) ?>]"
								name="product_text[<?= esc_attr($lang['locale']) ?>][description]"
								type="text" style="width: 100%; height: 300px;"><?= esc_html($text_description) ?></textarea>
						</p>
						
					</div>
				</p>
				
				<?php
			}
			
			?>
			<script>
			jQuery('.products_text .nav-tab').click(function(){
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
		</div>
		<?php
	}
	
	
	
	/**
	 * Categories
	 */
	public function show_categories()
	{
		global $wpdb;
		
		$product_category = [];
		
		/* Список категорий у товара */
		$sql = \Elberos\wpdb_prepare
		(
			"SELECT c.* FROM {$wpdb->base_prefix}elberos_commerce_products_categories as t
			inner join {$wpdb->base_prefix}elberos_commerce_categories as c
			  on (c.id = t.category_id)
			WHERE t.product_id=:product_id",
			[
				"product_id" => $this->form_item_id
			]
		);
		$product_category = $wpdb->get_results($sql, ARRAY_A);
		
		/* Список категорий */
		$sql = \Elberos\wpdb_prepare
		(
			"SELECT * FROM {$wpdb->base_prefix}elberos_commerce_categories WHERE classifier_id=:classifier_id",
			[
				"classifier_id" => $this->form_item["classifier_id"]
			]
		);
		$categories = $wpdb->get_results($sql, ARRAY_A);
		
		?>
		
		<div class='elberos-commerce elberos-commerce-categories' >
			
			<div class='web_form_label'>Категории товара</div>
			
			<div class='product_categories' style='padding-top: 10px;'>
				<?php 
					if (gettype($product_category) == 'array') foreach ($product_category as $category)
					{
						$find_category = null;
						$cat_id = $category['id'];
						foreach ($categories as $cat)
						{
							if ($cat['id'] == $cat_id)
							{
								$find_category = $cat;
							}
						}
						if ($find_category)
						{
						?>
						
						<div class='product_category' data-id='<?= esc_attr($find_category['id']) ?>'>
							<div class='product_category_name'><?= esc_html($find_category['name']) ?></div>
							<div class='product_category_buttons'>
								<button data-id='<?= esc_attr($find_category['id']) ?>' type='button'>
									Delete
								</button>
							</div>
							<input type='hidden' name='product_category[<?= esc_attr($find_category['id']) ?>]'
								value='<?= esc_attr($find_category['id']) ?>'>
						</div>
						
						<?php 
						}
					}
				?>
			</div>
			
			<select class='product_select_category' style='width: 100%'>
				<option value=''>Выберите категорию</option>
				<?php foreach ($categories as $cat) { ?>
					<option value="<?= esc_attr($cat['id']) ?>"><?= esc_html($cat['name']) ?></option>
				<?php } ?>
			</select>
			
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
							.attr('name', 'product_category[' + value + ']')
							.attr('value', value)
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
	
	
	
	/**
	 * Photos
	 */
	public function show_photos()
	{
		global $wpdb;
		
		$product_photo = [];
		
		/* Список фото у товара */
		$sql = \Elberos\wpdb_prepare
		(
			"SELECT p.* FROM {$wpdb->base_prefix}elberos_commerce_products_photos as t
			inner join {$wpdb->base_prefix}posts as p
			  on (p.ID = t.photo_id)
			WHERE t.product_id=:product_id
			order by pos asc",
			[
				"product_id" => $this->form_item_id
			]
		);
		$product_photo = $wpdb->get_results($sql, ARRAY_A);
		
		?>
		<div class='elberos-commerce elberos-commerce-photos'>
			<input type='button' class='button add-photo-button' value='Добавить фото'>
			
			<div class='product_photos'>
			<?php
			if (gettype($product_photo) == 'array') foreach ($product_photo as $photo)
			{
				if (!isset($photo['ID'])) continue;
				$href = \Elberos\get_image_url($photo['ID'], "thumbnail");
				?>
				<div class='product_photo' data-id='<?= esc_attr($photo["ID"]) ?>'>
					<img src='<?= esc_attr($href) ?>' />
					<span class="dashicons dashicons-no-alt button-delete" data-id='<?= esc_attr($photo["ID"]) ?>'></span>
					<input type='hidden' name='product_photo[<?= esc_attr($photo["ID"]) ?>][id]'
						value='<?= esc_attr($photo["ID"]) ?>' />
				</div>
				<?php
			}
			?>
			</div>
			
			<script>
				jQuery(document).on('click', '.product_photos .button-delete', '', function(){
					var data_id = jQuery(this).attr('data-id');
					var $items = jQuery('.product_photo');
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
				
				jQuery('.add-photo-button').click(function(){
					var uploader = wp.media
					({
						title: "Фотографии",
						button: {
							text: "Выбрать фото"
						},
						multiple: true
					})
					.on('select', function() {
						var attachments = uploader.state().get('selection').toJSON();
						
						for (var i=0; i<attachments.length; i++)
						{
							var photo = attachments[i];
							var photo_time = photo.date;
							if (photo_time.getTime != undefined) photo_time = photo_time.getTime();
							
							var div = jQuery(document.createElement('div'))
							.addClass('product_photo')
							.attr('data-id', photo.id)
							.append
							(
								jQuery(document.createElement('img'))
								.attr('src', photo.sizes.thumbnail.url + "?_=" + photo_time)
							)
							.append
							(
								jQuery(document.createElement('span'))
								.attr('class', 'dashicons dashicons-no-alt button-delete')
								.attr('data-id', photo.id)
							)
							.append
							(
								jQuery(document.createElement('input'))
								.attr('type', 'hidden')
								.attr('name', 'product_photo[' + photo.id + '][id]')
								.attr('value', photo.id)
							)
							jQuery('.product_photos').append(div);
						}
					})
					.open();
				});
			</script>
		</div>
		<?php
	}
	
	
	
	/**
	 * Params
	 */
	public function show_params()
	{
		global $wpdb;
		
		$product_photo = [];
		
		/* Список параметров у товара */
		$sql = \Elberos\wpdb_prepare
		(
			"SELECT t.* FROM {$wpdb->base_prefix}elberos_commerce_products_params as t
			WHERE t.product_id=:product_id
			order by id asc",
			[
				"product_id" => $this->form_item_id
			]
		);
		$product_params = $wpdb->get_results($sql, ARRAY_A);
		
		?>
		<div class='elberos-commerce elberos-commerce-product-params'>
			<label>Параметры товара</label>
			<?php foreach ($product_params as $params) { ?>
			<div class='elberos-commerce-product-params-item'>
				<div class='elberos-commerce-product-params-item-key'><?= esc_html($params['key']) ?></div>
				<input class='elberos-commerce-product-params-item-value' value='<?= esc_attr($params['value']) ?>' 
					readonly type='text' />
			</div>
			<?php } ?>
		</div>
		<?php
	}
	
	
	
	/**
	 * Offers
	 */
	public function show_offers()
	{
		global $wpdb;
		
		$product_offers = [];
		
		/* Список оферов у товара */
		$sql = \Elberos\wpdb_prepare
		(
			"SELECT t1.*, t2.price_type_id, t2.price_type_code_1c,
				t2.name, t2.price, t2.currency, t2.coefficient, t2.unit,
				t3.name as price_type_name
			FROM {$wpdb->base_prefix}elberos_commerce_products_offers as t1
			INNER JOIN {$wpdb->base_prefix}elberos_commerce_products_offers_prices as t2
				on (t1.id = t2.offer_id)
			LEFT JOIN {$wpdb->base_prefix}elberos_commerce_price_types as t3
				on (t3.id = t2.price_type_id)
			WHERE t1.product_id=:product_id
			order by id asc",
			[
				"product_id" => $this->form_item_id
			]
		);
		$product_offers = $wpdb->get_results($sql, ARRAY_A);
		
		?>
		<div class='elberos-commerce elberos-commerce-product-offers'>
			<label>Предложения товара</label>
			<table>
				<tr class='elberos-commerce-product-offers-head'>
					<th>Тип цены</th>
					<th>Цена</th>
					<th>Валюта</th>
					<th>Ед. изм.</th>
					<th></th>
				</tr>
			<?php foreach ($product_offers as $offer) { ?>
				<tr class='elberos-commerce-product-offers-item'>
					<td> <?= esc_html($offer['price_type_name']) ?></td>
					<td> <?= esc_html($offer['price']) ?></td>
					<td> <?= esc_html($offer['currency']) ?></td>
					<td> <?= esc_html($offer['unit']) ?></td>
					<td> <?= esc_html($offer['name']) ?></td>
				</tr>
			<?php } ?>
			</table>
		</div>
		<?php
	}
	
	
	public function show_relative()
	{
		global $wpdb;
		
		$products= [];
		
		/* Список оферов у товара */
		$sql = \Elberos\wpdb_prepare
		(
			"select products.* from {$wpdb->base_prefix}elberos_commerce_products_relative as t " .
			"inner join {$wpdb->base_prefix}elberos_commerce_products as products " .
				"on (t.relative_id = products.id)" .
			"where t.product_id=:product_id order by pos desc",
			[
				"product_id" => $this->form_item_id
			]
		);
		$products = $wpdb->get_results($sql, ARRAY_A);
		
		?>
		<style>
		.product_related_tab .product_related_tab_products th,
		.product_related_tab .product_related_tab_products td{
			padding: 10px;
			text-align: center;
		}
		.product_related_tab .product_related_tab_products img{
			height: 100px;
			width: 100px;
		}
		.product_related_tab_products_delete{
			cursor: pointer;
		}
		</style>
		<div class="product_related_tab">
			
			<h2>Сопутствующие товары</h2>
			
			<table class="product_related_tab_products">
				<tr>
					<th>ID товара</th>
					<th>Название</th>
					<th>Артикул</th>
					<th></th>
					<th></th>
				</tr>
				<?php
					foreach ($products as $product)
					{
						$photo_id = $product["main_photo_id"];
						$main_photo_url = \Elberos\get_image_url($photo_id, "thumbnail");
						?>
						<tr data-id="<?= $product['id'] ?>">
							<td><?= esc_html($product["id"]) ?></td>
							<td><?= esc_html($product["name"]) ?></td>
							<td><?= esc_html($product["vendor_code"]) ?></td>
							<td>
								<img src="<?= esc_attr($main_photo_url) ?>" />
							</th>
							<td>
								<button class="product_related_tab_products_delete"
									data-id="<?= $product['id'] ?>">Delete</button>
							</td>
						</tr>
						<?php
					}
				?>
			</table>
			
			<div class="product_related_add">
				
				<label>Добавить товар</label>
				
				<table>
					<tr class="product_related_add_row">
						<td>По артикулу</td>
						<td><input class="product_related_add_vendor_code_input" value="" /></td>
						<td><button class="product_related_add_vendor_code">Добавить</button></td>
					</tr>
					<tr class="product_related_add_row">
						<td>По ID Товара</td>
						<td><input class="product_related_add_product_id_input" value="" /></td>
						<td><button class="product_related_add_product_id">Добавить</button></td>
					</tr>
				</table>
				
			</div>
			
			<div class="product_related__result web_form_result"></div>
			
			<script>
			var $ = jQuery;
			</script>
			
			<script type="text/javascript">
			
			function add_relative_product(value, text)
			{
				var $form = $('.product_related_tab');
				var send_data =
				{
					"product_id": <?= json_encode($this->form_item_id) ?>,
					"value": value,
					"kind": text,
				};
				ElberosFormSetWaitMessage($form);
				elberos_api_send
				(
					"elberos_commerce_admin",
					"add_relative_product",
					send_data,
					(function (obj)
					{
						return function(res)
						{
							ElberosFormSetResponse($form, res);
							if (res.code == 1 && res.item)
							{
								var $row = $('<tr></tr>')
									.attr('data-id', res.item.id)
									.append
									(
										$('<td></td>').append( res.item.id )
									)
									.append
									(
										$('<td></td>').append( res.item.name )
									)
									.append
									(
										$('<td></td>').append( res.item.vendor_code )
									)
									.append
									(
										$('<td></td>').
											append
											(
												$('<img />')
													.attr('src', res.main_photo)
											)
									)
									.append
									(
										$('<td></td>').append
										(
											$('<button>Delete</button>')
												.attr('data-id', res.item.id)
										)
									)
								;
								$('.product_related_tab table.product_related_tab_products').append($row);
							}
						};
					})(this),
				);
			}
			
			$('.product_related_add_vendor_code').click(function(){
				var value = $(this)
					.parents('.product_related_add_row')
					.find('.product_related_add_vendor_code_input')
					.val();
				add_relative_product(value, "vendor_code");
			});
			
			$('.product_related_add_product_id').click(function(){
				var value = $(this)
					.parents('.product_related_add_row')
					.find('.product_related_add_product_id_input')
					.val();
				add_relative_product(value, "product_id");
			});
			
			$('.product_related_tab_products_delete').click(function(){
				var id = $(this).attr("data-id");
				var send_data =
				{
					"product_id": <?= json_encode($this->form_item_id) ?>,
					"relative_id": id,
				};
				elberos_api_send
				(
					"elberos_commerce_admin",
					"delete_relative_product",
					send_data,
					(function (obj)
					{
						return function(res)
						{
							if (res.code == 1)
							{
								$('.product_related_tab_products tr').each(function(){
									var id = $(this).attr('data-id');
									if (id == res.relative_id)
									{
										$(this).remove();
									}
								})
							}
						};
					})(this),
				);
			});
			</script>
			
		</div>
		<?php
		
	}
}

}