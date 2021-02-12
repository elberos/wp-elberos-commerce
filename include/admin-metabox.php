<?php
/*!
 *  Elberos Commerce
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
	 * Title
	 */
	public static function show_title($post, $meta)
	{
		// Используем nonce для верификации
		wp_nonce_field( plugin_basename(__FILE__), 'elberos_commerce_title' );
		
		$langs = \Elberos\wp_langs();
		
		?>
		<div class="elberos-commerce">
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
				$item_text = null;
				/*
				foreach ($item['text'] as $arr)
				{
					if ($arr['locale'] == $lang['locale'])
					{
						$item_text = $arr;
						break;
					}
				}
				*/
				
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
								name="text[name][<?= esc_attr($lang['locale']) ?>]"
								type="text" style="width: 100%"
								value="<?php echo esc_attr($text_name)?>" >
						</p>
						
						<p>
							<label for="description[<?= esc_attr($lang['locale']) ?>]">
								<?php _e('Описание', 'elberos-commerce')?> (<?= esc_attr($lang['name']) ?>):
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
		</div>
		<?php
	}
	
	public static function save_title($post_id)
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
		
	}
	
	
	
	/**
	 * Categories
	 */
	public static function show_categories($post, $meta)
	{
		// Используем nonce для верификации
		wp_nonce_field( plugin_basename(__FILE__), 'elberos_commerce_categories' );
		
		// Get products categories
		$products_catalog = get_post_meta( $post->ID, 'products_catalog', '' );
		
		global $wpdb;
		$sql = $wpdb->prepare
		(
			"SELECT * FROM {$wpdb->prefix}posts WHERE post_type = 'catalog' and post_status='publish'", []
		);
		$categories = $wpdb->get_results($sql, ARRAY_A);
		?>
		
		<div class='elberos-commerce' style='padding-top: 10px;'>
			
			<div class='product_categories'>
				<?php 
					if (gettype($products_catalog) == 'array') foreach ($products_catalog as $cat_id)
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
							<input type='hidden' name='products_catalog[<?= esc_attr($find_category['ID']) ?>]'
								value='<?= esc_attr($find_category['ID']) ?>'>
						</div>
						
						<?php 
						}
					}
				?>
			</div>
			
			
			<select class='product_select_category' style="width: 100%">
				<option value="">Выберите категорию</option>
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
							.attr('name', 'products_catalog[' + value + ']')
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
		
		$products_catalog = isset($_POST['products_catalog']) ? $_POST['products_catalog'] : [];
		$categories = array_values($products_catalog);
		\Elberos\update_post_meta_arr( $post_id, 'products_catalog', $categories );
	}
	
	
	
	/**
	 * Meta params
	 */
	public static function show_meta_params($post, $meta)
	{
		// Используем nonce для верификации
		wp_nonce_field( plugin_basename(__FILE__), 'elberos_commerce_meta_params' );
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
		
	}
	
	
	
	/**
	 * Photos
	 */
	public static function show_photos($post, $meta)
	{
		// Используем nonce для верификации
		wp_nonce_field( plugin_basename(__FILE__), 'elberos_commerce_photos' );
		
		// Get products photos
		$products_photos = get_post_meta( $post->ID, 'products_photos', '' );
		?>
		<div class='elberos-commerce'>
			<input type='button' class='button add-photo-button' value='Добавить фото'>
			
			<div class='product_photos'>
			<?php
			if (gettype($products_photos) == 'array') foreach ($products_photos as $photo)
			{
				$photo = @json_decode($photo, true);
				if (!isset($photo['ID'])) continue;
				$image = wp_get_attachment_image_src($photo['ID'], 'thumbnail');
				$post = get_post( $photo['ID'] );
				$href = $image[0] . "?_=" . strtotime($post->post_modified_gmt);
				?>
				<div class='product_photo' data-id='<?= esc_attr($post->ID) ?>'>
					<img src='<?= esc_attr($href) ?>' />
					<span class="dashicons dashicons-no-alt button-delete" data-id='<?= esc_attr($post->ID) ?>'></span>
					<input type='hidden' name='products_photos[<?= esc_attr($post->ID) ?>][ID]'
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
								.attr('name', 'products_photos[' + photo.id + '][ID]')
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
		
		$photos = isset($_POST['products_photos']) ? $_POST['products_photos'] : [];
		\Elberos\update_post_meta_arr( $post_id, 'products_photos', $photos, 'ID' );
	}
	
	
	
	/**
	 * Save metabox
	 */
	public static function save_metabox($post_id)
	{
		static::save_title($post_id);
		static::save_categories($post_id);
		static::save_photos($post_id);
		static::save_meta_params($post_id);
	}
}
	
}