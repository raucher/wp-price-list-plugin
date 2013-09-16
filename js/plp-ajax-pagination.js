function runPlpPagination(plpAjaxData, $)
{
    var pageCount = Math.ceil(plpAjaxData.totalItemCount/plpAjaxData.itemsPerPage);
    var priceListContainer = $('#' + plpAjaxData.htmlContainerId);
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

    $('dl', priceListContainer).after(paginationBlock);

    $('.plp-ajax-pagination li', priceListContainer).on('click', function(event){
        if($(this).hasClass('active'))
            return;
        var offset = plpAjaxData.itemsPerPage * ($(this).data('pagenum') - 1);
        var activeEl = $(this);
        $.post(plpAjaxData.ajaxurl, {
            'plp-pagination-offset': offset,
            'plp-items-per-page': plpAjaxData.itemsPerPage,
            'action': plpAjaxData.action,
            'plp-ajax-nonce': plpAjaxData.nonce,
            'plp-price-list-id': plpAjaxData.priceListObjectId
        },function(data){
            $('dl', priceListContainer).html(data.priceListHtml);

            $('.plp-ajax-pagination li', priceListContainer).removeClass('active');
            activeEl.addClass('active');
        }, 'json');
    });
};

jQuery(document).ready(function($){
    $(plpInitialDataContainer).each(function(i, el){
        runPlpPagination(el, $);
    });
});