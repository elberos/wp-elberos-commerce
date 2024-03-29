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


if ( !class_exists( Category_Table::class ) && class_exists( \Elberos\Table::class ) ) 
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
				/* Render field image_id */
				$struct->editField("image_id", [
					//"form_show" => false,
					"form_render" => function($struct, $field, $item)
					{
						$image_file_id = isset($item['image_id']) ? $item['image_id'] : '';
						$image_file_path = \Elberos\get_image_url($image_file_id, "thumbnail");
						?>
						<div class='image_file_path_wrap'>
							<input type='button' class='button image_file_path_add_photo' value='Добавить файл'><br/>
							<input type='hidden' class='image_file_id web_form_value'
								name='image_id' data-name='image_id'
								value='<?= esc_attr($image_file_id) ?>' readonly>
							<input type='hidden' class='image_file_path web_form_value'
								name='image_file_path' data-name='image_file_path'
								value='<?= esc_attr($image_file_path) ?>' readonly>
							<img class='image_file_path_image'
								src='<?= esc_attr($image_file_path) ?>' style="height: 250px;">
						</div>
						<?php
					},
				]);
				
				$struct->table_fields =
				[
					"id",
					"name",
					"code_1c",
					"image_id",
				];
				
				$struct->form_fields =
				[
					"name",
					"slug",
					"code_1c",
					"show_in_catalog",
					"image_id",
					"image_file_path",
					"seo_title",
					"seo_description",
					"seo_focus_word",
					"seo_tags",
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
	
	
	/**
	 * Get form id name
	 */
	function get_form_id_name()
	{
		return "sub_id";
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
			$args["parent_category_id"] = 0;
			$where[] = "is_deleted=0";
		}
		
		$per_page = $this->per_page();
		list($items, $total_items, $pages, $page) = \Elberos\wpdb_query
		([
			"table_name" => $this->get_table_name(),
			"where" => implode(" and ", $where),
			"args" => $args,
			"page" => 0,
			"per_page" => -1,
			"order_by" => "name asc",
			//"log"=>true,
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
		wp_enqueue_script( 'jquery-ui-widget' );
		wp_enqueue_script( 'jquery-ui-mouse' );
		wp_enqueue_script( 'jquery-ui-accordion' );
		wp_enqueue_script( 'jquery-ui-autocomplete' );
		wp_enqueue_script( 'jquery-ui-slider' );
		wp_enqueue_script( 'jquery.contextMenu.min.js',
			'/wp-content/plugins/wp-elberos-core/assets/jQuery-contextMenu/jquery.contextMenu.min.js', false );
		wp_enqueue_style( 'jquery.contextMenu.min.css',
			'/wp-content/plugins/wp-elberos-core/assets/jQuery-contextMenu/jquery.contextMenu.min.css', false );
		wp_enqueue_style( 'fancytree.css',
			'/wp-content/plugins/wp-elberos-core/assets/fancytree/skin-win8/ui.fancytree.min.css', false );
		wp_enqueue_script( 'fancytree.js',
			'/wp-content/plugins/wp-elberos-core/assets/fancytree/jquery.fancytree-all.min.js', false );
		wp_enqueue_script( 'script.js',
			'/wp-content/plugins/wp-elberos-core/assets/script.js', false, "202401081" );
		wp_enqueue_style( 'dialog.css',
			'/wp-content/plugins/wp-elberos-core/assets/dialog.css', false );
		wp_enqueue_style( 'web_form.css',
			'/wp-content/plugins/wp-elberos-core/assets/web_form.css', false );
		?>
		<script>
		//var $ = jQuery.noConflict();
		var $ = jQuery;
		</script>
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
		.category_admin_page{
			padding-top: 20px;
		}
		.category_admin_page_item{
			width: calc(50% - 5px);
			display: inline-block;
			vertical-align: top;
		}
		.category_admin_page .web_form_input, .elberos_dialog .web_form_input{
			width: 100%;
		}
		.elberos_form_buttons{
			text-align: center;
		}
		.elberos_form .web_form_result, .elberos_dialog .web_form_result{
			text-align: center;
			padding-top: 5px;
		}
		.elberos_form_edit_category .cursor, .elberos_form_edit_category a.cursor
		{
			cursor: pointer;
		}
		.elberos_form_edit_category .nav-tab-data
		{
			display: none;
			margin: 10px 5px;
		}
		.elberos_form_edit_category .nav-tab-data.nav-tab-data-active
		{
			display: block;
		}
		.elberos_form_edit_category .elberos_input_tags__wrap{
			border-radius: 4px;
			border: 1px solid #8c8f94;
			width: 100%;
		}
		.elberos_input_tags__tag{
			border-radius: 4px;
		}
		</style>
		<script>
		/* Add photo to category */
		jQuery(document).on('click', '.image_file_path_add_photo', function(){
			var $wrap = $(this).parents('.image_file_path_wrap');
			var uploader = wp.media
			({
				title: "Файлы",
				button: {
					text: "Выбрать файл"
				},
				multiple: false
			})
			.on('select',
				(function($wrap) {
					return function()
					{
						var attachments = uploader.state().get('selection').toJSON();
						
						for (var i=0; i<attachments.length; i++)
						{
							var photo = attachments[i];
							var photo_time = photo.date;
							if (photo_time.getTime != undefined) photo_time = photo_time.getTime();
							
							//jQuery($wrap).find('.image_file_path').val(photo.url);
							//jQuery($wrap).find('.image_file_path_image').attr('src', photo.url);
							jQuery($wrap).find('.image_file_id').val(photo.id);
							jQuery($wrap).find('.image_file_path').val(photo.url);
							jQuery($wrap).find('.image_file_path_image').attr('src', photo.url);
						}
					}
				})($wrap)
			)
			.open();
		});
		</script>
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
	 * Display form id
	 */
	function display_form_id()
	{
		?>
		<input type='hidden' class='web_form_value' name='id' data-name='id' />
		<?php
	}
	
	
	/**
	 * Display form
	 */
	function display_form()
	{
		if ($this->form_item == null)
		{
			return;
		}
		
		$tabs = [
			[
				"key" => "main",
				"title" => "Main",
			],
			[
				"key" => "seo",
				"title" => "SEO",
			],
		];
		$key = "main";
		
		?>
		<nav class="nav-tab-wrapper">
			<?php
			foreach ($tabs as $arr)
			{
				?><a class="nav-tab cursor <?= $key == $arr['key'] ? "nav-tab-active" : "" ?>"
					data-tab="elberos_form_category_<?= esc_attr($arr['key']) ?>"
					data-key="elberos_form_category"
				>
					<?= esc_html($arr['title']) ?>
				</a><?php
			}
			?>
		</nav>
		
		<div class='nav-tab-data <?= $key == "main" ? "nav-tab-data-active" : "" ?>'
			data-tab="elberos_form_category_<?= esc_attr($tabs[0]['key']) ?>"
			data-key="elberos_form_category"
		>
			<?php
				$this->display_form_id();
				echo $this->struct->renderForm(
					$this->form_item, $this->form_item['id'] > 0 ? "edit" : "add"
				);
			?>
		</div>
		<div class='nav-tab-data <?= $key == "seo" ? "nav-tab-data-active" : "" ?>'
			data-tab="elberos_form_category_<?= esc_attr($tabs[1]['key']) ?>"
			data-key="elberos_form_category"
		>
			<?php
				echo $this->struct->renderForm(
					$this->form_item, $this->form_item['id'] > 0 ? "edit" : "add",
					[
						"group" => "seo"
					]
				);
			?>
		</div>
		
		<script>
		jQuery('.elberos_form_edit_category .nav-tab').click(function(){
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
		
		<?php
		echo $this->struct->renderJS($this->form_item, $this->form_item['id'] > 0 ? "edit" : "add");
	}
	
	
	/**
	 * Display table
	 */
	function display_table()
	{
		$columns = $this->get_columns();
        $hidden = array();
        $sortable = $this->get_sortable_columns();
        $this->_column_headers = array($columns, $hidden, $sortable);
		
		$this->prepare_table_items();
		$page_name = $this->get_page_name();
		$this->form_item = $this->struct->getDefault();
		$this->form_item['id'] = 0;
		
		$classifier_id = (int)(isset($_GET["id"]) ? $_GET["id"] : 0);
		
		?>
		<div class="wrap">
			<h1 class="wp-heading-inline">Категории</h1>
			<a class="page-title-action button_category_add">Добавить категорию</a>
			<hr class="wp-header-end">
			<div class='category_admin_page'>
				<div class='category_admin_page_item'>
					<div id='fancytree' class='mar10--top fancytree'></div>
				</div>
				<div class='category_admin_page_item category_admin_page_item_edit_form'>
					<form class="elberos_form elberos_form_edit_category" method="POST" style="display: none;">
						<input type="hidden" name="nonce" value="<?php echo wp_create_nonce(basename(__FILE__))?>"/>
						<input type='hidden' name='classifier_id' data-name='classifier_id'
							value="<?= esc_attr($classifier_id) ?>" />
						<?php $this->display_form() ?>
						<div class="elberos_form_buttons">
							<input type="button" class="button-primary button--save" value="Сохранить">
						</div>
						<div class="web_form_result"></div>
					</form>
				</div>
			</div>
		</div>
		<script>
		
		var fancytree = 
		{
			data: null,
			tree: null,
			tree_selector: '#fancytree',
			
			buildTitle: function(obj)
			{
				return obj['name'];
			},
			
			buildEntity: function(obj)
			{
				return {
					key : "node" + new String(obj.id),
					folder: true,
					lazy: true,
					title : this.buildTitle(obj),
					data: obj,
				};
			},
			
			buildData: function(data)
			{
				var res = [];
				for (var i=0; i<data.length; i++)
					res.push( this.buildEntity(data[i]) );
				return res;
			},
			
			init: function()
			{
				// Init fancytree
				this.initFancytree();
				
				// Init context menu
				this.initContextMenu();
			},
			
			initFancytree: function()
			{
				$(this.tree_selector).fancytree({
					source: this.buildData(this.data),
					click: this.onClickFancyTree.bind(this),
					lazyLoad: this.onLoad.bind(this),
				});
				
				// Get tree
				this.tree = $.ui.fancytree.getTree( $(this.tree_selector) );
			},
			
			/**
			 * Init context menu
			 */
			initContextMenu: function(){
				$.contextMenu({
					selector: '.fancytree-node', 
					
					items: {
						"add": {name: "Добавить", icon: "add"},
						"delete": {name: "Удалить", icon: "delete"},
					},
					
					callback: (function(obj){
						return function(key, options){
							var node = $.ui.fancytree.getNode(this.get(0));
							obj.onContextMenuClick(node, key, options);
						}
					})(this),
					
					events: {
						show : function(options){
							var node = $.ui.fancytree.getNode(this.get(0));
							node.setActive();
						}
					}
				});
			},
			
			/**
			 * Event Lazy data load
			 */
			onLoad: function(event, data)
			{
				var node = data.node;
				data.result = $.Deferred();
				this.loadData(data.result, node);
			},
			
			/**
			 * Event Fancy tree mouse click event
			 */
			onClickFancyTree: function(event, data)
			{
				this.showEdit(data.node);
			},
			
			/**
			 * Event Fancy tree mouse click event
			 */
			onContextMenuClick: function(node, key, options){
				if (key == 'delete'){
					this.showDelete(node);
				}
				else if (key == 'add'){
					this.showAdd(node);
				}
			},
			
			/**
			 * Show add form
			 */
			showAdd: function (node){
				
				var $content = $('.category_admin_page_item_edit_form');
				
				this.add_dialog = new ElberosFormDialog();
				this.add_dialog.setContent($content.html());
				this.add_dialog.open();
				this.add_dialog.setTitle("Добавить категорию");
				
				var $form = this.add_dialog.$el.find(".elberos_form");
				this.setFormData($form, null);
				ElberosFormClearResult($form);
				
				/* Add input */
				var $input = $('<input type="hidden" class="web_form_value" name="parent_category_id" data-name="parent_category_id" value="0"></input>');
				if (node != null) $input.val(node.data.id);
				$form.append($input);
				
				/* Show */
				$form.show();
			},
			
			/**
			 * Show edit form
			 */			
			showEdit: function(node)
			{
				var $form = $('.category_admin_page .elberos_form');
				$form.show();
				this.clearFormData();
				this.setFormData($form, node.data);
			},
			
			/**
			 * Render content delete form
			 */
			renderContentDelete: function(node){
				var data = node.data;
				var id = htmlEscape(data.id);
				var name = htmlEscape(data.name);
				return `
					<form class="elberos_form" method="POST" style="">
						<input type="hidden" class="web_form_value" name="id" data-name="id" value="${id}">
						<div style="padding-bottom: 10px;">
							Вы действительно хотите удалить категорию "${name}"?
						</div>
						<div class="elberos_form_buttons">
							<input type="button" class="button-primary button-danger button_category_delete"
								data-id="${id}" value="Удалить">
						</div>
						<div class="web_form_result"></div>
					</form>
				`;
			},
			
			/**
			 * Show delete form
			 */
			showDelete: function(node)
			{				
				var content = this.renderContentDelete(node);
				var $content = $(content);
				this.delete_dialog = new ElberosFormDialog();
				this.delete_dialog.setContent($content);
				this.delete_dialog.open();
				this.delete_dialog.setTitle("Удалить категорию");
			},
			
			updateNode: function(node, item)
			{
				if (node)
				{
					node.data = item;
					node.setTitle( this.buildTitle(item) );
				}
			},
			
			/**
			 * Clear form data
			 */
			clearFormData: function()
			{
				var obj = this;
				var $form = $('.category_admin_page_item_edit_form');
				$form.find('.web_form_value').each(function(){
					var api_name = $(this).attr('data-name');
					obj.setFieldValue(this, null);
				});
				ElberosFormClearResult($form);
			},
			
			/**
			 * Set form data
			 */
			setFormData: function($form, data)
			{
				obj = this;
				$form.find('.web_form_value').each((function(obj){
					return function(){
						var api_name = $(this).attr('data-name');
						var value = obj.getObjectValue(data, api_name);
						
						if (value != null) obj.setFieldValue(this, value);
						else obj.setFieldValue(this, '');
					};
				})(obj));
				if (data != null)
				{
					$form.find('.image_file_path_image').attr('src', data.image_file_path);
				}
				else
				{
					$form.find('.image_file_path_image').attr('src', '');
				}
				
				$form.trigger('setFormData', [{"data":data}]);
			},
			
			/**
			 * Get value from Object
			 */
			getObjectValue: function(data, key)
			{
				var arr = key.split(".");
				for (var i in arr){
					var k = arr[i];
					if (data == null || typeof data[k] == "undefined")
						return null;
					data = data[k];
				}
				return data;
			},
			
			/**
			 * Set field value by DOM object
			 */
			setFieldValue: function(obj, value)
			{
				var $obj = $(obj);
				var type = $obj.attr('type');
				var api_name = $obj.attr('data-name');
				var tag = $obj.prop("tagName").toLowerCase();
				
				if (typeof obj.controller != "undefined" && obj.controller != null)
				{
					return obj.controller.setData(value);
				}
				else if ($obj.hasClass('ckeditor_type'))
				{
				}
				else if (tag == 'input' && type == 'checkbox')
				{
					if (value == "1") 
						$obj.prop('checked', true);
					else
						$obj.prop('checked', false);
				}
				else if (tag == 'input' && type == 'radio')
				{
					if (value == $obj.val()) 
						$obj.prop('checked', true);
					else
						$obj.prop('checked', false);
				}
				else if (tag == 'input' && type == 'file')
				{
					/*
					var $preview = this.$el.find('.web_form__field_image_preview[data-api-name='+api_name+']');
					if (value == null || typeof value.thumb == "undefined" || value.thumb == '') {
						$preview.attr('src', '');
						$preview.addClass('hidden');
					}
					else {
						$preview.attr('src', '/media' + value.thumb + '?_' + value.inc);
						$preview.removeClass('hidden');
					}
					$obj.val('');
					*/
				}
				else
					$obj.val(value);
			},
			
			/**
			 * Lazy data load
			 */
			loadData: function(dfd, node)
			{
				var parent_id = node.data.id;
				var send_data = {
					"classifier_id": <?= json_encode((int)(isset($_GET["id"]) ? $_GET["id"] : 0)) ?>,
					"parent_category_id": parent_id,
				};
				elberos_api_send
				(
					"elberos_commerce_admin",
					"categories_load",
					send_data,
					(function (obj)
					{
						return function(res)
						{
							if (res.code == 1)
							{
								dfd.resolve(obj.buildData(res.items));
							}
							else
							{
								dfd.reject(res.message);
							}
						};
					})(this),
				);
			},
		};
		
		$(document).ready(function(){
			fancytree.data = <?= json_encode($this->items) ?>;
			fancytree.init();
		});
		
		$(document).on("click", ".button_category_add", function(){
			fancytree.showAdd(null);
		});
		
		$(document).on("click", ".elberos_form_edit_category .button--save", function(){
			
			var $form = $(this).parents('.elberos_form');
			ElberosFormSetWaitMessage($form);
			
			var item = ElberosFormGetData($form);
			item["classifier_id"] = <?= json_encode((int)(isset($_GET["id"]) ? $_GET["id"] : 0)) ?>;
			
			var send_data = {
				"item": item,
			};
			elberos_api_send
			(
				"elberos_commerce_admin",
				"categories_save",
				send_data,
				(function ($form, obj)
				{
					return function(res)
					{
						ElberosFormSetResponse($form, res);
						if (res.code == 1)
						{
							$form.find('.web_form_value[data-name="id"]').val(res.item_id);
							if (res.action == "add")
							{
								var parent_category_id = res.item.parent_category_id;
								
								var node = null;
								if (parent_category_id == 0)
								{
									node = fancytree.tree.getRootNode();
								}
								else
								{
									node = fancytree.tree.getNodeByKey( "node" + new String(parent_category_id) );
								}
								
								if (node != null)
								{
									node.addChildren( fancytree.buildEntity(res.item) );
								}
							}
							else
							{
								node = fancytree.tree.getNodeByKey( "node" + new String(res.item_id) );
								fancytree.updateNode(node, res.item);
							}
							if (fancytree.add_dialog) fancytree.add_dialog.close();
							fancytree.add_dialog = null;
						}
					};
				})($form, this),
			);
			
		});
		
		$(document).on("click", ".button_category_delete", function(){
			
			var $form = $(this).parents('.elberos_form');
			ElberosFormSetWaitMessage($form);
			
			var send_data = {};
			send_data["id"] = $(this).attr("data-id");
			send_data["classifier_id"] = <?= json_encode((int)(isset($_GET["id"]) ? $_GET["id"] : 0)) ?>;
			
			elberos_api_send
			(
				"elberos_commerce_admin",
				"categories_delete",
				send_data,
				(function ($form, obj)
				{
					return function(res)
					{
						ElberosFormSetResponse($form, res);
						if (res.code == 1)
						{
							var node = fancytree.tree.getNodeByKey( "node" + new String(res.item_id) );
							if (node != null)
							{
								if (fancytree.delete_dialog) fancytree.delete_dialog.close();
								fancytree.delete_dialog = null;
								node.remove();
							}
						}
					};
				})($form, this),
			);
			
		});
		
		</script>
		<?php
		
	}
	
}

}