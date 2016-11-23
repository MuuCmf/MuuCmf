/**
 * 公共函数js文件
 */
function is_login() {
    return parseInt(MID);
}
/**
 * 模拟U函数
 * @param url
 * @param params
 * @returns {string}
 * @constructor
 */
function U(url, params, rewrite) {


    if (window.Think.MODEL[0] == 2) {

        var website = _ROOT_ + '/';
        url = url.split('/');

        if (url[0] == '' || url[0] == '@')
            url[0] = APPNAME;
        if (!url[1])
            url[1] = 'Index';
        if (!url[2])
            url[2] = 'index';
        website = website + '' + url[0] + '/' + url[1] + '/' + url[2];

        if (params) {
            params = params.join('/');
            website = website + '/' + params;
        }
        if (!rewrite) {
            website = website + '.html';
        }

    } else {
        var website = _ROOT_ + '/index.php';
        url = url.split('/');
        if (url[0] == '' || url[0] == '@')
            url[0] = APPNAME;
        if (!url[1])
            url[1] = 'Index';
        if (!url[2])
            url[2] = 'index';
        website = website + '?s=/' + url[0] + '/' + url[1] + '/' + url[2];
        if (params) {
            params = params.join('/');
            website = website + '/' + params;
        }
        if (!rewrite) {
            website = website + '.html';
        }
    }

    if (typeof (window.Think.MODEL[1]) != 'undefined') {
        website = website.toLowerCase();
    }
    return website;
}
/**播放背景音乐
 *
 * @param file 文件路径
 */
function playsound(file) {
    if (window.Think.ROOT == '') {
        file = '/' + file;
    } else {
        file = window.Think.ROOT + '/' + file;
    }
    $('embed').remove();
    $('body').append('<embed src="' + file + '" autostart="true" hidden="true" loop="false">');
    var div = document.getElementById('music');
    div.src = file;
}

/**
 * 友好时间
 * @param sTime
 * @param cTime
 * @returns {string}
 */
function friendlyDate(sTime, cTime) {
    var formatTime = function (num) {
        return (num < 10) ? '0' + num : num;
    };

    if (!sTime) {
        return '';
    }

    var cDate = new Date(cTime * 1000);
    var sDate = new Date(sTime * 1000);
    var dTime = cTime - sTime;
    var dDay = parseInt(cDate.getDate()) - parseInt(sDate.getDate());
    var dMonth = parseInt(cDate.getMonth() + 1) - parseInt(sDate.getMonth() + 1);
    var dYear = parseInt(cDate.getFullYear()) - parseInt(sDate.getFullYear());

    if (dTime < 60) {
        if (dTime < 10) {
            return '刚刚';
        } else {
            return parseInt(Math.floor(dTime / 10) * 10) + '秒前';
        }
    } else if (dTime < 3600) {
        return parseInt(Math.floor(dTime / 60)) + '分钟前';
    } else if (dYear === 0 && dMonth === 0 && dDay === 0) {
        return '今天' + formatTime(sDate.getHours()) + ':' + formatTime(sDate.getMinutes());
    } else if (dYear === 0) {
        return formatTime(sDate.getMonth() + 1) + '月' + formatTime(sDate.getDate()) + '日 ' + formatTime(sDate.getHours()) + ':' + formatTime(sDate.getMinutes());
    } else {
        return sDate.getFullYear() + '-' + formatTime(sDate.getMonth() + 1) + '-' + formatTime(sDate.getDate()) + ' ' + formatTime(sDate.getHours()) + ':' + formatTime(sDate.getMinutes());
    }
}
/**
 * Ajax系列
 */

/**
 * 处理ajax返回结果
 */
function handleAjax(a) {
    //如果需要跳转的话，消息的末尾附上即将跳转字样
    if (a.url) {
        a.info += '，页面即将跳转～';
    }

    //弹出提示消息
    if (a.status) {
        toast.success(a.info, '温馨提示');
    } else {
        toast.error(a.info, '温馨提示');
    }

    //需要跳转的话就跳转
    var interval = 1500;
    if (a.url == "refresh") {
        setTimeout(function () {
            location.href = location.href;
        }, interval);
    } else if (a.url) {
        setTimeout(function () {
            location.href = a.url;
        }, interval);
    }
}

/** 绑定发送私信事件**/
function iMessage() {
    $("#iMessageAjaxPost").unbind('click');
    $("#iMessageAjaxPost").click(function () {

        var $this = $(this);
        $this.text("发送中...");

        var to_uid = $("input[name$='iMessageUid']").val();
        var content = $("#iMessageTxt").val();

        $.post(U('Ucenter/Message/postiMessage'), {iMessageUid: to_uid,iMessageTxt: content}, function (msg) {
            if (msg.status) {
                toast.success(msg.info, '发送成功');
                $this.text("发送完成");

                //隐藏对话框
            } else {
                toast.error(msg.info, '发送失败');
                $this.text("发送");
            }
        }, 'json');
    })
}

/**
 * 绑定消息检查
 */
function bindMessageChecker() {
    $hint_count = $('#nav_hint_count');
    $nav_bandage_count = $('#nav_bandage_count');
    if (Config.GET_INFORMATION) {
        setInterval(function () {
            checkMessage();
        }, Config.GET_INFORMATION_INTERNAL);
    }
}

function play_bubble_sound() {
    playsound('./Public/js/ext/toastr/message.wav');
}
function paly_ios_sound() {
    playsound('./Public/js/ext/toastr/tip.mp3');
}
/**
 * 检查是否有新的消息
 */
function checkMessage() {
    $.get(U('Ucenter/Public/getInformation'), {}, function (msg) {
        if (msg.messages) {
            paly_ios_sound();
            var message = msg['messages'];
            for (var index in msg.messages) {
                if(message[index]['content']['untoastr']===undefined||message[index]['content']['untoastr']!=1){
                    tip_message(message[index]['content']['content'] + '<div style="text-align: right"> ' + message[index]['ctime'] + '</div>', message[index]['content']['title']);
                }
            }
        }

        $('[data-role="now-message-num"]').html(msg.message_count);
        if(msg.message_count==0){
            $('[data-role="now-message-num"]').hide();
        }else{
            $('[data-role="now-message-num"]').show();
        }

    }, 'json');

}

/**
 * 消息中心提示有新的消息
 * @param text
 * @param title
 */
function tip_message(text, title) {
    toast.info(text);
}