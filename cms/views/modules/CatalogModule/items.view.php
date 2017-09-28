<?php $model_name = "CatalogEntry"; ?>

<div class="uk-grid">
    <? if ($this->current_category): ?>
        <div class="uk-width-1-1">
            <h4>
                Категория «<?= $this->current_category->title ?>»
            </h4>
        </div>
    <? endif; ?>

    <div class="uk-width-1-1 uk-margin-top">
        <ul class="uk-subnav uk-subnav-pill">
            <?= new View($this->getFilePath() . "/block_subnavi") ?>
        </ul>
    </div>
</div>


<div class="uk-grid uk-margin-small-bottom">
    <div class="uk-width-2-10">

        <button class="uk-button uk-button-success uk-button-medium js_cms-create_item">
            <i class="uk-icon-plus"></i>
            <span class="uk-text-bold">
                добавить запись
            </span>
        </button>

    </div>

    <div class="uk-width-8-10 uk-text-right">        
        <button class="uk-button" data-uk-toggle="{target:'#filter'}">
            <i class="uk-icon-search"></i>
            фильтр
        </button>
    </div>
</div>

<div id="filter" class="uk-grud<? if (!Meta::vars("filter")): ?> uk-hidden<? endif; ?>">
    <div id="filter" class="uk-grud">
        <div class="uk-width-1-1 uk-panel uk-panel-box uk-margin-small-bottom">
            <form class="uk-form uk-panel uk-panel-box">
                <? if ($this->filters->search): ?>
                    <div class="uk-form-row">
                        <label class="uk-form-label">
                            Поиск
                        </label>
                        <div class="uk-form-controls">
                            <input type="text" name="search" class="uk-width-1-1" value="<?= $this->filters->search['value'] ?>">
                        </div>
                    </div>
                <? endif ?>

                <?= $this->filters ?>
                <input type="hidden" name="f">
            </form>
        </div>
    </div>
</div>



<hr class="uk-margin-top-remove uk-margin-small-top">


<div class="uk-grid">
    <div class="uk-width-1-1">
        <div class="js_cms-create_form_place"></div>

        <div class="js_cms-items uk-margin-large-bottom" namiModel=<?= $model_name ?>>

            <? foreach ($this->paginator->objects as $item): ?>
                <div class="js_cms-item<?= $item->enabled ? "" : " builder-list_item-disabled" ?>" namiObject="<?= $item->id ?>">
                    <div class='builder-items_list__item'>

                        <div class="builder-items_list__item_col_middle">
                            <div class="builder-list_item__item_content">
                                <div class="uk-grid">
                                    <?php if (!$this->current_category) : ?>
                                        <div class="uk-width-5-10">
                                            <span class='js_cms-item_title' namiText="title">
                                                <?= $item->title ?>
                                            </span>
                                        </div>
                                        <div class="uk-width-5-10">
                                            <a class='js_cms-item_title' namiText="category" href="/cms/catalog/?category=<?= $item->category->id; ?>&f=">
                                                <?= $item->category->title ?>
                                            </a>
                                        </div>
                                    <?php else: ?>
                                        <div class="uk-width-1-1">
                                            <span class='js_cms-item_title' namiText="title">
                                                <?= $item->title ?>
                                            </span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <div class="builder-items_list__item_col_right">
                            <div class="uk-text-right">
                                <div class="uk-button-group">
                                    <button style="display: none" class="uk-button"></button>
                                    <button class="uk-button js_cms-copy_item"><i class="uk-icon-copy"></i></button>
                                    <button class="uk-button js_cms-edit_item"><i class="uk-icon-pencil"></i></button>
                                    <button class="uk-button js_cms-enabled_item" namiField="enabled"><i class="uk-icon-eye<?= $item->enabled ? "" : "-slash" ?>"></i></button>
                                    <button class="uk-button js_cms-delete_item"><i class="uk-icon-trash"></i></button>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            <? endforeach; ?>

        </div>
    </div>  
</div>


<div class="uk-grid">
    <div class="uk-width-1-1">
        <?= $this->paginator ?>
    </div>
</div>


<div class='uk-width-1-1' id="js_cms-item_edit_form" style="display:none;">
    <div class="builder-add_item_form">
        <form class="uk-form uk-form-horizontal">
            <? if ($this->all_categories): ?>

                <div class="uk-form-row">
                    <label class="uk-form-label">
                        Категория
                    </label>
                    <div class="uk-form-controls">

                        <select name="category" class="uk-width-1-1">
                            <option value="">…</option>
                            <? foreach ($this->all_categories as $category): ?>
                                <option value="<?= $category['id'] ?>">
                                    <?
                                    $i = $category['lvl'];
                                    while ($i > 2) {
                                        echo "&nbsp;&nbsp;&nbsp;";
                                        $i--;
                                    }
                                    ?>
                                    <?= $category['title'] ?>
                                </option>
                            <? endforeach; ?>
                        </select>

                    </div>
                </div>

            <? endif; ?>

            <?= NamiFormGenerator::forModel($model_name); ?>

            <div class="uk-form-row">
                <div class="extrafields"></div>
            </div>

            <div class="uk-form-row">
                <div class="uk-text-right uk-margin-large-top">
                    <button type="button" class="uk-button uk-button-success js_cms_item_edit_form__save">
                        <i class="uk-icon-save"></i>
                        Сохранить
                    </button>

                    <button type="button" class="uk-button uk-button-danger js_cms_item_edit_form__cancel">
                        Отмена
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>


<div class="js_cms-item" id="js_cms-item_preview_template" style="display:none;">
    <div class='builder-items_list__item'>

        <div class="builder-items_list__item_col_middle">
            <div class="builder-list_item__item_content">

                <div class="uk-grid">
                    <div class="uk-width-1-1">
                        <span class='js_cms-item_title' namiText="title">
                        </span>
                    </div>
                </div>

            </div>
        </div>

        <div class="builder-items_list__item_col_right">
            <div class="uk-text-right">
                <div class="uk-button-group">
                    <button style="display: none" class="uk-button"></button>
                    <button class="uk-button js_cms-copy_item"><i class="uk-icon-copy"></i></button>
                    <button class="uk-button js_cms-edit_item"><i class="uk-icon-pencil"></i></button>
                    <button class="uk-button js_cms-enabled_item" namiField="enabled"><i class="uk-icon-eye"></i></button>
                    <button class="uk-button js_cms-delete_item"><i class="uk-icon-trash"></i></button>
                </div>
            </div>
        </div>

    </div>
</div>


<script>
    $(function () {

        var current_category = <?= $this->current_category ? $this->current_category->id : "false" ?>;
        var itemForm = Object.create(Nami.Form);


        itemForm.bind('beforefill', function () {
            var nami_form = this,
                    data = nami_form.loadedData;

            nami_form.form.find('select[name=category]').bind('change', function () {
                var category = $(this).val();
                nami_form.insert_extrafields(category, false);
            });

            nami_form.insert_extrafields(data.category, true);
        });


        itemForm.bind('fill', function (form, data) {
            if (current_category) {
                form.find('select[name=category]').val(current_category);
                form.find('select[name=category]').change();
            }           
        });

        itemForm.bind('fillitem', function (item, response) {
            if (response.edited) {
                item.removeClass("module_items__item-not_edited");
            } else {
                item.addClass("module_items__item-not_edited");
            }
            
            Nami.retrieve('CatalogCategory', Object.create(Nami.Query).use('get', {id: response.category}), function(category) {
                item.find("[namitext=category]").text(category.title);
            });            
        });

        /**
         * Заполнение кастомных полей для товара
         */
        itemForm.insert_extrafields = function (category, call_form_fill) {
            var nami_form = this,
                    $extrafields = nami_form.form.find('.extrafields');

            // перед тем, как менять поля, сохраним, все значения, которые были введены пользователем
            nami_form.save_data();
            if (category) {
                // вставляем в форму дополнительные поля
                // и заполняем ее значениями
                $extrafields.html('');
                $.ajax({
                    type: 'POST',
                    data: {id: category},
                    url: "<?= $this->ajaxUri ?>",
                    success: function (html) {
                        $extrafields.html(html);

                        Builder.enableChosens();
                        nami_form.fetchExtraInputs();
                        if (call_form_fill) {
                            nami_form.fill();
                        } else {
                            nami_form.fill_saved_data();
                            nami_form.show_inputs();
                        }
                    }
                });
            } else if (!category && !call_form_fill) {
                $extrafields.html('');
            } else {
                nami_form.fill();
            }
        };

        itemForm.fetchExtraInputs = function () {
            var inputs = {}
            this.form.find('.extrafields :input[name]').each(function () {
                var name = $(this).attr('name');
                if (name) {
                    if (inputs[ name ]) {
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
            this.extraInputs = inputs;
            return this;
        };

        /**
         * Задублированный из nami.js медот сборки данных из заполненных полей (fetchData).
         * Оригинальный не подходит, поскольку дергает свои обработчики, нам же просто нужен список данных.
         */
        itemForm.save_data = function () {
            if (typeof this.saved_data === 'undefined') {
                this.saved_data = {};
            }

            for (var name in this.extraInputs) {
                if (this.extraInputs.hasOwnProperty(name)) {
                    if (this.extraInputs[name] instanceof Array) {
                        for (var n in this.extraInputs[name]) {
                            if (this.extraInputs[name].hasOwnProperty(n)) {
                                var input = $(this.extraInputs[name][n]);
                                if (input.attr('type') == 'radio' && input.attr('checked')) {
                                    this.saved_data[name] = input.val();
                                    break;
                                }
                            }
                        }
                        continue;
                    }
                    if ($(this.extraInputs[name]).attr('type') == 'checkbox' && !$(this.extraInputs[name]).attr('checked')) {
                        this.saved_data[name] = 0;
                    } else if ($(this.extraInputs[name]).attr('type') == 'radio' && !$(this.extraInputs[name]).attr('checked')) {
                        continue;
                    } else {
                        this.saved_data[name] = $(this.inputs[name]).val();
                    }
                }
            }
        };

        itemForm.fill_saved_data = function () {
            this.fetchInputs();

            // Заполним поля ввода начальными данными
            for (var name in this.saved_data) {
                if (this.saved_data.hasOwnProperty(name)) {
                    var value = this.saved_data[ name ];
                    // Заполняем поле ввода
                    if (name in this.extraInputs) {
                        if (this.extraInputs[ name ] instanceof Array) {
                            // Массив полей - radio

                            for (var n in this.extraInputs[ name ]) {
                                if (this.extraInputs[ name ].hasOwnProperty(n)) {

                                    var input = $(this.extraInputs[ name ][ n ]);
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

                        var input = $(this.extraInputs[ name ]);
                        // Для полей типа ENUM, помещаемых в select, автоматически заполняем этот select
                        if (input.attr('tagName') == 'SELECT' && value && typeof value === 'object' && value['values'] instanceof Array) {
                            input.html('');
                            $(value['values']).each(function (i, n) {
                                $('<option></option>').attr('value', n).text(n).appendTo(input);
                            });
                            value = value['value'];
                            input.val(value || '');
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
        };

        itemForm.show_inputs = function () {
            for (var name in this.extraInputs) {
                if (this.extraInputs.hasOwnProperty(name)) {
                    $(this.extraInputs[ name ]).trigger('show', [this.loadedData[name], name, this]);
                }
            }
        };


        Builder.Interface
                .modelForm(itemForm)
                .listActions(itemForm, "create edit enable remove copy")
                .sortable(itemForm);

    });
</script>