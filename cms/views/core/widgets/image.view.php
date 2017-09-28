<div class="uk-form-row">
    <label class="uk-form-label">
        <?= $this->title ?>
    </label>

    <div class="uk-form-controls  js-cms_imagesupload__container">
        <input type="hidden" imageupload="yes" name="<?= $this->name ?>" />

        <div class="uk-width-1-1 js-cms_imagesupload__images">

            <div class="uk-overlay uk-margin-small-right uk-margin-small-bottom  js-cms_imagesupload__image_block js-cms_imagesupload__image_block-template" style="display: none">
                <img src="" style="width: 120px; height: 120px;">

                <div class="uk-overlay-area">
                    <div class="uk-overlay-area-content">
                        <a class="builder-zoom_img_icon js-cms_imagesupload__image_zoom" target="blank">
                            <i class="uk-icon-search"></i>
                        </a>
                        <a class="builder-remove_img_icon js-cms_imagesupload__image_remove">
                            <i class="uk-icon-remove"></i>
                        </a>
                    </div>
                </div>
            </div>

        </div>

        <div class="uk-width-1-1">
            <a href="javascript:;" class="js-cms_imagesupload__input_link">choose</a>
        </div>

        <span class="uk-text-primary uk-text-small">
            <?= $this->info ?>
        </span>
    </div>

</div>