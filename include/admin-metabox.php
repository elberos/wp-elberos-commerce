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

if ( !class_exists( Metabox::class ) ) 
{

class Metabox
{
	/**
	 * Products title
	 */
	public static function show_products_title($post, $options)
	{
		// Используем nonce для верификации
		wp_nonce_field( plugin_basename(__FILE__), 'elberos_commerce_title' );
		
		// Get langs
		$langs = \Elberos\wp_langs();
		
		// Get products text
		$product_text = get_post_meta( $post->ID, 'product_text', '' );
		$product_text = isset($product_text[0]) ? $product_text[0] : '';
		$product_text = @unserialize($product_text);
		
		// In catalog
		$product_in_catalog = get_post_meta( $post->ID, 'product_in_catalog', '' );
		$product_in_catalog = isset($product_in_catalog[0]) ? $product_in_catalog[0] : '';
		$product_price = get_post_meta( $post->ID, 'product_price', '' );
		$product_price = isset($product_price[0]) ? $product_price[0] : '';
		
		?>
		<div class="elberos-commerce products_text">
			
			<p>
				<label for="in_catalog"><?php _e('Разместить в каталоге:', 'elberos-commerce')?></label>
			<br>
				<select id="in_catalog" name="product_in_catalog" style="width: 100%"
					value="<?php echo esc_attr($product_in_catalog)?>">
					<option value="0" <?= $product_in_catalog == 0 ? "selected" : "" ?>>Нет</option>
					<option value="1" <?= $product_in_catalog == 1 ? "selected" : "" ?>>Да</option>
				</select>
			</p>
			<p>
				<label for="price"><?php _e('Цена:', 'elberos-commerce')?></label>
			<br>
				<input id="price" name="product_price" type="text" style="width: 100%"
					value="<?php echo esc_attr($product_price)?>" >
			</p>
			
			<p><nav class="nav-tab-wrapper">
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
			</nav></p>
			
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
	
	public static function save_products_title($post_id)
	{
		// проверяем nonce
		if ( ! wp_verify_nonce( $_POST['elberos_commerce_title'], plugin_basename(__FILE__) ) )
			return;

		// если это автосохранение ничего не делаем
		if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ) 
			return;

		// проверяем права юзера
		if( ! current_user_can( 'edit_post', $post_id ) )
			return;
		
		// Save text
		$product_text = isset($_POST['product_text']) ? $_POST['product_text'] : [];
		update_post_meta( $post_id, 'product_text', serialize($product_text) );
		
		// Save price
		$product_in_catalog = isset($_POST['product_in_catalog']) ? $_POST['product_in_catalog'] : 0;
		update_post_meta( $post_id, 'product_in_catalog', $product_in_catalog );
		
		// Save in catalog
		$product_price = isset($_POST['product_price']) ? $_POST['product_price'] : '';
		update_post_meta( $post_id, 'product_price', $product_price );
	}
	
	
	
	/**
	 * Products catalog title
	 */
	public static function show_products_catalog_title($post, $options)
	{
		// Используем nonce для верификации
		wp_nonce_field( plugin_basename(__FILE__), 'elberos_commerce_products_catalog_title' );
		
		// Get langs
		$langs = \Elberos\wp_langs();
		
		// Get products text
		$product_catalog_text = get_post_meta( $post->ID, 'product_catalog_text', '' );
		$product_catalog_text = isset($product_catalog_text[0]) ? $product_catalog_text[0] : '';
		$product_catalog_text = @unserialize($product_catalog_text);
		
		?>
		<div class="elberos-commerce product_catalog_text">
			
			<p><nav class="nav-tab-wrapper">
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
			</nav></p>
			
			<?php
			foreach ($langs as $key => $lang)
			{
				$locale = $lang['locale'];
				$item_text = isset($product_catalog_text[$locale]) ? $product_catalog_text[$locale] : null;
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
								name="product_catalog_text[<?= esc_attr($lang['locale']) ?>][name]"
								type="text" style="width: 100%"
								value="<?php echo esc_attr($text_name)?>" >
						</p>
						
						<p>
							<label for="description[<?= esc_attr($lang['locale']) ?>]">
								<?php _e('Описание', 'elberos-commerce')?> (<?= esc_attr($lang['name']) ?>):
							</label>
						<br>
							<textarea id="description[<?= esc_attr($lang['locale']) ?>]"
								name="product_catalog_text[<?= esc_attr($lang['locale']) ?>][description]"
								type="text" style="width: 100%; height: 300px;"><?= esc_html($text_description) ?></textarea>
						</p>
						
					</div>
				</p>
				
				<?php
			}
			
			?>
			<script>
			jQuery('.product_catalog_text .nav-tab').click(function(){
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
	 * Save products catalog title
	 */
	public static function save_products_catalog_title($post_id)
	{
		// проверяем nonce
		if ( ! wp_verify_nonce( $_POST['elberos_commerce_products_catalog_title'], plugin_basename(__FILE__) ) )
			return;

		// если это автосохранение ничего не делаем
		if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ) 
			return;

		// проверяем права юзера
		if( ! current_user_can( 'edit_post', $post_id ) )
			return;
		
		// Save text
		$product_catalog_text = isset($_POST['product_catalog_text']) ? $_POST['product_catalog_text'] : [];
		update_post_meta( $post_id, 'product_catalog_text', serialize($product_catalog_text) );
	}
	
	
	
	/**
	 * Categories
	 */
	public static function show_categories($post, $options)
	{
		// Используем nonce для верификации
		wp_nonce_field( plugin_basename(__FILE__), 'elberos_commerce_categories' );
		
		// Get products categories
		$product_catalog = get_post_meta( $post->ID, 'product_catalog', '' );
		
		global $wpdb;
		$sql = $wpdb->prepare
		(
			"SELECT * FROM {$wpdb->prefix}posts WHERE post_type = %s and post_status=%s",
			['catalog', 'publish']
		);
		$categories = $wpdb->get_results($sql, ARRAY_A);
		?>
		
		<div class='elberos-commerce' style='padding-top: 10px;'>
			
			<div class='product_categories'>
				<?php 
					if (gettype($product_catalog) == 'array') foreach ($product_catalog as $cat_id)
					{
						$find_category = null;
						foreach ($categories as $cat)
						{
							if ($cat['ID'] == $cat_id)
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
							<input type='hidden' name='product_catalog[<?= esc_attr($find_category['ID']) ?>]'
								value='<?= esc_attr($find_category['ID']) ?>'>
						</div>
						
						<?php 
						}
					}
				?>
			</div>
			
			<select class='product_select_category' style='width: 100%'>
				<option value=''>Выберите категорию</option>
				<?php foreach ($categories as $cat) { ?>
					<option value="<?= esc_attr($cat['ID']) ?>"><?= esc_html($cat['post_title']) ?></option>
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
							.attr('name', 'product_catalog[' + value + ']')
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
	
	public static function save_categories($post_id)
	{
		// проверяем nonce
		if ( ! wp_verify_nonce( $_POST['elberos_commerce_categories'], plugin_basename(__FILE__) ) )
			return;

		// если это автосохранение ничего не делаем
		if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ) 
			return;

		// проверяем права юзера
		if( ! current_user_can( 'edit_post', $post_id ) )
			return;
		
		$product_catalog = isset($_POST['product_catalog']) ? $_POST['product_catalog'] : [];
		$categories = array_values($product_catalog);
		\Elberos\update_post_meta_arr( $post_id, 'product_catalog', $categories );
	}
	
	
	
	/**
	 * Meta params
	 */
	public static function show_meta_params($post, $options)
	{
		global $wpdb;
		
		// Current product params
		$product_current_params = [];
		
		// Params
		$sql = "SELECT * FROM {$wpdb->prefix}elberos_products_params";
		$products_params = $wpdb->get_results($sql, ARRAY_A);
		$products_params_index = \Elberos\make_index($products_params, "alias");
		
		// Params values
		$sql = "SELECT * FROM {$wpdb->prefix}elberos_products_params_values";
		$products_params_values = $wpdb->get_results($sql, ARRAY_A);
		
		// Select product params
		$sql = $wpdb->prepare
		(
			"SELECT * FROM {$wpdb->prefix}postmeta WHERE post_id = %d",
			[$post->ID]
		);
		$options = $wpdb->get_results($sql, ARRAY_A);
		foreach ($options as $meta)
		{
			if (strpos($meta['meta_key'], 'product_param_') === 0)
			{
				$param_key = substr($meta['meta_key'], 14);
				if (isset($params[$param_key])) $params[$param_key] = [ 'alias' => $param_key, 'values' => [] ];
				$product_current_params[$param_key]['alias'] = $param_key;
				$product_current_params[$param_key]['values'][] = $meta['meta_value'];
			}
		}
		
		// Используем nonce для верификации
		wp_nonce_field( plugin_basename(__FILE__), 'elberos_commerce_meta_params' );
		?>
		
		<div class='elberos-commerce elberos-commerce--meta-params' style='display: none;'>
			
			<div class='product_params'>
				
				<div class='product_param' v-for='current_param in product_current_params' :data-key='current_param.alias'>
					<div class='product_param_name'>
						{{ info(current_param.alias).name }}
					</div>
					<div class='product_param_value'>
						
						<div v-if='info(current_param.alias).type == "text"'>
						</div>
						
						<div v-if='info(current_param.alias).type == "list"'>
							<select v-model='product_current_params[current_param.alias]["values"][0]'
								:name='"product_param[" + info(current_param.alias).alias + "]"'
							>
								<option value=''>Выберите параметр</option>
								<option :value='value.alias'
									v-for='value in products_params_values'
									v-if='value.param_id == info(current_param.alias).id'
								>{{ value.name }}</option>
							</select>
						</div>
						
						<div v-if='info(current_param.alias).type == "multilist"'>
						</div>
						
					</div>
					<div class='product_param_buttons'>
						<button type='button' :data-alias='info(current_param.alias).alias' v-on:click='onDelete'>
							Delete
						</button>
					</div>
				</div>
				
			</div>
			
			<br/>
			<b style='padding-bottom: 5px; display: block;'>Добавление параметра:</b>
			<select class='product_select_params' style='width: 100%'>
				<option value=''>Выберите параметр</option>
				<?php foreach ($products_params as $params) { ?>
					<option value="<?= esc_attr($params['alias']) ?>"><?= esc_html($params['name']) ?></option>
				<?php } ?>
			</select>
			
		</div>
		
		<script>
			jQuery(document).on('change', '.product_select_params', '', function(){
				var alias_key = jQuery(this).val();
				product_app.addParam(alias_key);
				jQuery(this).val('');
			});
			
			function get_products_params()
			{
				var products_params = JSON.parse(atob("<?= base64_encode(json_encode($products_params)) ?>"));
				var products_params_index = JSON.parse(atob("<?= base64_encode(json_encode($products_params_index)) ?>"));
				var products_params_values = JSON.parse(atob("<?= base64_encode(json_encode($products_params_values)) ?>"));
				var product_current_params = JSON.parse(atob("<?= base64_encode(json_encode($product_current_params)) ?>"));
				if (product_current_params instanceof Array) product_current_params = {};
				var obj = {
					"products_params": products_params,
					"products_params_index": products_params_index,
					"products_params_values": products_params_values,
					"product_current_params": product_current_params,
				};
				return obj;
			}
			
			jQuery(document).ready(function(){
				var product_app = new Vue({
					el: '.elberos-commerce--meta-params',
					data: get_products_params(),
					methods:
					{
						info: function(alias_key)
						{
							for (var i=0; i<this.products_params.length; i++)
							{
								if (this.products_params[i]['alias'] == alias_key)
								{
									return this.products_params[i];
								}
							}
							return {};
						},
						onDelete: function(e)
						{
							var alias_key = e.target.getAttribute('data-alias');
							delete this.$data.product_current_params[alias_key];
							this.$forceUpdate();
						},
						addParam: function(alias_key)
						{
							if (this.$data.product_current_params[alias_key] == undefined)
							{
								this.$data.product_current_params[alias_key] =
								{
									alias: alias_key,
									values: [""],
								};
								this.$forceUpdate();
							}
						},
					},
				});
				window["product_app"] = product_app;
				jQuery('.elberos-commerce--meta-params').show();
			});
		</script>
		
		<?php
	}
	
	public static function save_meta_params($post_id)
	{
		// проверяем nonce
		if ( ! wp_verify_nonce( $_POST['elberos_commerce_meta_params'], plugin_basename(__FILE__) ) )
			return;

		// если это автосохранение ничего не делаем
		if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ) 
			return;

		// проверяем права юзера
		if( ! current_user_can( 'edit_post', $post_id ) )
			return;
		
		$product_param = isset($_POST['product_param']) ? $_POST['product_param'] : [];
		foreach ($product_param as $key => $value)
		{
			update_post_meta( $post_id, 'product_param_' . $key, $value );
		}
	}
	
	
	
	/**
	 * Photos
	 */
	public static function show_photos($post, $options)
	{
		// Используем nonce для верификации
		wp_nonce_field( plugin_basename(__FILE__), 'elberos_commerce_photos' );
		
		// Get products photos
		$product_photo = get_post_meta( $post->ID, 'product_photo', '' );
		?>
		<div class='elberos-commerce'>
			<input type='button' class='button add-photo-button' value='Добавить фото'>
			
			<div class='product_photos'>
			<?php
			if (gettype($product_photo) == 'array') foreach ($product_photo as $photo)
			{
				if (!isset($photo['ID'])) continue;
				$image = wp_get_attachment_image_src($photo['ID'], 'thumbnail');
				$post = get_post( $photo['ID'] );
				$href = $image[0] . "?_=" . strtotime($post->post_modified_gmt);
				?>
				<div class='product_photo' data-id='<?= esc_attr($post->ID) ?>'>
					<img src='<?= esc_attr($href) ?>' />
					<span class="dashicons dashicons-no-alt button-delete" data-id='<?= esc_attr($post->ID) ?>'></span>
					<input type='hidden' name='product_photo[<?= esc_attr($post->ID) ?>][ID]'
						value='<?= esc_attr($post->ID) ?>' />
				</div>
				<?php
			}
			?>
			</div>
			
			<script>
				jQuery(document).on('click', '.product_photos .button-delete', '', function(){
					var data_id = jQuery(this).attr('data-id');
					var $items = jQuery('.product_photos');
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
								.attr('name', 'product_photo[' + photo.id + '][ID]')
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
	
	public static function save_photos($post_id)
	{
		// проверяем nonce
		if ( ! wp_verify_nonce( $_POST['elberos_commerce_photos'], plugin_basename(__FILE__) ) )
			return;

		// если это автосохранение ничего не делаем
		if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ) 
			return;

		// проверяем права юзера
		if( ! current_user_can( 'edit_post', $post_id ) )
			return;
		
		$photos = isset($_POST['product_photo']) ? $_POST['product_photo'] : [];
		\Elberos\update_post_meta_arr( $post_id, 'product_photo', $photos, 'ID' );
		
		$photos_id = array_map(function($item){ return $item["ID"]; }, $photos);
		\Elberos\update_post_meta_arr( $post_id, 'product_photo_id', $photos_id );
	}
	
	
	
	/**
	 * Save metabox
	 */
	public static function save_metabox($post_id)
	{
		static::save_products_title($post_id);
		static::save_products_catalog_title($post_id);
		static::save_categories($post_id);
		static::save_photos($post_id);
		static::save_meta_params($post_id);
	}
}
	
}