define(function (require,exports,module) {
    /*
        sku选择
    */
    require('transit');
    var sku_data = product_sku;
    var section = $('.sku-section');
    //console.log(sku_data);
    module.exports = {
        'in': function () {
            //console.log('in');
            section.show().find('.sku-icon').transition({rotate:'90deg'});
            section.nextAll('section').hide();
            $('.sku-content').show();
            setTimeout(function () {
                section.data('status','in')
            },500)
        },
        'out': function () {
            //console.log('out');
            section.find('.sku-icon').transition({rotate:'0deg'});
            section.nextAll('section').show();
            $('.sku-content').hide();
            setTimeout(function () {
                section.data('status','out')
            },500);
        }
    }
});