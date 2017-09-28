<div class="uk-form-row">
    <div class="js_cms-seo_widget">
        <div class="uk-form-row">

            <div style="margin-bottom: 20px;">
                <a class="js-toggle_seo" href="">
                    <?= $this->title ?>
                </a>
            </div>

        </div>

        <div style="display:none;" class="js-toggle_seo_block">
            <div class="uk-form-row">
                <label class="uk-form-label">
                    SEO заголовок
                </label>

                <div class="uk-form-controls">
                    <input type="text" class="uk-width-1-1" name="meta_title" />
                </div>
            </div>
            <div class="uk-form-row">
                <label class="uk-form-label">
                    SEO ключевые слова
                </label>

                <div class="uk-form-controls">
                    <input type="text" class="uk-width-1-1" name="meta_keywords" />
                </div>
            </div>
            <div class="uk-form-row">
                <label class="uk-form-label">
                    SEO описание
                </label>

                <div class="uk-form-controls">
                    <textarea class="uk-width-1-1" rows="3" name="meta_description"></textarea>

                    <span class="uk-text-primary uk-text-small">
                        <?= $this->info ?>
                    </span>
                </div>
            </div>
        </div>
    </div>
</div>