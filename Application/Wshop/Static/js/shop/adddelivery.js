$(document).ready(function () {
    /*********************************************************
    * 选择模板类型
    * */
    $('input[name="template-type"]').change(function () {
        var type = $('input[name="template-type"]:checked').val();
        $(".templateType").slideUp();
        $(".template-type"+type).slideDown()
    });
    /*********************************************************
     * 选择表格
     * */
    $('input[name="delivery"]').change(function () {
        $(".postage-table").slideUp();
        $('input[name="delivery"]:checked').each(function () {
            var table = $(this).val();
            $("#postage-table"+table).slideDown()
        })
    });
    /************************************************
    * 增加指定地区
    * */
    $(".add-more-delivery").click(function () {
        var newTr='';
        newTr+=
            '<tr class="moreDelivery"> ' +
            '<td style="width: 30%">' +
                '指定地区<small class="sel-addr">选择</small><small class="del-addr">删除</small><br/>' +
                '<span class="selected-addr">未选择任何区域</span>' +
            '</td> ' +
            '<td><div class="spe-td"><input class="customer-deli js-pattern-number" type="text" placeholder="必填"/></div></td> ' +
            '<td><div class="spe-td"><input class="customer-deli js-pattern-number" type="text" placeholder="必填"/></div></td> ' +
            '<td><div class="spe-td"><input class="customer-deli js-pattern-number" type="text" placeholder="必填"/></div></td> ' +
            '<td><div class="spe-td"><input class="customer-deli js-pattern-number" type="text" placeholder="必填"/></div></td> ' +
            '</tr>';
        $(this).before(newTr)
    });
    /**********************************************
    * 指定地区选择删除
    * */
    var btnIn;//记录进来的按钮
    $(".postage-table")
          .on("click",".sel-addr", function () {
            $('#addr-popup').modal();
            btnIn = this;
            /*初始化*/
            buildAddrBox();
            $(".addr-selected-list").html("")
            $('.am-dimmer').remove();
        }).on("click",".del-addr", function () {
                var r=confirm("继续删除将无法恢复。");
                if (r==true) {
                    $(this).parents(".moreDelivery").remove()
                }
    });
    /*******************************************************************
    * 地址列表选择功能
    * */
    $(".addr-list-box").on("click",".parent-addr", function () {
        var addr = $(this);
        if(addr.hasClass("am-icon-plus-circle")){
            addr.addClass("addr-active");
            addr.siblings(".second-level-box").children("dl").removeClass("addr-active");
            addr.removeClass("am-icon-plus-circle").addClass("am-icon-minus-circle");
            addr.siblings(".second-level-box").slideDown("fast")
        }else
        if(addr.hasClass("am-icon-minus-circle")){
            addr.removeClass("addr-active");
            addr.addClass("am-icon-plus-circle").removeClass("am-icon-minus-circle");
            addr.siblings(".second-level-box").slideUp("fast")
        }else{
            addr.toggleClass("addr-active")
        }
    });
    /***********
    * 第二层地址
    * */
    $(".addr-list-box").on("click",".child-addr", function () {
        var addr = $(this);
        addr.toggleClass("addr-active");
        addr.parent().siblings(".parent-addr").removeClass("addr-active")
    });
    /*********
    * 添加地址
    * */
    $(".addr-add-btn").click(function () {
        var selectAddr='';
        $(".addr-list-box").find(".addr-active").each(function () {
            var text = $(this).children("span").text();
            var idTap;
            if($(this).is("p")){
                /*选择已经选择过城市的省时*/
                if($(this).attr("noSelect")=="true"){
                    var r=confirm("已经选择其区域内城市，继续选择将取消已选择城市。");
                    if (r==true) {
                        $(this).removeClass("addr-active").attr("noSelect",false);
                        var parentId = $(this).parent().attr("id");
                        parentId=parentId.substr(0,6);
                        $("[data-id*='"+parentId+"']").fadeOut(function () {
                            $(this).children("span").click();
                            $(this).remove()
                        })
                    }
                    else return
                }
                $(this).parent().fadeOut();
                idTap=$(this).parent().attr("id");
                selectAddr+= '<p data-id="'+idTap+'">'+text+'<span class="am-icon-times-circle added-addr"></span></p>';
            }else{
                /*dl城市*/
                $(this).fadeOut().parent().siblings(".parent-addr").attr("noSelect",true);//标记上级
                idTap=$(this).attr("id");
                var parent = $(this).parent().siblings(".parent-addr").children("span").text();
                selectAddr+= '<p data-id="'+idTap+'" data-parent="'+parent+'">'+text+'<span class="am-icon-times-circle added-addr"></span></p>';
            }
            $(this).removeClass("addr-active")
        });
        var addrBox=$(".addr-selected-list");
        addrBox.append(selectAddr);
        $("#addr-count").text(addrBox.children("p").length)
    });
    /**********
    * 删除地址
    * */
    $(".addr-selected-list").on("click",".added-addr", function () {
       var idTap = $(this).parent().attr("data-id");
        $('#'+idTap).fadeIn();
        $(this).parent().remove();
        var addrNum=$("#addr-count");
        addrNum.text(parseInt(addrNum.text())-1)
    });
    /***********
    * 确定选择
    * */
    $('#addr-popup').find('[data-am-modal-confirm]').off('click.confirm.modal.amui').on('click', function() {
        var selectedBox = $(".addr-selected-list");
        if(selectedBox.html()==""){
            updateAlert("至少选择一个区域","1000");
        }else{
            var addr ='';
            selectedBox.children("p").each(function () {
                var parent = 'no';

                if($(this).attr("data-parent")){
                    parent=$(this).attr("data-parent")
                }

                addr+= '<span data-parent="'+parent+'">'+$(this).text()+'</span>';
            });
            $(btnIn).siblings(".selected-addr").html(addr);
            $("#addr-popup").modal('close');
        }
    });
    /*******************************************
    * 模板保存按钮
    * */
    $(".template-save").click(function () {
        if( $('input[name="template-type"]:checked').val()==1)
        {
            if(!($("#addtemplate-body").find('input[name="cbx-template-type"]').find(".am-field-error").length==0)){
                updateAlert("存在填写错误的地方，请正确填写",1000);
                return
            }
        }
        else
        {
            if(!($("#addtemplate-body").find("input[name='delivery']").find(".am-field-error").length==0)){
                updateAlert("存在填写错误的地方，请正确填写",1000);
                return
            }
        }

        var title = $("#template_title").val();
        var brief = $("#template_brief").val();
        if(title.trim()==""){
            updateAlert("请输入模板名称",1000);
            return
        }
        var valuation;
        var rule={};
        var returnPoint=false;//标记
        if($('input[name="template-type"]:checked').val()==1){
            //统一模板
            valuation=0;
            var cbx =  $('input[name="cbx-template-type"]:checked');
            if(cbx.length==0){
                updateAlert("请选择运费模板种类",1000);
                return
            }
            var price = $(".price-input");
            cbx.each(function () {
                var val = parseInt($(this).val());
                //判断价格有没有空的
                if(price.eq(val).val()==""){
                    updateAlert("请填写运费价格",1000);
                    returnPoint=true;
                    return
                }
                var type = parseInt($(this).attr("data-type"));
                switch (type){
                    case 1:
                        rule.express = parseInt(price.eq(0).val()*100);//保存单位（分）
                        break;
                    case 2:
                        rule.mail = parseInt(price.eq(1).val()*100);
                        break;
                    case 3:
                        rule.ems = parseInt(price.eq(2).val()*100);
                        break;
                }
            });
        }else{
            //特殊模板
            valuation=1;
            var rad =  $('input[name="delivery"]:checked');
            if(rad.length==0){
                updateAlert("请选择运费模板种类",1000);
                return
            }
            rad.each(function () {
                var type = parseInt($(this).val());
                var table = $("#postage-table"+type);

                var nDeli = table.find(".normal-deli");
                nDeli.each(function () {
                    if($(this).val()==""){
                        updateAlert("存在没有填写的运费价格",1000);
                        returnPoint = true; //标记
                    }
                });
                var normal = {
                    "start":parseInt(table.find(".normal-deli").eq(0).val()),
                    "start_fee":parseInt(table.find(".normal-deli").eq(1).val()*100),
                    "add":parseInt(table.find(".normal-deli").eq(2).val()),
                    "add_fee":parseInt(table.find(".normal-deli").eq(3).val()*100)
                };
                var customer=[];
                var moreDeli = table.find(".moreDelivery");

                if(!(moreDeli.length==0)){
                    //遍历每一个指定地区组合，index是位置
                    moreDeli.each(function (indexdex) {
                        var province = [];
                        var addr = $(this).find(".selected-addr").children("span");
                        //判断有没有填地址
                        if(addr.length==0){
                            updateAlert("存在没有选择的指定地址",1000);
                            returnPoint = true;//标记
                            return
                        }
                        var deli = $(this).find(".customer-deli");
                        //判断有没有填价格
                        deli.each(function () {
                            if($(this).val()==""){
                                updateAlert("存在没有填写的运费价格",1000);
                                returnPoint = true; //标记
                            }
                        });
                        //遍历每一个指定地区
                        addr.each(function (index) {
                            var city = $(this).text();
                            var parent = $(this).attr("data-parent");
                            if(parent=="no"){
                                province[index] = {
                                    "province":city
                                };
                            }else{
                                province[index] = {
                                    "province":parent,
                                    "city":city
                                };
                            }
                        });
                        customer[indexdex] = {
                            "location":province,
                            "start":parseInt(deli.eq(0).val()),
                            "start_fee":parseInt(deli.eq(1).val()*100),
                            "add":parseInt(deli.eq(2).val()),
                            "add_fee":parseInt(deli.eq(3).val()*100)
                        };
                    });
                }
                switch (type){
                    case 1:
                        rule.express = {
                            "normal":normal,
                            "customer":customer
                        };
                        break;
                    case 2:
                        rule.mail = {
                            "normal":normal,
                            "customer":customer
                        };
                        break;
                    case 3:
                        rule.ems = {
                            "normal":normal,
                            "customer":customer
                        };
                        break;
                }
            });
        }
        /*存在没有或错误的信息*/
        if(returnPoint){
            return
        }
        rule=JSON.stringify(rule);
        var data = {
            "title": title,
            "brief": brief,
            "valuation":valuation,
            "rule":rule,
            "id":delivery.id
        };
        console.log(data);
        var link = "/index.php?s=admin/wshop/delivery/action/add";
        $.post(link,data, function(data,status){
            if (data.status == 1) {
                if (data.url) {
                    updateAlert(data.info + ' 页面即将自动跳转~', 'success');
                } else {
                    updateAlert(data.info, 'success');
                }
                setTimeout(function () {
                    if (data.url) {
                        location.href = data.url;
                    } else if ($(that).hasClass('no-refresh')) {
                        $('#top-alert').find('button').click();
                    } else {
                        location.reload();
                    }
                }, 3000);
            } else {
                updateAlert(data.info);
                setTimeout(function () {
                    if (data.url) {
                        location.href = data.url;
                    } else {
                        $('#top-alert').find('button').click();
                    }
                }, 15000);
            }
        });
    });
    /****************************************************************/
     /* 编辑初始化 */
    /****************************************************************/
    if(delivery.valuation=="0"){
        //统一运费
        var price = $(".price-input");
        var cbx = $('input[name="cbx-template-type"]');
        if(delivery.rule.express){
            price.eq(0).val(delivery.rule.express/100);
            cbx.eq(0).prop("checked",true)
        }
        if(delivery.rule.mail){
            price.eq(1).val(delivery.rule.mail/100);
            cbx.eq(1).prop("checked",true)
        }
        if(delivery.rule.ems){
            price.eq(2).val(delivery.rule.ems/100);
            cbx.eq(2).prop("checked",true)
        }
    }else if(delivery.valuation=="1"){
        //特殊运费
        $('input[name="template-type"]').eq(1).click();
        if(delivery.rule.express){
            $("input[name='delivery']").eq(0).click();
            var express = delivery.rule.express;
            editTable(express,1)
        }
        if(delivery.rule.mail){
            $("input[name='delivery']").eq(1).click();
            var mail = delivery.rule.mail;
            editTable(mail,2)
        }
        if(delivery.rule.ems){
            $("input[name='delivery']").eq(2).click();
            var ems = delivery.rule.ems;
            editTable(ems,3)
        }
    }
});
/*************************
* 生成地址列表功能
* */
function buildAddrBox(){
    var addrHtml ='';
    $.each(districtData, function () {
        if(!!this.callcode){
            //console.log("自治区",this.name);
            addrHtml+=
                '<div class="first-level-box" id="addr'+this.code+'"> ' +
                '<p class="parent-addr padding-left">&nbsp;<span>'+this.name+'</span></p> ' +
                '</div>';
        }else{
            //console.log("省",this.name);
            addrHtml+=
                '<div class="first-level-box" id="addr'+this.code+'"> ' +
                '<p class="am-icon-plus-circle parent-addr">&nbsp;<span>'+this.name+'</span></p> ' +
                '<div class="second-level-box"> ' ;
            var cell =this.cell;
            for (var n in cell){
                addrHtml+='<dl class="child-addr" id="addr'+cell[n].code+'">&nbsp;<span>'+cell[n].name+'</span></dl> ' ;
            }
            addrHtml+=
                '</div> ' +
                '</div>';
        }
    });
    $(".addr-list-box").html(addrHtml)
}
/********************************
* 编辑模式读取表格的数据返回表格
* */
function editTable(data,type){
    var table = $("#postage-table"+type);
    table.find(".normal-deli").eq(0).val(data.normal.start);
    table.find(".normal-deli").eq(1).val(data.normal.start_fee/100);
    table.find(".normal-deli").eq(2).val(data.normal.add);
    table.find(".normal-deli").eq(3).val(data.normal.add_fee/100);
    if(!(data.customer.length==0)){
        console.log(data.customer.length);
        for(var i=0;i<data.customer.length;i++){
            table.find(".add-more-delivery").click();
            table.find(".moreDelivery").eq(i).find(".customer-deli").eq(0).val(data.customer[i].start);
            table.find(".moreDelivery").eq(i).find(".customer-deli").eq(1).val(data.customer[i].start_fee/100);
            table.find(".moreDelivery").eq(i).find(".customer-deli").eq(2).val(data.customer[i].add);
            table.find(".moreDelivery").eq(i).find(".customer-deli").eq(3).val(data.customer[i].add_fee/100);
            var addrHtml = '';
            $.each(data.customer[i].location, function () {
                if(this.city){
                    addrHtml+='<span data-parent="'+this.province+'">'+this.city+'</span>'
                }else{
                    addrHtml+='<span data-parent="no">'+this.province+'</span>'
                }
            });
            table.find(".moreDelivery").eq(i).find(".selected-addr").html(addrHtml)
        }
    }
}
