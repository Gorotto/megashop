<?
$model_name = "FormsHandlerLogItem";
$menu_items = FormsHandlerLogItems()->values("type");
$menu_items = array_unique($menu_items);
?>


<div class="uk-grid">
    <div class="uk-width-1-1">
        &nbsp;<!--заголовок-->
    </div>
</div>


<div class="uk-grid uk-margin-small-bottom">
    <div class="uk-width-1-1">
        <ul class="uk-subnav uk-subnav-pill">

            <li class="<? if (Meta::vars("type") === null): ?>uk-active<? endif ?>">
                <a href="<?= $this->uri ?>">
                    все формы
                </a>
            </li>

            <? foreach ($menu_items as $item): ?>
                <li class="<? if (Meta::vars("type") == $item): ?>uk-active<? endif ?>">
                    <a href="?type=<?= urlencode($item) ?>">
                        <?= mb_strtolower($item) ?>
                    </a>
                </li>
            <? endforeach ?>

        </ul>
    </div>
</div>


<div class="js_cms-create_form_place"></div>
<hr class="uk-margin-top-remove uk-margin-small-top">


<div class="js_cms-items uk-margin-large-bottom" namiModel=<?= $model_name ?>>

    <? foreach ($this->paginator->objects as $item): ?>
        <div class="js_cms-item" namiObject="<?= $item->id ?>">
            <div class='builder-items_list__item'>

                <div class="builder-items_list__item_col_middle">
                    <div class="builder-list_item__item_content">
                        <div class="uk-grid">
                            <div class="uk-width-3-10">
                                <span namiText="date">
                                    <?= $item->date ?>
                                </span>

                                <? if ($item->is_new && (($item->date->timestamp + 60 * 60 * 24) > time())): ?>
                                    <span class="uk-badge uk-badge_success uk-margin-small-left">
                                        новый
                                    </span>
                                <? endif; ?>
                            </div>

                            <div class="uk-width-2-10">
                                <?= $item->type ?>
                            </div>

                            <div class="uk-width-5-10">
                                <span class="js_cms-item_title uk-text-muted" namiHtml="title">
                                    <?= $item->title ?>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="builder-items_list__item_col_right">
                    <div class="uk-text-right">
                        <div class="uk-button-group">
                            <button style="display: none" class="uk-button"></button>
                            <button class="uk-button js_cms-edit_item"><i class="uk-icon-info-circle"></i></button>
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
            <div class="uk-form-row">
                <label class="uk-form-label">
                    Форма
                </label>
                <div class="uk-form-controls">
                    <input type="text" name="type" class="uk-width-1-1">
                    <span class="uk-text-primary uk-text-small">
                    </span>
                </div>
            </div>

            <div class="uk-form-row">
                <label class="uk-form-label">
                    Дата
                </label>
                <div class="uk-form-controls">
                    <div class="uk-form-icon">
                        <i class="uk-icon-calendar"></i>
                        <input type="text" name="date">
                    </div>
                    <br>
                    <span class="uk-text-primary uk-text-small">
                    </span>
                </div>
            </div>

            <div class="uk-form-row">
                <label class="uk-form-label">
                    Данные
                </label>
                <div class="uk-form-controls">
                    <textarea class="uk-width-1-1" name="text" cols="30" rows="5"></textarea>
                    <span class="uk-text-primary uk-text-small">
                    </span>
                </div>
            </div>


            <? if (CmsApplication::is_develop_mode()): ?>
                <div class="uk-form-row">
                    <label class="uk-form-label">
                        Информация о пользователе
                    </label>
                    <div class="uk-form-controls">
                        <textarea class="uk-width-1-1" name="user_info" cols="30" rows="5"></textarea>
                        <span class="uk-text-primary uk-text-small">
                        </span>
                    </div>
                </div>
            <? endif; ?>





            <div class="uk-form-row">
                <div class="uk-text-right uk-margin-large-top">
                    <button type="button" class="uk-button uk-button-primary js_cms_item_edit_form__save">
                        <span class="uk-icon-check"></span>
                        Закрыть
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>




<script>
    $(function() {
        var itemForm = Object.create(Nami.Form);

        Builder.Interface
                .modelForm(itemForm)
                .listActions(itemForm);


        itemForm.bind('fill', function(form, data) {
            form.find("input:first").change();
            form.find("input").hide();
            form.find("i").hide();
            form.find("textarea").hide();

            form.find("input, textarea").each(function() {
                if ($(this).val() != "") {
                    $(this).before("<span>" + $(this).val().replace(/\n/g, "<br>") + "</span");
                }
            });
        });

    });
</script>