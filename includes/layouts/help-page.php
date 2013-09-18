<?php
/**
 * @var $this object of the plugin main class
 */
?>

<div class="wrap" style="font-size: 16px;">
    <h2><?php _e('What is this and what the plugin does?', 'plp-domain') ?></h2>
    <hr>

    <p style="width: 70%;">
    <?php _e('This plugin allows you to create price lists and easily embed them to your content with shortcodes. Either several themes for price list representation are available. You can specify theme as the shortcode parameter, default is "green-circle"', 'plp-domain') ?>

    <h3 style="font-weight: normal; color: #464646;"><?php _e('Example shortcode:', 'plp-domain') ?></h3>
    <code style="font-size: 16px;">[plp-price-list list_title="Sample price list" per_page=2 theme="red-circle"]</code>
    <h3 style="font-weight: normal; color: #464646;"><?php _e('Shortcode parameters:', 'plp-domain') ?></h3>
    <table class="widefat" style="width: 70%">
        <thead>
        <tr>
            <th style="width: 25%;"><strong><?php _e('Parameter name', 'plp-domain') ?></strong></th>
            <th><strong><?php _e('Parameter description', 'plp-domain') ?></strong></th>
        </tr>
        </thead>
        <tbody>
        <tr>
            <td class="row-title" style="text-align: center; vertical-align: middle;">list_title</td>
            <td class="row-title"><?php _e('Get desired price list by title', 'plp-domain') ?></td>
        </tr>
        <tr>
            <td class="row-title" style="text-align: center; vertical-align: middle;">list_slug</td>
            <td class="row-title"><?php _e('Get the price list by slug. It is more safe way to query a price list, because you can change the price list title while editing it, but not the slug!', 'plp-domain') ?></td>
        </tr>
        <tr>
            <td class="row-title" style="text-align: center; vertical-align: middle;">list_id</td>
            <td class="row-title"><?php _e('Get the price list by id. Not very handy but you can be sure that you will get exactly the same post you want!', 'plp-domain') ?></td>
        </tr>
        <tr>
            <td class="row-title" style="text-align: center; vertical-align: middle;">per_page</td>
            <td class="row-title"><?php _e('Choose the amount of rows to display in price list. Pagination will be automatically enabled if amounts of goods exceeds the amount of rows to display per page', 'plp-domain') ?></td>
        </tr>
        <tr>
            <td class="row-title" style="text-align: center; vertical-align: middle;">theme</td>
            <td class="row-title"><?php _e('Price list visual representation. You can choose between "green-circle" and "red-circle" for this moment', 'plp-domain') ?></td>
        </tr>
        </tbody>
    </table>
</div>