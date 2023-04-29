select
`offers_prices`.`id` AS `offer_price_id`,
`offers_prices`.`offer_id` AS `offer_id`,
`offers_prices`.`price_type_id` AS `price_type_id`,
`price_types`.`code_1c` AS `price_type_code_1c`,
`price_types`.`name` AS `price_type_name`,
`offers_prices`.`name` AS `offer_price_name`,
`offers_prices`.`price` AS `price`,
`offers_prices`.`currency` AS `currency`,
`offers_prices`.`coefficient` AS `coefficient`,
`offers_prices`.`unit` AS `unit`,
`offers`.`count` AS `count`,
`offers`.`in_stock` AS `in_stock`,
`products`.`id` AS `product_id`,
`products`.`name` AS `product_name`,
`products`.`slug` AS `product_slug`,
`products`.`code_1c` AS `product_code_1c`,
`products`.`vendor_code` AS `product_vendor_code`,
`products`.`catalog_id` AS `product_catalog_id`,
`products`.`is_deleted` AS `product_is_deleted`,
`offers`.`code_1c` AS `offer_code_1c`
from `wp1_elberos_commerce_products` as `products`
left join `wp1_elberos_commerce_products_offers` as `offers`
    on (`offers`.`product_id` = `products`.`id`)
left join `wp1_elberos_commerce_products_offers_prices` as `offers_prices`
    on (`offers_prices`.`offer_id` = `offers`.`id`)
left join `wp1_elberos_commerce_price_types` as `price_types`
    on (`offers_prices`.`price_type_id` = `price_types`.`id`)
where
    `offers`.`prepare_delete` = 0 and `offers_prices`.`prepare_delete` = 0