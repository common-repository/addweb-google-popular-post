<?php
/*
Plugin Name: Traffic Post Page Views
Description: Retrieves and displays the page views for each post by linking to your G-Analytics account.
Version: 2.4.1
Author: AddWeb Solution Pvt. Ltd.
Author URI: http://www.addwebsolution.com
Text Domain: traffic-post-page-views
*/
define('TAPP_SLUG', 'addweb-traffic-post-page-views');
include_once( 'inc/functions.php' );

if (function_exists('load_plugin_textdomain')) {
	load_plugin_textdomain('addweb-traffic-post-page-views', false, dirname(plugin_basename(__FILE__)).'/languages' );
}

add_action('admin_menu', 'tapp_config_page');
function tapp_config_page() {
	if (function_exists('add_submenu_page')) {
        add_submenu_page('options-general.php',
            __('Traffic Post Page Views Admin Panel', 'addweb-traffic-post-page-views'),
            __('Traffic Post Page Views Admin Panel', 'addweb-traffic-post-page-views'),
            'manage_options', TAPP_SLUG, 'tapp_conf');
    }
}
function tapp_api_call($url, $params = array(), $urlEncode = true) {
	$options = tapp_options();
	if (time() >= $options['tapp_expires']) {
		$options = tapp_refresh_token();
	}

    $qs = '?access_token='.urlencode($options['tapp_token']);
    foreach ($params as $k => $v) {
        $qs .= '&'.$k.'='.($urlEncode ? urlencode($v) : $v);
    }

	$request = new WP_Http;
	$result = $request->request($url.$qs);
	$json = new stdClass();

    $options['tapp_error'] = null;
	if ( is_array( $result ) && isset( $result['response']['code'] ) && 200 === $result['response']['code'] ) {
        $json = json_decode($result['body']);
        update_option('tapp', $options);
		return $json;
	} else {
        if ( is_array( $result ) && isset( $result['response']['code'] ) && 403 === $result['response']['code'] ) {
            $json = json_decode($result['body'], true);
            $options['tapp_error'] = $json['error']['errors'][0]['message'];
            $options['tapp_token'] = null;
            $options['tapp_token_refresh'] = null;
            $options['tapp_expires'] = null;
            $options['tapp_gid'] = null;
            update_option('tapp', $options);
        }
		return new stdClass();
	}
}

function tapp_refresh_token() {
	$options = tapp_options();
	/* If the token has expired, we create it again */
	if (!empty($options['tapp_token_refresh'])) {

		$request = new WP_Http;

		$result = $request->request('https://accounts.google.com/o/oauth2/token', array(
			'method' => 'POST',
			'body' => array(
				'client_id' => $options['tapp_clientid'],
				'client_secret' => $options['tapp_psecret'],
				'refresh_token' => $options['tapp_token_refresh'],
				'grant_type' => 'refresh_token',
			),
		));

        $options['tapp_error'] = null;

		if ( is_array( $result ) && isset( $result['response']['code'] ) && 200 === $result['response']['code'] ) {

			$tjson = json_decode($result['body']);

			$request = new WP_Http;
			$result = $request->request('https://www.googleapis.com/oauth2/v1/userinfo?access_token='.urlencode($tjson->access_token));

			if ( is_array( $result ) && isset( $result['response']['code'] ) && 200 === $result['response']['code'] ) {

				$ijson = json_decode($result['body']);

				$options['tapp_token'] = $tjson->access_token;

				if (isset($tjson->refresh_token) && !empty($tjson->refresh_token)) {
					$options['tapp_token_refresh'] = $tjson->refresh_token;
				}

				$options['tapp_expires'] = time() + $tjson->expires_in;
				$options['tapp_gid'] = $ijson->id;

				update_option('tapp', $options);

			} elseif ( is_array( $result ) && isset( $result['response']['code'] ) && 403 === $result['response']['code'] ) {

                $json = json_decode($result['body'], true);

                $options['tapp_error'] = $json['error']['errors'][0]['message'];

                $options['tapp_token'] = null;
                $options['tapp_token_refresh'] = null;
                $options['tapp_expires'] = null;
                $options['tapp_gid'] = null;

                update_option('tapp', $options);

            }

		} 

	}

	return $options;

}

function tapp_options() {

	$options = get_option('tapp');

	if (!isset($options['tapp_clientid'])) {
		if (isset($options['tapp_pnumber'])) {
            if (isset($options['tapp_clientid'])){
                $options['tapp_clientid'] = $options['tapp_pnumber'] . '.apps.googleusercontent.com';
            }
		} else {
            if (isset($options['tapp_clientid'])) {
                $options = ['tapp_clientid' => null];
            }
		}
	}

	if (isset($options['tapp_pnumber'])) unset($options['tapp_pnumber']);
	if (!isset($options['tapp_psecret'])) $options = ['tapp_psecret' => null];
	if (!isset($options['tapp_gid'])) $options =['tapp_gid' => null] ;
	if (!isset($options['tapp_gmail'])) $options = ['tapp_gmail' => null];
	if (!isset($options['tapp_token']))  $options = ['tapp_token' => null];
	if (!isset($options['tapp_defaultval'])) $options['tapp_defaultval'] = 0;
    if (!isset($options['tapp_totalpost'])) $options['tapp_totalpost'] = 1;
    if (!isset($options['tapp_totalpost_grid'])) $options['tapp_totalpost_grid'] = 1;
    if (!isset($options['tapp_btn_color'])) $options['tapp_btn_color'] = null;
    
    if (!isset($options['tapp_sliderwidth'])) $options['tapp_sliderwidth'] = '100%';
    if (!isset($options['tapp_sliderheight'])) $options['tapp_sliderheight'] = '600px';
    if (!isset($options['tapp_analytics_code'])) $options['tapp_analytics_code'] = '';
	if (!isset($options['tapp_token_refresh'])) $options['tapp_token_refresh'] = null;
	if (!isset($options['tapp_expires'])) $options['tapp_expires'] = null;
	if (!isset($options['tapp_wid'])) $options['tapp_wid'] = null;
	if (!isset($options['tapp_column'])) $options['tapp_column'] = true;
    if (!isset($options['tapp_onoffswitch'])) $options['tapp_onoffswitch'] = true;
    if (!isset($options['tapp_onoffswitch_grid'])) $options['tapp_onoffswitch_grid'] = true;
    
	if (!isset($options['tapp_trailing'])) $options['tapp_trailing'] = true;
	if (!isset($options['tapp_cache'])) $options['tapp_cache'] = 60;
	if (!isset($options['tapp_metric'])) $options['tapp_metric'] = 'ga:pageviews';
    if(isset($options['tapp_startdate'])) {
        if (!preg_match('/^([0-9]{4})-([0-9]{2})-([0-9]{2})$/', $options['tapp_startdate'])) $options['tapp_startdate'] = '2007-09-29';
    }

	return $options;

}

function tapp_conf() {

	/** @var $wpdb WPDB */
	global $wpdb;

	$options = tapp_options();

	$updated = false;

    if (isset($_GET['state']) && $_GET['state'] == 'init' && $_GET['code']) {
        
		    $request = new WP_Http;

		    $result = $request->request('https://accounts.google.com/o/oauth2/token', array(
			    'method' => 'POST',
			    'body' => array(
				    'code' => sanitize_text_field($_GET['code']),
				    'client_id' => $options['tapp_clientid'],
				    'client_secret' => $options['tapp_psecret'],
				    'redirect_uri' => admin_url('options-general.php?page=' . TAPP_SLUG),
				    'grant_type' => 'authorization_code',
			    )
		    ));

		    if ( !is_array( $result ) || !isset( $result['response']['code'] ) && 200 !== $result['response']['code'] ) {

	            echo '<div id="message" class="error"><p>';
	            _e('There was something wrong with Authentication.', 'addweb-traffic-post-page-views');
	            echo "</p></div>";

			    var_dump($result);

	        }

	        $tjson = json_decode($result['body']);

	        $options['tapp_token'] = $tjson->access_token;
	        $options['tapp_token_refresh'] = $tjson->refresh_token;
	        $options['tapp_expires'] = time() + $tjson->expires_in;

	        update_option('tapp', $options);

	        $ijson = tapp_api_call('https://www.googleapis.com/oauth2/v1/userinfo', array());

	        $options['tapp_gid'] = $ijson->id;
	        $options['tapp_gmail'] = $ijson->email;

	        update_option('tapp', $options);

	        if (!empty($options['tapp_token']) && !empty($options['tapp_gmail'])) {

	            echo '<script>window.location = \''.admin_url('options-general.php?page=' . TAPP_SLUG).'\';</script>';
				exit;

	        }
	    

    } elseif (isset($_GET['state']) && $_GET['state'] == 'reset') {

     
        if (current_user_can('manage_options')) {
	        $options['tapp_gid'] = null;
	        $options['tapp_gmail'] = null;
	        $options['tapp_token'] = null;
	        $options['tapp_token_refresh'] = null;
	        $options['tapp_expires'] = null;
		    $options['tapp_defaultval'] = 0;
	        $options['tapp_totalpost'] = 1;
	        $options['tapp_totalpost_grid'] = 1;
	        $options['tapp_btn_color'] = null;
	        
	        $options['onoffswitch'] = 1;
	        $options['tapp_sliderwidth'] = '100%';
	        $options['tapp_sliderheight'] = '600px';
	        $options['tapp_analytics_code'] = '';
	        update_option('tapp', $options);

	        $updated = true;
	    }

    } elseif (isset($_GET['state']) && $_GET['state'] == 'clear') {
        
		    $options['tapp_clientid'] = null;
	        $options['tapp_psecret'] = null;

	        update_option('tapp', $options);

	        $updated = true;
	    

    } elseif (isset($_GET['refresh'])) {
        	tapp_refresh_token();
		    $options = tapp_options();
		    $updated = true;

    } elseif (isset($_GET['reset'])) {
        	$wpdb->query("DELETE FROM `" . $wpdb->options . "` WHERE `option_name` LIKE '_transient_tapp-transient-%'");
		    $wpdb->query("DELETE FROM `" . $wpdb->options . "` WHERE `option_name` LIKE '_transient_timeout_tapp-transient-%'");
	        set_transient('tapp-namespace-key', uniqid(), 86400 * 365);
		    $updated = true;
    }

	
    if (isset($_REQUEST['submit'])) {
		check_admin_referer('tapp', 'tapp-admin');
		if (current_user_can('manage_options')) {
			if (isset($_POST['tapp_clientid'])) {
	            $options['tapp_clientid'] = sanitize_text_field($_POST['tapp_clientid']);
			}

	        if (isset($_POST['tapp_psecret'])) {
	            $options['tapp_psecret'] = sanitize_text_field($_POST['tapp_psecret']);
	        }

	        if (isset($_POST['tapp_wid'])) {
	            $options['tapp_wid'] = sanitize_text_field($_POST['tapp_wid']);
	        }

			if (isset($_POST['tapp_cache'])) {
				$options['tapp_cache'] = sanitize_text_field($_POST['tapp_cache']);
			}

			if (isset($_POST['tapp_startdate'])) {
				$options['tapp_startdate'] = sanitize_text_field($_POST['tapp_startdate']);
			}

			if (isset($_POST['tapp_defaultval'])) {
				$options['tapp_defaultval'] = sanitize_text_field($_POST['tapp_defaultval']);
			}

	        if (isset($_POST['tapp_totalpost'])) {
	            $options['tapp_totalpost'] = sanitize_text_field($_POST['tapp_totalpost']);
	        }

	        if (isset($_POST['tapp_totalpost_grid'])) {
	            $options['tapp_totalpost_grid'] = sanitize_text_field($_POST['tapp_totalpost_grid']);
	        }

	        if (isset($_POST['tapp_btn_color'])) {
	            $options['tapp_btn_color'] = sanitize_text_field($_POST['tapp_btn_color']);
	        }
	        
	        if (isset($_POST['tapp_sliderwidth'])) {
	            $options['tapp_sliderwidth'] = sanitize_text_field($_POST['tapp_sliderwidth']);
	        }
			
	        if (isset($_POST['tapp_sliderheight'])) {
	            $options['tapp_sliderheight'] = sanitize_text_field($_POST['tapp_sliderheight']);
	        }

	        if (isset($_POST['tapp_analytics_code'])) {
	            $options['tapp_analytics_code'] = sanitize_text_field($_POST['tapp_analytics_code']);
	        }

			if (isset($_POST['tapp_metric'])) {
				$options['tapp_metric'] = sanitize_text_field($_POST['tapp_metric']);
			}

	        	$options['tapp_onoffswitch'] = (isset($_POST['tapp_onoffswitch']));	        
	        	$options['tapp_onoffswitch_grid'] = (isset($_POST['tapp_onoffswitch_grid']));	        
				$options['tapp_column'] = (isset($_POST['tapp_column']));	       
				$options['tapp_trailing'] = (isset($_POST['tapp_trailing']));
			update_option('tapp', $options);

			$updated = true;
		}

	}

    echo '<div class="wrap">';

    if ($updated) {
        
		    echo '<div id="message" class="updated fade"><p>';
		    _e('Configuration updated.', 'addweb-traffic-post-page-views');
		    echo '</p></div>';
		
    }    
    echo '<h2>'.__('Traffic Post Page Views Settings', 'addweb-traffic-post-page-views').'</h2>';

    if (empty($options['tapp_token'])) {

        if (empty($options['tapp_clientid']) || empty($options['tapp_psecret'])) {

            echo '<p>'.__('In order to connect to your G-Analytics Account, you need to create a new project in the <a href="https://console.developers.google.com/project" target="_blank">G-API Console</a> and activate the Analytics API in "APIs &amp; auth &gt; APIs".', 'addweb-traffic-post-page-views').'</p>';

            echo '<form action="'.admin_url('options-general.php?page=' . TAPP_SLUG).'" method="post" id="tapp-conf">';

            echo '<p>'.__('Then, create an OAuth Client ID in "APIs &amp; auth &gt; Credentials". Enter this URL for the Redirect URI field:', 'addweb-traffic-post-page-views').'<br/>';
            echo admin_url('options-general.php?page=' . TAPP_SLUG);
            echo '</p>';

	        echo '<p>'.__('You also have to fill the Product Name field in "APIs & auth" -> "Consent screen" — you need to select e-mail address as well.').'</p>';
			echo '<div class="tp-wrap"><div class="fa-plugin-setting config-wrap"><ul><li><a href = "#tp-setting">'.__('Settings').'</a></li><li><a href = "#tp-about">About Us</a></li></ul>';
			echo '<div id="tp-setting">';
			echo '
			<a href="https://www.wewp.io/" style="outline: hidden;" target="_blank"><img src="'.plugin_dir_url(__FILE__). 'inc/img/wewp-ad-plugin-400.png" alt="WeWp" width="300px" style="float:right;" ></a>
		    ';
            echo '<h3><label for="tapp_clientid">'.__('Client ID:', 'addweb-traffic-post-page-views').'</label></h3>';
            echo '<p><input type="text" id="tapp_clientid" name="tapp_clientid" value="'.esc_attr($options['tapp_clientid']).'" style="width: 400px;" /></p>';

            echo '<h3><label for="tapp_psecret">'.__('Client secret:', 'addweb-traffic-post-page-views').'</label></h3>';
            echo '<p><input type="text" id="tapp_psecret" name="tapp_psecret" value="'.esc_attr($options['tapp_psecret']).'" style="width: 400px;" /></p>';

            echo '<p class="submit" style="text-align: left">';
            wp_nonce_field('tapp', 'tapp-admin');
            echo '<input type="submit" name="submit" class="button-primary" value="'.__('Save', 'addweb-traffic-post-page-views').' &raquo;" /></p></form></div>';
			echo ' <div id="tp-about">';
			$arrAddwebPlugins = array(
				'woo-cart-customizer' => 'Simple Customization of Add to Cart Button',
				'aws-cookies-popup' => 'AWS Cookies Popup',
				'addweb-google-popular-post' => 'Traffic Post Page Views',
				'post-timer' => 'Post Timer',
				'wc-past-orders' => 'Track Order History for WooCommerce',
				'widget-social-share' => 'WSS: Widget Social Share'      
				
			);
			echo '<div class="advertise">';
			echo '<h2>'.__( 'Visit Our Other Plugins:', 'addweb-pt-timer-popup' ).'</h2>';
			echo '<div class="ad-content">'; 
				foreach($arrAddwebPlugins as $slug=>$name) {
					echo '<div class="ad-detail">';
					echo '<div class="ad-inner" >';
					echo '<a href="https://wordpress.org/plugins/'.$slug.'" target="_blank"><img height="160" src="'.plugin_dir_url(__FILE__).'inc/img/'.$slug.'.svg"></a>';
					echo '<a href="https://wordpress.org/plugins/'.$slug.'" class="ad-link" target="_blank"><b>'.$name.'</b></a>';
					echo '</div>';
					echo '</div>';
				}
				echo '</div>';
				echo '</div>';
				echo '<div style="margin:5px 0;width:100%;text-align: center;">';
				echo '<a href="http://www.wewp.io" style="outline: hidden;" target="_blank"><img src="'.plugin_dir_url(__FILE__). 'inc/img/wewp-logo.png" alt="WeWp" height="150px" width="100%"></a>';
				echo '</div>';
				echo '<div style="margin:5px 0;width:100%;text-align: center;">';
				echo '<h3>'.__('Developed with').' <img decoding="async" src="'.plugin_dir_url(__FILE__). 'inc/img/Heart-yellow.svg" alt="AddwebSolution"> By <a href="http://www.addwebsolution.com" style="outline: hidden;" target="_blank">ADDWEB SOLUTION</a></h3>';
				echo '</div>';
				echo '</div>';
				echo '</div>';
				

        } else {

            $url_auth = 'https://accounts.google.com/o/oauth2/auth?client_id='.$options['tapp_clientid'].'&redirect_uri=';
            $url_auth .= admin_url('options-general.php?page=' . TAPP_SLUG);
            $url_auth .= '&scope=https://www.googleapis.com/auth/analytics.readonly+https://www.googleapis.com/auth/userinfo.email+https://www.googleapis.com/auth/userinfo.profile&response_type=code&access_type=offline&state=init&approval_prompt=force';

            echo '<p><a href="'.esc_attr($url_auth).'">'.__('Connect to G-Analytics', 'addweb-traffic-post-page-views').'</a></p>';

            echo '<p><a href="'.admin_url('options-general.php?page=' . TAPP_SLUG).'&state=clear">'.__('Clear the API keys').' &raquo;</a></p>';

        }

    } else {
        echo '<div id="tabs"><ul><li><a href="#tabs-1">Analytics Setup</a></li><li><a href="#tabs-2">Slider Setting</a></li><li><a href="#tabs-4">Grid Setting</a></li><li><a href="#tabs-3">Analytics Code</a></li></ul>';
        echo '<div id="tabs-1">';
        echo '<p>'.__('You are connected to G-Analytics with the e-mail address:', 'addweb-traffic-post-page-views').' '.esc_attr($options['tapp_gmail']).'.</p>';

        echo '<p>'.__('Your token expires on:', 'addweb-traffic-post-page-views').' '.date_i18n( 'Y/m/d \a\t g:ia', esc_attr($options['tapp_expires']) + ( get_option( 'gmt_offset' ) * 3600 ) , 1 ).'.</p>';

	    echo '<p><a href="'.admin_url('options-general.php?page=' . TAPP_SLUG . '&state=reset').'">'.__('Disconnect from G-Analytics', 'addweb-traffic-post-page-views').' &raquo;</a></p>';

        echo '<p><a href="'.admin_url('options-general.php?page=' . TAPP_SLUG . '&refresh').'">'.__('Refresh G-API token', 'addweb-traffic-post-page-views').' &raquo;</a></p>';

	    echo '<p><a href="'.admin_url('options-general.php?page=' . TAPP_SLUG . '&reset').'">'.__('Empty pageviews cache', 'addweb-traffic-post-page-views').' &raquo;</a></p>';

        echo '<form action="'.admin_url('options-general.php?page=' . TAPP_SLUG).'" method="post" id="tapp-conf">';

        echo '<h3><label for="tapp_wid">'.__('Use this website to retrieve pageviews numbers:', 'addweb-traffic-post-page-views').'</label></h3>';
        echo '<p><select id="tapp_wid" name="tapp_wid" style="width: 400px;" />';

        echo '<option value=""';
        if (empty($options['tapp_wid'])) echo ' SELECTED';
        echo '>'.__('None', 'addweb-traffic-post-page-views').'</option>';

        $wjson = tapp_api_call('https://www.googleapis.com/analytics/v3/management/accounts/~all/webproperties/~all/profiles', array());

        if (is_array($wjson->items)) {

            foreach ($wjson->items as $item) {

                if ($item->type != 'WEB') {
                    continue;
                }

                echo '<option value="'.esc_attr($item->id).'"';
                if ($options['tapp_wid'] == $item->id) echo ' SELECTED';
                echo '>'.esc_attr($item->name).' ('.esc_url($item->websiteUrl).')</option>';

            }

        }

        echo '</select></p>';

	    echo '<h3><label for="tapp_metric">'.__('Metrics to retrieve:', 'addweb-traffic-post-page-views').'</label></h3>';
	    echo '<p><select id="tapp_metric" name="tapp_metric" style="width: 400px;" />';

	    echo '<option value="ga:pageviews"';
	    if ($options['tapp_metric'] == 'ga:pageviews') echo ' SELECTED';
	    echo '>'.__('Page views', 'addweb-traffic-post-page-views').'</option>';

	    echo '<option value="ga:uniquePageviews"';
	    if ($options['tapp_metric'] == 'ga:uniquePageviews') echo ' SELECTED';
	    echo '>'.__('Unique page views', 'addweb-traffic-post-page-views').'</option>';

	    echo '</select></p>';

        echo '<h3><label for="tapp_cache">'.__('Cache time:', 'addweb-traffic-post-page-views').'</label></h3>';
        echo '<p><select id="tapp_cache" name="tapp_cache">';

        echo '<option value="60"';
        if ($options['tapp_cache'] == 60) echo ' SELECTED';
        echo '>'.__('One hour', 'addweb-traffic-post-page-views').'</option>';

        echo '<option value="240"';
        if ($options['tapp_cache'] == 240) echo ' SELECTED';
        echo '>'.__('Four hours', 'addweb-traffic-post-page-views').'</option>';

        echo '<option value="360"';
        if ($options['tapp_cache'] == 360) echo ' SELECTED';
        echo '>'.__('Six hours', 'addweb-traffic-post-page-views').'</option>';

        echo '<option value="720"';
        if ($options['tapp_cache'] == 720) echo ' SELECTED';
        echo '>'.__('12 hours', 'addweb-traffic-post-page-views').'</option>';

        echo '<option value="1440"';
        if ($options['tapp_cache'] == 1440) echo ' SELECTED';
        echo '>'.__('One day', 'addweb-traffic-post-page-views').'</option>';

        echo '<option value="10080"';
        if ($options['tapp_cache'] == 10080) echo ' SELECTED';
        echo '>'.__('One week', 'addweb-traffic-post-page-views').'</option>';

        echo '<option value="20160"';
        if ($options['tapp_cache'] == 20160) echo ' SELECTED';
        echo '>'.__('Two weeks', 'addweb-traffic-post-page-views').'</option>';

        echo '</select></p>';

        echo '<h3><label for="tapp_startdate">'.__('Start date for the analytics:', 'addweb-traffic-post-page-views').'</label></h3>';
        echo '<p><input type="text" id="tapp_startdate" name="tapp_startdate" value="'.esc_attr($options['tapp_startdate']).'" /></p>';

	    echo '<h3><label for="tapp_defaultval">'.__('Default value when a count cannot be fetched:', 'addweb-traffic-post-page-views').'</label></h3>';
	    echo '<p><input type="text" id="tapp_defaultval" name="tapp_defaultval" value="'.esc_attr($options['tapp_defaultval']).'" /></p>';

	    echo '<h3><input type="checkbox" name="tapp_column" value="1" id="tapp_column" ' . (esc_attr($options['tapp_column']) ? 'checked' : null) . '> <label for="tapp_column">'.__('Display the Views column in Posts list', 'addweb-traffic-post-page-views').'</label></h3>';

	    echo '<h3><input type="checkbox" name="tapp_trailing" value="1" id="tapp_trailing" ' . (esc_attr($options['tapp_trailing']) ? 'checked' : null) . '> <label for="tapp_trailing">'.__('Search pageviews slugs with trailing slash', 'addweb-traffic-post-page-views').'</label></h3>';
        echo '</div><div id="tabs-2">';
        echo '<label class="switch">
                    <input type="checkbox" name="tapp_onoffswitch" value="1" id="tapp_onoffswitch" ' . (esc_attr($options['tapp_onoffswitch']) ? 'checked' : null) . '>
                    <span class="slider round"></span>
                </label>';
        echo '<h3><label for="tapp_totalpost">'.__('Set Total Number of post:', 'addweb-traffic-post-page-views').'</label></h3>';
        echo '<p><input type="number" id="tapp_totalpost" name="tapp_totalpost" value="'.esc_attr($options['tapp_totalpost']).'" /></p>';

        echo '<h3><label for="tapp_sliderwidth">'.__('Set Slider Width (Default 100%):', 'addweb-traffic-post-page-views').'</label></h3>';
        echo '<p><input type="text" id="tapp_sliderwidth" name="tapp_sliderwidth" value="'.esc_attr($options['tapp_sliderwidth']).'" /></p>';

        echo '<h3><label for="tapp_sliderheight">'.__('Set Slider Height (Default 600px):', 'addweb-traffic-post-page-views').'</label></h3>';
        echo '<p><input type="text" id="tapp_sliderheight" name="tapp_sliderheight" value="'.esc_attr($options['tapp_sliderheight']).'" /></p>';

        echo '</div><div id="tabs-4">';
        echo '<label class="switch">
                    <input type="checkbox" name="tapp_onoffswitch_grid" value="1" id="tapp_onoffswitch_grid" ' . (esc_attr($options['tapp_onoffswitch_grid']) ? 'checked' : null) . '>
                    <span class="slider round"></span>
                </label>';
        echo '<h3><label for="tapp_totalpost_grid">'.__('Set Total Number of post:', 'addweb-traffic-post-page-views').'</label></h3>';
        echo '<p><input type="number" id="tapp_totalpost_grid" name="tapp_totalpost_grid" value="'.esc_attr($options['tapp_totalpost_grid']).'" /></p>';
        

        echo '</div><div id="tabs-3">';
         echo '<h3><label for="tapp_analytics_code">'.__('Add Analytics Code if you haven not added already: ', 'addweb-traffic-post-page-views').'</label></h3>';
        echo '<p><input type="text" id="tapp_analytics_code" name="tapp_analytics_code" value="'.esc_attr($options['tapp_analytics_code']).'" /></p>';
        
        echo '<p><label></label></p>';
        echo '</div></div>';
        echo '<p class="submit" style="text-align: left">';
        
        wp_nonce_field('tapp', 'tapp-admin');
        echo '<input type="submit" class="components-button  is-primary" name="submit" value="'.__('Save', 'addweb-traffic-post-page-views').' " /></p></form></div>';

    }

}

function tapp_get_post_pageviews($ID = null, $format = true, $save = true) {

	$options = tapp_options();

	if ($ID) {

		$basename = basename(get_permalink($ID));

		if ($options['tapp_trailing']) {
			$basename .= '/';
		}

		$gaTransName = 'tapp-transient-'.$ID;
		$permalink = '/' . (($ID != 1) ? $basename : null);
		$postID = $ID;
		$postDate = get_the_date('Y-m-d', $postID);

	} else {

		$basename = basename(get_permalink());

		if ($options['tapp_trailing']) {
			$basename .= '/';
		}

		$gaTransName = 'tapp-transient-'.get_the_ID();
		$permalink = '/' . $basename;
		$postID = get_the_ID();
		$postDate = get_the_date('Y-m-d');

	}

	// Check if the published date is earlier than default start date

	if (strtotime($postDate) > strtotime($options['tapp_startdate'])) {
		$startDate = $postDate;
	} else {
		$startDate = $options['tapp_startdate'];
	}

    $namespaceKey = get_transient('tapp-namespace-key');

    if ($namespaceKey === false) {
        $namespaceKey = uniqid();
        set_transient('tapp-namespace-key', $namespaceKey, YEAR_IN_SECONDS);
    }

    $gaTransName .= '-' . $namespaceKey;

    $totalResult = get_transient($gaTransName);

    if ($totalResult !== false && is_numeric($totalResult)) {

	    if ($save && !add_post_meta($postID, '_tapp_post_views', $totalResult, true)) {
		    update_post_meta($postID, '_tapp_post_views', $totalResult);
	    }

	    return ($format) ? number_format_i18n($totalResult) : $totalResult;

    } else {

        if (empty($options['tapp_token'])) {

            return $options['tapp_defaultval'];

        }

	    if (!$ID || $ID != 1) {

		    if ($ID) {

			    $status = get_post_status($ID);

		    } else {

			    $status = get_post_status(get_the_ID());

		    }

		    if ($status !== 'publish') {

			    set_transient($gaTransName, '0', 60 * $options['tapp_cache']);

			    if (!add_post_meta($postID, '_tapp_post_views', '0', true)) {
				    update_post_meta($postID, '_tapp_post_views', '0');
			    }

			    return 0;

		    }

	    }

        $json = tapp_api_call('https://www.googleapis.com/analytics/v3/data/ga',
            array('ids' => 'ga:'.$options['tapp_wid'],
                'start-date' => $startDate,
                'end-date' => date('Y-m-d'),
                'metrics' => $options['tapp_metric'],
                'filters' => 'ga:pagePath=@' . $permalink,
                'max-results' => 1000)
        , false);        
	    if ( isset( $json->totalsForAllResults->{$options['tapp_metric']} ) ) {

		    $totalResult = $json->totalsForAllResults->{$options['tapp_metric']};
            echo $totalResult;
		    set_transient($gaTransName, $totalResult, 60 * $options['tapp_cache']);

		    if (!add_post_meta($postID, '_tapp_post_views', $totalResult, true)) {
			    update_post_meta($postID, '_tapp_post_views', $totalResult);
		    }

		    return ($format) ? number_format_i18n($totalResult) : $totalResult;

	    } else {

		    $default_value = $options['tapp_defaultval'];

		    // If we have an old value let's put that instead of the default one in case of an error
		    $meta_value = get_post_meta($postID, '_tapp_post_views', true);

		    if ($meta_value !== false) {
			    $default_value = $meta_value;
		    }

		    set_transient($gaTransName, $default_value, 60 * $options['tapp_cache']);

		    return $options['tapp_defaultval'];

	    }

    }

}

// Add a column in Posts list (Optional)

add_filter('manage_posts_columns', 'tapp_column_views');
add_action('manage_posts_custom_column', 'tapp_custom_column_views', 6, 2);
add_action('admin_head', 'tapp_column_style');
add_filter('manage_edit-post_sortable_columns', 'tapp_manage_sortable_columns');
add_action('pre_get_posts', 'tapp_pre_get_posts', 1);

function tapp_column_views($defaults) {

	$options = tapp_options();

	if (!empty($options['tapp_token']) && $options['tapp_column']) {

		$defaults['post_views'] = __('Views');

	}

	return $defaults;

}

function tapp_custom_column_views($column_name, $id) {

	if ($column_name === 'post_views') {

		echo tapp_get_post_pageviews(get_the_ID(), true, true);

	}

}

function tapp_column_style() {

	echo '<style>.column-post_views { width: 120px; }</style>';

}

function tapp_manage_sortable_columns($sortable_columns) {

	$sortable_columns['post_views'] = 'post_views';

	return $sortable_columns;

}

function tapp_pre_get_posts($query) {

	if ($query->is_main_query() && ($orderby = $query->get('orderby'))) {
		switch ($orderby) {
			case 'post_views':
				$query->set('meta_key', '_tapp_post_views');
				$query->set('orderby', 'meta_value_num');

				break;
		}
	}

	return $query;

}

function tapp_admin_notice() {

	$options = tapp_options();

	if (current_user_can('manage_options')) {

		if (isset($options['tapp_token']) && empty($options['tapp_token'])) {

			echo '<div class="error"><p>'.__('Traffic Post Page Views Warning: You have to (re)connect the plugin to your G-account.') . '<br><a href="'.admin_url('options-general.php?page=' . TAPP_SLUG).'">'.__('Update settings', 'addweb-traffic-post-page-views').' &rarr;</a></p></div>';

		} elseif (isset($options['tapp_error']) && !empty($options['tapp_error'])) {

            echo '<div class="error"><p>'.__('Traffic Post Page Views Error: ') . esc_attr($options['tapp_error']) . '<br><a href="'.admin_url('options-general.php?page=' . TAPP_SLUG).'">'.__('Update settings', 'addweb-traffic-post-page-views').' &rarr;</a></p></div>';

        }

	}

}
// Admin notice
add_action('admin_notices', 'tapp_admin_notice');

function tapp_ganalytics_scipt() {   
	wp_register_style( 'admin-custom-css', plugin_dir_url( __FILE__ ) . 'inc/css/admin/admin-custom.css');
	wp_register_style( 'jquery-ui-css', plugin_dir_url( __FILE__ ) . 'inc/css/admin/jquery-ui.css');
    if( isset( $_GET['page']) && $_GET['page'] == 'addweb-traffic-post-page-views' ){
		wp_enqueue_style('admin-custom-css', plugin_dir_url( __FILE__ ) . 'inc/css/admin/admin-custom.css');
        wp_enqueue_style('jquery-ui-css', plugin_dir_url( __FILE__ ) . 'inc/css/admin/jquery-ui.css');
      }
    
    wp_enqueue_script( 'custom', plugin_dir_url( __FILE__ ) . 'inc/js/admin/tabs.js', array('jquery-ui-tabs'), '', true  );
}
add_action('admin_enqueue_scripts', 'tapp_ganalytics_scipt');


function tapp_add_ganalytics() {
    $options = tapp_options();
    if($options['tapp_analytics_code'] != ' ') { ?>
        <script async src="https://www.googletagmanager.com/gtag/js?id=<?php echo $options['tapp_analytics_code'];?>"></script>
        <script>
          window.dataLayer = window.dataLayer || [];
          function gtag(){dataLayer.push(arguments);}
          gtag('js', new Date());

          gtag('config', '<?php echo esc_attr($options['tapp_analytics_code']);?>');
          
        </script>'
    <?php } ?>
<?php }
add_action('wp_head', 'tapp_add_ganalytics');
