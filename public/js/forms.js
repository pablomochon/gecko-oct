$(function () {
    $('.toggle-view-edit').click(function() {
        var containerTo = $(this).attr('containerTo');
        $.ajax({
            url: $(this).attr('addressTo'),
        }).done(function( data ) {
            $('#' + containerTo).html( data );
        });
    });

    $('.single-select2').each(function (i, obj) {
        if (typeof $(obj).attr('required') === 'undefined') {
            var required = true;
        } else {
            var required = false;
        }
        if (!$(obj).hasClass("select2-hidden-accessible")) {
            $(obj).select2({
                width: '100%',
                theme: 'bootstrap4',
                allowClear: required,
                placeholder: $(obj).attr('placeholder'),
            });
        }
    });
    
    $(".summernote").summernote({
        toolbar: [
            ['style', ['style']],
            ['font', ['bold', 'italic', 'underline', 'strikethrough', 'clear']],
            ['fontname', ['fontname', 'fontsize']],
            ['color', ['color']],
            ['para', ['ul', 'ol', 'hr', 'paragraph']],
            ['table', ['table']],
            ['view', ['fullscreen', 'codeview', 'undo', 'redo']],
          ]
    });

    $('.single-select2-ajax').each(function (i, obj) {
        if (typeof $(obj).attr('required') === 'undefined') {
            var required = true;
        } else {
            var required = false;
        }
        if (!$(obj).hasClass("select2-hidden-accessible")) {
            $(obj).select2({
                allowClear: required,
                placeholder: $(obj).attr('placeholder'),
                width: '100%',
                theme: 'bootstrap4',
                ajax: {
                    url: $(obj).attr('ajaxEndpoint'),
                    dataType: 'json',
                    delay: 250,
                    data: function (params) {
                        var query = {
                            search: params.term,
                            page: params.page || 1
                        }
                    
                        // Query parameters will be ?search=[term]&page=[page]
                        return query;
                    }
                }
            });
        }
    });
});