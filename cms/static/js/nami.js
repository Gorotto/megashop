/*global $ window*/

/**
 * Собственно, Его Величество Глобальный Объект Nami. Пустой, методы будут позже :D
 */
var Nami = window.Nami = {
    processorUri: '' // URI, по которому NamiProcessor живет на сервере, должен быть установлен для правильной работы, или processor должен жить в корне сайта :)
};

///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
/**
 * Параметры запроса Nami на выборку, некий аналог QuerySet-а. Только тут именно набор параметров, без методов их применения.
 * Замечателен тем, что если объект такого прототипа заполнить параметрами и сделать на его прототипе еще один объект,
 * то новый _скопирует_ себе параметры прототипа, и будет модифицировать уже свой набор параметров.
 */
Nami.Query = {};

/**
 * Добавление параметров выборки к запросу.
 * Принимает три вида аргументов. Каждый вызов use соответствует одной из функций серверного NamiQuerySet.
 * Примеры:
 * На стороне браузера                                   На сервере
 * query.use( 'order', 'pos' )                           $QuerySet->order( 'position' )
 * query.use( 'filter', { pos__gt: 20, ready: true } )   $QuerySet->filter( array( 'pos_gt' => 20, 'ready' => true ) )
 * query.use( 'orderDesc,pos,level' )                    $QuerySet->orderDesc( 'pos', 'level' )
 * Последний способ хорош, если аргументы простого запроса можно хранить в строке, и не нужно в динамике формировать.
 * Возвращает this во имя chain power.
 */
Nami.Query.use = function() {
    // Копируем свойство query, если его еще нет у нас
    if (!this.hasOwnProperty('query')) {
        this.query = this.query ? this.query.slice(0) : [];
    }

    if (arguments.length) {
        var args = []; // Храним аргументы, разобранные в массив, первый — имя вызова, остальные — аргументы вызова
        if (arguments.length > 1) {
            for (var i = 0; i < arguments.length; i++) {
                args[i] = arguments[i];
            }
        } else {
            // один аргумент, посмотрим на счет перечисления в нем частей запроса через ';' типа order;position
            // ',' для совместимости со старым кодом
            args = new String(arguments[0]).split(';');
            if (args.length < 2) {
                args = new String(arguments[0]).split(',');
            }
        }

        // Сохраняем
        var call = {};
        call[args[0]] = args.slice(1);
        this.query.push(call);
    }
    return this;
};

/**
 * Получение параметров запроса в виде, пригодном для Nami.
 * Возвращает json-кодированые параметры запроса.
 */
Nami.Query.get = function() {
    return {
        query: JSON.stringify(this.query)
    };
};


///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
/**
 * Путь к объекту Nami - модели, объекту модели или полю объекта модели.
 * Предназначен для адресации в вызовах Nami.
 * На основе клонов Nami.Path строятся RESTfull URI запросов к штормпроцессору.
 */
Nami.Path = {
    model: null,
    // Имя модели, например News
    object: null,
    // Идентификатор объекта, например 16
    field: null // Имя поля объекта, например enabled
};

/**
 * Разбор строки — URI в собственные поля
 * path — исходная строка вида News/16/enabled. Общий вид — имя_модели/идентификатор_объекта/имя_поля_объекта.
 *        имя поля или имя поля и идентификатор объекта могут отсутствовать.
 * Возвращает this
 */
Nami.Path.parse = function(path) {
    if (path) {
        var parts = path.split('/');
        var names = ['model', 'object', 'field'];
        for (var i = 0; i < names.length; i++) {
            this[names[i]] = parts[i] !== undefined ? parts[i] : null;
        }
    } else {
        this.model = this.object = this.field = null;
    }
    return this;
};

/**
 * Получение полного URI пути. Возвращает путь до модели или объекта модели. Поле не учитывается.
 */
Nami.Path.getUri = function() {
    var uri = null;
    if (this.model) {
        uri = Nami.processorUri + '/' + this.model;
        if (this.object) {
            uri += '/' + this.object;
        }
    }
    return uri;
};

/**
 * Получение пути модели
 */
Nami.Path.getModelUri = function() {
    return this.model ? Nami.processorUri + '/' + this.model : null;
};

/**
 * Путь ведет к модели?
 */
Nami.Path.pointsAtModel = function() {
    return this.model ? true : false;
};

/**
 * Путь ведет к объекту модели?
 */
Nami.Path.pointsAtObject = function() {
    return this.model && this.object ? true : false;
};

/**
 * Путь ведет к полю объекта модели?
 */
Nami.Path.pointsAtField = function() {
    return this.model && this.object && this.field ? true : false;
};

///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
/**
 * Все JSON-ответы от штормпроцессора представляют собой объекты с полями
 * success - boolean, флаг успешности;
 * data - данные в виде JSON-объекта, полученные от штормпроцессора в ответ на запрос;
 * message - сообщение, как правило, об ошибке.
 */

/**
 * Обработка ошибки Ajax соединения по умолчанию.
 * result - JSON-ответ от штормпроцессора.
 * Возвращает this.
 */
Nami.defaultOnFailure = function(result) {
    if (typeof UIkit != "undefined") {
        UIkit.notify("<i class='uk-icon-warning'></i>&ensp;" + result.message, {timeout: 0, pos: 'top-left', status: 'danger'});
    } else {
        alert(result.message);
    }
    return this;
};

/**
 * Обработка результата Ajax-соединения.
 * result - JSON-ответ от штормпроцессора.
 * onSuccess - пользовательский обработчик успешного завершения.
 * onFailure - пользовательский обработчик неуспешного завершения. Может вернуть false для того, чтобы не запускать по умолчанию.
 * Возвращает, как заведено, ссылку на объект Nami, то есть this :D
 */
Nami.processAjaxResult = function(result, onSuccess, onFailure) {
    if (result.success) {
        // В случае успеха вызываем пользовательский обработчик, если он есть. Если его нет — не делаем ничего.
        if (typeof onSuccess === 'function') {
            onSuccess.call({}, result.data || {});
        }
    } else {
        // С ошибками чуть сложнее — вызываем пользовательский обработчик, и следом — обработчик Nami по уполчанию, если только
        // пользовательский не вернул boolean значение false.
        if (typeof onFailure === 'function') {
            if (onFailure.call({}, result.data || {}) === false) {
                return this;
            }
        }
        this.defaultOnFailure(result);
    }
    return this;
};

/**
 * Постройка Nami.Path на основе дерева DOM
 * Вытаскивает ближайшие переданному элементу атрибуты namiField, namiObject и namiModel.
 * Возвращает клон Nami.Path
 */
Nami.buildPath = function(element) {
    var path = Object.create(Nami.Path);
    var nesting = $(element).parents().add(element);

    path.model = nesting.filter('[namiModel]:last').attr('namiModel');
    path.object = nesting.filter('[namiObject]:last').attr('namiObject');
    path.field = nesting.filter('[namiField]').attr('namiField');

    return path;
};

/**
 * Получение пути из строки, если передан объект - возвращает его, не модифицируя ( это скорее всего Nami.Path ).
 * Возвращает клон Nami.Path или переданный объект.
 */
Nami.getPath = function(path) {
    return Nami.Path.isPrototypeOf(path) ? path : Object.create(Nami.Path).parse(path);
};

/**
 * Все вызовы, так или иначе получающие данные из Nami, имеют последними двумя аргументами функции
 * onSuccess = function( data ) и onFailure = function()
 * Оба аргумента не являются обязательными.
 * В onFailure можно сделать return false для отмены запуска обработчика по умолчанию, иначе он запускается.
 * Для сохранения контекста обработчиков настоятельно рекомендуется использование использование Function.delegate().
 * В качестве контекста вызовов используется пустой объект.
 */

/**
 * Получение объекта, массива объектов, дерева объектов или еще чего угодно, что возвращает
 * NamiProcessor по запросам типа «дай этот объект этой модели», «отфильтруй эту модель», «сделай limit», «сделай tree»
 * и так далее. По сути — интерфейс ко _всем_ методам NamiQuerySet. И результат — прям такой же, как получится на сервере,
 * только с учетом toArray и JSON-представления.
 * Аргументы
 * path - клон Nami.Path, путь к модели или объекту
 * * query - опциональный, параметры запроса к модели, объект (используется напрямуюв $.post) или клон Nami.Query.
 * onSuccess - function( data ), обработчик успешного получения данных, data - данные.
 * * onFailure - function(), обработчик неудачи.
 * Возвращает this.
 * Примеры:
 *  Nami.retrieve( 'Model/777', function ( data ) {  alert( data.title ) } )
 *  Nami.retrieve( 'Model', Object.create( Nami.Query ).use( 'filter', { name__contains: 'pattern' } ).use( 'order', 'name' ), function( data ) { alert( data.length ) } )
 */
Nami.retrieve = function() {
    var path, query, onSuccess, onFailure;
    var next = 0;

    path = Nami.getPath(arguments[next++]);

    if (typeof arguments[next] === 'function') {
        query = undefined;
    } else {
        query = arguments[next++];
        if (Nami.Query.isPrototypeOf(query)) {
            query = query.get();
        }
    }
    onSuccess = arguments[next++];
    onFailure = arguments[next++];

    if (query && path.pointsAtModel()) {
        // Запрос к модели — фильтрация
        $.post(path.getModelUri() + '/retrieve/', query, function(r) {
            Nami.processAjaxResult(r, onSuccess, onFailure);
        }, 'json');
    } else if (path.pointsAtObject()) {
        // Запрос к объекту — прямая выборка
        $.getJSON(path.getUri() + '/retrieve/', function(r) {
            Nami.processAjaxResult(r, onSuccess, onFailure);
        });
    } else {
        this.defaultOnFailure({
            success: false,
            message: 'Недопустимый для Nami.retrieve путь ' + arguments[0] + '.'
        });
    }
    return this;
};


/**
 * Обновление одного или нескольких объектов
 * path - путь, может указывать на модель или на объект модели
 * data - данные, для обновления объекта — прямо набор его полей,
 *      для обновления модели — объект с ключами — идентификаторами обновляемых записей,
 *      значения — объекты-наборы данных полей.
 * onSuccess
 * onFailure
 * Возвращает this.
 * Примеры:
 * Nami.update( 'Model/777', { enabled: 1 }, function( data ) {  alert( data.title ); } );
 * Nami.update( 'Model', { 1: { enabled: 1 }, 2: { enabled: 0 }, 3: { name: 'new value' } }, function() {  alert( 'Hooray!' ) } );
 */
Nami.update = function(path, data, onSuccess, onFailure) {
    path = this.getPath(path);

    if (path.pointsAtModel() || path.pointsAtObject()) {
        $.post(path.getUri() + '/update/', path.pointsAtObject() ? {
            json_data: JSON.stringify(data)
        } : {
            objects: JSON.stringify(data)
        }, function(r) {
            Nami.processAjaxResult(r, onSuccess, onFailure);
        }, 'json');
    } else {
        this.defaultOnFailure({
            success: false,
            message: 'Недопустимый для Nami.update путь ' + arguments[0] + '.'
        });
    }
    return this;
};

/**
 * Создание объекта из переданных данных
 * path - путь к модели
 * data - данные объекта
 * onSuccess
 * onFailure
 * Возвращает this
 */
Nami.create = function(path, data, onSuccess, onFailure) {
    path = this.getPath(path);
    if (path.pointsAtModel()) {
        $.post(path.getModelUri() + '/create/', {
            json_data: JSON.stringify(data)
        }, function(r) {
            Nami.processAjaxResult(r, onSuccess, onFailure);
        }, 'json');
    } else {
        this.defaultOnFailure({
            success: false,
            message: 'Недопустимый для Nami.create путь ' + arguments[0] + '.'
        });
    }
    return this;
};


Nami.createMany = function(path, data, onSuccess, onFailure) {
    path = this.getPath(path);
    if (path.pointsAtModel()) {
        $.post(path.getModelUri() + '/create_many/', {
            json_data: JSON.stringify(data)
        }, function(r) {
            Nami.processAjaxResult(r, onSuccess, onFailure);
        }, 'json');
    } else {
        this.defaultOnFailure({
            success: false,
            message: 'Недопустимый для Nami.create путь ' + arguments[0] + '.'
        });
    }
    return this;
};


Nami.copy = function(path, onSuccess, onFailure) {
    path = this.getPath(path);

    if (path.pointsAtModel()) {
        $.getJSON(path.getUri() + '/copy/', function(r) {
            Nami.processAjaxResult(r, onSuccess, onFailure);
        }, 'json');
    } else {
        this.defaultOnFailure({
            success: false,
            message: 'Недопустимый для Nami.copy путь ' + arguments[0] + '.'
        });
    }

    return this;
};

/**
 * Переупорядочивание NestedSet объектов, удобно для использования с iNestedSortable.
 * path - модель
 * tree - дерево, задающее новый порядок узлов
 * onSuccess
 * onFailure
 * Возвращает this
 */
Nami.reorder = function(path, tree, onSuccess, onFailure) {
    path = this.getPath(path);
    if (path.pointsAtModel()) {
        $.post(path.getModelUri() + '/reorder/', {
            objects: JSON.stringify(tree)
        }, function(r) {
            Nami.processAjaxResult(r, onSuccess, onFailure);
        }, 'json');
    } else {
        this.defaultOnFailure({
            success: false,
            message: 'Недопустимый для Nami.reorder путь ' + arguments[0] + '.'
        });
    }
    return this;
};

/**
 * Удаление объекта
 * path - путь к объекту
 * onSuccess
 * onFailure
 * Возвращает this.
 */
Nami.remove = function(path, onSuccess, onFailure) {
    path = this.getPath(path);
    if (path.pointsAtObject()) {
        $.getJSON(path.getUri() + "/delete/", function(r) {
            Nami.processAjaxResult(r, onSuccess, onFailure);
        });
    } else {
        this.defaultOnFailure({
            success: false,
            message: 'Недопустимый для Nami.remove путь ' + arguments[0] + '.'
        });
    }
    return this;
};


Nami.removeMany = function(modelName, arrayIds, onSuccess, onFailure) {
    $.getJSON(Nami.processorUri + "/" + modelName + "/delete/", {
        objects: JSON.stringify(arrayIds)
    }, function(r) {
        Nami.processAjaxResult(r, onSuccess, onFailure);
    });

    return this;
};

/**
 * Получение пустой структуры объекта, заполненной только значениями по умолчанию
 * path - модель
 * [initialData] - значения, которыми следует инициализировать модель на сервере перед получением структуры, необязательный
 * onSuccess
 * onFailure
 * Возвращает this.
 */
Nami.structure = function(path, initialData, onSuccess, onFailure) {
    if (arguments.length === 3) {
        onFailure = onSuccess;
        onSuccess = initialData;
        initialData = {};
    }

    path = this.getPath(path);
    if (path.pointsAtModel()) {
        $.post(path.getModelUri(), initialData, function(r) {
            Nami.processAjaxResult(r, onSuccess, onFailure);
        }, 'json');
    } else {
        this.defaultOnFailure({
            success: false,
            message: 'Недопустимый для Nami.structure путь ' + arguments[0] + '.'
        });
    }
    return this;
};


/**
 * Toggle булевого поля, адресуемого path
 * path - путь к полю объекта модели, типа News/12/enabled
 * onSuccess
 * onFailure
 * Возвращает this.
 */
Nami.toggle = function(path, onSuccess, onFailure) {
    path = this.getPath(path);
    if (path.pointsAtField()) {
        this.retrieve(path, function(data) {
            var updateData = {};
            updateData[path.field] = data[path.field] ? 0 : 1;
            Nami.update(path, updateData, onSuccess, onFailure);
        }, onFailure);
    } else {
        this.defaultOnFailure({
            success: false,
            message: 'Недопустимый для Nami.toggle путь ' + arguments[0] + '.'
        });
    }
    return this;
};

///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
/**
 * Процессор-обработчик форм редактирования шторм-моделей.
 * Берет на себя большую часть работы по получению/сохранению данных, отображению/скрытию собственно форм и прочее.
 * Пример:
 *
 *
 *
 */

Nami.Form = Object.create(Object.Extendable).extend({
    urisBeingEdited: {},
    // Хэш путей редактируемых в данный момент объектов. Важно, что это объект, следовательно все клоны Nami.Form будут пользоваться именно им.
    /**
     *  Пометить поле как грязное. При редактировании на сервер отправляются только грязные поля.
     */
    markAsDirty: function(fieldName) {
        if (!this.hasOwnProperty('dirtyFields')) {
            this.dirtyFields = {};
        }
        this.dirtyFields[fieldName] = true;
        return this;
    },
    /**
     *  Пометить поле как не грязное :)
     */
    markAsClean: function(fieldName) {
        if (!this.hasOwnProperty('dirtyFields')) {
            this.dirtyFields = {};
        }
        this.dirtyFields[fieldName] = false;
        return this;
    },
    /**
     *  Получить список грязных полей.
     *  Возвращает
     */
    getDirtyFields: function() {
        var fields = {},
                i;
        if (this.hasOwnProperty('dirtyFields')) {
            for (name in this.dirtyFields) {
                if (this.dirtyFields.hasOwnProperty(name) && this.dirtyFields[name]) {
                    fields[name] = true;
                }
            }
        }

        return fields;
    },
    /**
     *  Грязно ли указанное поле?
     */
    fieldIsDirty: function(name) {
        return this.hasOwnProperty('dirtyFields') && this.dirtyFields[name] === true;
    },
    /**
     * Есть ли в форме измененные поля?
     * Если полей нет - можно, например, не отправлять ничего на сервер.
     * Возвращает true или false.
     */
    isDirty: function() {
        // Форма создания записи всегда грязная
        if (this.mode === 'create') {
            return true;
        }

        if (this.hasOwnProperty('dirtyFields')) {
            for (name in this.dirtyFields) {
                if (this.dirtyFields.hasOwnProperty(name) && this.dirtyFields[name]) {
                    return true;
                }
            }
        }

        return false;
    }
});

////// Обработчики, которые можно переопределить
/**
 * Форма запускается в работу, еще ничего не проверено и не подготовлено; самое начало процесса
 */
Nami.Form.onStart = function() {
};

/**
 * Форма создана
 * form - jQuery ссылка на реальную копию формы, с которой будет идти работа
 */
Nami.Form.onCreate = function(form) {
};

/**
 * Форма начала работу
 * form - jQuery ссылка на реальную копию формы
 */
Nami.Form.onLaunch = function(form) {
};

/**
 * Загружены начальный данные формы
 * data - данные. объект можно модифицировать — добавлять поля, удалять или менять значения.
 */
Nami.Form.onLoadData = function(data) {
};

/**
 * Прочитаны поля ввода формы
 * inputs - поля формы, объект, свойства - имена полей, значения - ссылки на HTML-элемент поля,
 * например для формы с единственным полем <input type="text" name="title"/>, объект inputs будет иметь вид
 * { title: _HTML_ELEMENT_ }, _HTML_ELEMENT_ - ссылка на объект DOM типа input, представляюший это поле ввода.
 * inputs можно модифицировать — добавлять, удалять элементы
 */
Nami.Form.onFetchInputs = function(inputs) {
};

/**
 * Форма заполнена загруженными данными
 * form - jQuery, форма
 * data - данные, которыми заполнена форма, свойство - имя поля, значение - значение
 */
Nami.Form.onFill = function(form, data) {
};

/**
 * Форма отображена
 * form - jQuery, форма
 */
Nami.Form.onShow = function(form) {
};

/**
 * Получены данные, которые пользователь ввел в форму
 * form - jQuery, форма
 * data - данные, которыми заполнена форма, свойство - имя поля, значение - значение
 * объект data можно модифицировать.
 */
Nami.Form.onFetchData = function(form, data) {
};

/**
 * Нажали кнопку сохранения, данные сохранены на сервер
 * form - jQuery, форма
 * data - данные, которые были отправлены на сервер для сохранения, свойство - имя поля, значение - значение
 * response - данные, полученные от сервера в ответ
 */
Nami.Form.onSubmit = function(form, data, response) {
};

/**
 * Нажали кнопку отмены
 * form - jQuery, форма
 */
Nami.Form.onCancel = function(form, item) {
};

/**
 * Объект заполнен данными, полученными после сохранения
 * item - jQuery, объект
 * response - данные, полученные от сервера при сохранении данных формы
 */
Nami.Form.onFillItem = function(item, response) {
};

/**
 * Форма спрятана
 * form - jQuery, форма
 */
Nami.Form.onHide = function(form) {
};

/**
 * Объект отображается вместо формы
 * item - jQuery, объект
 */
Nami.Form.onShowItem = function(item) {
};

/**
 * Форма вот-вот будет уничножена, самое время удалить все с ней связанное
 * form - jQuery, форма
 */
Nami.Form.onDestroy = function(form) {
};

////// Настраиваемые параметры
Nami.Form.single = true; // Редактирование только одного экземпляра объекта. Если форма с таким объектом уже открыта — форма просто не открывается.
Nami.Form.mode = null; // Режим работы, 'edit', 'create' или null, означающее автоопределение
Nami.Form.form = null; // HTML-форма, jQuery
Nami.Form.formPlace = null; // Место в которое добавляется форма, jQuery
Nami.Form.object = null; // Nami.Path, путь к объекту, который будем редактировать или создавать на сервере
Nami.Form.item = null; // Элемент, вместо которого форма отображается при редактировании и который заполняется при закрытии формы, jQuery
Nami.Form.itemPlace = null; // Объект, в который добавляется item после заполнения в режиме create, jQuery
Nami.Form.submitButtonSelector = ".js_cms_item_edit_form__save"; // jQuery селектор для кнопки отправки формы
Nami.Form.cancelButtonSelector = ".js_cms_item_edit_form__cancel"; // jQuery селектор для кнопки отмены редактирования
Nami.Form.formInputSelector = ':input[name]'; // jQuery селектор выборки полей редактирования данных формы

// Внутренние переменные формы, доступны внутри пользовательских обработчиков в том числе
Nami.Form.loadedData = null; // Данные, загруженные с сервера методом loadData
Nami.Form.inputs = null; // Связка имен данных, полученных с сервера с полями ввода формы, заполняется методом fetchInputs
Nami.Form.data = null; // Данные, полученные методом fetchData
Nami.Form.response = null; // Данные, полученные от сервера в ответ на запрос сохранения данных
Nami.Form.bind = function(event, handler) {
    if (!/^(start|create|launch|loaddata|fetchinputs|fill|show|fetchdata|submit|cancel|fillitem|hide|showitem|destroy|beforefill)$/.test(event)) {
        throw 'Неизвестное событие ' + event;
    }
    /*  Обработчики хранятся в объекте, который может быть унаследован от прототипа.
     Если это так, то нужно скопировать обработчики прототипа себе, и только после этого добавлять новый,
     иначе набор обработчиков прототипа с каждым вызовом будет засоряться */
    if (this.handlers) {
        // Обработчики унаследованы от прототипа?
        if (!this.hasOwnProperty('handlers')) {
            var handlers_copy = {};
            for (var prop in this.handlers) {
                if (this.handlers.hasOwnProperty(prop)) {
                    handlers_copy[prop] = this.handlers[prop].slice();
                }
            }
            // Теперь у нас собственные обработчики
            this.handlers = handlers_copy;
        }
    } else {
        this.handlers = {};
    }

    // Записываем обработчик
    if (!this.handlers[event]) {
        this.handlers[event] = [];
    }
    this.handlers[event].push(handler);

    return this;
};

Nami.Form.trigger = function(event) {
    if (this.handlers && this.handlers[event] && this.handlers[event].length) {
        for (var i = 0; i < this.handlers[event].length; i += 1) {
            if (typeof this.handlers[event][i] === "function") {
                this.handlers[event][i].apply(this, Array.prototype.slice.apply(arguments, [1]));
            }
        }
    }
    return this;
};



/**
 * Проверка параметров.
 * Возвращает this или выбрасывает exception.
 */
Nami.Form.startCheck = function() {
    // Получим путь объекта, который редактируем
    this.object = Nami.getPath(this.object);

    // Если режим редактирования не указан — попробуем определить его самостоятельно
    // Принцип прост — если задан путь к модели, режим — create; если задан путь к объекту модели — edit.
    if (!this.mode && this.object) {
        if (this.object.pointsAtObject()) {
            this.mode = 'edit';
        } else if (this.object.pointsAtModel()) {
            this.mode = 'create';
        }
    } else if (!(this.mode === 'create' || this.mode === 'edit')) {
        throw 'Параметр mode не указан, и определить его автоматически не удалось.';
    }

    if (!this.form) {
        throw 'Параметр form не указан.';
    }

    return this;
};

/**
 * Клонирование формы на основе шаблона. Внутренний метод, и не должен быть переопределен в большинстве случаев.
 * Возвращает this, модифицируя this.form.
 */
Nami.Form.cloneForm = function() {
    // Клонируем исходную форму вместе с обработчиками. Удаляем id.
    this.form = this.form.clone(true).hide().removeAttr('id');
    return this;
};

/**
 * Установка обработчиков формы — нажатие кнопок, сабмит по нажатию enter и тому подобное.
 * Возвращает this;
 */
Nami.Form.bindFormActions = function() {
    // Сабмит
    this.form.find(this.submitButtonSelector).bind('click', Function.delegate(this, function(event) {
        event.preventDefault();
        this.submit();
    }));

    // Отмена
    this.form.find(this.cancelButtonSelector).bind('click', Function.delegate(this, function(event) {
        event.preventDefault();
        this.cancel();
    }));

    // Сабмит по enter в текстовых однострочных полях
    this.form.find('input[type=text]').bind('keypress', Function.delegate(this, function(event) {
        if (event.keyCode === 13) {
            $(event.target).trigger('change');
            this.submit();

            event.preventDefault();
        }
    }));

    // Отмена по ESC в текстовых однострочных полях
//    this.form.find(':input:not(textarea,input[datetimepicker=yes])').bind('keyup', Function.delegate(this, function(event) {
//        if (event.keyCode === 27) {
//            this.cancel();
//        }
//    }));

    $(document).bind('keydown.cms', Function.delegate(this, function(event) {
        // сохранение по нажатию на ctrl+enter
        if ((event.ctrlKey) && ((event.keyCode === 0xA) || (event.keyCode === 0xD))) {
            // из-за нажатия горячих клавиш на textarea приходится мутить хак, чтобы поле стало грязным, иначе не сохраняется :(
            this.markAsDirty($(event.target).attr('name'));
            this.submit();
        }
        // ESC
//        if (event.keyCode === 27) {
//            this.cancel();
//        }

    }));

    return this;
};


/**
 * Запуск формы. Этот метод следует вызвать, чтобы форма начала работать.
 * Возвращает true, если форма запущена или false, если объект формы уже редактируется такой же формой.
 */
Nami.Form.start = function() {
    this.onStart();
    this.trigger('start');

    // Проверим параметры
    this.startCheck();

    if (this.object) {
        // Получим uri объекта, который будем редактировать
        var objectUri = this.object.getUri();

        // Проверим на счет одновременного редактирования одного и того же объекта в нескольких формах
        if (this.single && objectUri in this.urisBeingEdited) {
            return false;
        }

        this.urisBeingEdited[objectUri] = true;
    }

    this.create();
    return true;
};

/**
 * Создание формы. Копируем шаблон формы, вставляет его в нужное место DOM, устанавливает обработчики на кнопки и поля ввода.
 * Форма пока остается скрытой, item для edit-форм остается без изменений, для create-форм клонируется.
 * Возвращает this
 */
Nami.Form.create = function() {
    // Клонируем форму
    this.cloneForm();

    // Вставляем форму в DOM
    switch (this.mode) {
        case 'edit':
            if (this.formPlace) {
                this.formPlace.prepend(this.form);
            } else if (this.item) {
                this.item.before(this.form);
            } else {
                throw 'Для форм, работающих в режиме edit необходимо указывать параметр item или параметр formPlace';
            }
            break;

        case 'create':
            if (this.formPlace) {
                this.formPlace.prepend(this.form);
            } else {
                throw 'Необходимо указать параметр formPlace';
            }
            break;
    }

    // TODO разобраться, зачем клонировать item прямо сейчас.
    // Нельзя заниматься этим уже после того,
    // как прошел submit и получены ответные данные?
    if (this.mode === 'create' && this.item) {
        // Клонируем объект, который будет добавлен вместо формы. Удаляем id.
        this.item = this.item.clone(true).hide().removeAttr('id');

        if (this.itemPlace) {
            this.itemPlace.prepend(this.item);
        } else {
            this.formPlace.prepend(this.item);
        }
    }

    // Вешаем обработчики submit и cancel
    this.bindFormActions();

    this.onCreate(this.form);
    this.trigger('create', this.form);


    // Запускаем форму в работу
    this.launch();
};

/**
 * Запуск формы в работу
 */
Nami.Form.launch = function() {
    // Выполняем пользовательские штучки
    this.onLaunch(this.form);
    this.trigger('launch', this.form);

    // Следующий этап в жизни формы произойдет при получении данных для начального заполнения формы.
    if (this.object) {
        if (this.mode === 'create') {
            Nami.structure(this.object, this.extraData || {}, Function.delegate(this, this.loadData), Function.delegate(this, this.destroy));
        } else if (this.mode === 'edit') {
            Nami.retrieve(this.object, Function.delegate(this, this.loadData), Function.delegate(this, this.destroy));
        }
    } else {
        this.loadData({});
    }

    return this;
};

/**
 * Загрузка начальных данных формы
 */
Nami.Form.loadData = function(data) {
    // Вызываем пользовательскую обработку загруженных данных
    this.onLoadData(data);
    this.trigger('loaddata', data);

    // Сохраняем данные во внутреннюю переменную
    this.loadedData = data;

    // Заполняем форму
    if (this.handlers && this.handlers.beforefill) {
        this.trigger('beforefill');
    } else {
        this.fill();
    }

    return this;
};

/**
 * Обнаружение полей ввода в форме, которые будут использоваться для редактирования данных.
 * Обнаруженные поля можно отфильтровать или расширить в пользовательском обработчике onFetchInputs
 */
Nami.Form.fetchInputs = function() {
    var inputs = {},
            that = this;

    // Находим все поля ввода формы по селектору
    if (this.form) {
        this.form.find(this.formInputSelector).each(function() {
            var name = $(this).attr('name');
            if (name) {
                if (inputs[name]) {
                    if (inputs[name] instanceof Array) {
                        inputs[name].push(this);
                    } else {
                        inputs[name] = [inputs[name], this];
                    }
                } else {
                    inputs[name] = this;
                }
            }
        });
    }

    // Пользовательский обработчик
    this.onFetchInputs(inputs);
    this.trigger('fetchinputs', inputs);

    // По изменению состояния поля ввода помечаем его грязным
    for (name in inputs) {
        if (inputs.hasOwnProperty(name)) {
            $(inputs[name]).change(function() {
                that.markAsDirty($(this).attr('name'));
            });
        }
    }

    // Сохраняем список полей
    this.inputs = inputs;

    return this;
};

/**
 * Заполнение формы данными
 * Возвращает this
 */
Nami.Form.fill = function() {
    // Прочитаем поля ввода
    this.fetchInputs();

    if (this.form) {

        // Заполним поля ввода начальными данными
        for (var name in this.loadedData) {
            if (this.loadedData.hasOwnProperty(name)) {
                var value = this.loadedData[name];

                // Заполняем поле ввода
                if (name in this.inputs) {
                    if (this.inputs[name] instanceof Array) {
                        // Массив полей - radio
                        for (var n in this.inputs[name]) {
                            if (this.inputs[name].hasOwnProperty(n)) {

                                var input = $(this.inputs[name][n]);

                                if (input.attr('type') == 'radio') {
                                    if (value == input.val()) {
                                        input.attr('checked', 'checked');
                                        break;
                                    }
                                }

                            }
                        }

                        continue;
                    }

                    var input = $(this.inputs[name]);

                    // Для полей типа ENUM, помещаемых в select, автоматически заполняем этот select
                    if (!!input.has("option") && value && typeof value === 'object') {
                        input.val(value['value']);
                    }
                    // Чекбоксы требуют особого обращения
                    else if (input.attr('type') == 'checkbox') {
                        if (value) {
                            input.attr('checked', 'checked');
                        }
                    }
                    // Radiobutton тоже
                    else if (input.attr('type') == 'radio') {
                        if (value == input.val()) {
                            input.attr('checked', 'checked');
                        }
                    }
                    // Изображения
                    else if (input.attr('imageupload')) {
                        // Поле ввода заполняем оригиналом имеющегося изображения
                        input.val(value && value.original ? value.original.uri : '');
                    } else {
                        input.val(Object.typeOf(value) !== 'null' ? value : '');
                    }


                    var randomId = Math.floor(Math.random() * 100);
                    input.parents(".uk-form-row").find("label").attr("for", randomId);
                    input.attr("id", randomId);


                    // TODO добавить куда-то сюда обработку NamiImageDbField - они тоже приходят в виде объекта,
                    // и что-то с ним надо делать

                    // TODO добавить сюда обработку chosen
                }

                // Заполняем текст
                try {
                    this.form.find('[namiText=' + name + ']').text(value || '');
                } catch (e) {
                }

                // Заполняем html-текст
                try {
                    this.form.find('[namiHtml=' + name + ']').html(value || '');
                } catch (e) {
                }
            }
        }
    }

    // Пользовательский обработчик
    this.onFill(this.form, this.loadedData);
    this.trigger('fill', this.form, this.loadedData);

    // Форма заполнена, показываем ее
    this.show();

    return this;
};


/**
 * Показ формы. Прячет item в режиме edit и отображает саму форму.
 * Перед показом собственно формы вызывает пользовательский обработчик
 */
Nami.Form.show = function() {
    // В режиме edit прячем item
    if (this.mode === 'edit' && this.item) {
        this.item.hide();
    }

    // Показ формы
    this.onShow(this.form);
    this.trigger('show', this.form);

    // Показываем саму форму
    this.form.show();

    //фокус на первое поле
    this.form.find('input[type=text]:not([datepicker=yes],[imageupload=yes], [imagesupload=yes]), textarea:not([richtext=yes])').first().focus().select();

    // Триггерим показ полей ввода
    for (var name in this.inputs) {
        if (this.inputs.hasOwnProperty(name)) {
            $(this.inputs[name]).trigger('show', [this.loadedData[name], name, this]);
        }
    }

    //  В этот момент форма отправляется в свободное плавание; ждем нажатия submit или cancel пользователем
    return this;
};

/**
 * Получение данных, введенных пользователем в форму. Заполняет внутреннюю переменную this.data
 * Возвращает this
 */
Nami.Form.fetchData = function() {
    var data = {},
            dirty = this.getDirtyFields();

    // Вытаскиваем значения полей
    for (var name in this.inputs) {
        if (this.inputs.hasOwnProperty(name)) {
            // Обрабатываем только грязные поля
            if (this.mode !== 'create' && !dirty[name]) {
                continue;
            }

            if (this.inputs[name] instanceof Array) {
                // Массив полей - radio

                for (var n in this.inputs[name]) {
                        var input = $(this.inputs[name][n]);

                        if (input.attr('type') == 'radio' && input.is(':checked')) {
                            data[name] = input.val();
                            break;
                        }
                }

                continue;
            }

            if ($(this.inputs[name]).attr('type') == 'checkbox' && !$(this.inputs[name]).is(':checked')) {
                // Если чекбокс не отмечен, отправим на сервер false
                data[name] = 0;
            } else if ($(this.inputs[name]).attr('type') === 'radio' && !$(this.inputs[name]).attr('checked')) {
                // Если radiobutton не отмечена, продолжим искать отмеченную =)
                continue;
            } else {
                data[name] = $(this.inputs[name]).val();
            }
        }
    }

    if (this.extraData) {
        for (var prop in this.extraData) {
            if (this.extraData.hasOwnProperty(prop)) {
                data[prop] = this.extraData[prop];
            }
        }
    }

    // Вызываем пользовательскую обработку
    this.onFetchData(this.form, data);
    this.trigger('fetchdata', this.form, data);

    this.data = data;

    return this;
};

/**
 * Обработчик нажатия кнопки submit. Получает данные, которые пользователь ввел в форму, оправляет их на сервер для сохранения
 * Возвращает this
 */
Nami.Form.submit = function() {
    this.form.find('textarea[rich=yes]').each(function() {
        var editor_id = $(this).attr("id");
        if (typeof CKEDITOR.instances[editor_id] === "object") {
            CKEDITOR.instances[editor_id].updateElement();
        }
    });

    // Получаем данные, которые пользователь ввел
    this.fetchData();

    // Если форма чиста - делаем отмену :coolface:
    if (!this.isDirty()) {
        return this.cancel();
    }

    if (this.object) {
        if (this.mode === 'create') {
            Nami.create(this.object, this.data, Function.delegate(this, this.processResponse));
        } else if (this.mode === 'edit') {
            Nami.update(this.object, this.data, Function.delegate(this, this.processResponse));
        }
    } else {
        this.processResponse({});
    }

    return this;
};

/**
 * Обработка данных, полученных от сервера
 * response - объект с данными, которые передал сервер
 * Возвращает this
 */
Nami.Form.processResponse = function(response) {
    // Сохраняем ответ сервера
    this.response = response;

    // Вызываем пользовательский обработчик
    this.onSubmit(this.form, this.data, this.response);
    this.trigger('submit', this.form, this.data, this.response);

    // И переходим к заполнению элемента, который нужно показать вместо формы
    this.fillItem();

    return this;
};

/**
 * Заполнение элемента данными с сервера, готовим его к отображению вместо формы
 * Возвращает this
 */
Nami.Form.fillItem = function() {
    // Заполняем элемент

    var $item = this.item;

    $item.attr('namiObject', this.response.id);

    if ($(".uk-nestable").length) {
        //в nested модели, элемент может быть вложенным
        $item.attr('data-id', this.response.id);
        $item = this.item.find(".builder-items_list__item:first");
    }

    if ($item && this.response) {
        for (var name in this.response) {
            if (this.response.hasOwnProperty(name)) {
                try {
                    $item.find('[namiText=' + name + ']').text(this.response[name]);
                } catch (e) {
                }
                try {
                    $item.find('[namiHtml=' + name + ']').html(this.response[name]);
                } catch (e) {
                }
            }
        }
    }

    // Вызываем пользовательскую обработку
    this.onFillItem($item, this.response);
    this.trigger('fillitem', $item, this.response);

    // Прячем форму
    this.hide(true);

    return this;
};

/**
 * Обработка нажатия кнопки отмены
 * Возвращает this
 */
Nami.Form.cancel = function() {
    this.onCancel(this.form);
    this.trigger('cancel', this.form, this.item);

    if (this.mode === 'create' && this.item) {
        this.item.remove();
    }

    this.hide(this.mode !== 'create');
    return this;
};

/**
 * Скрытие формы
 * showItem - показывать item вместо формы после ее скрытия или нет
 * Возвращает this
 */
Nami.Form.hide = function(showItem) {
    this.form.hide();
    this.onHide(this.form);
    this.trigger('hide', this.form);

    if (this.item && showItem) {
        this.item.find(Builder.selectors.item).show();
        //в случае создания элемента отобразить и item
        this.item.show();
    }

    this.onShowItem(this.item);
    this.trigger('showitem', this.item);

    $(document).unbind('keydown.cms');

    this.destroy();

    return this;
};

/**
 * Уничтожеие формы
 * Возвращает this
 */
Nami.Form.destroy = function() {
    // Очищаем хэш открытых объектов
    var objectUri = this.object.getUri();
    if (objectUri in this.urisBeingEdited) {
        delete this.urisBeingEdited[objectUri];
    }
    // Обработчик
    this.onDestroy(this.form);
    this.trigger('destroy', this.form);

    // Удаляем форму
    this.form.remove();

    //фокус на кнопаре добавить
    $(Builder.selectors.create_button).focus();

    return this;
};