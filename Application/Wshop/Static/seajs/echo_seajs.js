define(function (require,exports,module) {
    require('js/echo.min.js');
    echo.init({
        offset: 0,
        callback: function (element, op) {
            //console.log(element, 'has been', op + 'ed')
        }
    });
});

