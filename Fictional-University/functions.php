<?php
    require get_theme_file_path('/inc/like-route.php');
    require get_theme_file_path('/inc/search-route.php');

    function university_custom_rest () {
        register_rest_field('post', 'authorName', array(
            'get_callback' => function () {return get_the_author();}
        ));

        register_rest_field('note', 'userNoteCount', array(
            'get_callback' => function () {return count_user_posts(get_current_user_id(), 'note');}
        ));
    }

    add_action('rest_api_init', 'university_custom_rest');

    function university_files() {
        wp_enqueue_style('university_main_styles', get_theme_file_uri('/build/style-index.css'));
        wp_enqueue_style('university_extra_styles', get_theme_file_uri('/build/index.css'));
        wp_enqueue_style('font_awesome', '//maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css');
        wp_enqueue_style('custom_google_fonts', '//fonts.googleapis.com/css?family=Roboto+Condensed:300,300i,400,400i,700,700i|Roboto:100,300,400,400i,700,700i');
        wp_enqueue_script('main_university_javascript', get_theme_file_uri('/build/index.js'), array('jquery'), 1.0, true);
        
        wp_localize_script('main_university_javascript', 'universityData', array(
            'root_url' => get_site_url(),
            'nonce' => wp_create_nonce('wp_rest')
        ));
    }
    add_action('wp_enqueue_scripts', 'university_files');
    
    function university_features () {
        register_nav_menu('headerMenuLocation', 'Header Menu Location');
        register_nav_menu('footerLocation1', 'Footer Location 1');
        register_nav_menu('footerLocation2', 'Footer Location 2');
        add_theme_support('title-tag');
        add_theme_support('post-thumbnails');
        add_image_size('professorLandscape', 400, 260, true);
        add_image_size('professorPortrait', 260, 400, true);
        add_image_size('pageBanner', 1500, 350, true);
    }
    
    add_action('after_setup_theme', 'university_features');

    function university_adjust_queries($query) {
        $today = date('Ymd');
        if (!is_admin() AND is_post_type_archive('event') AND is_main_query()) {
            $query->set('meta_key', 'event_date');
            $query->set('orderby', 'meta_value_num');
            $query->set('order', 'ASC');
            $query->set('meta_query', array(
                array (
                  'key' => 'event_date',
                  'compare' => '>=',
                  'value' => $today,
                  'type' => 'numeric'
                )
              ));
        }
        if (!is_admin() AND is_post_type_archive('event') AND is_main_query()) {
            $query->set('posts_per_page', -1);
            $query->set('orderby', 'title');
            $query->set('order', 'ASC');
        }
    }
    
    add_action('pre_get_posts', 'university_adjust_queries');

    function pageBanner ($args = NULL) {
        if (!isset($args['title'])) {
            $args['title'] = get_the_title();
        }
        if (!isset($args['subtitle'])) {
            $args['subtitle'] = get_field('page_banner_subtitle');
        }
        if (!isset($args['photo'])) {
            if (get_field('page_banner_background_image') AND !is_archive() AND !is_home()) {
                $args['photo'] = get_field('page_banner_background_image')['sizes']['pageBanner'];
            }
            else {
                $args['photo'] = get_theme_file_uri('/images/ocean.jpg');
            }
        }
        ?>
        <div class="page-banner">
          <div class="page-banner__bg-image" style="background-image: url(<?php echo $args['photo'] ?>)"></div>
            <div class="page-banner__content container container--narrow">
              <h1 class="page-banner__title">
                <div class="row group">
                    <div class="one-third">
                        <?php the_post_thumbnail('professorPortrait'); ?>
                    </div>
                    <div class="two-third">
                        <?php echo $args ['title'] ?>
                        <div class="page-banner__intro">
                            <p><?php echo $args ['subtitle'] ?></p>
                        </div>
                        
                    </div>
                </div>
              </h1>
            </div>
        </div>
    <?php }


    // restrict user
    add_action('admin_init', 'redirectSubs');
    
    function redirectSubs() {
        $currentUser = wp_get_current_user();

        if (count($currentUser->roles)==1 AND $currentUser->roles[0]=='subscriber') {
            wp_redirect(site_url('/'));
            exit;
        }
    }

    add_action('wp_loaded', 'noSubsAdminBar');
    
    function noSubsAdminBar() {
        $currentUser = wp_get_current_user();

        if (count($currentUser->roles)==1 AND $currentUser->roles[0]=='subscriber') {
            show_admin_bar(false);
        }
    }

    add_filter('login_headerurl', 'ourHeaderUrl');

    function ourHeaderUrl () {
        return esc_url(site_url('/'));
    }

    add_action('login_enqueue_scripts', 'ourLogicCSS');

    function ourLogicCSS () {
        wp_enqueue_style('university_main_styles', get_theme_file_uri('/build/style-index.css'));
        wp_enqueue_style('university_extra_styles', get_theme_file_uri('/build/index.css'));
        wp_enqueue_style('font_awesome', '//maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css');
        wp_enqueue_style('custom_google_fonts', '//fonts.googleapis.com/css?family=Roboto+Condensed:300,300i,400,400i,700,700i|Roboto:100,300,400,400i,700,700i');
    }

    add_filter('login_headertitle', 'ourLoginTitle');

    function ourLoginTitle () {
        return get_bloginfo('name');
    }

    add_filter('wp_insert_post_data', 'make_note_private', 10, 2);

    function make_note_private($data, $postarr) {
        if ($data['post_type']=='note') {
            if (count_user_posts(get_current_user_id(), 'note')>4 AND !$postarr['ID']) {
                die("you have reached your note limit.");
            }
            $data['post_title'] = sanitize_text_field($data['post_title']);
            $data['post_content'] = sanitize_textarea_field($data['post_content']);
        }
        
        if ($data['post_type']=='note' AND $data['post_status'] != "trash") {
            $data['post_status'] = "private";
        }
        return $data;
    }
?>