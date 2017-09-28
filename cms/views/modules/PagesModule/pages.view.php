<?php
$root_item = $this->items[0];
$this->items = $this->items[0]->getChildren();
$model_name = "Page";

function print_tree_item($item) {
    $show_edit_interface = true;
    $show_drag_interface = true;
    if ($item->hide_edit_interface && !CmsApplication::is_develop_mode()) {
        $show_edit_interface = false;
    }
    if ($item->hide_drag_interface && !CmsApplication::is_develop_mode()) {
        $show_drag_interface = false;
    }
    ?>

    <li
        data-id="<?= $item->id ?>"
        class="uk-nestable-list-item js_cms-item<?= $item->enabled ? "" : " builder-list_item-disabled" ?>"
        namiObject="<?= $item->id ?>">

        <div class="uk-nestable-item">
            <div class='builder-items_list__item<? if (!$show_drag_interface): ?> js_cms-drag_drop_disabled<? endif; ?>'>
                <div class="builder-items_list__item_col_middle">
                    <div class="builder-list_item__item_content">

                        <div class="uk-grid">
                            <div class="uk-width-1-1">
                                <span class='js_cms-item_title' namiText="title">
                                    <?= $item->title ?>
                                </span>

                                <span class="uk-text-muted uk-margin-left" namiText="name">
                                    <?= $item->name ?>
                                </span>
                            </div>
                        </div>

                    </div>
                </div>

                <div class="builder-items_list__item_col_right">
                    <div class="uk-text-right">
                        <div class="uk-button-group">
                            <button style="display: none" class="uk-button"></button>
                            <?php if ($show_edit_interface): ?>
                                <button title="Редактировать" class="uk-button js_cms-edit_item"><i class="uk-icon-pencil"></i></button>
                                <button title="Отображать на сайте" class="uk-button js_cms-enabled_item" namiField="enabled"><i
                                        class="uk-icon-eye<?= $item->enabled ? "" : "-slash" ?>"></i></button>
                                <button title="Удалить" class="uk-button js_cms-delete_item"><i class="uk-icon-trash"></i></button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <?php $sub_items = $item->getChildren(); ?>
        <?php if ($sub_items): ?>
            <ul class='uk-nestable-list'>

                <?php foreach ($sub_items as $sub_item): ?>
                    <?php print_tree_item($sub_item) ?>
                <?php endforeach ?>

            </ul>
        <?php endif ?>

    </li>
    <?php
}
?>


<?= new View($this->getFilePath() . "block_subnavi", array('view_name' => $this->file)) ?>


<div class="uk-grid uk-margin-small-bottom">
    <div class="uk-width-1-1">
        <button class="uk-button uk-button-success uk-button-medium js_cms-create_item">
            <i class="uk-icon-plus"></i>
            <span class="uk-text-bold">
                добавить запись
            </span>
        </button>
    </div>
</div>


<div class="js_cms-create_form_place"></div>

<div
    class="uk-nestable-item js_cms-item<?= $root_item->enabled ? "" : " builder-list_item-disabled" ?>"
    namiObject="<?= $root_item->id ?>"
    namiModel="<?= $model_name ?>">

    <div class='builder-items_list__item'>
        <div class="builder-items_list__item_col_middle">
            <div class="builder-list_item__item_content">

                <div class="uk-grid">
                    <div class="uk-width-1-1">
                        <span class='js_cms-item_title' namiText="title">
                            <?= $root_item->title ?>
                        </span>

                        <span class="uk-text-muted uk-margin-left" namiText="name">
                            <?= $root_item->name ?>
                        </span>
                    </div>
                </div>

            </div>
        </div>

        <div class="builder-items_list__item_col_right">
            <div class="uk-text-right">
                <div class="uk-button-group">
                    <button style="display: none" class="uk-button"></button>
                    <button title="Редактировать" class="uk-button js_cms-edit_item"><i class="uk-icon-pencil"></i></button>
                </div>
            </div>
        </div>
    </div>

</div>


<div class="uk-margin-large-left uk-margin-top">
    <ul class="uk-nestable js_cms-items" namiModel="<?= $model_name ?>" data-root_id="<?= $root_item->id ?>">
        <? foreach ($this->items as $item): ?>

            <?= print_tree_item($item) ?>

        <? endforeach; ?>
    </ul>
</div>


<li id="js_cms-item_preview_template" class="js_cms-item uk-nestable-list-item" style="display:none;">

    <div class="uk-nestable-item">
        <div class='builder-items_list__item'>
            <div class="builder-items_list__item_col_middle">
                <div class="builder-list_item__item_content">

                    <div class="uk-grid">
                        <div class="uk-width-1-1">
                            <span class='js_cms-item_title' namiText="title">
                            </span>

                            <span class="uk-text-muted uk-margin-left" namiText="name">
                            </span>
                        </div>
                    </div>

                </div>
            </div>

            <div class="builder-items_list__item_col_right">
                <div class="uk-text-right">
                    <div class="uk-button-group">
                        <button style="display: none" class="uk-button"></button>
                        <button title="Редактировать" class="uk-button js_cms-edit_item"><i class="uk-icon-pencil"></i></button>
                        <button title="Отображать на сайте" class="uk-button js_cms-enabled_item" namiField="enabled"><i class="uk-icon-eye"></i></button>
                        <button title="Удалить" class="uk-button js_cms-delete_item"><i class="uk-icon-trash"></i></button>
                    </div>
                </div>
            </div>
        </div>
    </div>

</li>


<div class='uk-width-1-1' id="js_cms-item_edit_form" style="display:none;">
    <div class="builder-add_item_form">
        <form class="uk-form uk-form-horizontal">

            <?php if (CmsApplication::is_develop_mode()) {
                $types = PageTypes()
                    ->values(array('id', 'title', 'enabled'));
            } else {
                $types = PageTypes()
                    ->filter(array("enabled" => true))
                    ->values(array('id', 'title', 'enabled'));
            }
            ?>


            <div class="uk-form-row">
                <label class="uk-form-label" for="typepage">
                    Тип страницы
                </label>

                <div class="uk-form-controls">
                    <select class="uk-width-1-1" name="type" id="typepage">
                        <option value="">…</option>
                        <? foreach ($types as $type): ?>
                            <option value="<?= $type['id'] ?>">
                                <?= $type['title'] ?>
                            </option>
                        <? endforeach; ?>
                    </select>
                </div>
            </div>

            <?= NamiFormGenerator::forModel($model_name); ?>

            <?php if (CmsApplication::is_develop_mode()): ?>
                <div class="uk-form-row">
                    <label class="uk-form-label" for="123123">
                        Скрывать от пользователя интерфейс редактирования
                    </label>
                    <div class="uk-form-controls">
                        <input type="checkbox" name="hide_edit_interface" id="123123">
                    </div>
                </div>
                <div class="uk-form-row">
                    <label class="uk-form-label" for="drag">
                        Скрывать от пользователя интерфейс перетаскивания
                    </label>
                    <div class="uk-form-controls">
                        <input type="checkbox" name="hide_drag_interface" id="drag">
                    </div>
                </div>
            <?php endif; ?>


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


<script>
    $(function() {
        var itemForm = Object.create(Nami.Form);

        Builder
            .Interface
            .modelForm(itemForm)
            .listActions(itemForm)
            .nestedSortable(itemForm);


        itemForm.bind('fill', function(form, data) {
            if (data.name === "/") {
                form.find("input[name=name]").attr("disabled", true);
            }

            form
                .find('select[name=type]')
                .on('change', function(e) {
                    var types = <?= json_encode($this->page_types) ?>,
                        type_toggle__text = !!(types[$(this).val()] ? types[$(this).val()].has_text * 1 : false),
                        type_toggle__meta = !!(types[$(this).val()] ? types[$(this).val()].has_meta * 1 : false);

                    form.find('textarea[name=text]').parents('.uk-form-row:first').toggle(type_toggle__text);
                    form.find('.js_cms-seo_widget').parents('.uk-form-row:first').toggle(type_toggle__meta);
                })
                .trigger('change');
        });

    });
</script>