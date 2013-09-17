<?php
if(!get_option('plp_is_installed'))
    return;

$samplePost = get_posts(array(
    'name' => 'plp-sample-price-list',
    'post_type' => 'price_list',
    'posts_per_page' => 1,
));
wp_delete_post($samplePost[0]->ID, true);

delete_option('plp_is_installed');