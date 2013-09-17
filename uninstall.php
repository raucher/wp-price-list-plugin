<?php
$priceLists = get_posts(array(
    'post_type' => 'price_list',
));

foreach ($priceLists as $priceList) {
    wp_delete_post($priceList->ID, true);
}

delete_option('plp_is_installed');