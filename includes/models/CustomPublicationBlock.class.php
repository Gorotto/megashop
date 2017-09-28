<?


class CustomPublicationBlock extends NamiModel {

    public static $types = [
        "richtext" => [
            "title" => "текстовый блок",
            "fields" => ["text"],
        ],
        "single_image" => [
            "title" => "изображение, во всю ширину",
            "fields" => ["single_image", "single_image_text"],
        ],
        "slider" => [
            "title" => "изображения, слайдер",
            "fields" => ["slider_images"],
        ],
        "mosaic" => [
            "title" => "текст с изображением",
            "fields" => ["mosaic_first", "mosaic_image", "mosaic_text"],
        ],
        "youtube" => [
            "title" => "видео youtube",
            "fields" => ["youtube_link"],
        ],
        "blockquote" => [
            "title" => "цитата",
            "fields" => ["blockquote_text", "blockquote_author"],
        ],
        "gallery_images" => [
            "title" => "изображения, галерея",
            "fields" => ["gallery_images"],
        ],
    ];

    static function definition() {
        return array(
            'publication' => new NamiFkDbField(array('model' => 'CustomPublication')),
            'html' => new NamiTextDbField(),
            'type' => new NamiCharDbField(array('maxlength' => 250, 'default' => "richtext")),
            'text' => new NamiTextDbField(),
            'single_image' => new NamiImageDbField(array('path' => "/static/uploaded/images/custom_publications/single_image",
                    'variants' => array(
                        'base' => array('width' => 1920, "height" => 600, "crop" => true),
                        'cms' => array('width' => 120, 'height' => 120, 'crop' => true),
                    ))
            ),
            'single_image_text' => new NamiCharDbField(['maxlength' => 500]),
            'slider_images' => new NamiArrayDbField(array(
                    'type' => 'NamiImageDbField',
                    'path' => '/static/uploaded/images/custom_publications/slider_images',
                    'variants' => array(
                        'base' => array('height' => 600, 'crop' => true),
                        'cms' => array('width' => 120, 'height' => 120, 'crop' => true),
                    ))
            ),
            'gallery_images' => new NamiArrayDbField(array(
                    'type' => 'NamiImageDbField',
                    'path' => '/static/uploaded/images/custom_publications/gallery_images',
                    'variants' => array(
                        'full' => array('width' => 1280, 'height' => 800, 'enlarge' => false),
                        'preview' => array('height' => 248, 'width' => '248', 'crop' => true),
                        'cms' => array('width' => 120, 'height' => 120, 'crop' => true),
                    ))
            ),
            'mosaic_image' => new NamiImageDbField(array('path' => "/static/uploaded/images/custom_publications/mosaic_image",
                    'variants' => array(
                        'base' => array('width' => 764, 'height' => 509, 'crop' => true),
                        'cms' => array('width' => 120, 'height' => 120, 'crop' => true),
                    ))
            ),
            'mosaic_text' => new NamiTextDbField(),
            'mosaic_first' => new NamiCharDbField(array('maxlength' => 250, 'default' => 'text')),
            'youtube_link' => new NamiCharDbField(array('maxlength' => 250)),
            'blockquote_text' => new NamiTextDbField(),
            'blockquote_author' => new NamiCharDbField(array('maxlength' => 250)),
            'enabled' => new NamiBoolDbField(array('default' => true, 'index' => true)),
            'position' => new NamiIntegerDbField(array("index" => true)),
            'resaved' => new NamiBoolDbField(array('default' => false, 'index' => true)),
        );
    }

    public $description = [
        "text" => ["title" => "Текст", "widget" => "richtext"],
        "single_image" => ["title" => "Изображение", 'info' => 'рекомендуемый размер 1920x600 пикс.'],
        "single_image_text" => ["title" => "Подпись"],
        "slider_images" => ["title" => "Изображения", "widget" => "images", 'info' => 'высота изображения не менее 500 пикс.'],
        "gallery_images" => ["title" => "Изображения", "widget" => "images", 'info' => 'рекомендуемый размер 1920x800 пикс.'],
        "mosaic_image" => ["title" => "Изображение", 'info' => 'рекомендуемый размер 764x509 пикс.'],
        "mosaic_text" => ["title" => "Текст", "widget" => "richtext"],
        "mosaic_first" => ["title" => "Последовательность", "widget" => "select", "choices" => ["text" => "Текст слева, изображение справа", "image" => "Текст справа, изображение слева"]],
        "youtube_link" => ["title" => "Ссылка на видео youtube", 'info' => 'Например, "http://www.youtube.com/watch?v=HQ7R_buZPSo" или "https://youtu.be/HQ7R_buZPSo"'],
        "blockquote_text" => ["title" => "Текст"],
//        "blockquote_author" => ["title" => "Автор, имя"],
    ];

    function afterSave($is_new) {
        $this->saveContent();
        $this->publication->saveContent();
    }

    public function saveContent(){
        $this->html = (string) new View('modules/CustomEditorModule/blocks_templates/' . $this->type, array("block" => $this));
        $this->hiddenSave();
    }

    function beforeSave() {
        if ($this->type == "youtube"){
            if  (!Meta::getYoutubeIdentify($this->youtube_link)){
                throw new Exception("Ссылка на видео неверна");
            }
        }
    }

    function beforeDelete() {
        $this->publication->saveContent($this->id);
    }

}
