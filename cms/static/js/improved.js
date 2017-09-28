/*
Библиотека улучшения javascript, написанная под вдохновением от javascript.crockford.com, откуда взята часть кода.
http://javascript.crockford.com/remedial.html
*/

/**
Создание клона объекта на основе переданного прототипа.
Забудьте о классах, создавайте объекты, и наследуйте их!
Читать тут: http://javascript.crockford.com/prototypal.html
*/
if( typeof Object.create !== 'function' ) {
	Object.create = function ( o ) {
		function F() {}
		F.prototype = o;
		return new F();
    };
}

/**
	Оператор typeof работает неправильно — для массивов и null возвращается тип 'object'.
	Функция Object.typeof призвана исправить эту ситуацию.
*/
if( typeof Object.typeOf !== 'function' ) {
	Object.typeOf = function ( value ) {
		var s = typeof value;
		if( s === 'object' ) {
			if( value ) {
				if( value instanceof Array ) {
					s = 'array';
				}
			} else {
				s = 'null';
			}
		}
		return s;
	};
}

/**
*	Полезный объект, который можно использовать в качестве прототипа.
*	Имеет один единственный метод extend в базовой поставке — копирование в себя самого всех переданных аргументами объектов.
*	Копируются только прямые члены объекта, полученные от прототипов — нет.
*/
if( typeof Object.Extendable !== 'object' ) {
	Object.Extendable = {};
	Object.Extendable.extend = function() {
		for( var i = 0; i < arguments.length; i++ ) {
			for( var member in arguments[i] ) {
				if( arguments[i].hasOwnProperty( member ) ) {
					this[ member ] = arguments[ i ][ member ];
				}
			}
			return this; // TODO: похоже это баг! Внешний цикл выполняется только один раз. Исправить.
		}
	};
	/**
	*  Загрузка значений, не имеющихся в текущем объекте.
	*  Учитываются все свойства this, в том числе унаследованные от прототипа.
	*/
	Object.Extendable.defaults = function(defaults) {
		for (var member in defaults) {
			if (defaults.hasOwnProperty(member)) {
                if (! member in this || Object.typeOf(this[member]) === 'null') {
    				this[member] = defaults[member];
                }
			}
		}
		return this;
	};
}

if( typeof Object.Improved !== 'object' ) {
    Object.Improved = {
        beget: function() {
            return Object.create( this );
        },
        
        extend: function() {
            var i, p;
            for( var i = 0; i < arguments.length; i++ ) {
                for( p in arguments[i] ) {
                    if( arguments[i].hasOwnProperty( p ) ) {
                        this[p] = arguments[i][p];
                    }
                }
            }
            return this;
        },
        
        /**
        *   Примешивание к объекту произвольного количества других объектов.
        *       повторяющиеся методы объединяются в цепочку вызовов,
        *       повторяющиеся массивы склеиваются,
        *       повторяющиеся объекты смешиваются этой же функцией,
        *       повторяющиеся литералы перезаписываются.
        *   В расчет берутся только непосредственные свойства объекта ( obj.hasOwnProperty( prop ) === true )
        *   Первый объект становится прототипом полученной смеси, свойства остальных смешиваются и комбинируются в свойствах объекта.
        *   Возвращает объект-смесь.
        */
        chainmix: function() {
            var functions = {}; // коллекция функций
            var objects = {};   // коллекция объектов
            var arrays = {};    // коллекция массивов
            
            var sources = Array.prototype.slice.apply( arguments, [0] );
            sources.unshift( this );
            
            var collection, value;
            
            var i, p;
            
            // Пройдемся по переданным объектам и разложим по коллекциям методы, объекты и массивы.
            // Ключ коллекции - имя свойства, значение - массив, чтобы учесть повторения
            for( i = 0; i < sources.length; i++ ) {
                for( p in sources[i] ) {
                    if( sources[i].hasOwnProperty( p ) ) {
                        value = sources[i][p];
                        switch( Object.typeOf( sources[i][p] ) ) {
                        case 'function':
                            collection = functions;
                            value = sources[i];
                            break;
                        case 'object':
                            collection = objects;
                            break;
                        case 'array':
                            collection = arrays;
                            break;
                        default:
                            collection = null;
                            break;
                        }
                        if( collection ) {
                            if( ! collection.hasOwnProperty( p ) ) {
                                collection[p] = [];
                            }
                            collection[p].push( value );
                        } else {
                            this[p] = value;    
                        }
                    } 
                }
            }
            
            // Теперь склеим коллекции и поместим их в объект

            // Одноименные массивы склеиваем в один общий массив
            for( p in arrays ) {
                if( arrays.hasOwnProperty( p ) ) {
                    this[p] = Array.prototype.concat.apply( [], arrays[p] );
                }
            }
            
            // Одноименные массивы рекурсивно chainmix-им ;)
            for( p in objects ) {
                if( objects.hasOwnProperty( p ) ) {
                    this[p] = objects[p].length > 1 ? Object.Improved.chainmix.apply( {}, objects[p] ) : objects[p][0];
                }
            }

            // Самое интересное:
            // Одноименные функции объединяем в цепочку - при вызове метода исходные будут вызваны подряд.
            // Делаем это так, чтобы resolve метода происходил в момент вызова цепочки (!),
            // таким образом мы сохраним труевое javascript-way-ное прототипное наследование
            for( p in functions ) {
                if( functions.hasOwnProperty( p ) ) {
                    if( functions[p].length > 1 ) {
                        this[p] = ( function() {    // Замыкание для нового контекста
                            var mediums = [];   // Насоздаем объектов, прототипами которых будут исходные :D
                            var method = p;
                            for( i = 0; i < functions[p].length; i++ ) {
                                mediums.push( Object.create( functions[p][i] ) );
                            }
                            return function() { // Сам метод цепочного вызова
                                var r, i;
                                for( i = 0; i < mediums.length; i++ ) {
                                    if( Object.typeOf( mediums[i][method] ) === 'function' ) {
                                        r = mediums[i][method].apply( this, arguments );
                                    }
                                }
                                return r;
                            }
                        } )();
                    } else {
                        this[p] = functions[p][0][p];
                    }
                }
            }
            
            return this;
        }
    };
}

/**
	Создание делегата метода объекта.
	Фозвращает функцию, при вызове вызыващую переданный метод в контексте переданного объекта с реальными аргументами.
	Возвращает результат выполнения метода.
*/
if( typeof Function.delegate !== 'function' ) {
	Function.delegate = function ( object, method ) {
		return function () {
			return method.apply( object, arguments );
		};
	};
}

/**
	Создание коллбэка функции с «замороженными» аргументами.
	Фозвращает функцию, при вызове вызывающую переданную функцию с переданными аргументами.
	Возвращает результат выполнения функции.
*/
if( typeof Function.callback !== 'function' ) {
	Function.callback = function ( fnct ) {
		// «Замораживаем» аргументы и саму функцию
		var args = [];
		for( var i = 0; i < arguments.length; i++ ) {
			args[i] = arguments[i];
		}
		return function () {
			return args[0].apply( null, args.slice( 1 ) );
		};
	};
};

/**
*	Заполнение строки значениями переданного объекта по шаблону, аналог printf.
*	data - объект с данными 
*	Возвращает строку с замененными данными.
*	Пример 'Самое время отправиться {where}!'.supplant( { where: 'погулять' } );
*/
String.prototype.supplant = function ( data ) {
	return this.replace( /{([^{}]+)}/g, function( a, b ) {
		var r = data[b];
		return r !== undefined ? r : a;
	} );
};


var Improved = window.Improved = {
    createStylesheet: function (text) {
        var e;      // DOM element
        try {
            e = document.createElement("style");
            e.type = "text/css";

            // Для старых IE важно выполнять манипуляции с таблицей стилей только после вставки её в дерево документа
            document.getElementsByTagName("head")[0].appendChild(e);

            if (e.styleSheet) {
                // The Microsoft Way
                e.styleSheet.cssText = text;
            } else {
                e.appendChild(document.createTextNode(text));
            }

            return true;
        } catch (x) {
            return false;
        }
    }
};