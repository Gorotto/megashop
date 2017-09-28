<div class="uk-form-row">
    <label class="uk-form-label">
        <?= $this->field->title ?>
    </label>
    <div class="uk-form-controls">

        <select name="<?= $this->field->name ?>" class="uk-width-1-1">
            <!-- <option value="">...</option> -->
            <?
            // Соберем список значений для выпадающего списка
            $values = array();

//            if (!$this->field->required) {
//                $values[] = array('value' => '', 'title' => '…');
//            }

            $variands = explode("\n", $this->field->settings);
            foreach ($variands as $i) {
                $values[] = array('value' => htmlentities($i, ENT_QUOTES, 'UTF-8'), 'title' => $i);
            }
            ?>
            <? foreach ($values as $i): ?>
                <option value="<?= $i['value'] ?>"><?= $i['title'] ?></option>
            <? endforeach ?>
        </select>
    </div>
</div>