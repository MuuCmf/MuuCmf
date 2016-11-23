define(function (require,exports,module) {
    require('js/district-all.js');
    var first_select = $('.select-address.first-select');
    var second_select = $('.select-address.second-select');
    var third_select = $('.select-address.third-select');

    var first = buildOption(districtData);
    first_select.html(first).change(function () {
        var index = $(this).val();
        if(districtData[index]&&(districtData[index]['callcode'])) {
            second_select.parents('.select-section').hide();//
            var third = buildOption(districtData[index]['cell']);
            third_select.html(third)
        }
        else{
            second_select.parents('.select-section').show();
            var second = buildOption(districtData[index]['cell']);
            second_select.html(second)
        }
    });

    second_select.change(function () {
        var first_index = first_select.val();
        var second_index = second_select.val();
        if(districtData[first_index]['cell'][second_index]['cell']==undefined)
        {
            third_select.parents('.select-section').hide();//
        }
        else{
            third_select.parents('.select-section').show();//
            var third = buildOption(districtData[first_index]['cell'][second_index]['cell']);
            third_select.html(third)
        }
    });

    function buildOption(data){
        var option = '<option data-code="">请选择</option>';
        $.each(data, function (index) {
            option+= '<option data-code="'+this.code+'" value="'+index+'">'+this.name+'</option>';
        });
        return option
    }

});