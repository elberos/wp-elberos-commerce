ALTER TABLE `wp1_elberos_commerce_products_photos`
CHANGE `product_id` `product_id` bigint(20) NULL FIRST;


ALTER TABLE `wp1_elberos_commerce_catalogs`
ADD FOREIGN KEY (`classifier_id`) REFERENCES `wp1_elberos_commerce_classifiers` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE

ALTER TABLE `wp1_elberos_commerce_params`
ADD FOREIGN KEY (`classifier_id`) REFERENCES `wp1_elberos_commerce_classifiers` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE

ALTER TABLE `wp1_elberos_commerce_params_values`
ADD FOREIGN KEY (`param_id`) REFERENCES `wp1_elberos_commerce_params` (`id`) ON DELETE CASCADE ON UPDATE CASCADE

ALTER TABLE `wp1_elberos_commerce_price_types`
ADD FOREIGN KEY (`classifier_id`) REFERENCES `wp1_elberos_commerce_classifiers` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE

ALTER TABLE `wp1_elberos_commerce_products`
ADD FOREIGN KEY (`catalog_id`) REFERENCES `wp1_elberos_commerce_catalogs` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE

ALTER TABLE `wp1_elberos_commerce_products_categories`
ADD FOREIGN KEY (`product_id`) REFERENCES `wp1_elberos_commerce_products` (`id`) ON DELETE CASCADE ON UPDATE CASCADE

ALTER TABLE `wp1_elberos_commerce_products_categories`
ADD FOREIGN KEY (`category_id`) REFERENCES `wp1_elberos_commerce_categories` (`id`) ON DELETE CASCADE ON UPDATE CASCADE

ALTER TABLE `wp1_elberos_commerce_products_offers`
ADD FOREIGN KEY (`product_id`) REFERENCES `wp1_elberos_commerce_products` (`id`) ON DELETE CASCADE ON UPDATE CASCADE

ALTER TABLE `wp1_elberos_commerce_products_offers_prices`
ADD FOREIGN KEY (`offer_id`) REFERENCES `wp1_elberos_commerce_products_offers` (`id`) ON DELETE CASCADE ON UPDATE CASCADE

ALTER TABLE `wp1_elberos_commerce_products_offers_prices`
ADD FOREIGN KEY (`price_type_id`) REFERENCES `wp1_elberos_commerce_price_types` (`id`) ON DELETE CASCADE ON UPDATE CASCADE

ALTER TABLE `wp1_elberos_commerce_products_params`
ADD FOREIGN KEY (`product_id`) REFERENCES `wp1_elberos_commerce_products` (`id`) ON DELETE CASCADE ON UPDATE CASCADE

ALTER TABLE `wp1_elberos_commerce_products_params`
ADD FOREIGN KEY (`param_id`) REFERENCES `wp1_elberos_commerce_params` (`id`) ON DELETE CASCADE ON UPDATE CASCADE

ALTER TABLE `wp1_elberos_commerce_products_params`
ADD FOREIGN KEY (`param_value_id`) REFERENCES `wp1_elberos_commerce_params_values` (`id`) ON DELETE CASCADE ON UPDATE CASCADE

ALTER TABLE `wp1_elberos_commerce_products_photos`
ADD FOREIGN KEY (`product_id`) REFERENCES `wp1_elberos_commerce_products` (`id`) ON DELETE SET NULL ON UPDATE CASCADE

ALTER TABLE `wp1_elberos_commerce_products_relative`
ADD FOREIGN KEY (`product_id`) REFERENCES `wp1_elberos_commerce_products` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
