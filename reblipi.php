<?php
/*
Plugin Name: Reblipi.pl
Plugin URI: http://re.blipi.pl
Description: Dodaje przycisk do szybkiego blipowania o postach na blogu, zachęcający czytelników do dzielenia się linkiem do danego wpisu, oraz licznik pokazujący liczbę blipnięć o nim.
Version: 1.0
Author: Blipi
Author URI: http://blipi.pl
*/

function re_options() {
	add_menu_page('Reblipi', 'Reblipi', 8, basename(__FILE__), 're_options_page');
	add_submenu_page(basename(__FILE__), 'Ustawienia', 'Ustawienia', 8, basename(__FILE__), 're_options_page');
}

function re_generate_button() {
	global $post;
	$url = '';
	if (get_post_status($post->ID) == 'publish') {
	    $url = get_permalink();
	}

	$button = '<div id="reblipi" style="' . get_option('re_style') . '">';
	$button .= '<iframe src="http://gadget.blipi.pl/reblipi/';

	if (get_option('re_version') == 'mini') {
		$button .= 'mini/';
	}

	$button .= 'url=' . $url . '" ';

	if (get_option('re_version') == 'mini') {
		$button .= 'width="100" height="16"';
	} else {
		$button .= 'width="50" height="60"';
	}

	$button .= ' frameborder="0" scrolling="no" allowtransparency="true"></iframe></div>';

	return $button;
}

function re_update($content) {

    global $post;

    // add the manual option, code added by kovshenin
    if (get_option('re_where') == 'manual') {
        return $content;
    }

    if (get_option('re_display_page') == null && is_page()) {
        return $content;
    }

    if (get_option('re_display_front') == null && is_home()) {
        return $content;
    }

    if (is_feed()) {
		$button = '';
		$where = 're_rss_where';
    } else {
		$button = re_generate_button();
		$where = 're_where';
	}

	if (is_feed() && get_option('re_display_rss') == null) {
		return $content;
	}

	if (get_option($where) == 'shortcode') {
		return str_replace('[reblipi]', $button, $content);
	} else {
		// if we have switched the button off
		if (get_post_meta($post->ID, 'reblipi') == null) {
			if (get_option($where) == 'beforeandafter') {
				return $button . $content . $button;
			} else if (get_option($where) == 'before') {
				return $button . $content;
			} else {
				return $content . $button;
			}
		} else {
			return $content;
		}
	}
}

// Manual output
function reblipi() {
    if (get_option('re_where') == 'manual') {
        return re_generate_button();
    } else {
        return false;
    }
}

// Remove the filter excerpts
function re_remove_filter($content) {
	if (!is_feed()) {
    	remove_action('the_content', 're_update');
	}
    return $content;
}

function re_js_admin_header() {
	// use JavaScript SACK library for Ajax
	wp_print_scripts(array('sack'));
	?>
	<script type="text/javascript">
		//<![CDATA[
		function loadAnalytics(url, row) {
    		var mysack = new sack("<?php bloginfo('wpurl'); ?>/wp-admin/admin-ajax.php");
			mysack.execute = 1;
			mysack.method = 'POST';
			mysack.setVar("action", "re_ajax_elev_lookup");
			mysack.setVar("url", url);
			mysack.setVar("id", 't' + row);
			mysack.onError = function() {
				alert('Ajax error in looking up url');
			};
			mysack.runAJAX();
  			return true;
		}
		//]]>
	</script>
<?php
}

function re_options_page() {
?>
    <div class="wrap">
    <div class="icon32" id="icon-options-general"><br/></div><h2>Ustawienia gadżetu Reblipi</h2>
    <p>Ta wtyczka doda do każdego postu gadżet <a href="http://re.blipi.pl">Reblipi</a>, który pozwoli Twoim czytelnikom na szybkie dodanie linku do postu w serwisie Blip.pl oraz pokaże ile już wystąpiło blipnięć zawierających ten link. Możesz łatwo zmieniać wygląd gadżet za pomocą id <code>reblipi</code>.
    </p>
    <form method="post" action="options.php">
    <?php
        // New way of setting the fields, for WP 2.7 and newer
        if(function_exists('settings_fields')){
            settings_fields('re-options');
        } else {
            wp_nonce_field('update-options');
            ?>
            <input type="hidden" name="action" value="update" />
            <input type="hidden" name="page_options" value="re_where,re_style,re_version,re_display_page,re_display_front" />
            <?php
        }
    ?>
        <table class="form-table">
            <tr>
	            <tr>
	                <th scope="row">
	                    Wyświetlanie gadżetu
	                </th>
	                <td>
	                    <p>
	                        <input type="checkbox" value="1" <?php if (get_option('re_display_page') == '1') echo 'checked="checked"'; ?> name="re_display_page" id="re_display_page" group="re_display"/>
	                        <label for="re_display_page">Pokazuj na podstronach</label>
	                    </p>
	                    <p>
	                        <input type="checkbox" value="1" <?php if (get_option('re_display_front') == '1') echo 'checked="checked"'; ?> name="re_display_front" id="re_display_front" group="re_display"/>
	                        <label for="re_display_front">Pokazuj na stronie głównej (home)</label>
	                    </p>
	                </td>
	            </tr>
                <th scope="row">
                    Pozycja
                </th>
                <td>
                	<p>
                		<select name="re_where">
                			<option <?php if (get_option('re_where') == 'before') echo 'selected="selected"'; ?> value="before">Przed</option>
                			<option <?php if (get_option('re_where') == 'after') echo 'selected="selected"'; ?> value="after">Po</option>
                			<option <?php if (get_option('re_where') == 'beforeandafter') echo 'selected="selected"'; ?> value="beforeandafter">Przed i po</option>
                			<option <?php if (get_option('re_where') == 'shortcode') echo 'selected="selected"'; ?> value="shortcode">W miejsce skrótu [reblipi]</option>
                			<option <?php if (get_option('re_where') == 'manual') echo 'selected="selected"'; ?> value="manual">Ręcznie</option>
                		</select>
                	</p>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="re_style">Wygląd</label></th>
                <td>
                    <input type="text" value="<?php echo htmlspecialchars(get_option('re_style')); ?>" name="re_style" id="re_style" />
                    <span class="setting-description">Dodaj styl do diva zawierającego gadżet, np. <code>float: left; margin-right: 10px;</code></span>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    Rozmiar
                </th>
                <td>
                    <p>
                        <input type="radio" value="standard" <?php if (get_option('re_version') == 'standard') echo 'checked="checked"'; ?> name="re_version" id="re_version_standard" group="re_version"/>
                        <label for="re_version_standard">Standard (50x60px)</label>
                    </p>
                    <p>
                        <input type="radio" value="mini" <?php if (get_option('re_version') == 'mini') echo 'checked="checked"'; ?> name="re_version" id="re_version_mini" group="re_version" />
                        <label for="re_version_mini">Mini (100x16px)</label>
                    </p>
                </td>
            </tr>
        </table>
        <p class="submit">
            <input type="submit" name="Submit" value="<?php _e('Save Changes') ?>" />
        </p>
    </form>
    </div>
<?php
}

// On access of the admin page, register these variables (required for WP 2.7 & newer)
function re_init(){
    if(function_exists('register_setting')){
        register_setting('re-options', 're_display_page');
        register_setting('re-options', 're_display_front');
        register_setting('re-options', 're_style');
        register_setting('re-options', 're_version');
        register_setting('re-options', 're_where');
    }
}

// Only all the admin options if the user is an admin
if(is_admin()){
    add_action('admin_menu', 're_options');
    add_action('admin_init', 're_init');
}

// Set the default options when the plugin is activated
function re_activate(){
    add_option('re_where', 'before');
    add_option('re_style', 'float: right; margin-left: 10px;');
    add_option('re_version', 'standard');
    add_option('re_display_page', '1');
    add_option('re_display_front', '1');
}

add_filter('the_content', 're_update');
add_filter('get_the_excerpt', 're_remove_filter', 9);

add_action('admin_print_scripts', 're_js_admin_header');

register_activation_hook( __FILE__, 're_activate');
