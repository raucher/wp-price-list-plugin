/**
 * Script to manage price list items
 */
jQuery(document).ready(function($){
    /*************************************
     * Add-Remove Price List Items
     *************************************/
    var reName = /^([\w-]+\[)(\d+)(\].+)$/i; // RegExp to generate new name
    $('#add-price-list-item').click(function(){
        var newItem = $('.price-list-item-wrapper:last').clone(); // Clone last item block
        newItem.find('input, textarea').each(function(){ // Select all inputs inside the wrapper
            $(this).attr({
                'name': $(this).attr('name').replace(reName, function(str, p1, p2, p3){
                    return p1+(++p2)+p3; // Increase item array index
                }),
                'value': '' // Clear item values
            });
        });
        newItem.insertAfter('.price-list-item-wrapper:last'); // Insert newly created item into the DOM
        return false;
    });
    $('#remove-price-list-item').click(function(){
        if($('.price-list-item-wrapper').size()>1){ // If we have more than 1 item, delete last
            $('.price-list-item-wrapper:last').remove();
        }
        return false;
    });

    /*************************************
     * Add-Remove Ingredients
     *************************************/
    $('#add-ingredient').click(function(){
        var newIngredient = $('.ingredient:last').clone();
        newIngredient.find('input').attr('value', '');
        newIngredient.insertAfter('.ingredient:last');

        return false;
    });
    $('#remove-ingredient').click(function(){
        if($('.ingredient').size()>1){ // If we have more than 1 item, delete last
            $('.ingredient:last').remove();
        }
        return false;
    });
});