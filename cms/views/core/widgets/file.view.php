<div class="uk-form-row">
    <label class="uk-form-label">
        <?= $this->title ?>
    </label>

    <div class="uk-form-controls js-cms_filesupload__container">
        <input type="hidden" fileupload="yes" name="<?= $this->name ?>" />

        <div class="uk-width-1-1 uk-margin-small-bottom js-cms_filesupload__files">

            <div class="builder-files_list__item uk-panel uk-panel-box uk-panel-hover uk-clearfix js-cms_filesupload__file_block js-cms_filesupload__file_block-template" style="display: none;">
                <div class="uk-float-right uk-margin-small-right builder-files_list__links">
                    <a class="js-cms_filesupload__file_edit uk-text-primary uk-margin-small-right" title="Редактировать название">
                        изменить название
                    </a>

                    <a class="js-cms_filesupload__file_save uk-text-primary uk-margin-small-right" title="Сохранить" style="display: none">
                        сохранить
                    </a>

                    <a class="js-cms_filesupload__file_cancel uk-text-danger uk-margin-small-right" title="Отменить" style="display: none">
                        отменить
                    </a>

                    <a class="js-cms_filesupload__file_remove uk-text-danger" title="Удалить файл">
                        удалить
                    </a>
                </div>

                <div class="uk-float-left">
                    <span style="margin-right: 10px;"></span>

                    <input name="filetitle" type="text" placeholder="название файла" class="js-cms_filesupload__file_block_edittitle uk-form-small" style="display: none; width: 300px;">

                    <span class="js-cms_filesupload__file_block_filetitle">
                        new file title
                    </span>

                    <span class="js-cms_filesupload__file_block_fileinfo uk-text-muted">
                        (filename.ext size 123,2Kb)
                    </span>

                </div>
            </div>

        </div>

        <div class="uk-width-1-1">
            <a href="javascript:;" class="js-cms_filesupload__input_link">choose</a>
        </div>

        <span class="uk-text-primary uk-text-small">
            <?= $this->info ?>
        </span>
    </div>

</div>