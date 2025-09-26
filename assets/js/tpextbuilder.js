(function (w) {
    w.layerOpen = function (obj, size, url) {
        var href = url || $(obj).data('url') || $(obj).attr('url') || $(obj).attr('href');

        var text = $(obj).data('title') || $(obj).attr('title') || $(obj).text();
        if ($(obj).data('layer-size')) {
            size = $(obj).data('layer-size').split(',');
        }
        var winheight = $(window).height() - 14;
        layer.open({
            type: 2,
            title: text,
            shadeClose: false,
            scrollbar: false,
            maxmin: true,
            anim: 5,    //渐显
            shade: 0.3,
            maxHeight: winheight,
            area: size || ['90%', '400'],
            offset: size && size.length > 1 & size[1] == '100%' ? '0' : '7px',
            content: href,
            success: function (layero, index) {
                if (!size || size[1] == 'auto' || size[1] == '' || size[1] == '0') {
                    var iframe = layero.find('iframe').get(0);

                    var mainheight = $(iframe.contentWindow.document.body).find('.panel-default.content').height() + 10;
                    if (mainheight < 400) {
                        mainheight = 400;
                    }
                    if (mainheight > winheight - 43) {
                        mainheight = winheight - 43;
                    }
                    $(iframe).height(mainheight);
                    //layero.css('top', ((winheight - mainheight - 43) / 2) + 'px');
                    layer.iframeAuto(index);
                }

                $(':focus').blur();
                this.enterEsc = function (event) {
                    if (event.keyCode === 13) {
                        return false; //阻止系统默认回车事件
                    }
                    if (event.keyCode === 0x1B) {
                        var index2 = layer.msg(__blang.bilder_confirm_close_this_window, {
                            time: 2000,
                            btn: [__blang.bilder_button_ok, __blang.bilder_button_cancel],
                            yes: function (params) {
                                layer.close(index);
                                layer.close(index2);
                            }
                        });
                        return false; //阻止系统默认esc事件
                    }
                };
                $(document).on('keydown', this.enterEsc);	//监听键盘事件，关闭层
            },
            end: function () {
                $(document).off('keydown', this.enterEsc);	//解除键盘关闭事件
            }
        });

        return false;
    };

})(window);

$(function () {

    $('body').on('click', '.btn-loading', function () {
        $(this).lyearloading({
            opacity: 0.2,
            spinnerSize: 'nm'
        });
    });

    $('select').each(function (i, e) {
        if ($(e).attr('readonly')) {
            setTimeout(function () {
                $(e).parent('div').find('span.select2-selection__choice__remove').first().css('display', 'none');
                $(e).parent('div').find('li.select2-search').first().css('display', 'none');
                $(e).parent('div').find('span.select2-selection__clear').first().css('display', 'none');
                $(e).parent('div').find('span.select2-selection').first().css('background-color', '#eee');
            }, 1000);
        }
    });

    $('body').on('click', '.btn-close-layer', function () {
        if (parent && parent.layer) {
            var index = parent.layer.getFrameIndex(window.name); //获取窗口索引
            parent.layer.close(index);
        }
    });

    $('body').on('click', '.layer-open,.btn-open-layer', function () {
        window.layerOpen(this);
    });

    $(".form-control.readonly").attr('readonly', 'readonly');
    $(".form-control.disabled").attr('disabled', 'disabled');
    $(".form-control.not-readonly").removeAttr('readonly');
    $(".form-control.not-disabled").removeAttr('disabled');

    $("label.readonly input").attr('readonly', 'readonly');
    $("label.disabled input").attr('disabled', 'disabled');
    $("label.not-readonly input").removeAttr('readonly');
    $("label.not-disabled input").removeAttr('disabled');
});