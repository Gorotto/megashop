<?

class BannerPlaceBanner extends NamiModel {

    static function definition() {
        return array(
            'banner' => new NamiFkDbField(array('model' => 'Banner')),
            'bannerplace' => new NamiFkDbField(array('model' => 'BannerPlace')),
        );
    }

}
