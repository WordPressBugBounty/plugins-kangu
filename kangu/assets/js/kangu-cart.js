(function ($) {
    $(document).ready(function () {
        removeDefaultWooCommerceShippingMethods();
    });
    $(document).on('submit', '.woocommerce-shipping-calculator', function(e) {

    }).ajaxComplete(function() {
        removeDefaultWooCommerceShippingMethods();
    });
    function removeDefaultWooCommerceShippingMethods()
    {
        $('.woocommerce-shipping-calculator').each(function(index, el){
            tr = $(el).closest('tr');
            isKanguWoocommerceShippingMethods = tr.hasClass('woocommerce-shipping-totals-custom shipping');
            if(!isKanguWoocommerceShippingMethods){
                tr.remove();
            }
        });
    }
})(jQuery);