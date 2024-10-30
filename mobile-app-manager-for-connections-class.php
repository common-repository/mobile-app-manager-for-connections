<?php
if ( !function_exists('get_plugins') ){
    require_once( ABSPATH . '/wp-admin/includes/plugin.php' );
}
if( is_plugin_active( 'connections/connections.php' ) ) {
    global $mam_local_app_content;
    $mam_local_app_content['Connections'] = array('content_type' => 'Connections Directory' , 'class' => 'local_app_connections_dir' , 'vc_type' => 'list');
}

if (!class_exists('mam_local_app_connections_dir')) {

    class mam_local_app_connections_dir{

        function __construct(){


        }

        function get_blank_content_html(){

            $html_line = '<div class="local-app-content-type-parent" data-id="BLANK_ID">';
            $html_line .= $this->get_content_type_form('BLANK_ID', '', '');
            $html_line .= '</div>';

            return $html_line;
        }

        function get_content_type_form($id, $content_source, $indent = '', $listing_count_only = false){

            $html_line = '';

            if (function_exists('Connections_Directory')) {
                $instance = Connections_Directory();
                $catagories = $instance->retrieve->categories();

                if (sizeof($catagories) == 0) {
                    return 'No Connections Directory Categories Found';
                }

                $html_line .= '<select class="local-app-content-source" data-type="Connections" data-id="' . $id . '">';

                if (!$content_source) {
                    $html_line .= '<option value="0" selected>Select Category...</option>';
                }

                for ($x = 0; $x < sizeof($catagories); $x++) {

                    $selected_item = '';
                    if ($catagories[$x]->term_id == $content_source) $selected_item = 'selected';
                    if($listing_count_only) return $this->get_page_count( $content_source );

                    $html_line .= '<option value="' . $catagories[$x]->term_id . '" ' . $selected_item . '>' . $indent . $catagories[$x]->name . '</option>';

                    if ($catagories[$x]->children) $html_line .= $this->get_child_catagories_for_content_type_form($catagories[$x]->children, $content_source, $indent . $catagories[$x]->name . ' - ');

                }

                $html_line .= '</select>';
            }

            if($listing_count_only) return 0;

            return $html_line;

        }

        private function get_page_count( $catagory ){

            $has_results = true;
            $instance  = Connections_Directory();

            $limit = 50;
            $offset = 0;
            $total_posts = 0;

            while($has_results) {

                $retrieveAttr['limit'] = $limit;
                $retrieveAttr['offset'] = $offset;
                $retrieveAttr['visibility'] = 'public';
                $retrieveAttr['status'] = 'approved';
                $retrieveAttr['list_type'] = 'organization';

                $results = $instance->retrieve->entries($retrieveAttr);

                $offset = $offset + 50;

                if (sizeof($results) == 0) {
                    break;
                }

                foreach( $results as $index => $post) {
                    if ($post->entry_type == 'organization' && $post->visibility == 'public') {

                        $entry = new cnOutput($instance->retrieve->entry($post->id));
                        $catagory_list = $entry->getCategory();

                        for ($c = 0; $c < sizeof($catagory_list); $c++) {
                            if ($catagory == $catagory_list[$c]->term_taxonomy_id) {
                                $total_posts++;
                            }
                        }
                    }
                }
                if(sizeof($results) <= $limit){
                    $has_results = false;
                }
            }

            return $total_posts;
        }

        private function get_child_catagories_for_content_type_form($catagories , $content_source , $indent){

            $html_line = '';

            for($x=0;$x<sizeof($catagories);$x++) {

                $selected_item = '';
                if ($catagories[$x]->term_id == $content_source) $selected_item = 'selected';
                $html_line .= '<option value="' . $catagories[$x]->term_id . '" ' . $selected_item . '>'.$indent . $catagories[$x]->name . '</option>';

                if($catagories[$x]->children) $html_line .= $this->get_child_catagories_for_content_type_form($catagories[$x]->children , $content_source, $indent . $catagories[$x]->name . ' - ');

            }

            return $html_line;
        }

        private function get_child_catagories_for_initial_buttons($catagories , $indent , $this_array){

            $icon_id = sizeof($this_array) + 1;

            for($x=0;$x<sizeof($catagories);$x++) {
                if ($icon_id < 13) {
                    $base_dir = plugin_dir_url(__FILE__) . 'icons/default/';
                    $this_array[] = array('id' => uniqid(), 'name' => $indent . $catagories[$x]->name, 'icon' => $base_dir . 'icon_' . $icon_id . '.png', 'type' => 'Connections', 'source' => $catagories[$x]->term_id);
                    $icon_id++;
                    if ($icon_id > 11) return $this_array;
                    if ($catagories[$x]->children) $this_array = $this->get_child_catagories_for_initial_buttons($catagories[$x]->children, $indent . ' - ', $this_array);
                }
            }

            return $this_array;
        }

        public function create_initial_buttons($this_array ){

            if (!function_exists('Connections_Directory')) {
                return $this_array;
            }
            if (sizeof($this_array) > 11) return $this_array;

            //get category information
            $icon_id = sizeof($this_array) + 1;
            $instance  = Connections_Directory();
            $catagories = $instance->retrieve->categories();

            for($x=0;$x<sizeof($catagories);$x++) {
                if ($icon_id < 13) {
                    $base_dir = plugin_dir_url(__FILE__) . 'icons/default/';
                    $this_array[] = array('id' => uniqid(), 'name' => $catagories[$x]->name, 'icon' => $base_dir . 'icon_' . $icon_id . '.png', 'type' => 'Connections', 'source' => $catagories[$x]->term_id);
                    $icon_id++;
                    if ($icon_id > 11) return $this_array;
                    if (sizeof($catagories[$x]->children) > 0) $this_array = $this->get_child_catagories_for_initial_buttons($catagories[$x]->children, $catagories[$x]->name . ' - ', $this_array);
                    if (sizeof($this_array) > 11) return $this_array;
                }
            }

            return $this_array;
        }

        public function get_notification_info($post_id){

            if (!function_exists('Connections_Directory')) {
                return null;
            }

            $this_array = array();
            $has_results = true;
            $instance  = Connections_Directory();

            $limit = 50;
            $offset = 0;

            while($has_results) {

                $retrieveAttr['limit'] = $limit;
                $retrieveAttr['offset'] = $offset;
                $retrieveAttr['visibility'] = 'public';
                $retrieveAttr['status'] = 'approved';
                $retrieveAttr['list_type'] = 'organization';

                $results = $instance->retrieve->entries($retrieveAttr);

                $offset = $offset + 50;

                if (sizeof($results) == 0) {
                    break;
                }

                foreach ($results as $index => $post) {
                    if ($post->entry_type == 'organization' && $post->id == $post_id) {
                        if (strlen($post->entry_type) > 0) $this_array['title'] = $post->organization;

                        $permalink = cnURL::permalink(array('slug' => $post->slug, $post->id, 'data' => 'url', 'return' => true));
                        $this_array['url'] = $permalink;

                    }
                }
                if(sizeof($results) <= $limit){
                    $has_results = false;
                }
            }

            return $this_array;

        }

        public function get_listing_count( $catagory ){

            if (!function_exists('Connections_Directory')) {
                return null;
            }

            global $local_app_condir_listings;
            $has_results = true;

            $instance  = Connections_Directory();

            $limit = 50;
            $offset = 0;

            while($has_results) {

                $retrieveAttr['limit'] = $limit;
                $retrieveAttr['offset'] = $offset;
                $retrieveAttr['visibility'] = 'public';
                $retrieveAttr['category'] = $catagory;
                $retrieveAttr['status'] = 'approved';
                $retrieveAttr['list_type'] = 'organization';

                $results = $instance->retrieve->entries($retrieveAttr);

                $offset = $offset + 50;

                if(sizeof($results) == 0){
                    break;
                }

                foreach ($results as $index => $post) {

                    $entry = new cnOutput($instance->retrieve->entry($post->id));
                    $catagory_list = $entry->getCategory();

                    $inlist = false;

                    for ($c = 0; $c < sizeof($catagory_list); $c++) {
                        if ($catagory == $catagory_list[$c]->term_taxonomy_id) {
                            $inlist = true;
                        }
                    }

                    if ($inlist == true) {
                        if ($post->visibility == 'public' && $post->entry_type == 'organization') {
                            $this_array['id'] = $post->id;
                            if (!isset($local_app_condir_listings[$this_array['id']])) {
                                $local_app_condir_listings[$this_array['id']] = $this_array['id'];
                            }
                        }
                    }
                }

                if(sizeof($results) <= $limit){
                    $has_results = false;
                }
            }
         }

        public function get_data_for_app($catagory){

            if (!function_exists('Connections_Directory')) {
                return null;
            }

            global $local_app_listings_allowed;
            global $local_app_condir_listings;
            global $show_map_button;
            global $show_map_button_listing;
            $show_map_button_listing = 'no';
            $published_records = 0;
            $dir_details = array();
            $has_results = true;

            $instance  = Connections_Directory();

            $limit = 50;
            $offset = 0;

            while($has_results) {

                if(sizeof($dir_details) > 200){
                    break;
                }

                $retrieveAttr['limit'] = $limit;
                $retrieveAttr['offset'] = $offset;
                $retrieveAttr['visibility'] = 'public';
                $retrieveAttr['category'] = $catagory;
                $retrieveAttr['status'] = 'approved';
                $retrieveAttr['list_type'] = 'organization';

                $results = $instance->retrieve->entries($retrieveAttr);

                $offset = $offset + 50;

                if(sizeof($results) == 0){
                    break;
                }

                foreach ($results as $index => $post) {

                    if ($local_app_listings_allowed < 1){
                        $has_results = false;
                        break;
                    }

                    $entry = new cnOutput($instance->retrieve->entry($post->id));
                    $catagory_list = $entry->getCategory();

                    $inlist = false;

                    for ($c = 0; $c < sizeof($catagory_list); $c++) {
                        if ($catagory == $catagory_list[$c]->term_taxonomy_id) {
                            $inlist = true;
                        }
                    }

                    if ($inlist == true) {

                        if (isset($_REQUEST['demo']) && $published_records > 15) {
                            break;
                        }
                        if ($post->entry_type != 'organization') continue;

                        if ($post->visibility == 'public') {


                            $this_array = array();

                            $this_array['website'] = ' ';
                            $this_array['content'] = ' ';

                            if ($post->entry_type == 'organization') {
                                if (strlen($post->entry_type) > 0) $this_array['title'] = $post->organization;
                            }

                            if (strlen($entry->getBio()) > 0) $this_array['content'] = $entry->getBio();

                            $featured_image = $entry->getImageMeta(array('type' => 'photo'));

                            if ($featured_image && !is_wp_error($featured_image)) {
                                $this_image = $featured_image['name'];
                                if (strlen($this_image) > 0) {
                                    if (file_exists($featured_image['path'])) {
                                        $this_array['image'] = str_replace(' ', '-', str_replace('/', '-', urldecode($this_image)));
                                        $this_array['imageurl'] = $featured_image['url'];
                                        $parts = explode('.', $this_array['image']);
                                        $this_array['extension'] = $parts[sizeof($parts) - 1];
                                        $this_array['image_id'] = filemtime($featured_image['path']);
                                        if ($this_array['image_id'] < 1) {
                                            $this_array['image_id'] = ' ';
                                        }
                                        $ext = strtolower($this_array['extension']);
                                        if ($ext == 'png' || $ext == 'jpg' || $ext = 'jpeg') {
                                            $image_data = wp_get_attachment_image_src($this_array['image_id'], 'full');
                                            if ($image_data[1] > 800 || $image_data[2] > 800) {
                                                $this_array['imageurl'] = 'https://tinyscreenlabs.com/wp-content/plugins/tsl-traffic-manager/tsl-image-sizer.php?file=' . $this_array['imageurl'];
                                            }
                                        }
                                    }
                                }
                            }

                            $metadata = $entry->getMeta(array('key' => 'business_hours', 'single' => FALSE));

                            if (!empty($metadata)) {
                                //process hours
                                $this_array['hours'] = $this->format_hours($metadata);
                            }

                            $phoneNumbers = $entry->getPhoneNumbers();

                            if (!empty($phoneNumbers)) {
                                foreach ($phoneNumbers as $phone) {
                                    if ($phone->preferred) $this_array['phone'] = $phone->number;
                                    if (!isset($this_array['phone']) && $phone->visibility = 'public') $this_array['phone'] = $phone->number;
                                }
                            }

                            $links = $entry->getLinks();

                            if (!empty($links)) {
                                foreach ($links as $link) {
                                    if ($link->type == 'website') $this_array['website'] = $link->url;
                                }
                            }

                            $this_array['url'] = cnURL::permalink(array('slug' => $post->slug, $post->id, 'data' => 'url', 'return' => true));

                            $addresses = $entry->getAddresses();
                            $this_array['lon'] = 0;
                            $this_array['lat'] = 0;

                            if (!empty($addresses)) {
                                $has_preferred = false;
                                $add_type = '';
                                foreach ($addresses as $address) {
                                    if ($address->preferred) {
                                        $has_preferred = true;
                                    }
                                    if ($address->visibility = 'public') {
                                        $add_type = $address->type;
                                    }
                                    if ($address->type == 'work' && $address->visibility = 'public') {
                                        $add_type = 'work';
                                    }
                                }


                                foreach ($addresses as $address) {
                                    if (($has_preferred && $address->preferred) || (!$has_preferred && $address->type == $add_type)) {

                                        if (strlen($address->latitude) > 0) {
                                            $this_array['lat'] = $address->latitude;
                                            $show_map_button = 'yes';
                                            $show_map_button_listing = 'yes';
                                        }
                                        if (strlen($address->longitude) > 0) {
                                            $this_array['lon'] = $address->longitude;
                                            $show_map_button = 'yes';
                                            $show_map_button_listing = 'yes';
                                        }
                                        if (strlen($address->line_1) > 0) $this_array['street'] = $address->line_1;
                                        if (strlen($address->city) > 0) $this_array['city'] = $address->city;
                                        if (strlen($address->state) > 0) $this_array['state'] = $address->state;
                                        if (strlen($address->zipcode) > 0) $this_array['zip'] = $address->zipcode;
                                    }
                                }
                            }

                            $offers_data = $entry->getMeta(array('key' => 'business_offers', 'single' => FALSE));

                            if (!empty($offers_data)) {

                                $deals = array();
                                $dom = new DOMDocument;

                                $dom->loadHTML($offers_data[0]['connections-business-directory-offers-text']);

                                foreach ($dom->getElementsByTagName('span') as $tag) {

                                    try {

                                        $this_item = array();

                                        if ($tag->getAttribute('data-title')) $this_item['title'] = $tag->getAttribute('data-title');
                                        if (strpos($tag->nodeValue, '[VOUCHER') !== false && 1 == 2) {
                                            $left_string = substr($tag->nodeValue, 0, strpos($tag->nodeValue, '[VOUCHER'));
                                            $right_start = strpos($tag->nodeValue, ']', strlen($left_string));
                                            $right_string = substr($tag->nodeValue, $right_start + 1);
                                            $coupon_name = substr($tag->nodeValue, strlen($left_string) + 9, $right_start - strlen($left_string) - 9);
                                            $this_item['content'] = $left_string . $coupon_name . $right_string;
                                        } else {
                                            $this_item['content'] = $tag->nodeValue;
                                        }

                                        $this_item['visible'] = ' ';
                                        $this_item['starts'] = ' ';
                                        $this_item['ends'] = ' ';
                                        $this_item['always_visible'] = ' ';

                                        if (strlen($tag->getAttribute('data-visible_date')) > 2) $this_item['visible'] = $this->convert_date($tag->getAttribute('data-visible_date'), $tag->getAttribute('data-date_format'));
                                        if (strlen($tag->getAttribute('data-start_date')) > 2) $this_item['starts'] = $this->convert_date($tag->getAttribute('data-start_date'), $tag->getAttribute('data-date_format'));
                                        if (strlen($tag->getAttribute('data-end_date')) > 2) $this_item['ends'] = $this->convert_date($tag->getAttribute('data-end_date'), $tag->getAttribute('data-date_format'));

                                        if ($tag->getAttribute('data-always_visible')) $this_item['always_visible'] = $tag->getAttribute('data-always_visible');

                                        if ($tag->getAttribute('data-voucher_image')) $this_item['coupon_url'] = $tag->getAttribute('data-voucher_image');

                                        $deals[] = $this_item;
                                    } catch (Exception $e) {
                                        //print_r($e->getMessage());
                                    }
                                }

                                if ($deals) $this_array['deals'] = $deals;

                            }

                            $this_array['id'] = $post->id;

                            if (!isset($local_app_condir_listings[$this_array['id']])) {
                                $local_app_condir_listings[$this_array['id']] = $this_array['id'];
                                $local_app_listings_allowed--;
                            }
                            $dir_details[] = $this_array;
                            $published_records++;

                        }
                    }
                }
                if(sizeof($results) <= $limit){
                    $has_results = false;
                }
            }

            foreach ($dir_details as $key => $row) {
                $titles[$key] = $row['title'];
            }

            if(sizeof($dir_details) > 0) array_multisort($titles, SORT_ASC, $dir_details);

            return $dir_details;

        }

        function format_hours( $metadata ){

            global $wp_locale;

            $weekStart = apply_filters( 'cnbh_start_of_week', get_option('start_of_week') );
            $weekday   = $wp_locale->weekday;

            for ( $i = 0; $i < $weekStart; $i++ ) {

                $day = array_slice( $weekday, 0, 1, true );
                unset( $weekday[ $i ] );

                $weekday = $weekday + $day;
            }

            $html_line = '';

            for($x=0;$x<sizeof($weekday);$x++){

                if(strlen($metadata[0][$x][0]['open']) > 0) $html_line .= $weekday[$x] . ': ' . $this->formatTime($metadata[0][$x][0]['open']) . ' to ' . $this->formatTime($metadata[0][$x][0]['close']) . '<br>';
            }

            return $html_line;
        }

        public static function timeFormat() {

			return apply_filters( 'cnbh_time_format', get_option('time_format') );
		}

		public static function formatTime( $value, $format = NULL ) {

			$format = is_null( $format ) ? self::timeFormat() : $format;

			if ( strlen( $value ) > 0 ) {

				return date( $format, strtotime( $value ) );

			} else {

				return $value;
			}
		}

        public function convert_date($this_date , $format){

            $date_value = '';
            if(strlen($this_date) < 4 ) return '';

            if(strlen($format) < 4) $format = 'mm/dd/yy';

            try{

                switch($format){
                    case 'dd/mm/yy':
                        $date_value = DateTime::createFromFormat("d/m/Y H:i:s", $this_date . '00:00:00');
                        break;
                   case 'mm/dd/yy':
                        $date_value = DateTime::createFromFormat("m/d/Y H:i:s", $this_date . '00:00:00');
                        break;
                   case 'd M, yy':
                        $date_value = DateTime::createFromFormat("d M, Y H:i:s", $this_date . '00:00:00');
                        break;
                   case 'd MM, yy':
                        $date_value = DateTime::createFromFormat("d F, Y H:i:s", $this_date . '00:00:00');
                        break;
                }

                if($date_value) {
                    return $date_value->format('U');
                }else{
                    return '';
                }

            } catch ( exception $e ) {

                return ' ';
            }
        }

    }
}
?>