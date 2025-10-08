const defaults = function () {
    let disableEnterSubmit = function () {
        $(window).keydown(function(event){
            if(event.keyCode == 13) {
                event.preventDefault();
                return false;
            }
        });
    }
    
    let modalAjax = function () {
        $('#modal-add').on('show.bs.modal', function (e) {
            $.ajax({
                url: $(e.relatedTarget).attr('urlAdd'),
            }).done(function( data ) {
                $('#modal-add *.modal-body').html( data );
            });
        }).on('hide.bs.modal', function (e) {
            $('#modal-add *.modal-body').html( '' );
        });

        $('#modal-detail').on('hide.bs.modal', function (e) {
            $('#nav-detail').html( '' );
        });

        $('#modal-edit').on('hide.bs.modal', function (e) {
            $('#modal-edit *.modal-body').html( '' );
        });
    };

    let runSidebarActive = function () {
        let index = location.protocol == 'https:' ? 8 : 7;
        let currentUrl = location.href.substr( location.href.indexOf( '/', index ) );
        let found = false;

        $('ul.nav-sidebar > li.nav-item > a.nav-link').each(
            function () {
                if( currentUrl == $(this).attr('href') ) {
                    found = true;
                    $(this).addClass('active')
                }
            }
        );

        $('ul.nav-sidebar > li.nav-item > ul.nav-treeview > li.nav-item > a.nav-link').each(
            function () {
                if( currentUrl == $(this).attr('href') ) {
                    found = true;
                    $(this).parent().parent().parent().removeClass('menu-closed').addClass('menu-open');
                    $(this).addClass('active');
                    $('.menu-open > a.nav-link').addClass('active');
                }
            }
        );

        $('ul.nav-sidebar > li.nav-item > ul.nav-treeview > li.nav-item > ul.nav-treeview > li.nav-item > a.nav-link').each(
            function () {
                if( currentUrl == $(this).attr('href') ) {
                    found = true;
                    $(this).parent().parent().parent().parent().parent().removeClass('menu-closed').addClass('menu-open');
                    $(this).parent().parent().parent().removeClass('menu-closed').addClass('menu-open');
                    $(this).addClass('active');
                    $('.menu-open > a.nav-link').addClass('active');
                }
            }
        );

        if(found == false) {
            currentUrl = currentUrl.split('?')[0];

            $('ul.nav-sidebar > li.nav-item > a.nav-link').each(
                function () {
                    if( currentUrl == $(this).attr('href') ) {
                        $(this).addClass('active')
                    }
                }
            );

            $('ul.nav-sidebar > li.nav-item > ul.nav-treeview > li.nav-item > a.nav-link').each(
                function () {
                    if( currentUrl == $(this).attr('href') ) {
                        $(this).parent().parent().parent().removeClass('menu-closed').addClass('menu-open');
                        $(this).addClass('active');
                        $('.menu-open > a.nav-link').addClass('active');
                    }
                }
            );

            $('ul.nav-sidebar > li.nav-item > ul.nav-treeview > li.nav-item > ul.nav-treeview > li.nav-item > a.nav-link').each(
                function () {
                    if( currentUrl == $(this).attr('href') ) {
                        $(this).parent().parent().parent().parent().parent().removeClass('menu-closed').addClass('menu-open');
                        $(this).parent().parent().parent().removeClass('menu-closed').addClass('menu-open');
                        $(this).addClass('active');
                        $('.menu-open > a.nav-link').addClass('active');
                    }
                }
            );
        }
    };

    let searchActive = function () {
        var generalSearch = new Bloodhound({
            datumTokenizer: Bloodhound.tokenizers.whitespace,
            queryTokenizer: Bloodhound.tokenizers.whitespace,
            
            remote: {
                wildcard: '%QUERY',
                url: '/search/%QUERY',
            },
        });
        
        $('#mainSearch').typeahead({
            hint: true,
            highlight: true,
            minLength: 3
        },
        {
            name: 'allItems',
            source: generalSearch,
            display: 'value',
            templates: {
                notFound: 'Result not found',
                pending: 'Searching...',
                suggestion: function(data) {
                    var answer = '<div>' + data.value + ' <div style="font-size: 0.8em; display: inline;">(' + data.type + ')</div>';
                    if (typeof data.extra !== 'undefined') {
                        answer = answer + '<br/><div style="font-size: 0.7em; display: inline-block; padding-top: 3px; height: 25px; line-height: 25px; padding-top: 5px;">' + data.extra + '</div>';
                    }
                    answer = answer + '</div>';

                    return answer;
                }
            }
        });

        $('#mainSearch').bind('typeahead:select', function(ev, suggestion) {
            window.location.href = suggestion.href;
        });
    };

    let smallBoxLink = function () {
        $('.small-box').click(function () {
            window.location.href = $(this).find('a').attr('href');
        });
    };
    
    let select2ModalFix = function () {
        $.fn.modal.Constructor.prototype._enforceFocus = function() {};
    };
    
    return {
        init: function () {
            disableEnterSubmit();
            runSidebarActive();
            //searchActive();
            smallBoxLink();
            modalAjax();
            select2ModalFix();
        },
    }
}();


$(function () {
    defaults.init();
});
