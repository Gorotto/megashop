<?
$model_name = "SocialMediaPost";
?>



<div class="uk-grid">
    <div class="uk-width-1-1">
        &nbsp;<!--заголовок-->
    </div>
</div>

<div class="uk-grid">
    <div class="uk-width-1-2">
        <ul class="uk-subnav uk-subnav-pill">
            <li class="uk-active">
                <a href="<?= $this->uri ?>/">
                    Посты
                </a>
            </li>
            <li>
                <a href="<?= $this->uri ?>/hashtags/">
                    Хэштеги
                </a>
            </li>
            <li>
                <a href="<?= $this->uri ?>/stat/">
                    Статистика постов и заведений
                </a>
            </li>
        </ul>
    </div>

    <div class="uk-width-1-2 uk-text-right">
        <button class="uk-button" data-uk-toggle="{target:'#filter'}">
            <i class="uk-icon-search"></i>
            фильтр
        </button>
    </div>
</div>


<div id="filter" class="uk-grud<? if (!Meta::vars("filter")): ?> uk-hidden<? endif; ?>">
    <div class="uk-width-1-1 uk-panel uk-panel-box uk-margin-small-top">
        <form action="<?= $this->uri ?>" class="uk-form uk-form-horizontal">
            <input type="hidden" name="filter" value="1" />

            <div class="uk-grid">
                <div class="uk-width-1-2">
                    <div class="uk-form-row">
                        <label class="uk-form-label">
                            Начиная с даты:
                        </label>
                        <div class="uk-form-controls">
                            <div class="uk-form-icon uk-width-1-1">
                                <i class="uk-icon-calendar"></i>
                                <input type="text" class="uk-width-1-1" datetimepicker="yes" name="date_start" value="<?= $this->date_start ?>" />
                            </div>
                        </div>
                    </div>
                </div>

                <div class="uk-width-1-2">
                    <div class="uk-form-row">
                        <label class="uk-form-label">
                            Заканчивая датой:
                        </label>
                        <div class="uk-form-controls">
                            <div class="uk-form-icon uk-width-1-1">
                                <i class="uk-icon-calendar"></i>
                                <input type="text" class="uk-width-1-1" datetimepicker="yes" name="date_stop" value="<?= $this->date_stop ?>" />
                            </div>
                        </div>
                    </div>
                </div>
            </div>


            <?
            $items = array(
                "publication" => "публикации сайта",
                "twitter" => "твиттер",
                "instagram" => "инстаграм",
            );
            ?>

            <div class="uk-grid uk-margin-small-top">
                <div class="uk-width-1-2">
                    <div class="uk-form-row">
                        <label class="uk-form-label">
                            Тип данных:
                        </label>
                        <div class="uk-form-controls">
                            <select name="type" class="uk-width-1-1">
                                <option value="">все подряд</option>
                                <? foreach ($items as $item_name => $item_title): ?>
                                    <option value="<?= $item_name ?>" <? if (Meta::vars("type") == $item_name): ?> selected<? endif; ?>>
                                        <?= $item_title ?>
                                    </option>
                                <? endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="uk-width-1-2">
                    <div class="uk-form-row">
                        <label class="uk-form-label">
                            Текст содержит:
                        </label>
                        <div class="uk-form-controls">
                            <input type="text" name="text" class="uk-width-1-1" value="<?= stripslashes(Meta::vars("text")) ?>" />
                        </div>
                    </div>
                </div>
            </div>


            <div class="uk-margin-top uk-text-right">
                <? if (Meta::vars("filter")): ?>
                    <a href="<?= $this->uri ?>" class="uk-button uk-button-danger">
                        <i class="uk-icon-close"></i>
                        сбросить параметры
                    </a>
                <? endif; ?>

                <button type="submit" class="uk-button uk-button-primary">
                    <i class="uk-icon-check"></i>
                    поиск
                </button>
            </div>
        </form>
    </div>
</div>


<div class="js_cms-create_form_place"></div>
<br>
<hr class="uk-margin-top-remove uk-margin-small-top">


<div class="js_cms-items uk-margin-large-bottom" namiModel=<?= $model_name ?>>

    <? foreach ($this->paginator->objects as $item): ?>
        <div class="js_cms-item<?= $item->enabled ? "" : " builder-list_item-disabled" ?>" namiObject="<?= $item->id ?>">
            <div class='builder-items_list__item'>

                <div class="builder-items_list__item_col_middle">
                    <div class="builder-list_item__item_content">
                        <div class="uk-grid">
                            <div class="uk-width-2-10 uk-text-muted" namiText="date">
                                <?= $item->date ?>
                            </div>

                            <div class="uk-width-7-10">
                                <span class='js_cms-item_title' namiText="title">
                                    <?= $item->title ?>
                                </span>
                            </div>

                            <div class="uk-width-1-10" namiText="type">
                                <?= $item->type ?>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="builder-items_list__item_col_right">
                    <div class="uk-text-right">
                        <div class="uk-button-group">
                            <button style="display: none" class="uk-button"></button>
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


<div class="uk-grid">
    <div class="uk-width-1-1">
        <?= $this->paginator ?>
    </div>
</div>


<div class='uk-width-1-1' id="js_cms-item_edit_form" style="display:none;">
    <div class="builder-add_item_form">
        <form class="uk-form uk-form-horizontal">
            <?= NamiFormGenerator::forModel($model_name); ?>

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
                    <div class="uk-width-2-10 uk-text-muted" namiText="date">
                    </div>

                    <div class="uk-width-8-10">
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
                    <button class="uk-button js_cms-edit_item"><i class="uk-icon-pencil"></i></button>
                    <button class="uk-button js_cms-enabled_item" namiField="enabled"><i class="uk-icon-eye"></i></button>
                    <button class="uk-button js_cms-delete_item"><i class="uk-icon-trash"></i></button>
                </div>
            </div>
        </div>

    </div>
</div>



<script>
    $(function() {
        var itemForm = Object.create(Nami.Form),
            filterTimePicker = {
                lang: 'ru',
                i18n: {
                    de: {
                        months: ['Январь', 'Февраль', 'Март', 'Апрель', 'Май', 'Июнь', 'Июль', 'Август', 'Сентябрь', 'Октябрь', 'Ноябрь', 'Декабрь'],
                        dayOfWeek: ['Вс', 'Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб']
                    }
                },
                format: 'd.m.Y',
                timepicker: false,
                closeOnDateSelect: true,
                dayOfWeekStart: 1,
            };

        $("input[name=date_start],input[name=date_stop]")
            .datetimepicker(filterTimePicker);


        Builder.Interface
            .modelForm(itemForm)
            .listActions(itemForm);

        itemForm.bind('fill', function($form, data) {
            var text = decodeURIComponent($form.find("textarea").val().replace(/\+/g, '%20'));
            $form.find("textarea").val(text);
        });

        itemForm.bind('fetchdata', function($form, data) {
            var text = encodeURIComponent($form.find("textarea").val().replace(/\+/g, '%20'));
            data.text = text;
        });

    });
</script>