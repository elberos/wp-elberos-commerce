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


namespace Elberos\Commerce\_1C;

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
		
		if ( isset($_POST["nonce"]) && (int)wp_verify_nonce($_POST["nonce"], basename(__FILE__)) > 0 )
		{
			$elberos_commerce_1c_login = isset($_POST['elberos_commerce_1c_login']) ?
				$_POST['elberos_commerce_1c_login'] : '';
			$elberos_commerce_1c_password = isset($_POST['elberos_commerce_1c_password']) ?
				$_POST['elberos_commerce_1c_password'] : '';
			
			static::update_key("elberos_commerce_1c_login", $elberos_commerce_1c_login);
			static::update_key("elberos_commerce_1c_password", $elberos_commerce_1c_password);
		}
		
		$item = 
		[
			'elberos_commerce_1c_login' => static::get_key( 'elberos_commerce_1c_login', '' ),
			'elberos_commerce_1c_password' => static::get_key( 'elberos_commerce_1c_password', '' ),
		];
		
		?>
		<div class="icon32 icon32-posts-post" id="icon-edit"><br></div>
		<h2><?php _e('QIWI P2P Settings', 'elberos-commerce')?></h2>
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
		
		<p>
			<label class='vertical-align-middle'><?php _e('URL:', 'elberos-commerce')?></label>
			<span class='vertical-align-middle'><?= esc_html( site_url("/api/1c_exchange/") ) ?></span>
		</p>
		
		<!-- Public key -->
		<p>
		    <label for="elberos_commerce_1c_login"><?php _e('Login:', 'elberos-commerce')?></label>
		<br>
            <input id="elberos_commerce_1c_login" name="elberos_commerce_1c_login" type="text" style="width: 100%"
				value="<?php echo esc_attr($item['elberos_commerce_1c_login'])?>" >
		</p>
		
		<!-- Secret key -->
		<p>
		    <label for="elberos_commerce_1c_password"><?php _e('Password:', 'elberos-commerce')?></label>
		<br>
            <input id="elberos_commerce_1c_password" name="elberos_commerce_1c_password" type="text" style="width: 100%"
				value="<?php echo esc_attr($item['elberos_commerce_1c_password'])?>" >
		</p>
		
		<?php
	}
	
}

}