var Builder = window.Builder = {};

// URI к системе управления
Builder.uri = /(\/[^\/]+)/.test(location.pathname) ? RegExp.$1 : '';

//jquery селекторы для страницы администрирования
Builder.selectors = {
    items: ".js_cms-items",
    item: ".js_cms-item",
    item_body: ".js_cms-item_body",
    item_edit_button: ".js_cms-edit_item",
    item_enabled_button: ".js_cms-enabled_item",
    item_switcher_button: ".js_cms-switcher_item",
    item_copy_button: ".js_cms-copy_item",
    item_delete_button: ".js_cms-delete_item",
    item_title: "js_cms-item_title",
    item_edit_form: "#js_cms-item_edit_form",
    //определены в nami.js
//    item_edit_form_save_button: ".js_cms_item_edit_form__save",
//    item_edit_form_cancel_button: ".js_cms_item_edit_form__cancel",
//
    item_is_disabled: "builder-list_item-disabled", //только название класса
    item_preview_template: "#js_cms-item_preview_template",
    item_preview_title: ".js_cms-item_title",
    create_form_place: ".js_cms-create_form_place",
    create_button: ".js_cms-create_item",
    drag_drop_disable: ".js_cms-drag_drop_disabled",
    dependent_item_class: "js_cms-dependent_item"
};

Builder.showAjaxLoader = function() {
    $('#ajax-notifier').show();
};

Builder.hideAjaxLoader = function() {
    $('#ajax-notifier').hide();
};

/**
 * Задействование wysiwyg редактора
 */
Builder.enableRichTextareas = function() {
    var ck_count = 1;
    $('textarea[rich=yes]').bind('show', function(event, inputValue, inputName, namiForm) {
        var $textarea = $(this),
            editor, ed = CKEDITOR,
            editor_config = {
                allowedContent: true
            },
            $form = $textarea.parents(Builder.selectors.item_edit_form),
            $first_textarea = $textarea.parents(Builder.selectors.item_edit_form).find('.block:first textarea'),
            first_elem_name = $first_textarea.size() ? $first_textarea.attr('name') : '';

        $textarea.attr("id", "ck_edit_" + ck_count);

        // если первый элемент является textarea c richtext=yes, то на такой элемент тоже нужно поставить фокус
        if (first_elem_name === $textarea.attr('name')) {
            editor_config.startupFocus = true;
        }
        ed = ed.replace("ck_edit_" + ck_count, editor_config);

        editor = CKEDITOR.instances["ck_edit_" + ck_count];
        // editor.on('key', function(e) {
            // if (e.data.keyCode == '1114125') {
            //     $form.find(Nami.Form.submitButtonSelector).click();
            // }
            // if (e.data.keyCode == '27') {
            //     $form.find(Nami.Form.cancelButtonSelector).click();
            // }
        // });

        editor.on('change', function() {
            $textarea.trigger("change");
        });

        ck_count++;

        namiForm.bind('destroy', function(form) {
            ed.destroy();
        });

        $textarea.parents('.block').addClass('richtext');
    });
    return this;
};


/**
 *   Оживление загрузчиков изображений
 */
Builder.enableImageuploaders = function(immediate) {
    $('input[imageupload=yes],input[imagesupload=yes]').on('show', function(event, inputValue, inputName, namiForm) {

        var $input = $(this),
            isMultiple = $input.attr('imagesupload') === 'yes',
            $container = $input.parents(".js-cms_imagesupload__container"),
            $images = $container.find(".js-cms_imagesupload__images"),
            $choose = $container.find(".js-cms_imagesupload__input_link"),
            ajaxUploadUri = "/create/",
            ajaxUploadMultiple = false,
            ajaxUploadFileName = 'image';


        /**
         *   Отображение картинки.
         *   Создает DOM-объекты и добавляет их в .images
         */
        function displayImage(imageValue) {
            var $newImg = $images
                .find(".js-cms_imagesupload__image_block-template")
                .clone()
                .removeClass("js-cms_imagesupload__image_block-template");

            $newImg
                .data('uri', imageValue.original.uri);

            $newImg
                .find('img')
                .attr('src', imageValue.cms.uri);

            $newImg
                .find('.js-cms_imagesupload__image_zoom')
                .attr('href', imageValue.original.uri)
                .attr('onclick', "window.open('" + location.origin + imageValue.original.uri + "', '_blank', 'location=yes,scrollbars=yes,status=yes')")
                .on("click", function(e) {
                    e.preventDefault();
                });


            $newImg.find('.js-cms_imagesupload__image_remove').click(function() {
                namiForm.markAsDirty(inputName);
                $newImg.remove();
                setChooseText();
            });

            $images.append($newImg);
            $newImg.show();
        }

        /**
         *   Устанавливает текст ссылки «выбрать изображение»
         *   в соответствии с количеством уже загруженных картинок
         */
        var setChooseText = function() {
            var chosenCount = $images.find('.js-cms_imagesupload__image_block:not(.js-cms_imagesupload__image_block-template)').size(),
                chosenText = "";

            chosenText += "<button class='uk-button uk-button-small'>";
            if (isMultiple) {
                chosenText += chosenCount ? '<i class="uk-icon-plus"></i> добавить изображения' : '<i class="uk-icon-plus"></i> выбрать изображения';
            } else {
                chosenText += chosenCount ? '<i class="uk-icon-refresh"></i> заменить изображение' : '<i class="uk-icon-plus"></i> выбрать изображение';
            }
            chosenText += "</button>";

            $choose.html(chosenText);
        };


        // Во множественном редакторе картинки можно сортировать перетаскиванием
        if (isMultiple) {
            var filesSortableObject = UIkit.sortable($images, {
                animation: false,
            });

            filesSortableObject.options.change = function() {
                namiForm.markAsDirty(inputName);
            };

            ajaxUploadUri = "/create_many/";
            ajaxUploadMultiple = true;
        }


        // Загрузчик картинки вешаем на ссылку в .choose
        new AjaxUpload($choose.get(0), {
            action: Nami.getPath('TempImage').getUri() + ajaxUploadUri,
            name: "files[]",
            multiple: ajaxUploadMultiple,
            responseType: 'json',
            data: {
                is_file_upload: true,
                replace_field: ajaxUploadFileName
            },
            onSubmit: function() {
                Builder.showAjaxLoader();
            },
            onComplete: function(file, r) {
                Builder.hideAjaxLoader();
                if (r.success) {
                    if (isMultiple) {
                        for (var i = 0, max = r.data.length; i < max; i++) {
                            displayImage(r.data[i].image);
                        }
                    } else {
                        $images.find('.js-cms_imagesupload__image_block:not(.js-cms_imagesupload__image_block-template)').remove();
                        displayImage(r.data.image);
                    }

                    setChooseText();
                    namiForm.markAsDirty(inputName);
                } else {
                    Nami.defaultOnFailure({
                        success: false,
                        message: 'Ошибка при загрузке изображения ' + file + '. ' + r.message
                    });
                }
            }
        });


        namiForm.bind('fetchdata', function(form, data) {
            if (namiForm.fieldIsDirty(inputName)) {

                var imageUris = [];
                $images.find('.js-cms_imagesupload__image_block:not(.js-cms_imagesupload__image_block-template)').each(function() {
                    imageUris.push({
                        server_path_uri: $(this).data('uri'),
                        title: "",
                    });
                });

                if (isMultiple) {
                    data[inputName] = imageUris;
                } else {
                    data[inputName] = imageUris.length ? imageUris[0] : null;
                }
            }
        });


        // Отображаем в редакторе текущее состояние
        if (inputValue) {
            if (isMultiple) {
                for (var i = 0; i < inputValue.length; i += 1) {
                    displayImage(inputValue[i]);
                }
            } else {
                displayImage(inputValue);
            }
        }

        setChooseText();

    });

    return this;
};

Builder.getFileIconClass = function(filename) {
    var match, mapping = {
        csv: 'file-archive-o',
        xls: 'file-excel-o',
        xlsx: 'file-excel-o',
        ods: 'file-excel-o',
        swf: 'file-video-o',
        jpg: 'file-image-o',
        jpeg: 'file-image-o',
        png: 'file-image-o',
        gif: 'file-image-o',
        bmp: 'file-image-o',
        ico: 'file-image-o',
        pdf: 'file-pdf-o',
        psd: 'file-archive-o',
        ppt: 'file-powerpoint-o',
        pptx: 'file-powerpoint-o',
        odp: 'file-powerpoint-o',
        rtf: 'file-text-o',
        htm: 'file-text-o',
        html: 'file-text-o',
        txt: 'file-text-o',
        doc: 'file-word-o',
        docx: 'file-word-o',
        odt: 'file-word-o',
        mov: 'file-movie-o',
        avi: 'file-movie-o',
        mkv: 'file-movie-o',
        mp4: 'file-movie-o',
        wmv: 'file-movie-o',
        zip: 'file-archive-o',
        rar: 'file-archive-o',
        gz: 'file-archive-o',
        tar: 'file-archive-o',
        tgz: 'file-archive-o',
        mp3: 'file-audio-o',
        wav: 'file-audio-o',
        ape: 'file-audio-o',
        flac: 'file-audio-o'
    };

    match = filename.match(/\.([^.]+)$/);

    return 'uk-icon-{name}'.supplant({
        name: mapping[match && match[1]] || 'file-archive-o'
    });
};

Builder.getReadableFileSize = function(byteCount) {
    var units = [
            [1073741824, 'Гб'],
            [1048576, 'Мб'],
            [1024, 'Кб'],
            [1, 'Б']
        ],
        i,
        ten_sizes,
        remainder;

    for (i = 0; i < units.length; i += 1) {
        if (byteCount >= units[i][0]) {
            ten_sizes = byteCount * 10 / units[i][0];
            remainder = Math.floor(ten_sizes % 10);
            return '{quotient}{remainder} {name}'.supplant({
                quotient: Math.floor(ten_sizes / 10),
                remainder: remainder ? '.' + remainder : '',
                name: units[i][1]
            });
        }
    }
};

Builder.enableFileuploaders = function() {
    $('input[fileupload=yes], input[filesupload=yes]').on('show', function(event, inputValue, inputName, namiForm) {

        var $input = $(this),
            isMultiple = $input.attr('filesupload') === 'yes',
            $container = $input.parents(".js-cms_filesupload__container"),
            $files = $container.find(".js-cms_filesupload__files"),
            $choose = $container.find(".js-cms_filesupload__input_link"),
            ajaxUploadUri = "/create/",
            ajaxUploadMultiple = false,
            ajaxUploadFileName = 'file';


        /**
         *   Отображение картинки.
         *   Создает DOM-объекты и добавляет их в .images
         */
        var displayFile = function(fileValue) {
            if (!fileValue) {
                return false;
            }

            var $newFile = $files
                .find(".js-cms_filesupload__file_block-template")
                .clone()
                .removeClass("js-cms_filesupload__file_block-template");

            $newFile
                .data('uri', fileValue.uri)
                .data('title', fileValue.title);


            var $titleText_ = $newFile.find('.js-cms_filesupload__file_block_filetitle'),
                $titleInput_ = $newFile.find('.js-cms_filesupload__file_block_edittitle'),
                $fileInfo_ = $newFile.find('.js-cms_filesupload__file_block_fileinfo'),
                $editLink_ = $newFile.find('.js-cms_filesupload__file_edit'),
                $saveLink_ = $newFile.find('.js-cms_filesupload__file_save'),
                $cancelLink_ = $newFile.find('.js-cms_filesupload__file_cancel'),
                $removeLink_ = $newFile.find('.js-cms_filesupload__file_remove');


            $titleText_.text(fileValue.title);

            $titleInput_
                .on("change", function() {
                    $newFile.data("title", $(this).val());
                    namiForm.markAsDirty(inputName);
                })
                .val(fileValue.title);


            var fileInfoText = "";

            fileInfoText += "&emsp;";
            fileInfoText += "<i class='uk-icon " + Builder.getFileIconClass(fileValue.name) + "'></i> ";
            fileInfoText += "<a>";
            fileInfoText += fileValue.name.split('.').pop();
            fileInfoText += ", ";
            fileInfoText += Builder.getReadableFileSize(fileValue.size);
            fileInfoText += "</a>";

            $fileInfo_.html(fileInfoText);

            $fileInfo_
                .find("a")
                .attr('href', fileValue.uri)
                .attr('onclick', "window.open('" + location.origin + fileValue.uri + "', '_blank', 'location=yes,scrollbars=yes,status=yes')")
                .on("click", function(e) {
                    e.preventDefault();
                });


            $editLink_.on("click", function(e) {
                e.preventDefault();

                $editLink_.hide();
                $titleText_.hide();
                $titleInput_.show().focus().select();
                $saveLink_.show();
                $cancelLink_.show();
            });

            $saveLink_.on("click", function(e) {
                e.preventDefault();

                $saveLink_.hide();
                $cancelLink_.hide();
                $editLink_.show();
                $titleText_.show().text($titleInput_.val().substring(0, 100));
                $titleInput_.hide();
            });

            $titleInput_.on("keypress", function(e) {
                if (e.keyCode == 13) {
                    $saveLink_.trigger("click");
                }
            });

            $cancelLink_.on("click", function(e) {
                e.preventDefault();

                $newFile.data('title', $titleText_.text());

                $saveLink_.hide();
                $cancelLink_.hide();
                $editLink_.show();
                $titleText_.show();
                $titleInput_.hide();
            });

            $removeLink_.click(function() {
                namiForm.markAsDirty(inputName);
                $newFile.remove();
                setChooseText();
            });

            $files.append($newFile);
            $newFile.show();
        };

        /**
         *   Устанавливает текст ссылки «выбрать изображение»
         *   в соответствии с количеством уже загруженных картинок
         */
        var setChooseText = function() {
            var chosenCount = $files.find('.js-cms_filesupload__file_block:not(.js-cms_filesupload__file_block-template)').size(),
                chosenText = "";

            chosenText += "<button class='uk-button uk-button-small'>";
            if (isMultiple) {
                chosenText += chosenCount ? '<i class="uk-icon-plus"></i> добавить файлы' : '<i class="uk-icon-plus"></i> выбрать файлы';
            } else {
                chosenText += chosenCount ? '<i class="uk-icon-refresh"></i> заменить файл' : '<i class="uk-icon-plus"></i> выбрать файл';
            }
            chosenText += "</button>";

            $choose.html(chosenText);
        };

        if (isMultiple) {
            var sortable = UIkit.sortable($files, {
                animation: false,
                handleClass: 'uk-nestable-handle__files',
            });

            sortable.options.change = function() {
                namiForm.markAsDirty(inputName);
            };

            ajaxUploadUri = "/create_many/";
            ajaxUploadMultiple = true;
        }

        // Загрузчик картинки вешаем на ссылку в .choose
        new AjaxUpload($choose.get(0), {
            action: Nami.getPath('TempFile').getUri() + ajaxUploadUri,
            name: "files[]",
            multiple: ajaxUploadMultiple,
            responseType: 'json',
            data: {
                is_file_upload: true,
                replace_field: ajaxUploadFileName
            },
            onSubmit: function() {
                Builder.showAjaxLoader();
            },
            onComplete: function(file, r) {
                Builder.hideAjaxLoader();
                if (r.success) {
                    if (isMultiple) {
                        for (var i = 0, max = r.data.length; i < max; i++) {
                            displayFile(r.data[i].file);
                        }
                    } else {
                        $files.find('.js-cms_filesupload__file_block:not(.js-cms_filesupload__file_block-template)').remove();
                        displayFile(r.data.file);
                    }

                    setChooseText();
                    namiForm.markAsDirty(inputName);
                } else {
                    Nami.defaultOnFailure({
                        success: false,
                        message: 'Ошибка при загрузке файла ' + file + '. ' + r.message
                    });
                }
            }
        });


        // На получение данных формы выдаем значение, которое собираем из загруженных файлов
        namiForm.bind('fetchdata', function(form, data) {
            if (namiForm.fieldIsDirty(inputName)) {
                var filesUris = [];
                $files.find('.js-cms_filesupload__file_block:not(.js-cms_filesupload__file_block-template)').each(function() {
                    filesUris.push({
                        server_path_uri: $(this).data('uri'),
                        title: $(this).data('title'),
                    });
                });

                if (isMultiple) {
                    data[inputName] = filesUris;
                } else {
                    data[inputName] = filesUris.length ? filesUris[0] : null;
                }
            }
        });


        // Отображаем в редакторе текущее состояние
        if (inputValue) {
            if (isMultiple) {
                for (var i = 0; i < inputValue.length; i += 1) {
                    displayFile(inputValue[i]);
                }
            } else {
                displayFile(inputValue);
            }
        }

        setChooseText();
    });

    return this;
};

Builder.enableChosens = function() {
    $('.js-cms_chosen_widget').on('show', function(event, inputValue, inputName, namiForm) {
        var $input = $(this);

        if (namiForm.mode === "edit") {
            if ($(this).attr("data-not_use_own_id")) {
                var id = namiForm.loadedData.id;
                $(this).find("option[value=" + id + "]").remove();
            }
        }

        $input
            .chosen({
                width: "100%"
            })
            .change(function() {
                namiForm.markAsDirty(inputName);
            });

        namiForm.bind('fetchdata', function(form, data) {
            data[inputName] = JSON.stringify($input.val());
        });

        if (inputValue) {
            var dbVal = $.parseJSON(inputValue);
            $input.val(dbVal).trigger("chosen:updated");
        }
    });
};

Builder.enableDatepickers = function() {
    var options = {
        lang: 'ru',
        scrollMonth: false,
        scrollTime: false,
        scrollInput: false,
        i18n: {
            de: {
                months: ['Январь', 'Февраль', 'Март', 'Апрель', 'Май', 'Июнь', 'Июль', 'Август', 'Сентябрь', 'Октябрь', 'Ноябрь', 'Декабрь'],
                dayOfWeek: ['Вс', 'Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб']
            }
        },
        closeOnDateSelect: true,
        dayOfWeekStart: 1,
    };

    $('input[datepicker=yes]').on('show', function() {
        var optionsDatePicker = options;
        optionsDatePicker.timepicker = false;
        optionsDatePicker.format = 'd.m.Y';

        $(this).datetimepicker(optionsDatePicker);
    });

    $('input[datetimepicker=yes]').on('show', function() {
        var optionsTimePicker = options;
        optionsTimePicker.timepicker = true;
        optionsTimePicker.format = 'd.m.Y H:i:s';

        $(this).datetimepicker(optionsTimePicker);
    });

    return this;
};


Builder.enableJSONInputs = function() {
    $('.js-cms_json_input').on('show', function(event, inputValue, inputName, namiForm) {
        var $input = $(this),
            $container = $input.parents(".js-cms_json_input__container"),
            $table = $container.find("table"),
            $template = $table.find(".js-cms_json_input__row-template"),
            $addRow = $table.find(".js-cms_json_input__add_row"),
            $addButton = $table.find(".js-cms_json_input__add_btn");


        var saveInputValue = function() {
            var newValue = [];

            $table.find("tr").each(function() {
                if (!$(this).hasClass("js-cms_json_input__row")) {
                    return true;
                }

                var newRowData = {},
                    $row = $(this);

                $row.find("[data-key_name]").each(function() {
                    newRowData[$(this).attr("data-key_name")] = $(this).text();
                });

                newValue.push(newRowData);
            });


            $input.val(JSON.stringify(newValue));
            namiForm.markAsDirty(inputName);
        };

        var renderTableRow = function(rowData) {
            var $newRow = $template.clone();

            for (var key in rowData) {
                $newRow.find("[data-key_name=" + key + "]").text(rowData[key]);
            }

            $newRow
                .show()
                .removeClass("js-cms_json_input__row-template")
                .addClass("js-cms_json_input__row")
                .insertBefore($table.find("tr:last"));


            //пользовательские контроллы
            $newRow.on("click", ".js-cms_json_input__edit_btn", function() {
                $newRow.find(".js-cms_json_input__actions_preview").hide();
                $newRow.find(".js-cms_json_input__actions_edit").show();
                $newRow.find("td[data-key_name]").each(function() {
                    var $newInput = $("<input />");
                    $newInput
                        .addClass("uk-width-1-1")
                        .addClass("uk-form-small")
                        .val($(this).text())
                        .attr("data-old_value", $(this).text())
                        .on("keypress", function(e) {
                            if (e.keyCode == 13) {
                                $newRow
                                    .find(".js-cms_json_input__save_btn")
                                    .trigger("click");
                            }
                        });

                    $(this).html($newInput);
                });
                $newRow.find("input:first").focus();
            });

            $newRow.on("click", ".js-cms_json_input__remove_btn", function() {
                $newRow.remove();
                saveInputValue();
            });

            $newRow.on("click", ".js-cms_json_input__save_btn", function() {
                $newRow.find("td[data-key_name]").each(function() {
                    $(this).text($(this).find("input").val());
                });
                saveInputValue();
                $newRow.find(".js-cms_json_input__actions_preview").show();
                $newRow.find(".js-cms_json_input__actions_edit").hide();
            });

            $newRow.on("click", ".js-cms_json_input__cancel_btn", function() {
                $newRow.find("td[data-key_name]").each(function() {
                    $(this).text($(this).find("input").attr("data-old_value"));
                });
                saveInputValue();
                $newRow.find(".js-cms_json_input__actions_preview").show();
                $newRow.find(".js-cms_json_input__actions_edit").hide();
            });
        };


        if (inputValue) {
            try {
                var oldValue = $.parseJSON(inputValue);
                for (var i = 0; i < oldValue.length; i++) {
                    renderTableRow(oldValue[i]);
                }
            } catch (e) {
                console.log("неверное значение в поле `" + inputName + "`");
            }
        }


        $table.on("click", ".js-cms_json_input__remove_btn", function(e) {
            e.preventDefault();

            $(this).parents("tr").remove();
            saveInputValue();
        });


        $addButton.on("click", function(e) {
            e.preventDefault();

            var newData = {};

            $addRow
                .find("input")
                .each(function() {
                    newData[$(this).attr("data-key_name")] = $(this).val();
                });

            $addRow.find("input").val("");

            renderTableRow(newData)
            saveInputValue();
        });

        $addRow
            .find("input")
            .on("keypress", function(e) {
                if (e.keyCode == 13) {
                    e.preventDefault();
                    $addButton.trigger("click");
                    $addRow
                        .find("input:first")
                        .focus();
                }
            });
    });

    return this;
};


Builder.Interface = {
//     группировка элементов
    group_depented: function(form) {
        form.bind('fill', function(form, data) {
            form.find('.' + Builder.selectors.dependent_item_class).each(function() {
                var $dependent_block = $(this),
                    input_name = $dependent_block.attr("data-cms_dependent_input"),
                    $dependent_input = form.find("input[name=" + input_name + "], textarea[name=" + input_name + "], select[name=" + input_name + "]");

                var value = '';

                if ($dependent_input.attr('type') == 'checkbox') {
                    value = $dependent_input.is(":checked");
                } else {
                    value = $dependent_input.val();
                }

                if (!value) {
                    $dependent_block.hide();
                }

                $dependent_input.on('change input', function(e) {
                    var value = '';

                    if (this.type == 'checkbox') {
                        value = $(this).is(":checked");
                    } else {
                        value = $(this).val();
                    }

                    if (value) {
                        $dependent_block.show();
                    } else {
                        $dependent_block.hide();
                    }
                });
            });
        });
    },
    customEditorInterface: function(namiForm) {
        if (namiForm.form.find(".js-custom_edit_widget").length) {
            var moduleUrl = "/cms/customeditor/",
                moduleAjaxUrl = "/cms/ajax/customeditor/",
                generateNewPublication = function(modelName, modelId, fieldName, callback) {
                    $.ajax({
                        type: 'POST',
                        dataType: 'json',
                        data: {
                            action: "generate-new-id",
                            modelId: modelId,
                            modelName: modelName,
                            modelField: fieldName,
                        },
                        url: moduleAjaxUrl,
                        success: function(response) {
                            if (response.status) {
                                callback(response.data);
                            }
                        }
                    });
                };


            namiForm.bind('fill', function($form, data) {
                var modelId = this.object.object,
                    mode = this.mode,
                    form = this,
                    modelName = this.object.model;

                $form.find(".js-custom_edit_widget").each(function() {
                    var $link = $(this).find(".js-custom_edit_widget__link"),
                        $field = $(this).find("input");

                    if (mode == "create") {
                        var fieldName = $field.attr("name"),
                            newPublicationId = null;

                        generateNewPublication(modelName, modelId, fieldName, function(id) {
                            newPublicationId = id;

                            $link.attr("href", moduleUrl + id + "/");
                            $field.val(id).trigger("change");
                        });


                        form.bind('fillitem', function($item, response) {
                            $.ajax({
                                type: 'POST',
                                dataType: 'json',
                                data: {
                                    action: "set-model-id",
                                    modelId: response.id,
                                    publicationId: newPublicationId,
                                },
                                url: moduleAjaxUrl,
                            });
                        });


                    } else {
                        if ($field.val()) {
                            $link.attr("href", moduleUrl + $field.val() + "/");
                        } else {
                            generateNewPublication(modelName, modelId, $field.attr("name"), function(id) {
                                $link.attr("href", moduleUrl + id + "/");
                                $field.val(id).trigger("change");
                            });
                        }
                    }
                });
            });
        }
    },
    formControlInterface: function(namiForm) {
        if (namiForm.form.find(".js-form_control_widget").length) {

            namiForm.bind('fill', function($form, data) {
                $form.find(".js-form_control_widget").each(function() {
                    var $select = $(this).find("select");

                    $select
                        .on("change", function() {
                            var curValue = $(this).val();

                            if ($select.attr("data-hide_" + curValue)) {
                                var hideItemsNames = $select.attr("data-hide_" + curValue).split(",");

                                hideItemsNames.forEach(function(name) {
                                    $form
                                        .find("[name=" + name + "]")
                                        .parents(".uk-form-row")
                                        .hide();
                                });
                            }

                            if ($select.attr("data-show_" + curValue)) {
                                var showItemsNames = $select.attr("data-show_" + curValue).split(",");

                                showItemsNames.forEach(function(name) {
                                    $form
                                        .find("[name=" + name + "]")
                                        .parents(".uk-form-row")
                                        .show();
                                });
                            }
                        })
                        .trigger("change");


                    if (!$select.val()) {
                        $select.val($select.find("option:first").val());
                        $select.change();
                    }

                    // медленный редактор загружается позже всех
                    if (typeof CKEDITOR != "undefined") {
                        CKEDITOR.on("instanceReady", function(event) {
                            $select.change();
                        });
                    }
                });
            });

        }
    },
    catalog_item_ids_widget_interface: function(namiForm) {
        if (namiForm.form.find(".js-catalog_item_ids_widget").length) {
            var ENTRY_MODEL_NAME = 'CatalogEntry',
                CATEORY_MODEL_NAME = 'CatalogCategory';


            //отрисовка отмеченных элементов каталога
            var renderCatalogItem = function(title, id, $widgetBlock) {
                var $newCatalogItem = $widgetBlock
                    .find(".js-catalog_item_ids_widget__item-template")
                    .clone()
                    .removeClass("js-catalog_item_ids_widget__item-template");

                $newCatalogItem.find(".js-catalog_item_ids_widget__item_title").text(title);

                $newCatalogItem.insertAfter($widgetBlock.find(".js-catalog_item_ids_widget__item-template"));
                $newCatalogItem.show();
                $newCatalogItem.attr("data-item_id", id);

                $newCatalogItem.find(".js-catalog_item_ids_widget__item_remove").click(function(e) {
                    e.preventDefault();

                    $(this).parents(".catalog_item_ids_widget__item").remove();

                    $widgetBlock
                        .find(".js-catalog_item_ids_widget__add_conntrolls-category")
                        .change();

                    update_field_value($widgetBlock);
                });
            }

            //обновление скрытого инпута
            var update_field_value = function($widgetBlock) {
                var newItemIds = [];
                $widgetBlock.find(".catalog_item_ids_widget__item:not(.js-catalog_item_ids_widget__item-template)").each(function() {
                    newItemIds.push($(this).attr("data-item_id"));
                });

                $widgetBlock
                    .find(".catalog_item_ids_widget__input")
                    .val(JSON.stringify(newItemIds))
                    .change();
            }


            namiForm.bind('fill', function(namiForm, namiFormData) {

                namiForm.find(".js-catalog_item_ids_widget").each(function() {
                    var $widgetBlock = $(this),
                        oldIdsValue = namiFormData[$widgetBlock.find(".catalog_item_ids_widget__input").attr("name")],
                        useOwnId = $widgetBlock.find(".js-catalog_item_ids_widget-not_use_own_id").length;

                    if (oldIdsValue) {
                        oldIdsValue = $.parseJSON(oldIdsValue);
                    } else {
                        oldIdsValue = [];
                    }


                    //наполнение элементами выбранными ранее
                    Nami.retrieve(ENTRY_MODEL_NAME, Object.create(Nami.Query).use('filter', {id__in: oldIdsValue}).use('order', 'title'), function(data) {
                        for (var i = 0, max = data.length; i < max; i++) {
                            renderCatalogItem(data[i].title, data[i].id, $widgetBlock);
                        }
                    });

                    //добавление позиции
                    $widgetBlock
                        .find(".js-catalog_item_ids_widget__add_conntrolls-button")
                        .bind("click", function() {

                            renderCatalogItem(
                                $widgetBlock.find(".js-catalog_item_ids_widget__add_conntrolls-entry").find("option:selected").text(),
                                $widgetBlock.find(".js-catalog_item_ids_widget__add_conntrolls-entry").val(),
                                $widgetBlock
                            );

                            $widgetBlock.find(".js-catalog_item_ids_widget__add_conntrolls-entry").find("option:selected").remove()

                            $widgetBlock
                                .find(".js-catalog_item_ids_widget__add_conntrolls-entry")
                                .change();

                            update_field_value($widgetBlock);
                        });

                    //включение отключение кнопки добавить
                    $widgetBlock
                        .find(".js-catalog_item_ids_widget__add_conntrolls-entry")
                        .bind("change", function() {
                            if ($(this).val() > 0) {
                                $widgetBlock
                                    .find(".js-catalog_item_ids_widget__add_conntrolls-button")
                                    .removeAttr("disabled");
                            } else {
                                $widgetBlock
                                    .find(".js-catalog_item_ids_widget__add_conntrolls-button")
                                    .attr("disabled", "disabled");
                            }
                        });


                    //отображение интерфейса добавления
                    $widgetBlock.find(".js-catalog_item_ids_widget__add").click(function() {
                        //заполнение селекта категорий
                        Nami.retrieve(CATEORY_MODEL_NAME, Object.create(Nami.Query).use('order,sortpos'), function(data) {
                            if (data) {
                                var spaces = "";
                                for (var i = 0, max = data.length; i < max; i++) {
                                    $widgetBlock
                                        .find(".js-catalog_item_ids_widget__add_conntrolls-category")
                                        .append($("<option value='" + data[i].id + "'>" + data[i].title + "</option>"));
                                }
                            }

                            //заполнение селекта с товарами
                            $widgetBlock
                                .find(".js-catalog_item_ids_widget__add_conntrolls-category")
                                .bind("change", function() {

                                    //сброс значений в селекте товаров
                                    $widgetBlock
                                        .find(".js-catalog_item_ids_widget__add_conntrolls-entry")
                                        .empty();

                                    //отключение кнопки добавить
                                    $widgetBlock
                                        .find(".js-catalog_item_ids_widget__add_conntrolls-button")
                                        .attr("disabled", "disabled");

                                    $widgetBlock
                                        .find(".js-catalog_item_ids_widget__add_conntrolls-entry")
                                        .append($("<option value=''>…</option>"));


                                    var hideEntryIds = [];
                                    $widgetBlock.find(".catalog_item_ids_widget__item:not(.js-catalog_item_ids_widget__item-template)").each(function() {
                                        hideEntryIds.push($(this).attr("data-item_id"));
                                    });

                                    if (useOwnId) {
                                        hideEntryIds.push(namiFormData.id);
                                    }

                                    //запрос
                                    var queryObject = Object
                                        .create(Nami.Query)
                                        .use('filter', {
                                            category: $(this).val(),
                                            id__notin: hideEntryIds
                                        })
                                        .use('order,title');

                                    Nami.retrieve(ENTRY_MODEL_NAME, queryObject, function(data) {
                                        for (var i = 0, max = data.length; i < max; i++) {
                                            $widgetBlock
                                                .find(".js-catalog_item_ids_widget__add_conntrolls-entry")
                                                .append($("<option value='" + data[i].id + "'>" + data[i].title + "</option>"));
                                        }
                                    });
                                });
                        });

                        $widgetBlock.find(".catalog_item_ids_widget__add_item_link").hide();
                        $widgetBlock.find(".catalog_item_ids_widget__add_conntrolls").show();
                    });


                    //сброс значений в селекте товаров
                    $widgetBlock
                        .find(".js-catalog_item_ids_widget__add_conntrolls-entry")
                        .empty();


                    //отключение кнопки добавить
                    $widgetBlock
                        .find(".js-catalog_item_ids_widget__add_conntrolls-button")
                        .attr("disabled", "disabled");

                    $widgetBlock
                        .find(".js-catalog_item_ids_widget__add_conntrolls-entry")
                        .append($("<option value=''>…</option>"));

                    $widgetBlock.find(".catalog_item_ids_widget__add_item_link").show();
                    $widgetBlock.find(".catalog_item_ids_widget__add_conntrolls").hide();
                });

            });
        }
    },
    yandexmapInterface: function(nami_form) {
        if (nami_form.form.find(".js-map_widget").length) {


            function CrossControl(options) {
                this.events = new ymaps.event.Manager();
                this.options = new ymaps.option.Manager();
            }

            CrossControl.prototype = {
                constructor: CrossControl,
                setParent: function(parent) {
                    this.parent = parent;

                    if (parent) {
                        var map = this._map = parent.getMap();
                        this._setPosition(map.container.getSize());
                        this._setupListeners();
                        this.layout = new this.constructor.Layout({options: this.options});
                        this.layout.setParentElement(map.panes.get('events').getElement());
                    } else {
                        this.layout.setParentElement(null);
                        this._clearListeners();
                    }

                    return this;
                },
                getParent: function() {
                    return this.parent;
                },
                _setPosition: function(size) {
                    // -8, так как картинка 16х16
                    this.options.set('position', {
                        top: size[1] / 2 - 8,
                        right: size[0] / 2 - 8
                    });
                },
                _onPositionChange: function(e) {
                    this._setPosition(e.get('newSize'));
                },
                _setupListeners: function() {
                    this._map
                        .container
                        .events
                        .add('sizechange', this._onPositionChange, this);
                },
                _clearListeners: function() {
                    if (this._map) {
                        this._map
                            .container
                            .events
                            .remove('sizechange', this._onPositionChange, this);
                    }
                }
            };


            var mapLoaded = false,
                defaultStartCoord = [55.99247, 92.78974];

            function renderMap($maps) {
                $maps = $maps || $(".js-map_widget:visible");

                if (!mapLoaded) {
                    return false;
                }

                $maps.each(function() {
                    var $input = $(this).find(".js-map_widget__coord"),
                        $map = $(this).find(".js-map_widget__map"),
                        inputStartCoord = $input.val().split(",");

                    if (inputStartCoord.length != 2) {
                        inputStartCoord = null;
                    }

                    $map.empty();

                    var map = new ymaps.Map($map.get(0), {
                        center: inputStartCoord || defaultStartCoord,
                        zoom: 16
                    });

                    map.controls.add(new CrossControl);
                    map.behaviors.disable('scrollZoom');

                    map.events.add('actionend', function(e) {
                        var coord = map.getCenter();
                        $input.val(coord[0] + ", " + coord[1]).change();
                    });
                });
            }

            $.getScript("http://api-maps.yandex.ru/2.1/?lang=ru_RU", function(data, textStatus, jqxhr) {
                ymaps.ready(function() {
                    CrossControl.Layout = ymaps.templateLayoutFactory.createClass(
                        '<div class="cross-control" style="right:$[options.position.right]px; top:$[options.position.top]px;"></div>'
                    );

                    mapLoaded = true;
                    renderMap();
                });
            });

            nami_form.bind('fill', function(form, data) {
                renderMap(form.find(".js-map_widget"));
            });
        }
    },
    modelForm: function(form) {
        form
            .extend({
                form: $(Builder.selectors.item_edit_form)
            })
            .bind('fillitem', function(item, data) {
                var $item = item;
                if ($(".uk-nestable").length) {
                    $item = item.parents(".js_cms-item:first");
                }

                if (data.hasOwnProperty('enabled')) {
                    $item
                        .find(Builder.selectors.item_enabled_button + ":first i")
                        .attr("class", data.enabled ? 'uk-icon-eye' : 'uk-icon-eye-slash');

                    if (!data.enabled) {
                        $item.addClass(Builder.selectors.item_is_disabled);
                    } else {
                        $item.removeClass(Builder.selectors.item_is_disabled);
                    }
                }
            });

        this.group_depented(form);
        this.yandexmapInterface(form);
        this.customEditorInterface(form);
        this.formControlInterface(form);
        this.catalog_item_ids_widget_interface(form);

        if (typeof editorParams === 'undefined') {
            //фокус на кнопаре добавить
            $(Builder.selectors.create_button).focus();
        }

        return this;
    },
    listActions: function(formPrototype, action_names) {
        var actions = (action_names || 'create edit enable switcher remove').split(' ');
        var map = {
            create: function() {
                $(Builder.selectors.create_button).click(function() {
                    Object.create(formPrototype).defaults({
                        object: $(Builder.selectors.items).attr('namiModel'),
                        formPlace: $(Builder.selectors.create_form_place),
                        itemPlace: $(Builder.selectors.items),
                        item: $(Builder.selectors.item_preview_template)
                    }).start();

                    return false;
                });
            },
            edit: function() {
                $(Builder.selectors.item_edit_button).click(function() {
                    Object.create(formPrototype).extend({
                        object: Nami.buildPath(this),
                        item: $(this).parents(Builder.selectors.item + ":first")
                    }).start();

                    return false;
                });

                //тоггл сео блока
                $(".js-toggle_seo").click(function(e) {
                    $(this).parents(".js_cms-seo_widget").find(".js-toggle_seo_block").toggle();
                    e.preventDefault();
                });
            },
            copy: function() {
                $(Builder.selectors.item_copy_button).click(function() {
                    var $button = $(this);

                    Nami.copy(
                        Nami.buildPath(this),
                        function(result) {
                            var $new_item = $(Builder.selectors.item_preview_template).clone(true).hide().removeAttr('id');

                            //заполнение полей для элемента
                            if (result) {
                                for (var name in result) {
                                    if (result.hasOwnProperty(name)) {
                                        try {
                                            $new_item.find('[namiText=' + name + ']').text(result[name]);
                                        } catch (e) {
                                        }
                                        try {
                                            $new_item.find('[namiHtml=' + name + ']').html(result[name]);
                                        } catch (e) {
                                        }

                                        if (name === "id") {
                                            $new_item.attr("namiObject", result[name]);
                                        }

                                        if (name === "enabled") {
                                            var $enableImg = $new_item.find(Builder.selectors.item_enabled_button + " i");
                                            if (result[name]) {
                                                if ($enableImg.length) {
                                                    $enableImg.attr("class", "uk-icon-eye");
                                                }

                                            } else {
                                                if ($enableImg.length) {
                                                    $enableImg.attr("class", "uk-icon-eye-slash");
                                                }

                                                $new_item.addClass(Builder.selectors.item_is_disabled);
                                            }
                                        }
                                    }
                                }
                            }

                            $button.parents(Builder.selectors.item + ":eq(0)").after($new_item);
                            $new_item.slideDown(150);
                        },
                        function(result) {
                            Nami.defaultOnFailure({
                                success: false,
                                message: 'Системная ошибка. Копирование не удалось'
                            });
                        }
                    );

                    return false;
                });
            },
            switcher: function() {
                $(Builder.selectors.item_switcher_button).click(function() {
                    Nami.toggle(Nami.buildPath(this), Function.delegate(this, function(data) {
                        if (data[$(this).attr('namiField')]) {
                            $(this).find("i").attr('class', 'uk-icon-' + $(this).attr('namiIconTrue'));
                        } else {
                            $(this).find("i").attr('class', 'uk-icon-' + $(this).attr('namiIconFalse'));
                        }
                    }));
                    return false;
                });
            },
            enable: function() {
                $(Builder.selectors.item_enabled_button).click(function() {
                    Nami.toggle(Nami.buildPath(this), Function.delegate(this, function(data) {
                        if (data.enabled) {
                            $(this).parents(Builder.selectors.item + ":first").removeClass(Builder.selectors.item_is_disabled);
                            $(this).find("i").attr('class', 'uk-icon-eye');
                        } else {
                            $(this).parents(Builder.selectors.item + ":first").addClass(Builder.selectors.item_is_disabled);
                            $(this).find("i").attr('class', 'uk-icon-eye-slash');
                        }
                    }));
                    return false;
                });
            },
            remove: function() {
                $(Builder.selectors.item_delete_button).click(function() {
                    var title = $(this).parentsUntil(Builder.selectors.item).find(Builder.selectors.item_preview_title).text();
                    if (confirm('Удалить «' + title.replace(/^\s*/, '').replace(/\s*$/, '') + '»?')) {
                        Nami.remove(Nami.buildPath(this), Function.delegate(this, function() {
                            $(this).parents(Builder.selectors.item + ":first").remove();
                            if ($(".uk-nestable").length) {
                                $(this).parents("ul:first").remove();
                            }
                        }));
                    }
                    return false;
                });
            }
        };

        for (var i = 0, max = actions.length; i < max; i += 1) {
            map[actions[i]].apply(this);
        }

        return this;
    },
    sortable: function(form) {
        $(Builder.selectors.items)
            .find(".builder-items_list__item")
            .prepend('<div class="builder-items_list__item_col_left">' +
                '<div class="uk-nestable-handle uk-nestable-handle__items"></div>' +
                '</div>');

        if ($(".builder-items_list__descr").length) {
            $(".builder-items_list__descr")
                .find(".builder-items_list__item")
                .prepend('<div class="builder-items_list__item_col_left"></div>');
        }


        var itemsSortableObject = UIkit.sortable($(Builder.selectors.items), {
            animation: 0,
            handleClass: 'uk-nestable-handle__items',
            threshold: 0,
        });

        $(Builder.selectors.items).on('stop.uk.sortable', function() {
            saveSortableItems();
        });

        form.bind('showitem', function(item) {
            if (!item.find(".uk-nestable-handle__items").length) {
                item
                    .find(".builder-items_list__item")
                    .prepend('<div class="builder-items_list__item_col_left">' +
                        '<div class="uk-nestable-handle uk-nestable-handle__items"></div>' +
                        '</div>');
            }

            if (this.mode === 'create') {
                saveSortableItems();
            }
        });

        function saveSortableItems() {
            var objects = [],
                path;

            $(Builder.selectors.item).each(function() {
                var id = $(this).attr('namiObject');
                objects.push(id);
                if (!path) {
                    path = Nami.buildPath(this);
                }
            });

            if (objects.length) {
                Nami.reorder(path, objects);
            }
        }

        return true;
    },
    /**
     * Интерфейс сортировки в виде дерева.
     * @param {NamiForm} Nami форма
     * @param {bool} Сколапсировать дерево при загрузке
     * @param {int} макс вложенность дерева
     */
    nestedSortable: function(form, maxDepth) {
        maxDepth = maxDepth || 10;

        $(Builder.selectors.items)
            .find(".builder-items_list__item:not(" + Builder.selectors.drag_drop_disable + ")")
            .prepend('<div class="builder-items_list__item_col_left">' +
                '<div class="uk-nestable-handle uk-nestable-handle__items"></div>' +
                '<div data-nestable-action="toggle"></div>' +
                '</div>');

        $(Builder.selectors.items)
            .find(".builder-items_list__item" + Builder.selectors.drag_drop_disable)
            .prepend('<div class="builder-items_list__item_col_left">&nbsp;</div>');

        var itemsNestableObject = UIkit.nestable($(Builder.selectors.items), {
            maxDepth: maxDepth,
            handleClass: 'uk-nestable-handle__items',
            animation: 0,
            threshold: 20,
            group: 'my-group'
        });

        $(Builder.selectors.items).on('stop.uk.nestable', function() {
            saveTree();
        });


        form.bind('showitem', function(item) {
            if (!item.find(".uk-nestable-handle__items").length) {
                item
                    .find(".builder-items_list__item")
                    .prepend('<div class="builder-items_list__item_col_left">' +
                        '<div class="uk-nestable-handle uk-nestable-handle__items"></div>' +
                        '<div data-nestable-action="toggle"></div>' +
                        '</div>');
            }

            if (this.mode === 'create') {
                saveTree();
            }
        });

        function saveTree() {
            Nami.reorder($(Builder.selectors.items).attr("namiModel"), {
                id: $(Builder.selectors.items).attr("data-root_id"),
                children: itemsNestableObject.serialize(),
            });
        }

        return true;
    }
};

