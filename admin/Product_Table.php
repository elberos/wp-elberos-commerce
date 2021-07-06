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

if ( !class_exists( Product_Table::class ) ) 
{

class Product_Table extends \Elberos\Table 
{
	
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
					"main_photo_id",
					"name",
					"code_1c",
				];
				
				$struct->form_fields =
				[
					"catalog_id",
					"show_in_catalog",
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
			"INNER JOIN $table_name_catalog as catalog on (catalog.id = t.catalog_id) " .
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
		$product_text = isset($_POST['product_text']) ? $_POST['product_text'] : [];
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
	 * Фильтр
	 */
	function extra_tablenav( $which )
	{
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
			<span class="table_filter">
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
				<input type="text" name="name" class="web_form_value" placeholder="Название товара"
					value="<?= esc_attr( isset($_GET["name"]) ? $_GET["name"] : "" ) ?>">
				<input type="button" class="button dosearch" value="Поиск">
			</span>
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
				document.location.href = 'admin.php?page=elberos-commerce'+filter;
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
		
		/* Catalog id */
		if (isset($_GET["catalog_id"]))
		{
			$where[] = "catalog_id=:catalog_id";
			$args["catalog_id"] = (int)$_GET["catalog_id"];
		}
		
		/* Name */
		if (isset($_GET["name"]))
		{
			$where[] = "name like :name";
			$args["name"] = "%" . $wpdb->esc_like($_GET["name"]) . "%";
		}
		
		/* Is deleted */
		if (isset($_GET["is_deleted"]) && $_GET["is_deleted"] == "true")
		{
			$where[] = "is_deleted=1";
		}
		else
		{
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
		wp_enqueue_media();
		wp_enqueue_script( 'vue', '/wp-content/plugins/wp-elberos-core/assets/vue.min.js', false );
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
	 * Display form content
	 */
	function display_form_content()
	{
		parent::display_form_content();
		
		echo '<div class="add_or_edit_form_right">';
		$this->show_categories();
		$this->show_photos();
		echo '</div>';
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
		
		/* Список категорий у товара */
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
				<div class='product_photo' data-id='<?= esc_attr($post->ID) ?>'>
					<img src='<?= esc_attr($href) ?>' />
					<span class="dashicons dashicons-no-alt button-delete" data-id='<?= esc_attr($post->ID) ?>'></span>
					<input type='hidden' name='product_photo[<?= esc_attr($post->ID) ?>][id]'
						value='<?= esc_attr($post->ID) ?>' />
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
	
	
}

}