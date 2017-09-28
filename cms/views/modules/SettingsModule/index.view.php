<?
$modules = Modules()
        ->filter(array(
            'id__in' => Session::getInstance()->getUser()->getAvaliableModulesIds()
        ))
        ->sortedOrder()
        ->all();
?>




<div class="uk-width-1-2">
    <form class="uk-form uk-form-horizontal">
        <div class="uk-form-row">
            <label class="uk-form-label" for="setting_module">После входа в систему</label>

            <div class="uk-form-controls">
                <select id="setting_module" class="">
                    <option value="0">показать страницу приветствия</option>

                    <? foreach ($modules as $m): ?>
                        <option value="<?= $m->id ?>" <?= Session::getInstance()->getUser()->start_module && Session::getInstance()->getUser()->start_module->id == $m->id ? 'selected' : '' ?> >открыть модуль «<?= $m->title ?>»</option>
                    <? endforeach ?>
                </select>
            </div>
        </div>
    </form>
</div>


<script type="text/javascript">

    $(document).ready(function() {
        $("#setting_module").on('change', function(e) {

            e.preventDefault();

            $.post("<?= $this->ajaxUri ?>/module/", {module: $(this).val()}, function(r) {
                if (!r.success) {
                    alert(r.message);
                }
            }, 'json');
        });
    });

</script>
