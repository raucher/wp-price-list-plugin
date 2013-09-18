<?php
/**
 * @var $this object of the plugin main class
 * @var $metaBoxData Array of meta box data
 */
?>

<?php wp_nonce_field('plp_save_list_items', 'plp_nonce_field'); // generate WP nonce to check later ?>

<!--Output table with list item content-->
<div id="postcustomstuff">
    <table id="newmeta" width="100%">
        <thead>
            <tr>
                <th class="left"><?php _e('Item Description', 'plp-domain') ?></th>
                <th><?php _e('Item Price', 'plp-domain') ?></th>
            </tr>
        </thead>
        <?php if(is_array($metaBoxData)):
        foreach ($metaBoxData as $i => $data): ?>
            <tr class="price-list-item-wrapper">
                <td class="left" width="80%"><textarea name="price-list-item[<?php echo $i ?>][desc]"><?php echo $data['desc'] ?></textarea></td>
                <td width="20%"><input type="text" name="price-list-item[<?php echo $i ?>][price]" value="<?php echo $data['price'] ?>"></td>
            </tr>
        <?php endforeach; ?>
        <?php else: // If price list doesn't have any item yet, generate empty fields for them ?>
            <tr class="price-list-item-wrapper">
                <td class="left" width="80%"><textarea name="price-list-item[0][desc]"></textarea></td>
                <td width="20%"><input type="text" name="price-list-item[0][price]" value=""></td>
            </tr>
        <?php endif; ?>
<!-- Close main table -->
    </table>
</div>

<!-- Echo out Add/Delete buttons -->
<div style="margin-top:10px">
    <button id="add-price-list-item"><?php _e('Add Item', 'plp-domain') ?></button>
    <button id="remove-price-list-item"><?php _e('Remove Item', 'plp-domain') ?></button>
</div>