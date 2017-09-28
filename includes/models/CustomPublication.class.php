<?


class CustomPublication extends NamiModel
{

    static function definition()
    {
        return array(
            'title' => new NamiCharDbField(['maxlength' => 250]),
            'link' => new NamiCharDbField(['maxlength' => 250]),
            'last_revision' => new NamiDatetimeDbField(['default_callback' => 'return time();', 'format' => '%d.%m.%Y %H:%M:%S', 'index' => true]),
            'model_name' => new NamiCharDbField(['maxlength' => 250]),
            'model_id' => new NamiCharDbField(['maxlength' => 250]),
            'model_field' => new NamiCharDbField(['maxlength' => 250]),
            'html' => new NamiTextDbField(),
            'resaved' => new NamiBoolDbField(array('default' => false, 'index' => true)),
        );
    }

    public $description = [
        'title' => ['title' => 'Заголовок'],
        'link' => ['title' => 'link'],
        'last_revision' => ['title' => 'last_revision'],
        'model_name' => ['title' => 'model_name'],
        'model_id' => ['title' => 'model_id'],
        'model_field' => ['title' => 'model_field'],
        'html' => ['title' => 'html'],
    ];

    function saveContent($exclude_id = null)
    {
        $content_html = "";

        $blocks = CustomPublicationBlocks()
            ->filter([
                "enabled" => true,
                "publication" => $this,
            ]);

        if ($exclude_id) {
            $blocks = $blocks->filter(["id__ne" => $exclude_id]);
        }

        $blocks = $blocks->order("position")->all();
        foreach ($blocks as $block) {
            $content_html .= (string)new View('_custom_editor/blocks/' . $block->type, ["block" => $block]);
        }


        $this->html = $content_html;
        $this->title = Meta::cut_text(strip_tags($content_html), 200);


        $this->save();
    }

    function reorderBlocksPositions($blocks_ids_list)
    {
        if (!$blocks_ids_list) {
            return false;
        }


        $sql = "UPDATE `custompublicationblock` SET `position`=0 WHERE `id` IN (" . implode(",", $blocks_ids_list) . ")";
        NamiCore::getBackend()->cursor->execute($sql);


        foreach ($blocks_ids_list as $position => $block_id) {
            $sql = "UPDATE `custompublicationblock` SET `position`={$position} WHERE `id` ={$block_id}";
            NamiCore::getBackend()->cursor->execute($sql);
        }


        $this->saveContent();
        return true;
    }

    function beforeSave()
    {
        if ($this->model_name && $this->model_id) {
            $original_item = NamiQuerySet($this->model_name)->get($this->model_id);

            if ($original_item) {
                $this->title = @$original_item->title;
                $this->link = @$original_item->full_uri;
            }
        }
    }

    function beforeDelete()
    {
        $blocks = CustomPublicationBlocks()
            ->filter([
                "publication" => $this
            ])
            ->all();

        foreach ($blocks as $block) {
            $block->delete();
        }
    }

    public static function revision(){
        $last_items = CustomPublications()
            ->filter([
                "last_revision__lt" => strtotime("-1 day"),
            ])
            ->orderDesc("last_revision")
            ->limit(10);

        foreach ($last_items as $publication) {
            if (!$publication->model_name || !$publication->model_id || !$publication->model_field){
                $publication->delete();
            }else{
                $original_item = NamiQuerySet($publication->model_name)->get($publication->model_id);

                if (!$original_item){
                    $publication->delete();
                }
            }

            $publication->last_revision = time();
            $publication->save();
        }
    }
}
