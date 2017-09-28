/**
*	Инициализация системы управления
*/
$( function () {
	// Nami-процессор
	Nami.processorUri = Builder.uri;
	
	// Установим показ и скрытие axaj-нотификатора
	( function () {
		var timeout = null;
		var notifier = $( '#ajax-notifier' );
		notifier.
		ajaxStart( function () {
			timeout = setTimeout( function () { notifier.show(); }, 500 );
		} ).
		ajaxStop( function () {
			clearTimeout( timeout );
			notifier.hide();
		} );        
	} )();

	// Запрещаем перетаскивание за кнопки
	$( '.control img, .actions img' ).mousedown( function ( e ) {
		e.stopPropagation();
	} );
	
	// Включаем расширенные редакторы текста
	Builder.enableRichTextareas();
	
	// Подключаем выпадающие календари
	Builder.enableDatepickers();

	// Подключаем загрузчики картинок
	Builder.enableImageuploaders();

	// Подключаем загрузчики файлов
	Builder.enableFileuploaders();

	// Подключаем загрузчики видеороликов
	Builder.enableVideouploaders();
	
	// Теговыбиралки
	Builder.enableTagPickers();

} );