<?php
/*
	Plugin Name: WoW Realm Status
	Plugin URI: http://www.yourfirefly.com
	Description: A widget for displaying the status of any World of Warcraft US Realm
	Version: 0.0.1
	Author: Ryan Cain
	Author URI: http://www.yourfirefly.com
	
	*** SPECIAL THANKS to Ricardo González for creating Twitter for Wordpress ***
	*** This plugin uses quite a bit of the code from that plugin.            ***

	Copyright 2009  Ryan Cain  (email : onezero dot ss at gmail dot com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

define('MAGPIE_CACHE_AGE', 10);
define('MAGPIE_CACHE_ON', 1); //2.7 Cache Bug
define('MAGPIE_INPUT_ENCODING', 'UTF-8');
define('MAGPIE_OUTPUT_ENCODING', 'UTF-8');

$realmstatus_options['widget_fields']['title'] = array('label'=>'Title:', 'type'=>'text', 'default'=>'WoW Realm Status');
$realmstatus_options['widget_fields']['realm'] = array('label'=>'Realm:', 'type'=>'text', 'default'=>'');

function realm_status($realm)
{
	global $realmstatus_options;
	include_once(ABSPATH . WPINC . '/rss.php');
	
	$status = fetch_rss("http://www.worldofwarcraft.com/realmstatus/status-events-rss.html?r=" . str_replace(' ', '+', $realm));
	
	echo '<br />';
	
	if ( $realm == '' )
	{
		echo 'RSS not configured';
	}
	else
	{
		if ( empty($status->items) )
		{
			echo 'Could not retrieve realm status.';
		}
		else
		{
			foreach ( $status->items as $message )
			{
				if (strstr($message['title'], 'Up'))
				{
					$trim = 2;
					$state = 'Up';
				}
				else
				{
					$trim = 4;
					$state = 'Down';
				}
				$type = substr($message['title'], strlen($realm), strlen($message['title']) - ($trim + 7) - strlen($realm));
				$population = substr($message['description'], 12);
				
				if ($state == 'Up')
					$color = '#234303';
				else
					$color = '#660D02';
				
				$realm = str_replace('+', ' ', $realm);
				echo '<p><b>' . $realm . '</b> : ' . $type . ' : <b><span style="padding: 2px; background-color: ' . $color . '; color: white;">' . $state . '</span></b> : <b>Pop: </b>' . $population . '</p>';
			      
				$i++;
				
				if ( $i >= $num ) break;
			}
		}
	}
}

// Widget initialization
function widget_realmstatus_init() {

	if ( !function_exists('register_sidebar_widget') )
		return;
	
	$check_options = get_option('widget_realmstatus');
  if ($check_options['number']=='') {
    $check_options['number'] = 1;
    update_option('widget_realmstatus', $check_options);
  }
  
	function widget_realmstatus($args, $number = 1) {

		global $realmstatus_options;
		
		// $args is an array of strings that help widgets to conform to
		// the active theme: before_widget, before_title, after_widget,
		// and after_title are the array keys. Default tags: li and h2.
		extract($args);

		// Each widget can store its own options. We keep strings here.
		include_once(ABSPATH . WPINC . '/rss.php');
		$options = get_option('widget_realmstatus');
		
		// fill options with default values if value is not set
		$item = $options[$number];
		foreach($realmstatus_options['widget_fields'] as $key => $field) {
			if (! isset($item[$key])) {
				$item[$key] = $field['default'];
			}
		}

		// These lines generate our output.
    	echo $before_widget . $before_title . $item['title'] . $after_title;
		realm_status($item['realm']);
		echo $after_widget;
				
	}

	// This is the function that outputs the form to let the users edit
	// the widget's title. It's an optional feature that users cry for.
	function widget_realmstatus_control($number) {
	
		global $realmstatus_options;

		// Get our options and see if we're handling a form submission.
		$options = get_option('widget_realmstatus');
		if ( isset($_POST['realmstatus-submit']) ) {

			foreach($realmstatus_options['widget_fields'] as $key => $field) {
				$options[$number][$key] = $field['default'];
				$field_name = sprintf('%s_%s_%s', $realmstatus_options['prefix'], $key, $number);

				if ($field['type'] == 'text') {
					$options[$number][$key] = strip_tags(stripslashes($_POST[$field_name]));
				} elseif ($field['type'] == 'checkbox') {
					$options[$number][$key] = isset($_POST[$field_name]);
				}
			}

			update_option('widget_realmstatus', $options);
		}

		foreach($realmstatus_options['widget_fields'] as $key => $field) {
			
			$field_name = sprintf('%s_%s_%s', $realmstatus_options['prefix'], $key, $number);
			$field_checked = '';
			if ($field['type'] == 'text') {
				$field_value = htmlspecialchars($options[$number][$key], ENT_QUOTES);
			} elseif ($field['type'] == 'checkbox') {
				$field_value = 1;
				if (! empty($options[$number][$key])) {
					$field_checked = 'checked="checked"';
				}
			}
			
			printf('<p style="text-align:right;" class="realmstatus_field"><label for="%s">%s <input id="%s" name="%s" type="%s" value="%s" class="%s" %s /></label></p>',
				$field_name, __($field['label']), $field_name, $field_name, $field['type'], $field_value, $field['type'], $field_checked);
		}

		echo '<input type="hidden" id="realmstatus-submit" name="realmstatus-submit" value="1" />';
	}
	
	function widget_realmstatus_setup() {
		$options = $newoptions = get_option('widget_realmstatus');
		
		if ( isset($_POST['realmstatus-number-submit']) ) {
			$number = (int) $_POST['realmstatus-number'];
			$newoptions['number'] = $number;
		}
		
		if ( $options != $newoptions ) {
			update_option('widget_realmstatus', $newoptions);
			widget_realmstatus_register();
		}
	}
	
	
	function widget_realmstatus_page() {
		$options = $newoptions = get_option('widget_realmstatus');
	?>
		<div class="wrap">
			<form method="POST">
				<h2><?php _e('WoW Realm Status Widgets'); ?></h2>
				<p style="line-height: 30px;"><?php _e('How many WoW Realm Status widgets would you like?'); ?>
				<select id="realmstatus-number" name="realmstatus-number" value="<?php echo $options['number']; ?>">
	<?php for ( $i = 1; $i < 10; ++$i ) echo "<option value='$i' ".($options['number']==$i ? "selected='selected'" : '').">$i</option>"; ?>
				</select>
				<span class="submit"><input type="submit" name="realmstatus-number-submit" id="realmstatus-number-submit" value="<?php echo attribute_escape(__('Save')); ?>" /></span></p>
			</form>
		</div>
	<?php
	}
	
	
	function widget_realmstatus_register() {
		
		$options = get_option('widget_realmstatus');
		$dims = array('width' => 300, 'height' => 300);
		$class = array('classname' => 'widget_realmstatus');

		for ($i = 1; $i <= 9; $i++) {
			$name = sprintf(__('WoW Realm Status #%d'), $i);
			$id = "realmstatus-$i"; // Never never never translate an id
			wp_register_sidebar_widget($id, $name, $i <= $options['number'] ? 'widget_realmstatus' : /* unregister */ '', $class, $i);
			wp_register_widget_control($id, $name, $i <= $options['number'] ? 'widget_realmstatus_control' : /* unregister */ '', $dims, $i);
		}
		
		add_action('sidebar_admin_setup', 'widget_realmstatus_setup');
		add_action('sidebar_admin_page', 'widget_realmstatus_page');
	}

	widget_realmstatus_register();
}

// Run our code later in case this loads prior to any required plugins.
add_action('widgets_init', 'widget_realmstatus_init');

?>