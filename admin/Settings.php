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


if ( !class_exists( Settings::class ) ) 
{

class Settings
{
	
	public static function update_key($key, $value)
	{
		if ( ! is_multisite() )
		{
			if (!add_option($key, $value, "", "no"))
			{
				update_option($key, $value);
			}
		}
		else
		{
			if (!add_network_option(1, $key, $value, "", "no"))
			{
				update_network_option(1, $key, $value);
			}
		}
	}
	
	public static function get_key($key, $value)
	{
		if ( ! is_multisite() )
		{
			return get_option($key, $value);
		}
		return get_network_option(1, $key, $value);
	}
	
	public static function show()
	{
		$fields_name =
		[
			"elberos_commerce_1c_login",
			"elberos_commerce_1c_password",
			"elberos_commerce_1c_file_max_size",
			"elberos_commerce_1c_file_default_size",
			"elberos_commerce_1c_invoice_encode",
			"elberos_commerce_1c_max_task",
			"elberos_commerce_invoice_admin_email",
			"elberos_commerce_products_photos_term_id",
		];
		
		if ( isset($_POST["nonce"]) && (int)wp_verify_nonce($_POST["nonce"], basename(__FILE__)) > 0 )
		{
			foreach ($fields_name as $field_name)
			{
				$value = isset($_POST[$field_name]) ? $_POST[$field_name] : '';
				static::update_key($field_name, $value);
			}
		}
		
		$item = [];
		foreach ($fields_name as $field_name)
		{
			$item[$field_name] = static::get_key($field_name, '');
		}
		
		/* Default values */
		if ($item["elberos_commerce_1c_max_task"] == "")
		{
			$item["elberos_commerce_1c_max_task"] = 1000000;
		}
		if ($item["elberos_commerce_1c_file_max_size"] == "")
		{
			$item["elberos_commerce_1c_file_max_size"] = "12";
		}
		if ($item["elberos_commerce_1c_file_default_size"] == "")
		{
			$item["elberos_commerce_1c_file_default_size"] = "8";
		}
		
		?>
		<div class="icon32 icon32-posts-post" id="icon-edit"><br></div>
		<h2><?php _e('Настройки интернет магазина', 'elberos-commerce')?></h2>
		<div class="wrap">			
			<form id="form" method="POST">
				<input type="hidden" name="nonce" value="<?php echo wp_create_nonce(basename(__FILE__))?>"/>
				<div class="metabox-holder" id="poststuff">
					<div id="post-body">
						<div id="post-body-content">
							<div class="add_or_edit_form" style="width: 60%">
								<? static::display_form($item) ?>
							</div>
							<input type="submit" id="submit" class="button-primary" name="submit"
								value="<?php _e('Save', 'elberos-commerce')?>" >
						</div>
					</div>
				</div>
			</form>
		</div>
		<?php
	}
	
	
	
	public static function display_form($item)
	{
		?>
		
		<style>
		.vertical-align-middle{
			vertical-align: middle;
		}
		</style>
		
		<!-- Invoice admin email -->
		<p>
		    <label for="elberos_commerce_invoice_admin_email">
				<?php _e('Invoice admin email:', 'elberos-commerce')?>
			</label>
		<br>
            <input id="elberos_commerce_invoice_admin_email"
				name="elberos_commerce_invoice_admin_email" type="text" style="width: 100%"
				value="<?php echo esc_attr($item['elberos_commerce_invoice_admin_email'])?>" >
		</p>
		
		<!-- Term ID -->
		<p>
		    <label for="elberos_commerce_products_photos_term_id">
				<?php _e('Product photos term id:', 'elberos-commerce')?>
			</label>
		<br>
            <input id="elberos_commerce_products_photos_term_id"
				name="elberos_commerce_products_photos_term_id" type="text" style="width: 100%"
				value="<?php echo esc_attr($item['elberos_commerce_products_photos_term_id'])?>" >
		</p>
		
		<p>
			<label class='vertical-align-middle'><?php _e('1C URL:', 'elberos-commerce')?></label>
		<br/>
			<span class='vertical-align-middle'><?= esc_html( site_url("/api/1c_exchange/") ) ?></span>
		</p>
		
		<!-- 1C Login -->
		<p>
		    <label for="elberos_commerce_1c_login"><?php _e('1C Login:', 'elberos-commerce')?></label>
		<br>
            <input id="elberos_commerce_1c_login" name="elberos_commerce_1c_login" type="text" style="width: 100%"
				value="<?php echo esc_attr($item['elberos_commerce_1c_login'])?>" >
		</p>
		
		<!-- 1C Password -->
		<p>
		    <label for="elberos_commerce_1c_password"><?php _e('1C Password:', 'elberos-commerce')?></label>
		<br>
            <input id="elberos_commerce_1c_password" name="elberos_commerce_1c_password" type="text" style="width: 100%"
				value="<?php echo esc_attr($item['elberos_commerce_1c_password'])?>" >
		</p>
		
		<!-- 1C File Default Size -->
		<p>
		    <label for="elberos_commerce_1c_file_default_size">
				<?php _e('1C Default file size (Mb):', 'elberos-commerce')?>
			</label>
		<br>
            <input id="elberos_commerce_1c_file_default_size"
				name="elberos_commerce_1c_file_default_size" type="text" style="width: 100%"
				value="<?php echo esc_attr($item['elberos_commerce_1c_file_default_size'])?>" >
		</p>
		
		<!-- 1C File Max Size -->
		<p>
		    <label for="elberos_commerce_1c_file_max_size">
				<?php _e('1C Max file size (Mb):', 'elberos-commerce')?>
			</label>
		<br>
            <input id="elberos_commerce_1c_file_max_size"
				name="elberos_commerce_1c_file_max_size" type="text" style="width: 100%"
				value="<?php echo esc_attr($item['elberos_commerce_1c_file_max_size'])?>" >
		</p>
		
		<!-- 1C Max Task -->
		<p>
		    <label for="elberos_commerce_1c_max_task">
				<?php _e('1C Max Task:', 'elberos-commerce')?>
			</label>
		<br>
            <input id="elberos_commerce_1c_max_task"
				name="elberos_commerce_1c_max_task" type="text" style="width: 100%"
				value="<?php echo esc_attr($item['elberos_commerce_1c_max_task'])?>" >
		</p>
		
		<!-- 1C invoice -->
		<p>
		    <label for="elberos_commerce_1c_invoice_encode">
				<?php _e('1C Invoice encode:', 'elberos-commerce')?>
			</label>
		<br>
			<select id="elberos_commerce_1c_invoice_encode" name="elberos_commerce_1c_invoice_encode"
				style="width: 100%"
			>
				<option value="utf8"
					<?= \Elberos\is_value_selected($item['elberos_commerce_1c_invoice_encode'], "utf8") ?>>UTF-8
				</option>
				<option value="windows1251"
					<?= \Elberos\is_value_selected($item['elberos_commerce_1c_invoice_encode'], "windows1251") ?>>
					Windows-1251
				</option>
			</select>
		</p>
		
		
		
		<?php
	}
	
}

}