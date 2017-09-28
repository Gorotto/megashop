<?php
$model_name = "SiteUser";
?>


<div class="uk-grid">
    <div class="uk-width-1-1">
        <?php if (Meta::vars("filter_mail")): ?>
            Результаты поиска по запросу <strong>«<?= Meta::vars('filter_mail') ?>»</strong>
        <?php else: ?>
            &nbsp;
        <?php endif; ?>
    </div>
</div>


<div class="uk-grid uk-margin-small-bottom">
    <div class="uk-width-3-10">
        <button class="uk-button uk-button-success uk-button-medium js_cms-create_item">
            <i class="uk-icon-plus"></i>
            <span class="uk-text-bold">
                добавить пользователя сайта
            </span>
        </button>
    </div>

    <div class="uk-width-7-10 uk-text-right">
        <form action="<?= $this->uri ?>/" method="get" class="uk-form">
            <input
                placeholder="электронная почта"
                type="text"
                name="filter_mail"
                value="<?= Meta::vars('filter_mail') ?>"
                title="Введите email пользователя" />

            <button class="uk-button uk-button-primary">
                найти
            </button>
        </form>
    </div>
</div>


<div class="js_cms-create_form_place"></div>

<div class="builder-items_list__descr">
    <div class="uk-panel uk-margin-small-bottom">
        <div class='builder-items_list__item'>
            <div class="builder-items_list__item_col_middle">
                <div class="builder-list_item__item_content">

                    <div class="uk-grid">
                        <div class="uk-width-1-10 uk-text-muted">
                            #
                        </div>

                        <div class="uk-width-5-10 uk-text-muted">
                            Электронная почта
                        </div>

                        <div class="uk-width-4-10 uk-text-muted">
                            Последний вход
                        </div>
                    </div>

                </div>
            </div>

            <div class="builder-items_list__item_col_right">
                &nbsp;
            </div>
        </div>
    </div>
</div>


<div class="js_cms-items uk-margin-large-bottom" namiModel=<?= $model_name ?>>

    <? foreach ($this->paginator->objects as $item): ?>
        <div class="js_cms-item" namiObject="<?= $item->id ?>">
            <div class='builder-items_list__item'>

                <div class="builder-items_list__item_col_middle">
                    <div class="builder-list_item__item_content">
                        <div class="uk-grid">
                            <div class="uk-width-1-10">
                                <span namiText="id" class="uk-text-muted">
                                    <?= $item->id ?>
                                </span>
                            </div>

                            <div class="uk-width-5-10">
                                <span class='js_cms-item_title' namiText="email">
                                    <?= $item->email ?>
                                </span>
                            </div>

                            <div class="uk-width-4-10">
                                <span namiText="last_login">
                                    <?= $item->last_login ?>
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
                <label class="uk-form-label">
                    Пароль
                </label>
                <div class="uk-form-controls">
                    <input type="text" class="uk-width-1-1" name="password">
                    <small>
                        <a href="" class="js-create_passord">
                            сгенерировать новый пароль
                        </a>
                    </small>
                </div>
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
                    <div class="uk-width-1-10">
                        <span namiText="id" class="uk-text-muted">
                        </span>
                    </div>

                    <div class="uk-width-5-10">
                        <span class='js_cms-item_title' namiText="email">
                        </span>
                    </div>

                    <div class="uk-width-4-10">
                        <span namiText="last_login">
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
                    <button class="uk-button js_cms-delete_item"><i class="uk-icon-trash"></i></button>
                </div>
            </div>
        </div>

    </div>
</div>



<script>
    $(function() {
        var itemForm = Object.create(Nami.Form);

        Builder.Interface
                .modelForm(itemForm)
                .listActions(itemForm);

        var passwordReplacement = '•••••';

        $('#item-form')
                .find('input[name=password]')
                .focus(function() {
                    if ($(this).val() === passwordReplacement) {
                        $(this).val('');
                    }
                })
                .blur(function() {
                    if (!$(this).val()) {
                        $(this).val(passwordReplacement);
                    }
                });


        itemForm
                .bind('loaddata', function(data) {
                    if (data.password) {
                        data.password = passwordReplacement;
                    }
                })
                .bind('fetchdata', function(form, data) {
                    if (data.password === passwordReplacement || !data.password) {
                        delete data.password;
                    }
                });


        $(".js-create_passord").on("click", function(e) {
            e.preventDefault();

            var newPass = "",
                    hash = ['a', 'b', 'c', 'd', 'e', 'f', 'g'
                                , 'h', 'i', 'j', 'k', 'l', 'm', 'n', 'o', 'p'
                                , 'r', 's', 't', 'u', 'v', 'x', 'y', 'z', 'A'
                                , 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J'
                                , 'K', 'L', 'M', 'N', 'O', 'P', 'R', 'S', 'T'
                                , 'U', 'V', 'X', 'Y', 'Z', '1', '2', '3', '4'
                                , '5', '6', '7', '8', '9', '0'],
                    hashLength = hash.length;

            for (var i = 0, max = 8; i < max; i++) {
                newPass += hash[Math.floor(Math.random() * (hashLength))];
            }

            $(this)
                    .parents(".uk-form-row")
                    .find("input")
                    .val(newPass)
                    .change();
        });
    });
</script>