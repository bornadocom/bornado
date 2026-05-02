<?php
namespace ElementorAdforest;
use Elementor\Widgets_Manager;
use Elementor\Elements_Manager;

/**
 * Class Plugin
 *
 * Main Plugin class.
 *
 * @since 1.2.0
 */
class Plugin {
    /**
     * Single instance
     *
     * @var Plugin|null
     */
    private static $_instance = null;

    /**
     * Get instance
     *
     * @return Plugin
     */
    public static function instance() {
        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    /**
     * Constructor
     */
    public function __construct() {
        add_action( 'elementor/widgets/register', [ $this, 'register_widgets' ], 10, 1 );
        add_action( 'elementor/widgets/widgets_registered', [ $this, 'after_widgets_registered' ], 10, 1 );

        add_action( 'elementor/elements/categories_registered', [ $this, 'add_elementor_widget_categories' ], 10, 1 );
        add_action( 'elementor/init', [ $this, 'maybe_register_category_on_init' ] );

        require_once __DIR__ . '/adforest_elementor_functions.php';
        require_once __DIR__ . '/widget_shortcodes.php';
        require_once __DIR__ . '/AdPostMaphandler.php';
        require_once __DIR__ . '/ad_post_shortcode.php';
    }

    /**
     * Include all widget files
     */
    private function include_widgets_files() {
        $files = [
            'AdforestSignUp.php',
            'AdforestSignIn.php',
            'AdforestContactUs.php',
            'AdforestPackages.php',
            'AdforestAboutUs.php',
            'AdforestWorkflow.php',
            'FrequentlyAskedQuestions.php',
            'PartnersSlider.php',
            'AdPostModern.php',
            'MainHero1.php',
            'FeaturedAds.php',
            'MainHero2.php',
            'CategoryBasedAds.php',
            'RecentAds.php',
            'HorizontalAd.php',
            'MainHero3.php',
            'MiniCategorySlider.php',
            'AdLocations.php',
            'MainHero4.php',
            'MainHero5.php',
            'AdsAndLocationMiniBoxes.php',
            'AdsModernBg.php',
            'MultivendorHero.php',
            'MultivendorProductCarousel.php',
            'MultivendorProductCategories.php',
            'MultivendorProductsWithSidebar.php',
            'MultivendorServices.php',
            'RealEstateHero.php',
            'AdGridCarouselModern.php',
            'HowItWorksModern.php',
            'LocationsModern.php',
            'OurArticles.php',
            'CarDealerHero.php',
            'CategorySlider.php',
            'AdGridCarouselFancy.php',
            'AboutUsModern.php',
            'BrandsCarousel.php',
            'AdtCallToAction.php',
            'AdListModern.php',
            'AdtInfo.php',
            'ClassicHome.php',
            'MainHero6.php',
            'AdGridFancy.php',
            'LocationsFancy.php',
            'LocationsFancy2.php',
            'CyberSaleHero.php',
            'MultivendorCategorySliderMini.php',
            'MultivendorProductCarouselWithAds.php',
            'MultivendorProductsGrid.php',
            'FlashSaleWidget.php',
            'EcommerceHome.php',
            'DealsOfTheDay.php',
            'EcommerceDealsBoxes.php',
        ];

        foreach ( $files as $file ) {
            require_once __DIR__ . '/widgets/' . $file;
        }

        if ( class_exists( 'SbPro' ) ) {
            $pro_files = [
                'DirectoryHero.php',
                'AboutUsEvents.php',
                'EventHomeHero.php',
                'EventListWidget.php',
                'EventsGridCarousel.php',
            ];
            foreach ( $pro_files as $file ) {
                require_once __DIR__ . '/widgets/' . $file;
            }
        }
    }

    private function include_traits_files() {
        $files = [
            'SliderControlsTrait.php',
            // add more traits here later if needed
        ];

        foreach ($files as $file) {
            require_once __DIR__ . '/traits/' . $file;
        }
    }

    /**
     * Register Widgets with Elementor
     *
     * @param Widgets_Manager $manager
     */
    public function register_widgets( Widgets_Manager $manager ) {
        $this->include_traits_files();
        $this->include_widgets_files();

        $widgets = [
            new Widgets\AdforestSignUp(),
            new Widgets\AdforestSignIn(),
            new Widgets\AdforestContactUs(),
            new Widgets\AdforestPackages(),
            new Widgets\AdforestAboutUs(),
            new Widgets\AdforestWorkflow(),
            new Widgets\FrequentlyAskedQuestions(),
            new Widgets\PartnersSlider(),
            new Widgets\AdPostModern(),
            new Widgets\MainHero1(),
            new Widgets\FeaturedAds(),
            new Widgets\MainHero2(),
            new Widgets\CategoryBasedAds(),
            new Widgets\RecentAds(),
            new Widgets\HorizontalAd(),
            new Widgets\MainHero3(),
            new Widgets\MiniCategorySlider(),
            new Widgets\AdLocations(),
            new Widgets\MainHero4(),
            new Widgets\MainHero5(),
            new Widgets\AdsAndLocationMiniBoxes(),
            new Widgets\AdsModernBg(),
            new Widgets\MultivendorHero(),
            new Widgets\MultivendorProductCarousel(),
            new Widgets\MultivendorProductCategories(),
            new Widgets\MultivendorProductsWithSidebar(),
            new Widgets\MultivendorServices(),
            new Widgets\RealEstateHero(),
            new Widgets\AdGridCarouselModern(),
            new Widgets\HowItWorksModern(),
            new Widgets\LocationsModern(),
            new Widgets\OurArticles(),
            new Widgets\CarDealerHero(),
            new Widgets\CategorySlider(),
            new Widgets\AdGridCarouselFancy(),
            new Widgets\AboutUsModern(),
            new Widgets\BrandsCarousel(),
            new Widgets\AdtCallToAction(),
            new Widgets\AdListModern(),
            new Widgets\AdtInfo(),
            new Widgets\ClassicHome(),
            new Widgets\MainHero6(),
            new Widgets\AdGridFancy(),
            new Widgets\LocationsFancy(),
            new Widgets\LocationsFancy2(),
            new Widgets\CyberSaleHero(),
            new Widgets\MultivendorCategorySliderMini(),
            new Widgets\MultivendorProductCarouselWithAds(),
            new Widgets\MultivendorProductsGrid(),
            new Widgets\FlashSaleWidget(),
            new Widgets\EcommerceHome(),
            new Widgets\DealsOfTheDay(),
            new Widgets\EcommerceDealsBoxes(),
        ];

        foreach ( $widgets as $widget ) {
            $manager->register( $widget );
        }

        if ( class_exists( 'SbPro' ) ) {
            $pro_widgets = [
                new Widgets\DirectoryHero(),
                new Widgets\AboutUsEvents(),
                new Widgets\EventHomeHero(),
                new Widgets\EventListWidget(),
                new Widgets\EventsGridCarousel(),
            ];
            foreach ( $pro_widgets as $widget ) {
                $manager->register( $widget );
            }
        }
    }

    /**
     * After all widgets are registered
     *
     * @param Widgets_Manager $manager
     */
    public function after_widgets_registered( Widgets_Manager $manager ) {
        // For unregistering or adjusting core/third-party widgets if needed
    }

    /**
     * Pro-only category registration
     *
     * @param Elements_Manager $manager
     */
    public function add_elementor_widget_categories( Elements_Manager $manager ) {
        $manager->add_category( 'adforest_widgets', [
            'title' => __( 'Adforest Widgets', 'adforest-elementor' ),
            'icon'  => 'fa fa-home',
        ] );
    }

    /**
     * Free fallback category registration on init
     */
    public function maybe_register_category_on_init() {
        $manager = \Elementor\Plugin::instance()->elements_manager;
        if ( ! array_key_exists( 'adforest_widgets', $manager->get_categories() ) ) {
            $manager->add_category( 'adforest_widgets', [
                'title' => __( 'Adforest Widgets', 'adforest-elementor' ),
                'icon'  => 'fa fa-home',
            ] );
        }
    }
}

Plugin::instance();