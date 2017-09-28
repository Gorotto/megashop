/**
 *	Инициализация системы управления
 */
$(function() {
    // Nami-процессор
    Nami.processorUri = Builder.uri;

    // Установим показ и скрытие axaj-нотификатора
    var ajaxTimeout = null;
    $(document).ajaxStart(function() {
        ajaxTimeout = setTimeout(function() {
            Builder.showAjaxLoader();
        }, 200);

    }).ajaxStop(function() {
        clearTimeout(ajaxTimeout);
        Builder.hideAjaxLoader();
    });


    // Включаем расширенные редакторы текста
    Builder.enableRichTextareas();
    // Подключаем выпадающие календари
    Builder.enableDatepickers();
    // Подключаем загрузчики картинок
    Builder.enableImageuploaders();
    // Подключаем загрузчики файлов
    Builder.enableFileuploaders();
    // Подключаем chosens
    Builder.enableChosens();

    Builder.enableJSONInputs();
});