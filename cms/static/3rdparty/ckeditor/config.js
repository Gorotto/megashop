/**
 * @license Copyright (c) 2003-2012, CKSource - Frederico Knabben. All rights reserved.
 * For licensing, see LICENSE.html or http://ckeditor.com/license
 */

CKEDITOR.editorConfig = function(config) {

    /* integrate kcfinder  */
    config.filebrowserBrowseUrl = '/cms/static/3rdparty/kcfinder/browse.php?type=files';
    config.filebrowserImageBrowseUrl = '/cms/static/3rdparty/kcfinder/browse.php?type=images';
    config.filebrowserFlashBrowseUrl = '/cms/static/3rdparty/kcfinder/browse.php?type=flash';
    config.filebrowserUploadUrl = '/cms/static/3rdparty/kcfinder/upload.php?type=files';
    config.filebrowserImageUploadUrl = '/cms/static/3rdparty/kcfinder/upload.php?type=images';
    config.filebrowserFlashUploadUrl = '/cms/static/3rdparty/kcfinder/upload.php?type=flash';

    //режим вставки - только текст
    config.forcePasteAsPlainText = true;
    //допустимые теги для выпалающего списка
    config.format_tags = 'h2;h3;p';

    config.language = 'ru';
//    config.removeFormatTags = '';
//    config.uiColor = '#909960';

    config.extraPlugins = 'typofilter';

    config.height = '350px';

    config.toolbar_Base =
        [
            {name: 'document', items: ['Maximize', '-', 'Source']},
            {name: 'editing', items: ['Cut', 'Copy', 'PasteText', '-', 'Undo', 'Redo', '-', 'Find', 'Replace']},
            {name: 'paragraph', items: ['NumberedList', 'BulletedList', '-', 'Outdent', 'Indent']},
            {name: 'links', items: ['Link', 'Unlink', 'Anchor']},
            {name: 'insert', items: ['Image', 'MediaEmbed', '-', 'Table', 'SpecialChar']},
            '/',
            {name: 'basicstyles', items: ['Bold', 'Italic', 'Underline', 'Strike', '-', 'Subscript', 'Superscript', '-', 'RemoveFormat']},
//            { name: 'styles', items : [ 'Styles','Format','Blockquote' ] }
//            { name: 'additional', items : [ 'HorizontalRule','Typofilter' ] },
            {name: 'styles', items: ['Styles', '-', 'Format']},
            {name: 'additional', items: ['Blockquote', '-', 'Typofilter']},
        ];
    config.toolbar = 'Base';

    //user styles
    config.stylesSet =
        [
            //картинки
            {
                name: 'Изображение: выравнивание по центру',
                element: 'img',
                attributes: {style: '', class: 'h-align_center'}
            },
            {
                name: 'Изображение: выравнивание по правому краю',
                element: 'img',
                attributes: {style: '', class: 'h-align_right'}
            },
            {
                name: 'Изображение: выравнивание по левому краю',
                element: 'img',
                attributes: {style: '', class: 'h-align_left'}
            },
            //текст
            {
                name: 'Текст: выравнивание по центру',
                element: 'p',
                attributes: {style: '', class: 'h-talign_center'}
            },
            {
                name: 'Текст: выравнивание по правому краю',
                element: 'p',
                attributes: {style: '', class: 'h-talign_right'}
            },
            {
                name: 'Текст: выравнивание по левому краю',
                element: 'p',
                attributes: {style: '', class: 'h-talign_left'}
            },
            {
                name: 'Текст: выравнивание по ширине',
                element: 'p',
                attributes: {style: '', class: 'h-talign_justify'}
            },
//
//        {name: 'Выравнивание по центру', element: 'p', attributes: {'style': 'text-align: center;'}},
//        { name : 'Код города в шапке', element : 'span', attributes : { 'class' : 'head_phone__code' } },
//        { name : 'Темный текст', element : 'span', attributes : { 'class' : 'dark_text' } },
//        { name : 'Голубой текст', element : 'span', attributes : { 'class' : 'blue_text' } },
//        { name : 'Красный текст', element : 'span', attributes : { 'class' : 'red_text' } },
//        { name : 'Оранжевый текст', element : 'span', attributes : { 'class' : 'orange_text' } },
        ];

    config.contentsCss = '/cms/static/3rdparty/ckeditor/ckeditor_preview.css';

};
