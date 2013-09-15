jQuery(document).ready(function($){
    console.log(plpAjaxData);
    var pageCount = plpAjaxData.totalItemCount/plpAjaxData.itemsPerPage;
    var paginationBlock = $('<ul/>', {
        'class': 'plp-ajax-pagination'
    });
    for(var i= 1; i <= pageCount; i++ ){
        $('<li/>', {
            'class': (i==1) ? 'active' : '',
            'text': i,
            'data-pagenum': i
        }).appendTo(paginationBlock);
    }
    $('.plp-price-list-block dl').after(paginationBlock);

    $('.plp-ajax-pagination li').on('click', function(event){
        var offset = plpAjaxData.itemsPerPage * ($(this).data('pagenum') - 1);
        $.post(plpAjaxData.ajaxurl, {
            'plp-pagination-offset': offset,
            'action': 'plp-ajax-pagination', // plpAjaxData.action,
            'plp-ajax-nonce' : plpAjaxData.nonce
        },function(data){
            alert(data.offset);
        }, 'json');
    });
});