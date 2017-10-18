$(document).ready(function () {

    /*show search form categories list*/
    /*$(document).on('click', '.search__form--trigger', function () {
        var list = $(this).next('.search__form--list');
        list.slideToggle('fast');
    });
    $(document).on('click', '.search__form--list li', function () {
        var listText = $(this).html(),
            listAttr = $(this).attr('data-id'),
            list = $(this).parent();
        list.slideUp('fast');
        $('.search__form--trigger .search__form--title').html(listText).attr('data-id', listAttr);
    });*/
    /*close*/

    /*header submenu*/
    $(document).on('click', '.header__nav li a', function (event) {//меню каталога
        var HeadSubmenu = $(this).next('.header__catalog'),//блок каталога
            BackgroundBlock = $(this).closest('.header').nextAll().find('.catalog__bg');//блок затемнения
        if ($(this).hasClass('show-head-submenu')) {//при наличии подменю
            $(this).removeClass('show-head-submenu');//убираем класс-индикатор
            HeadSubmenu.slideUp(400);//убираем блок каталога
            BackgroundBlock.fadeOut('400');//скрываем блок затемнения
        } else if (HeadSubmenu.length > 0){
            event.preventDefault();
            $('.header__nav li a').removeClass('show-head-submenu');//убираем у всех элементов меню класс-индикатор
            $('.header__catalog').slideUp(400);//скрываем все блоки каталогов
            $(this).addClass('show-head-submenu');//добавляем нужной ссылке класс-индикатор
            HeadSubmenu.slideDown(400);//показываем нужный блок каталога
            BackgroundBlock.fadeIn('400');//показываем блок затемнения
        }
    });
    $(document).on('click', function (e) {
        if ($(e.target).closest('.header__nav').length != 1) {
            $('.header__nav li a').removeClass('show-head-submenu');
            $('.header__catalog').slideUp(400);
            $('.catalog__bg').fadeOut('400');
        }
    });
    /*close*/

    /*cabinet submenu*/
    $(document).on('click', '.header__top-links--enter', function (event) {
        if ($(this).next('.header__top-links--cabinet-submenu').length > 0) {
            event.preventDefault();
            var submenu = $(this).next('.header__top-links--cabinet-submenu');
            $(this).toggleClass('show-cabinet-menu');
            submenu.slideToggle(400);
            return false;
        }
    });
    /*close*/

    /*mobile menu*/
    $(document).on('click', '#mobile-menu', function (event) {
        event.preventDefault();
        var menu = $(this).next('.header__nav');
        $(this).toggleClass('header__trigger--active');
        menu.slideToggle('slow');
        return false;
    });
    $(document).on('click', '#mobile-menu', function (event) {
        event.preventDefault();
        var userMenu = $(this).next('.header__cabinet-nav');
        $(this).toggleClass('header__trigger--active');
        userMenu.slideToggle('slow');
        return false;
    });
    /*close*/

    /*product slider*/
    $('.product-slider__carousel').slick({
        dots: false,
        infinite: false,
        speed: 300,
        slidesToShow: 3,
        slidesToScroll: 1,
        responsive: [
            {
                breakpoint: 1024,
                settings: {
                    slidesToShow: 3,
                    slidesToScroll: 1,
                    infinite: true,
                    dots: true
                }
            },
            {
                breakpoint: 770,
                settings: {
                    slidesToShow: 2,
                    slidesToScroll: 1
                }
            },
            {
                breakpoint: 660,
                settings: {
                    slidesToShow: 1,
                    slidesToScroll: 1,
                    arrows: false
                }
            }
        ]
    });
    /*close*/

    /*single product slider*/
    $('.product__views--slider').slick({
        slidesToShow: 1,
        slidesToScroll: 1,
        arrows: false,
        fade: true,
        asNavFor: '.product__views--carousel'
    });
    $('.product__views--carousel').slick({
        slidesToShow: 4,
        slidesToScroll: 1,
        asNavFor: '.product__views--slider',
        dots: false,
        centerMode: true,
        centerPadding: 0,
        focusOnSelect: true,
        responsive: [
            {
                breakpoint: 1024,
                settings: {
                    slidesToShow: 3,
                    slidesToScroll: 3,
                    infinite: true,
                    dots: true
                }
            },
            {
                breakpoint: 770,
                settings: {
                    slidesToShow: 4,
                    slidesToScroll: 1,
                }
            },
            {
                breakpoint: 660,
                settings: {
                    slidesToShow: 3,
                    slidesToScroll: 1
                }
            }
        ]
    });
    /*close*/

    /*product counter*/
    $(document).on('click', '.plus', function (event) {
        event.preventDefault();
        var count = $(this).closest('.product__views--counter').find('.number'),
            val = parseInt($(this).closest('.product__views--counter').find('.number').val());
        if (val == 999) {
            return false;
        } else {
            count.val(val + 1);
            $('.js-single-addtocart').attr('data-quantity', count.val());
            $('.js-single-favorites').attr('data-quantity', count.val());
        }
        return false;
    });
    $(document).on('click', '.minus', function (event) {
        event.preventDefault();
        var count = $(this).closest('.product__views--counter').find('.number');
        var counter = parseInt(count.val()) - 1;
        counter = counter < 1 ? 1 : counter;
        count.val(counter);
        count.change();
        $('.js-single-addtocart').attr('data-quantity', counter);
        $('.js-single-favorites').attr('data-quantity', counter);
        return false;
    });
    /*close*/

    /*product tabs*/
    $('.product__descr--box').each(function (i) {
        if (i != 0) {
            $(this).hide(0)
        }
    });
    $(document).on('click', '.product__descr--tabs a', function (event) {
        event.preventDefault();
        var tabId = $(this).attr('href');
        $('.product__descr--tabs a').removeClass('active');
        $(this).addClass('active');
        $('.product__descr--box').hide(0);
        $(tabId).fadeIn();
    });
    /*close*/

    /*coins tabs*/
    $('.coins__tabs-container--box').each(function (i) {
        if (i != 0) {
            $(this).hide(0)
        }
    });
    $(document).on('click', '.coins__tabs a', function (event) {
        event.preventDefault();
        var tabId = $(this).attr('href');
        $('.coins__tabs a').removeClass('active');
        $(this).addClass('active');
        $('.coins__tabs-container--box').hide(0);
        $(tabId).fadeIn();
    });
    /*close*/

    /*main page stock line*/
    if ($('.product__stock--quantity').length > 0) {
        var number = parseInt($('.product__stock--quantity-number').find('.val').html()),//находим цифру остатка товаров
            total = parseInt($('.product__stock--quantity-number:nth-last-of-type(2)').find('.val').html()),//находим сколько всего продано
            width = ((number / (number + total)) * 100);//вычисляем процент
        $('.product__stock--quantity').find('.product__stock--quantity-fillline').css({width: width + '%'});//задаем линии длину, раную количеству процентов
    }
    /*close*/

    /*main page countdown*/
    if (('#countdown').length > 0) {
        /*var date = $('#countdown').attr('data-date');*/
        $('#countdown').countdown({
            date: '16 november 2017 12:00:00',
            format: "on",
            languge: 'ru'
        });
    }
    /*close single afisha countdown*/

    /*reistration mobile forms*/
    $(document).on('click', '.regist__column .title .mobile-trigger', function () {
        var hoverbox = $(this).closest('.regist__column').find('.regist__column--wrapper');
        $(this).toggleClass('show-form');
        hoverbox.slideToggle(400);
        return false;
    });
    /*close*/

    /*show region*/
    $(document).on('click', '.city-select', function (event) {
        event.preventDefault();
        $(this).toggleClass('show-hover-region');
        $(this).next('.header__top-links--hover-region').toggle();
        return false;
    });
    $(document).on('click', '.header__top-links--hover-region .yes', function (event) {
        event.preventDefault();
        var SelectCityTrigger = $(this).closest('.header__top-links--region').find('.city-select'),
            SelectCityBox = $(this).closest('.header__top-links--hover-region');
        SelectCityTrigger.removeClass('show-hover-region');
        SelectCityBox.toggle();

    });
    /*close*/

    /*----------catalog scripts----------*/
    /*catigories submenu*/
    $(document).on('click', '.first-submenu', function (event) {//клик по меню первого уровная
        event.preventDefault();
        var selector = $(this),//элемент, по которуму кликаем
            selectorClass = $('.main-submenu');// елемент, который нужно показать
        showSubmenu(selector, selectorClass);//вызываем функцию выпадающего меню
    });
    $(document).on('click', '.second-submenu', function (event) {//клик по меню вторго уровная
        event.preventDefault();
        var selector = $(this),//элемент, по которуму кликаем
            selectorClass = $('.main-submenu__list');// елемент, который нужно показать
        showSubmenu(selector, selectorClass);//вызываем функцию выпадающего меню
    });
    $(document).on('click', '.third-submenu', function (event) {//клик по меню третьего уровная
        event.preventDefault();
        var selector = $(this),//элемент, по которуму кликаем
            selectorClass = $('.main-submenu__list--catalog');// елемент, который нужно показать
        showSubmenu(selector, selectorClass);//вызываем функцию выпадающего меню
    });
    /*close*/

    /*sidebar line filter*/
    $(function () {

        var min = parseInt($("input[name='minPrice']").val(), 10);
        var max = parseInt($("input[name='maxPrice']").val(), 10);
        var selMin = $("input[name='minPrice']").attr('selprice');
        var selMax = parseInt($("input[name='maxPrice']").attr('selprice'), 10);
        var number = 3500;

        $("#slider_price").slider({
            range: true,
            min: min,
            max: max,
            values: [selMin, selMax],
            slide: function (event, ui) {
                $("#price").val(ui.values[0]);//Поле минимального значения
                $("#price2").val(ui.values[1]); //Поле максимального значения
            },
            stop: function (event, ui) {
                $("input[name='minPrice']").val(ui.values[0]).change();
                $("input[name='maxPrice']").val(ui.values[1]).change();
            }
        });
        //Записываем значения ползунков в момент загрузки страницы
        //То есть значения по умолчанию
        $("#price").val($("#slider_price").slider("values", 0));
        $("#price2").val($("#slider_price").slider("values", 1));
    });
    $('#price').change(function () {
        var val = $(this).val();
        var obj = $(this).closest('div');
        $('#slider_price').slider("values", 0, val);
    });
    $('#price2').change(function () {
        var val1 = $(this).val();
        var obj = $(this).closest('div');
        $('#slider_price').slider("values", 1, val1);
    });
    /*close*/

    /*show more filter elements*/
    $(document).on('click', '.catalog__sidebar--show-more', function (event) {
        event.preventDefault();
        var parent = $(this).parent('.catalog__sidebar--maker'),
            titleHeight = parent.find('.subtitle').height(),
            elHeight = parent.find('.catalog__sidebar--element').height(),
            elLength = parent.find('.catalog__sidebar--element').length;
        if ($(this).hasClass('show-all')) {
            $(this).removeClass('show-all').text('+ Показать еще');
            parent.css({
                height: '270px'
            });
        } else {
            $(this).addClass('show-all').text('- Скрыть');
            parent.css({
                height: (elHeight + titleHeight) * elLength + 'px'
            });
        }
    });
    /*close*/
    /*refresh all checked items*/
    $(document).on('click', '.catalog__sidebar--maker .revers', function (event) {//при клике на кнопку "сбросить"
        event.preventDefault();
        var item = $(this).parent()//находим все лежащие за кнопкой чекбоксы
            .nextAll('.catalog__sidebar--element')
            .find('.catalog__sidebar--checkbox');
        if (item.prop('checked') == true) {// проверяем, если чекбокс отмечен
            item.removeAttr("checked");// убираем отметку
        }
    });
    /*close*/

    /*catalog slider*/
    $('.catalog__main--slider').slick({
        arrows: true,
        dots: false,
        infinite: true,
        speed: 300,
        slidesToShow: 1,
        slidesToScroll: 1,
        autoplay: true,
        autoplaySpeed: 3000,
        responsive: [
            {
                breakpoint: 1024,
                settings: {
                    slidesToShow: 1,
                    slidesToScroll: 1,
                    infinite: true,
                    dots: false
                }
            },
            {
                breakpoint: 770,
                settings: {
                    slidesToShow: 1,
                    slidesToScroll: 1
                }
            },
            {
                breakpoint: 660,
                settings: {
                    slidesToShow: 1,
                    slidesToScroll: 1,
                    arrows: false
                }
            }
        ]
    });
    /*close*/

    /*catalog layots tabs*/
    // $('.catalog__main--tabs-box').each(function (i) {
    //     if (i != 0) {
    //         $(this).hide(0)
    //     }
    // });
    // $(document).on('click', '.catalog__main--tabs a', function (event) {
    //     event.preventDefault();
    //     var tabId = $(this).attr('href');
    //     $('.catalog__main--tabs a').removeClass('active');
    //     $(this).addClass('active');
    //     $('.catalog__main--tabs-box').hide(0);
    //     $(tabId).fadeIn();
    // });
    /*close*/

    /*show mobile filter*/
    $(document).on('click', '.catalog__sidebar--trigger', function (event) {
        event.preventDefault();
        var background = $(this).closest('.catalog').find('.catalog__bg'),
            sidebar = $(this).closest('.catalog__sidebar');
        if ($(this).hasClass('show-sidebar')) {
            $(this).removeClass('show-sidebar');
            sidebar.css({left: '-270px'});
            background.fadeOut('400');
        } else {
            $(this).addClass('show-sidebar');
            sidebar.css({left: '0'});
            background.fadeIn('400');
        }
    });
    $(document).on('click', '.catalog__sidebar--submit', function (e) {
        $('.catalog__sidebar--trigger').removeClass('show-sidebar');
        $(this).closest('.catalog').find('.catalog__sidebar').css({left: '-270px'});
        $(this).closest('.catalog').find('.catalog__bg').fadeOut('400');
    });
    /*close*/

    /*catalog categories mobile*/
    $(document).on('click', '.catalog__category--trigger', function (event) {//клик по стреле рядом с заголовком категории
        event.preventDefault();
        var CatList = $(this).closest('.catalog__category').find('.catalog__category--list-box');//блок со списком ссылок
        $(this).toggleClass('show-categories');//добовляем/убираем класс сработки
        CatList.slideToggle(400);//показываем/скрываем список
        return false;
    });
    /*close*/
    /*----------close-----------*/

    /*----------modals----------*/
    /*city modal*/
    $(document).on('click', '.header__top-links--hover-region .chose', function (event) {
        event.preventDefault();
        $('#black-overlay').fadeIn(400,
            function () {
                $('#city-selection').css('display', 'block').animate({opacity: 1}, 200);
            });
    });
    $(document).on('click', '.modal-city__close, #black-overlay', function () {
        $('#city-selection').animate({opacity: 0}, 200,
            function () {
                $(this).css('display', 'none');
                $('#black-overlay').fadeOut(400);
            }
        );
    });
    /*close*/
    /*1 click buy modal*/
    $(document).on('click', '.product__views--click', function (event) {
        event.preventDefault();
        $('#black-overlay').fadeIn(400,
            function () {
                $('#one-click-buy').css('display', 'block').animate({opacity: 1}, 200);
            });
    });
    $(document).on('click', '.modal-city__close, #black-overlay', function () {
        $('#one-click-buy').animate({opacity: 0}, 200,
            function () {
                $(this).css('display', 'none');
                $('#black-overlay').fadeOut(400);
            }
        );
    });
    /*close*/
    /*offer modal*/
    $(document).on('click', '.offer-doc', function (event) {
        event.preventDefault();
        $('#black-overlay').fadeIn(400,
            function () {
                $('#offer-modal').css('display', 'block').animate({opacity: 1}, 200);
            });
    });
    $(document).on('click', '.offer-modal__close, #black-overlay', function () {
        $('#offer-modal').animate({opacity: 0}, 200,
            function () {
                $(this).css('display', 'none');
                $('#black-overlay').fadeOut(400);
            }
        );
    });
    /*close*/
    /*confirm modal*/
    $(document).on('click', '.order__single--ok', function (event) {
        event.preventDefault();
        $('#modal-confirm').css('display', 'block').animate({opacity: 1}, 200);
    });
    $(document).on('click', '.modal-confirm__no, .modal-confirm__yes', function () {
        event.preventDefault();
        $('#modal-confirm').animate({opacity: 0}, 200).css('display', 'none');
    });
    /*close*/
    /*start claim modal*/
    $(document).on('click', '.order__single--return', function (event) {
        event.preventDefault();
        $('#black-overlay').fadeIn(400,
            function () {
                $('#modal-start-claim').css('display', 'block').animate({opacity: 1}, 200);
            });
    });
    $(document).on('click', '.modal-confirm__close, .modal-confirm__no, #black-overlay', function () {
        $('#modal-start-claim').animate({opacity: 0}, 200,
            function () {
                $(this).css('display', 'none');
                $('#black-overlay').fadeOut(400);
            }
        );
    });
    /*close*/
    /*send ms to admin*/
    $(document).on('click', '.about-error', function (event) {
        event.preventDefault();
        $('#black-overlay').fadeIn(400,
            function () {
                $('#modal-to-admin').css('display', 'block').animate({opacity: 1}, 200);
            });
    });
    $(document).on('click', '.modal-confirm__close, #black-overlay', function () {
        $('#modal-to-admin').animate({opacity: 0}, 200,
            function () {
                $(this).css('display', 'none');
                $('#black-overlay').fadeOut(400);
            }
        );
    });
    /*close*/
    /*modal-feedback open*/
    $(document).on('click', '.cabinet__edit--reviews-edit', function (event) {
        event.preventDefault();
        var parent = $(this).parent().next('.modal-feedback');

        parent.css('display', 'block').animate({opacity: 1}, 200);
    });
    $(document).on('click', '.modal-confirm__no, .modal-confirm__yes', function (event) {

      event.preventDefault();
      var parent = $(this).parent();
      parent.css('display', 'none').animate({opacity: 1}, 200);

    });
    /*close*/
    /*----------close-----------*/

    /*rating*/
    $(document).on('click', '.js-review-rating', function () {
        var selectedCssClass = 'rating__fil-star',
            prevStars = $(this).prevAll('.js-review-rating'),
            nextStars = $(this).nextAll('.js-review-rating');

        prevStars.addClass(selectedCssClass);
        nextStars.removeClass(selectedCssClass);
        $(this).addClass(selectedCssClass).parent().addClass('vote-cast');

    });
    /*close*/

    /*selected-message*/
    $(document).on('click', '.cabinet__content--forcheck', function () {
        var row = $(this).closest('.cabinet__content--row');
        if ($(this).hasClass('focus')){
            $(this).removeClass('focus');
            row.removeClass('selected-message');
        } else {
            $(this).addClass('focus');
            row.addClass('selected-message');
        }
    });
    /*close*/

    /*cabinet-sidebar*/
    $(document).on('click', '.cabinet__sidebar--button', function (event) {
        event.preventDefault();
        $(this).toggleClass('show-menu');
        $(this).next('.cabinet__sidebar').slideToggle(500);
        return false;
    });
    /*close*/

    /*datetimepicker*/
    if ($('.datepicker-inner').length > 0) {
      $('.datepicker-inner').datetimepicker({
       timepicker:false,
       todayButton: false,
       format:'d/m/Y',
       defaultTime:'00:00'
      });
      jQuery.datetimepicker.setLocale('ru');
    }
    /*close*/

    /*select-fon*/
    $(document).on('click', '.cabinet__shops--select-fon', function (event) {
        event.preventDefault();
        $('.cabinet__shops--fons').slideToggle(500);
        return false;
    });
    /*close*/
    /*document modal*/
    $(document).on('click', '.document-modal__open', function (event) {
        event.preventDefault();
        $('#document-modal').css('display', 'block').animate({opacity: 1}, 200);
    });
    $(document).on('click', '.modal-confirm__no, .modal-confirm__yes, .modal-confirm__close', function () {
        event.preventDefault();
        $('#document-modal').animate({opacity: 0}, 200).css('display', 'none');
    });
    /*close*/
});

function showSubmenu(selector, selectorClass) {//функция выпадающего списка
    var CatList = selector.next(selectorClass);//находим необходимый список
    selector.toggleClass('main-submenu-active');//добавляем/удаляем активный класс
    CatList.slideToggle(400);//показываем/скрываем список
    return false;
}
