function showSubmenu(e,t){var a=e.next(t);return e.toggleClass("main-submenu-active"),a.slideToggle(400),!1}$(document).ready(function(){if($(document).on("click",".search__form--trigger",function(){$(this).next(".search__form--list").slideToggle("fast")}),$(document).on("click",".search__form--list li",function(){var e=$(this).html(),t=$(this).attr("data-id");$(this).parent().slideUp("fast"),$(".search__form--trigger .search__form--title").html(e).attr("data-id",t)}),$(document).on("click",".header__nav li",function(e){e.preventDefault();var t=$(this).find(".header__catalog"),a=$(this).closest(".header").nextAll().find(".catalog__bg");$(this).hasClass("show-head-submenu")&&t.length>0?($(this).removeClass("show-head-submenu"),t.slideUp(400),a.fadeOut("400")):($(".header__nav li").removeClass("show-head-submenu"),$(".header__catalog").slideUp(400),$(this).addClass("show-head-submenu"),t.slideDown(400),a.fadeIn("400"))}),$(document).on("click",function(e){1!=$(e.target).closest(".header__nav").length&&($(".header__nav li").removeClass("show-head-submenu"),$(".header__catalog").slideUp(400),$(".catalog__bg").fadeOut("400"))}),$(document).on("click",".header__top-links--enter",function(e){if($(this).next(".header__top-links--cabinet-submenu").length>0){e.preventDefault();var t=$(this).next(".header__top-links--cabinet-submenu");return $(this).toggleClass("show-cabinet-menu"),t.slideToggle(400),!1}}),$(document).on("click","#mobile-menu",function(e){e.preventDefault();var t=$(this).next(".header__nav");return $(this).toggleClass("header__trigger--active"),t.slideToggle("slow"),!1}),$(document).on("click","#mobile-menu",function(e){e.preventDefault();var t=$(this).next(".header__cabinet-nav");return $(this).toggleClass("header__trigger--active"),t.slideToggle("slow"),!1}),$(".product-slider__carousel").slick({dots:!1,infinite:!1,speed:300,slidesToShow:3,slidesToScroll:1,responsive:[{breakpoint:1024,settings:{slidesToShow:3,slidesToScroll:1,infinite:!0,dots:!0}},{breakpoint:770,settings:{slidesToShow:2,slidesToScroll:1}},{breakpoint:660,settings:{slidesToShow:1,slidesToScroll:1,arrows:!1}}]}),$(".product__views--slider").slick({slidesToShow:1,slidesToScroll:1,arrows:!1,fade:!0,asNavFor:".product__views--carousel"}),$(".product__views--carousel").slick({slidesToShow:4,slidesToScroll:1,asNavFor:".product__views--slider",dots:!1,centerMode:!0,centerPadding:0,focusOnSelect:!0,responsive:[{breakpoint:1024,settings:{slidesToShow:3,slidesToScroll:3,infinite:!0,dots:!0}},{breakpoint:770,settings:{slidesToShow:4,slidesToScroll:1}},{breakpoint:660,settings:{slidesToShow:3,slidesToScroll:1}}]}),$(document).on("click",".plus",function(e){e.preventDefault();var t=$(this).closest(".product__views--counter").find(".number"),a=parseInt($(this).closest(".product__views--counter").find(".number").val());return 999!=a&&(t.val(a+1),$(".js-single-addtocart").attr("data-quantity",t.val()),$(".js-single-favorites").attr("data-quantity",t.val()),!1)}),$(document).on("click",".minus",function(e){e.preventDefault();var t=$(this).closest(".product__views--counter").find(".number"),a=parseInt(t.val())-1;return a=a<1?1:a,t.val(a),t.change(),$(".js-single-addtocart").attr("data-quantity",a),$(".js-single-favorites").attr("data-quantity",a),!1}),$(".product__descr--box").each(function(e){0!=e&&$(this).hide(0)}),$(document).on("click",".product__descr--tabs a",function(e){e.preventDefault();var t=$(this).attr("href");$(".product__descr--tabs a").removeClass("active"),$(this).addClass("active"),$(".product__descr--box").hide(0),$(t).fadeIn()}),$(".coins__tabs-container--box").each(function(e){0!=e&&$(this).hide(0)}),$(document).on("click",".coins__tabs a",function(e){e.preventDefault();var t=$(this).attr("href");$(".coins__tabs a").removeClass("active"),$(this).addClass("active"),$(".coins__tabs-container--box").hide(0),$(t).fadeIn()}),$(".product__stock--quantity").length>0){var e=parseInt($(".product__stock--quantity-number").find(".val").html()),t=parseInt($(".product__stock--quantity-number:nth-last-of-type(2)").find(".val").html()),a=e/(e+t)*100;$(".product__stock--quantity").find(".product__stock--quantity-fillline").css({width:a+"%"})}"#countdown".length>0&&$("#countdown").countdown({date:"16 november 2017 12:00:00",format:"on",languge:"ru"}),$(document).on("click",".regist__column .title .mobile-trigger",function(){var e=$(this).closest(".regist__column").find(".regist__column--wrapper");return $(this).toggleClass("show-form"),e.slideToggle(400),!1}),$(document).on("click",".city-select",function(e){return e.preventDefault(),$(this).toggleClass("show-hover-region"),$(this).next(".header__top-links--hover-region").toggle(),!1}),$(document).on("click",".header__top-links--hover-region .yes",function(e){e.preventDefault();var t=$(this).closest(".header__top-links--region").find(".city-select"),a=$(this).closest(".header__top-links--hover-region");t.removeClass("show-hover-region"),a.toggle()}),$(document).on("click",".first-submenu",function(e){e.preventDefault(),showSubmenu($(this),$(".main-submenu"))}),$(document).on("click",".second-submenu",function(e){e.preventDefault(),showSubmenu($(this),$(".main-submenu__list"))}),$(document).on("click",".third-submenu",function(e){e.preventDefault(),showSubmenu($(this),$(".main-submenu__list--catalog"))}),$(function(){var e=parseInt($("input[name='minPrice']").val(),10),t=parseInt($("input[name='maxPrice']").val(),10),a=$("input[name='minPrice']").attr("selprice"),s=parseInt($("input[name='maxPrice']").attr("selprice"),10);$("#slider_price").slider({range:!0,min:e,max:t,values:[a,s],slide:function(e,t){$("#price").val(t.values[0]),$("#price2").val(t.values[1])},stop:function(e,t){$("input[name='minPrice']").val(t.values[0]).change(),$("input[name='maxPrice']").val(t.values[1]).change()}}),$("#price").val($("#slider_price").slider("values",0)),$("#price2").val($("#slider_price").slider("values",1))}),$("#price").change(function(){var e=$(this).val();$(this).closest("div");$("#slider_price").slider("values",0,e)}),$("#price2").change(function(){var e=$(this).val();$(this).closest("div");$("#slider_price").slider("values",1,e)}),$(document).on("click",".catalog__sidebar--show-more",function(e){e.preventDefault();var t=$(this).parent(".catalog__sidebar--maker"),a=t.find(".subtitle").height(),s=t.find(".catalog__sidebar--element").height(),i=t.find(".catalog__sidebar--element").length;$(this).hasClass("show-all")?($(this).removeClass("show-all").text("+ Показать еще"),t.css({height:"270px"})):($(this).addClass("show-all").text("- Скрыть"),t.css({height:(s+a)*i+"px"}))}),$(document).on("click",".catalog__sidebar--maker .revers",function(e){e.preventDefault();var t=$(this).parent().nextAll(".catalog__sidebar--element").find(".catalog__sidebar--checkbox");1==t.prop("checked")&&t.removeAttr("checked")}),$(".catalog__main--slider").slick({arrows:!0,dots:!1,infinite:!0,speed:300,slidesToShow:1,slidesToScroll:1,autoplay:!0,autoplaySpeed:3e3,responsive:[{breakpoint:1024,settings:{slidesToShow:1,slidesToScroll:1,infinite:!0,dots:!1}},{breakpoint:770,settings:{slidesToShow:1,slidesToScroll:1}},{breakpoint:660,settings:{slidesToShow:1,slidesToScroll:1,arrows:!1}}]}),$(".catalog__main--tabs-box").each(function(e){0!=e&&$(this).hide(0)}),$(document).on("click",".catalog__main--tabs a",function(e){e.preventDefault();var t=$(this).attr("href");$(".catalog__main--tabs a").removeClass("active"),$(this).addClass("active"),$(".catalog__main--tabs-box").hide(0),$(t).fadeIn()}),$(document).on("click",".catalog__sidebar--trigger",function(e){e.preventDefault();var t=$(this).closest(".catalog").find(".catalog__bg"),a=$(this).closest(".catalog__sidebar");$(this).hasClass("show-sidebar")?($(this).removeClass("show-sidebar"),a.css({left:"-270px"}),t.fadeOut("400")):($(this).addClass("show-sidebar"),a.css({left:"0"}),t.fadeIn("400"))}),$(document).on("click",".catalog__sidebar--submit",function(e){$(".catalog__sidebar--trigger").removeClass("show-sidebar"),$(this).closest(".catalog").find(".catalog__sidebar").css({left:"-270px"}),$(this).closest(".catalog").find(".catalog__bg").fadeOut("400")}),$(document).on("click",".catalog__category--trigger",function(e){e.preventDefault();var t=$(this).closest(".catalog__category").find(".catalog__category--list-box");return $(this).toggleClass("show-categories"),t.slideToggle(400),!1}),$(document).on("click",".header__top-links--hover-region .chose",function(e){e.preventDefault(),$("#black-overlay").fadeIn(400,function(){$("#city-selection").css("display","block").animate({opacity:1},200)})}),$(document).on("click",".modal-city__close, #black-overlay",function(){$("#city-selection").animate({opacity:0},200,function(){$(this).css("display","none"),$("#black-overlay").fadeOut(400)})}),$(document).on("click",".product__views--click",function(e){e.preventDefault(),$("#black-overlay").fadeIn(400,function(){$("#one-click-buy").css("display","block").animate({opacity:1},200)})}),$(document).on("click",".modal-city__close, #black-overlay",function(){$("#one-click-buy").animate({opacity:0},200,function(){$(this).css("display","none"),$("#black-overlay").fadeOut(400)})}),$(document).on("click",".offer-doc",function(e){e.preventDefault(),$("#black-overlay").fadeIn(400,function(){$("#offer-modal").css("display","block").animate({opacity:1},200)})}),$(document).on("click",".offer-modal__close, #black-overlay",function(){$("#offer-modal").animate({opacity:0},200,function(){$(this).css("display","none"),$("#black-overlay").fadeOut(400)})}),$(document).on("click",".order__single--ok",function(e){e.preventDefault(),$("#modal-confirm").css("display","block").animate({opacity:1},200)}),$(document).on("click",".modal-confirm__no, .modal-confirm__yes",function(){event.preventDefault(),$("#modal-confirm").animate({opacity:0},200).css("display","none")}),$(document).on("click",".order__single--return",function(e){e.preventDefault(),$("#black-overlay").fadeIn(400,function(){$("#modal-start-claim").css("display","block").animate({opacity:1},200)})}),$(document).on("click",".modal-confirm__close, .modal-confirm__no, #black-overlay",function(){$("#modal-start-claim").animate({opacity:0},200,function(){$(this).css("display","none"),$("#black-overlay").fadeOut(400)})}),$(document).on("click",".about-error",function(e){e.preventDefault(),$("#black-overlay").fadeIn(400,function(){$("#modal-to-admin").css("display","block").animate({opacity:1},200)})}),$(document).on("click",".modal-confirm__close, #black-overlay",function(){$("#modal-to-admin").animate({opacity:0},200,function(){$(this).css("display","none"),$("#black-overlay").fadeOut(400)})}),$(document).on("click",".js-review-rating",function(){var e="rating__fil-star",t=$(this).prevAll(".js-review-rating"),a=$(this).nextAll(".js-review-rating");t.addClass(e),a.removeClass(e),$(this).addClass(e).parent().addClass("vote-cast")}),$(document).on("click",".cabinet__content--forcheck",function(){var e=$(this).closest(".cabinet__content--row");$(this).hasClass("focus")?($(this).removeClass("focus"),e.removeClass("selected-message")):($(this).addClass("focus"),e.addClass("selected-message"))}),$(document).on("click",".cabinet__sidebar--button",function(e){return e.preventDefault(),$(this).toggleClass("show-menu"),$(this).next(".cabinet__sidebar").slideToggle(500),!1})});