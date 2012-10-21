<?php
/*
Plugin Name: Visual Form Builder
Description: Dynamically build forms using a simple interface. Forms include jQuery validation, a basic logic-based verification system, and entry tracking.
Author: Matthew Muro
Author URI: http://matthewmuro.com
Version: 2.3.2
*/

/*
This program is free software; you can redistribute it and/or modify 
it under the terms of the GNU General Public License as published by 
the Free Software Foundation; version 2 of the License.

This program is distributed in the hope that it will be useful, 
but WITHOUT ANY WARRANTY; without even the implied warranty of 
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the 
GNU General Public License for more details. 

You should have received a copy of the GNU General Public License 
along with this program; if not, write to the Free Software 
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA 
*/

/* Instantiate new class */
$visual_form_builder = new Visual_Form_Builder();

/* Restrict Categories class */
class Visual_Form_Builder{
	
	protected $vfb_db_version = '2.3.2';

	public $countries = array( "", "Afghanistan", "Albania", "Algeria", "Andorra", "Angola", "Antigua and Barbuda", "Argentina", "Armenia", "Australia", "Austria", "Azerbaijan", "Bahamas", "Bahrain", "Bangladesh", "Barbados", "Belarus", "Belgium", "Belize", "Benin", "Bhutan", "Bolivia", "Bosnia and Herzegovina", "Botswana", "Brazil", "Brunei", "Bulgaria", "Burkina Faso", "Burundi", "Cambodia", "Cameroon", "Canada", "Cape Verde", "Central African Republic", "Chad", "Chile", "China", "Colombi", "Comoros", "Congo (Brazzaville)", "Congo", "Costa Rica", "Cote d'Ivoire", "Croatia", "Cuba", "Cyprus", "Czech Republic", "Denmark", "Djibouti", "Dominica", "Dominican Republic", "East Timor (Timor Timur)", "Ecuador", "Egypt", "El Salvador", "Equatorial Guinea", "Eritrea", "Estonia", "Ethiopia", "Fiji", "Finland", "France", "Gabon", "Gambia, The", "Georgia", "Germany", "Ghana", "Greece", "Grenada", "Guatemala", "Guinea", "Guinea-Bissau", "Guyana", "Haiti", "Honduras", "Hungary", "Iceland", "India", "Indonesia", "Iran", "Iraq", "Ireland", "Israel", "Italy", "Jamaica", "Japan", "Jordan", "Kazakhstan", "Kenya", "Kiribati", "Korea, North", "Korea, South", "Kuwait", "Kyrgyzstan", "Laos", "Latvia", "Lebanon", "Lesotho", "Liberia", "Libya", "Liechtenstein", "Lithuania", "Luxembourg", "Macedonia", "Madagascar", "Malawi", "Malaysia", "Maldives", "Mali", "Malta", "Marshall Islands", "Mauritania", "Mauritius", "Mexico", "Micronesia", "Moldova", "Monaco", "Mongolia", "Morocco", "Mozambique", "Myanmar", "Namibia", "Nauru", "Nepa", "Netherlands", "New Zealand", "Nicaragua", "Niger", "Nigeria", "Norway", "Oman", "Pakistan", "Palau", "Panama", "Papua New Guinea", "Paraguay", "Peru", "Philippines", "Poland", "Portugal", "Qatar", "Romania", "Russia", "Rwanda", "Saint Kitts and Nevis", "Saint Lucia", "Saint Vincent", "Samoa", "San Marino", "Sao Tome and Principe", "Saudi Arabia", "Senegal", "Serbia and Montenegro", "Seychelles", "Sierra Leone", "Singapore", "Slovakia", "Slovenia", "Solomon Islands", "Somalia", "South Africa", "Spain", "Sri Lanka", "Sudan", "Suriname", "Swaziland", "Sweden", "Switzerland", "Syria", "Taiwan", "Tajikistan", "Tanzania", "Thailand", "Togo", "Tonga", "Trinidad and Tobago", "Tunisia", "Turkey", "Turkmenistan", "Tuvalu", "Uganda", "Ukraine", "United Arab Emirates", "United Kingdom", "United States of America", "Uruguay", "Uzbekistan", "Vanuatu", "Vatican City", "Venezuela", "Vietnam", "Yemen", "Zambia", "Zimbabwe" );
	
	public function __construct(){
		global $wpdb;
		
		/* Setup global database table names */
		$this->field_table_name = $wpdb->prefix . 'visual_form_builder_fields';
		$this->form_table_name = $wpdb->prefix . 'visual_form_builder_forms';
		$this->entries_table_name = $wpdb->prefix . 'visual_form_builder_entries';
		
		/* Make sure we are in the admin before proceeding. */
		if ( is_admin() ) {
			/* Build options and settings pages. */
			add_action( 'admin_menu', array( &$this, 'add_admin' ) );
			add_action( 'admin_menu', array( &$this, 'save' ) );
			
			add_action( 'wp_ajax_visual_form_builder_process_sort', array( &$this, 'process_sort_callback' ) );
			add_action( 'wp_ajax_visual_form_builder_create_field', array( &$this, 'create_field_callback' ) );
			add_action( 'wp_ajax_visual_form_builder_delete_field', array( &$this, 'delete_field_callback' ) );
			add_action( 'wp_ajax_visual_form_builder_form_settings', array( &$this, 'form_settings_callback' ) );
			
			add_action( 'load-settings_page_visual-form-builder', array( &$this, 'add_contextual_help' ) );

			/* Adds additional media button to insert form shortcode */
			add_action( 'media_buttons_context', array( &$this, 'add_media_button' ) );
			add_action( 'admin_footer', array( &$this, 'display_media_button' ) );
			
			/* Load the includes files */
			add_action( 'load-settings_page_visual-form-builder', array( &$this, 'includes' ) );
			
			/* Adds a Screen Options tab to the Entries screen */
			add_action( 'admin_init', array( &$this, 'save_screen_options' ) );
			add_filter( 'screen_settings', array( &$this, 'add_visual_form_builder_screen_options' ) );
			
			/* Adds a Settings link to the Plugins page */
			add_filter( 'plugin_action_links', array( &$this, 'visual_form_builder_plugin_action_links' ), 10, 2 );
			
			/* Add a database version to help with upgrades and run SQL install */
			if ( !get_option( 'vfb_db_version' ) ) {
				update_option( 'vfb_db_version', $this->vfb_db_version );
				$this->install_db();
			}
			
			/* If database version doesn't match, update and run SQL install */
			if ( get_option( 'vfb_db_version' ) != $this->vfb_db_version ) {
				update_option( 'vfb_db_version', $this->vfb_db_version );
				$this->install_db();
			}
			
			/* Load the jQuery and CSS we need if we're on our plugin page */
			add_action( 'load-settings_page_visual-form-builder', array( &$this, 'form_admin_scripts' ) );
			add_action( 'load-settings_page_visual-form-builder', array( &$this, 'form_admin_css' ) );
			
			/* Display update messages */
			add_action('admin_notices', array( &$this, 'admin_notices' ) );
		}
		
		/* Load i18n */
		load_plugin_textdomain( 'visual-form-builder', false , basename( dirname( __FILE__ ) ) . '/languages' );
		
		add_shortcode( 'vfb', array( &$this, 'form_code' ) );
		add_action( 'init', array( &$this, 'email' ), 10 );
		add_action( 'init', array( &$this, 'confirmation' ), 12 );
		
		/* Add jQuery and CSS to the front-end */
		add_action( 'wp_head', array( &$this, 'form_css' ) );
		add_action( 'template_redirect', array( &$this, 'form_validation' ) );
	}
	
	/**
	 * Adds extra include files
	 * 
	 * @since 1.2
	 */
	public function includes(){
		global $entries_list, $entries_detail;
		
		/* Load the Entries List class */
		require_once( trailingslashit( plugin_dir_path( __FILE__ ) ) . 'class-entries-list.php' );
		$entries_list = new VisualFormBuilder_Entries_List();
		
		/* Load the Entries Details class */
		require_once( trailingslashit( plugin_dir_path( __FILE__ ) ) . 'class-entries-detail.php' );
		$entries_detail = new VisualFormBuilder_Entries_Detail();
	}
	
	/**
	 * Adds the media button image
	 * 
	 * @since 2.3
	 */
	public function add_media_button( $context ){
		$out = '<a href="#TB_inline?width=450&inlineId=vfb_form" class="thickbox" title="Add Visual Form Builder form"><img src="'. plugins_url( 'visual-form-builder-pro/css/vfb_icon.png' ) . '" alt="Add Visual Form Builder form" /></a>';
    	
    	return $context . $out;
	}
	
	/**
	 * Display the additional media button
	 * 
	 * Used for inserting the form shortcode with desired form ID
	 *
	 * @since 2.3
	 */
	public function display_media_button(){
		global $wpdb;
		
		/* Sanitize the sql orderby */
		$order = sanitize_sql_orderby( 'form_id ASC' );
		
		/* Build our forms as an object */
		$forms = $wpdb->get_results( "SELECT form_id, form_title FROM $this->form_table_name ORDER BY $order" );
	?>
		<script type="text/javascript">
			jQuery(document).ready(function($) {
	            $( '#add_vfb_form' ).submit(function(e){
	                e.preventDefault();
	                
	                window.send_to_editor( '[vfb id=' + $( '#vfb_forms' ).val() + ']' );
	            });
            });
        </script>
		<div id="vfb_form" style="display:none;">
			<form id="add_vfb_form" class="media-upload-form type-form validate">
				<h3 class="media-title">Insert Visual Form Builder Form</h3>
				<p>Select a form below to insert into any Post or Page.</p>
				<select id="vfb_forms" name="vfb_forms">
					<?php foreach( $forms as $form ) : ?>
						<option value="<?php echo $form->form_id; ?>"><?php echo $form->form_title; ?></option>
					<?php endforeach; ?>
				</select>
				<p><input type="submit" class="button" value="Insert Form" /></p>
			</form>
		</div>
	<?php	
	}
	
	/**
	 * Display admin notices
	 * 
	 * @since 1.0
	 */
	public function admin_notices(){
		if ( isset( $_REQUEST['action'] ) ) {
			switch( $_REQUEST['action'] ) {
				case 'create_form' :
					echo __( '<div id="message" class="updated"><p>The form has been successfully created.</p></div>' , 'visual-form-builder');
				break;
				case 'update_form' :
					echo sprintf( __( '<div id="message" class="updated"><p>The <strong>%s</strong> form has been updated.</p></div>' , 'visual-form-builder'), stripslashes( $_REQUEST['form_title'] ) );
				break;
				case 'deleted' :
					echo __( '<div id="message" class="updated"><p>The form has been successfully deleted.</p></div>' , 'visual-form-builder');
				break;
				case 'copy_form' :
					echo __( '<div id="message" class="updated"><p>The form has been successfully duplicated.</p></div>' , 'visual-form-builder');
				break;
			}
			
		}
	}
	
	/**
	 * Register contextual help. This is for the Help tab dropdown
	 * 
	 * @since 1.0
	 */
	public function add_contextual_help(){
		$screen = get_current_screen();

		$screen->add_help_tab( array(
			'id' => 'vfb-help-tab-getting-started',
			'title' => 'Getting Started',
			'content' => '<ul>
						<li>Click on the + tab, give your form a name and click Create Form.</li>
						<li>Select form fields from the box on the left and click a field to add it to your form.</li>
						<li>Edit the information for each form field by clicking on the down arrow.</li>
						<li>Drag and drop the elements to put them in order.</li>
						<li>Click Save Form to save your changes.</li>
					</ul>'
		) );
		
		$screen->add_help_tab( array(
			'id' => 'vfb-help-tab-item-config',
			'title' => 'Form Item Configuration',
			'content' => "<ul>
						<li><em>Name</em> will change the display name of your form input.</li>
						<li><em>Description</em> will be displayed below the associated input.</li>
						<li><em>Validation</em> allows you to select from several of jQuery's Form Validation methods for text inputs. For more about the types of validation, read the <em>Validation</em> section below.</li>
						<li><em>Required</em> is either Yes or No. Selecting 'Yes' will make the associated input a required field and the form will not submit until the user fills this field out correctly.</li>
						<li><em>Options</em> will only be active for Radio and Checkboxes.  This field contols how many options are available for the associated input.</li>
						<li><em>Size</em> controls the width of Text, Textarea, Select, and Date Picker input fields.  The default is set to Medium but if you need a longer text input, select Large.</li>
						<li><em>CSS Classes</em> allow you to add custom CSS to a field.  This option allows you to fine tune the look of the form.</li>
					</ul>"
		) );

		$screen->add_help_tab( array(
			'id' => 'vfb-help-tab-validation',
			'title' => 'Validation',
			'content' => "<p>Visual Form Builder uses the <a href='http://docs.jquery.com/Plugins/Validation/Validator'>jQuery Form Validation plugin</a> to perform clientside form validation.</p>
					<ul>
						
						<li><em>Email</em>: makes the element require a valid email.</li>
						<li><em>URL</em>: makes the element require a valid url.</li>
						<li><em>Date</em>: makes the element require a date. <a href='http://docs.jquery.com/Plugins/Validation/Methods/date'>Refer to documentation for various accepted formats</a>.
						<li><em>Number</em>: makes the element require a decimal number.</li>
						<li><em>Digits</em>: makes the element require digits only.</li>
						<li><em>Phone</em>: makes the element require a US or International phone number. Most formats are accepted.</li>
						<li><em>Time</em>: choose either 12- or 24-hour time format (NOTE: only available with the Time field).</li>
					</ul>"
		) );

		$screen->add_help_tab( array(
			'id' => 'vfb-help-tab-confirmation',
			'title' => 'Confirmation',
			'content' => "<p>Each form allows you to customize the confirmation by selecing either a Text Message, a WordPress Page, or to Redirect to a URL.</p>
					<ul>
						<li><em>Text</em> allows you to enter a custom formatted message that will be displayed on the page after your form is submitted. HTML is allowed here.</li>
						<li><em>Page</em> displays a dropdown of all WordPress Pages you have created. Select one to redirect the user to that page after your form is submitted.</li>
						<li><em>Redirect</em> will only accept URLs and can be used to send the user to a different site completely, if you choose.</li>
					</ul>"
		) );

		$screen->add_help_tab( array(
			'id' => 'vfb-help-tab-notification',
			'title' => 'Notification',
			'content' => "<p>Send a customized notification email to the user when the form has been successfully submitted.</p>
					<ul>
						<li><em>Sender Name</em>: the name that will be displayed on the email.</li>
						<li><em>Sender Email</em>: the email that will be used as the Reply To email.</li>
						<li><em>Send To</em>: the email where the notification will be sent. This must be a required text field with email validation.</li>
						<li><em>Subject</em>: the subject of the email.</li>
						<li><em>Message</em>: additional text that can be displayed in the body of the email. HTML tags are allowed.</li>
						<li><em>Include a Copy of the User's Entry</em>: appends a copy of the user's submitted entry to the notification email.</li>
					</ul>"
		) );

		$screen->add_help_tab( array(
			'id' => 'vfb-help-tab-tips',
			'title' => 'Tips',
			'content' => "<ul>
						<li>Fieldsets, a way to group form fields, are an essential piece of this plugin's HTML. As such, at least one fieldset is required and must be first in the order. Subsequent fieldsets may be placed wherever you would like to start your next grouping of fields.</li>
						<li>Security verification is automatically included on very form. It's a simple logic question and should keep out most, if not all, spam bots.</li>
						<li>There is a hidden spam field, known as a honey pot, that should also help deter potential abusers of your form.</li>
						<li>Nesting is allowed underneath fieldsets and sections.  Sections can be nested underneath fieldsets.  Nesting is not required, however, it does make reorganizing easier.</li>
					</ul>"
		) );
	}
	
	/**
	 * Adds the Screen Options tab to the Entries screen
	 * 
	 * @since 1.2
	 */
	public function add_visual_form_builder_screen_options($current){
		global $current_screen;
		
		$options = get_option( 'visual-form-builder-screen-options' );

		if ( $current_screen->id == 'settings_page_visual-form-builder' && isset( $_REQUEST['view'] ) && in_array( $_REQUEST['view'], array( 'entries' ) ) ){
			$current = '<h5>Show on screen</h5>
					<input type="text" value="' . $options['per_page'] . '" maxlength="3" id="visual-form-builder-per-page" name="visual-form-builder-screen-options[per_page]" class="screen-per-page"> <label for="visual-form-builder-per-page">Entries</label>
					<input type="submit" value="Apply" class="button" id="visual-form-builder-screen-options-apply" name="visual-form-builder-screen-options-apply">';
		}
		
		return $current;
	}
	
	/**
	 * Saves the Screen Options
	 * 
	 * @since 1.2
	 */
	public function save_screen_options(){
		$options = get_option( 'visual-form-builder-screen-options' );
		
		/* Default is 20 per page */
		$defaults = array(
			'per_page' => 20
		);
		
		/* If the option doesn't exist, add it with defaults */
		if ( !$options )
			update_option( 'visual-form-builder-screen-options', $defaults );
		
		/* If the user has saved the Screen Options, update */
		if ( isset( $_REQUEST['visual-form-builder-screen-options-apply'] ) && in_array( $_REQUEST['visual-form-builder-screen-options-apply'], array( 'Apply', 'apply' ) ) ) {
			$per_page = absint( $_REQUEST['visual-form-builder-screen-options']['per_page'] );
			$updated_options = array(
				'per_page' => $per_page
			);
			update_option( 'visual-form-builder-screen-options', $updated_options );
		}
	}
	
	/**
	 * Install database tables
	 * 
	 * @since 1.0 
	 */
	static function install_db() {
		global $wpdb;
		
		$field_table_name = $wpdb->prefix . 'visual_form_builder_fields';
		$form_table_name = $wpdb->prefix . 'visual_form_builder_forms';
		$entries_table_name = $wpdb->prefix . 'visual_form_builder_entries';
		
		/* Explicitly set the character set and collation when creating the tables */
		$charset = ( defined( 'DB_CHARSET' && '' !== DB_CHARSET ) ) ? DB_CHARSET : 'utf8';
		$collate = ( defined( 'DB_COLLATE' && '' !== DB_COLLATE ) ) ? DB_COLLATE : 'utf8_general_ci';
		
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' ); 
				
		$field_sql = "CREATE TABLE $field_table_name (
				field_id BIGINT(20) NOT NULL AUTO_INCREMENT,
				form_id BIGINT(20) NOT NULL,
				field_key VARCHAR(255) NOT NULL,
				field_type VARCHAR(25) NOT NULL,
				field_options TEXT,
				field_description TEXT,
				field_name TEXT NOT NULL,
				field_sequence BIGINT(20) DEFAULT '0',
				field_parent BIGINT(20) DEFAULT '0',
				field_validation VARCHAR(25),
				field_required VARCHAR(25),
				field_size VARCHAR(25) DEFAULT 'medium',
				field_css VARCHAR(255),
				field_layout VARCHAR(255),
				field_default TEXT,
				UNIQUE KEY  (field_id)
			) DEFAULT CHARACTER SET $charset COLLATE $collate;";

		$form_sql = "CREATE TABLE $form_table_name (
				form_id BIGINT(20) NOT NULL AUTO_INCREMENT,
				form_key TINYTEXT NOT NULL,
				form_title TEXT NOT NULL,
				form_email_subject TEXT,
				form_email_to TEXT,
				form_email_from VARCHAR(255),
				form_email_from_name VARCHAR(255),
				form_email_from_override VARCHAR(255),
				form_email_from_name_override VARCHAR(255),
				form_success_type VARCHAR(25) DEFAULT 'text',
				form_success_message TEXT,
				form_notification_setting VARCHAR(25),
				form_notification_email_name VARCHAR(255),
				form_notification_email_from VARCHAR(255),
				form_notification_email VARCHAR(25),
				form_notification_subject VARCHAR(255),
				form_notification_message TEXT,
				form_notification_entry VARCHAR(25),
				form_label_alignment VARCHAR(25),
				UNIQUE KEY  (form_id)
			) DEFAULT CHARACTER SET $charset COLLATE $collate;";
		
		$entries_sql = "CREATE TABLE $entries_table_name (
				entries_id BIGINT(20) NOT NULL AUTO_INCREMENT,
				form_id BIGINT(20) NOT NULL,
				data TEXT NOT NULL,
				subject TEXT,
				sender_name VARCHAR(255),
				sender_email VARCHAR(25),
				emails_to TEXT,
				date_submitted DATETIME,
				ip_address VARCHAR(25),
				UNIQUE KEY  (entries_id)
			) DEFAULT CHARACTER SET $charset COLLATE $collate;";
		
		/* Create or Update database tables */
		dbDelta( $field_sql );
		dbDelta( $form_sql );
		dbDelta( $entries_sql );
	}

	/**
	 * Queue plugin CSS for admin styles
	 * 
	 * @since 1.0
	 */
	public function form_admin_css() {
		wp_enqueue_style( 'visual-form-builder-style', plugins_url( 'visual-form-builder' ) . '/css/visual-form-builder-admin.css' );
		wp_enqueue_style( 'visual-form-builder-main', plugins_url( 'visual-form-builder' ) . '/css/nav-menu.css' );
	}
	
	/**
	 * Queue plugin scripts for sorting form fields
	 * 
	 * @since 1.0 
	 */
	public function form_admin_scripts() {
		wp_enqueue_script( 'jquery-ui-sortable' );
		wp_enqueue_script( 'jquery-form-validation', 'http://ajax.aspnetcdn.com/ajax/jquery.validate/1.9/jquery.validate.min.js', array( 'jquery' ), '', true );
		wp_enqueue_script( 'form-elements-add', plugins_url( 'visual-form-builder' ) . '/js/visual-form-builder.js' , array( 'jquery', 'jquery-form-validation' ), '', true );
		wp_enqueue_script( 'nested-sortable', plugins_url( 'visual-form-builder' ) . '/js/jquery.ui.nestedSortable.js' , array( 'jquery', 'jquery-ui-sortable' ), '', true );
	}
	
	/**
	 * Queue form validation scripts
	 * 
	 * @since 1.0 
	 */
	public function form_validation() {
		wp_enqueue_script( 'jquery-form-validation', 'http://ajax.aspnetcdn.com/ajax/jquery.validate/1.9/jquery.validate.min.js', array( 'jquery' ), '', true );
		wp_enqueue_script( 'jquery-ui-core ', 'https://ajax.googleapis.com/ajax/libs/jqueryui/1.8.18/jquery-ui.min.js', array( 'jquery' ), '', true );
		wp_enqueue_script( 'visual-form-builder-validation', plugins_url( 'visual-form-builder' ) . '/js/visual-form-builder-validate.js' , array( 'jquery', 'jquery-form-validation' ), '', true );
		wp_enqueue_script( 'visual-form-builder-quicktags', plugins_url( 'visual-form-builder' ) . '/js/js_quicktags.js' );
		wp_enqueue_script( 'visual-form-builder-metadata', plugins_url( 'visual-form-builder' ) . '/js/jquery.metadata.js' , array( 'jquery', 'jquery-form-validation' ), '', true );
	}
	
	/**
	 * Add form CSS to wp_head
	 * 
	 * @since 1.0 
	 */
	public function form_css() {
		echo apply_filters( 'visual-form-builder-css', '<link rel="stylesheet" href="' . plugins_url( 'css/visual-form-builder.css', __FILE__ ) . '" type="text/css" />' );
		echo apply_filters( 'vfb-date-picker-css', '<link media="all" type="text/css" href="http://ajax.googleapis.com/ajax/libs/jqueryui/1.8.6/themes/base/jquery-ui.css" rel="stylesheet" />' );
	}
	
	/**
	 * Add Settings link to Plugins page
	 * 
	 * @since 1.8 
	 * @return $links array Links to add to plugin name
	 */
	public function visual_form_builder_plugin_action_links( $links, $file ) {
		if ( $file == plugin_basename( __FILE__ ) )
			$links[] = '<a href="options-general.php?page=visual-form-builder">' . __( 'Settings' , 'visual-form-builder') . '</a>';
	
		return $links;
	}
	
	/**
	 * Add options page to Settings menu
	 * 
	 * 
	 * @since 1.0
	 * @uses add_options_page() Creates a menu item under the Settings menu.
	 */
	public function add_admin() {  
		add_options_page( __( 'Visual Form Builder', 'visual-form-builder' ), __( 'Visual Form Builder', 'visual-form-builder' ), 'create_users', 'visual-form-builder', array( &$this, 'admin' ) );
	}
	
	
	/**
	 * Actions to save, update, and delete forms/form fields
	 * 
	 * 
	 * @since 1.0
	 */
	public function save() {
		global $wpdb;
				
		if ( isset( $_REQUEST['page'] ) && $_REQUEST['page'] == 'visual-form-builder' && isset( $_REQUEST['action'] ) ) {
			
			switch ( $_REQUEST['action'] ) {
				case 'create_form' :
					
					$form_id = absint( $_REQUEST['form_id'] );
					$form_key = sanitize_title( $_REQUEST['form_title'] );
					$form_title = esc_html( $_REQUEST['form_title'] );
					
					check_admin_referer( 'create_form-' . $form_id );
					
					$newdata = array(
						'form_key' => $form_key,
						'form_title' => $form_title
					);
					
					/* Set message to display */
					$this->message = sprintf( __( '<div id="message" class="updated"><p>The <strong>%s</strong> form has been created.</p></div>' , 'visual-form-builder'), $form_title );
					
					/* Create the form */
					$wpdb->insert( $this->form_table_name, $newdata );
					
					/* Get form ID to add our first field */
					$new_form_selected = $wpdb->insert_id;
					
					/* Setup the initial fieldset */
					$initial_fieldset = array(
						'form_id' => $wpdb->insert_id,
						'field_key' => 'fieldset',
						'field_type' => 'fieldset',
						'field_name' => 'Fieldset',
						'field_sequence' => 0
					);
					
					/* Add the first fieldset to get things started */ 
					$wpdb->insert( $this->field_table_name, $initial_fieldset );
					
					$verification_fieldset = array(
						'form_id' => $new_form_selected,
						'field_key' => 'verification',
						'field_type' => 'verification',
						'field_name' => 'Verification',
						'field_description' => '(This is for preventing spam)',
						'field_sequence' => 1
					);
					
					/* Insert the submit field */ 
					$wpdb->insert( $this->field_table_name, $verification_fieldset );
					
					$verify_fieldset_parent_id = $wpdb->insert_id;
					
					// TODO: Update this to default to asking for a two digit number, but give the option of swapping in a recaptcha.
					$secret = array(
						'form_id' => $new_form_selected,
						'field_key' => 'secret',
						'field_type' => 'recaptcha',
						'field_name' => 'reCAPTCHA',
						'field_size' => 'medium',
						'field_required' => 'yes',
						'field_parent' => $verify_fieldset_parent_id,
						'field_sequence' => 2
					);
					
					/* Insert the submit field */ 
					$wpdb->insert( $this->field_table_name, $secret );
					
					/* Make the submit last in the sequence */
					$submit = array(
						'form_id' => $new_form_selected,
						'field_key' => 'submit',
						'field_type' => 'submit',
						'field_name' => 'Submit',
						'field_parent' => $verify_fieldset_parent_id,
						'field_sequence' => 3
					);
					
					/* Insert the submit field */ 
					$wpdb->insert( $this->field_table_name, $submit );
					
					/* Redirect to keep the URL clean (use AJAX in the future?) */
					wp_redirect( 'options-general.php?page=visual-form-builder&form=' . $new_form_selected );
					exit();
					
				break;
				
				case 'update_form' :

					$form_id = absint( $_REQUEST['form_id'] );
					$form_key = sanitize_title( $_REQUEST['form_title'], $form_id );
					$form_title = esc_html( $_REQUEST['form_title'] );
					$form_subject = esc_html( $_REQUEST['form_email_subject'] );
					$form_to = serialize( array_map( 'esc_html', $_REQUEST['form_email_to'] ) );
					$form_from = esc_html( $_REQUEST['form_email_from'] );
					$form_from_name = esc_html( $_REQUEST['form_email_from_name'] );
					$form_from_override = esc_html( $_REQUEST['form_email_from_override'] );
					$form_from_name_override = esc_html( $_REQUEST['form_email_from_name_override'] );
					$form_success_type = esc_html( $_REQUEST['form_success_type'] );
					$form_notification_setting = esc_html( $_REQUEST['form_notification_setting'] );
					$form_notification_email_name = esc_html( $_REQUEST['form_notification_email_name'] );
					$form_notification_email_from = esc_html( $_REQUEST['form_notification_email_from'] );
					$form_notification_email = esc_html( $_REQUEST['form_notification_email'] );
					$form_notification_subject = esc_html( $_REQUEST['form_notification_subject'] );
					$form_notification_message = wp_richedit_pre( $_REQUEST['form_notification_message'] );
					$form_notification_entry = esc_html( $_REQUEST['form_notification_entry'] );
					$form_label_alignment = esc_html( $_REQUEST['form_label_alignment'] );
					
					/* Add confirmation based on which type was selected */
					switch ( $form_success_type ) {
						case 'text' :
							$form_success_message = wp_richedit_pre( $_REQUEST['form_success_message_text'] );
						break;
						case 'page' :
							$form_success_message = esc_html( $_REQUEST['form_success_message_page'] );
						break;
						case 'redirect' :
							$form_success_message = esc_html( $_REQUEST['form_success_message_redirect'] );
						break;
					}
					
					check_admin_referer( 'update_form-' . $form_id );
					
					$newdata = array(
						'form_key' => $form_key,
						'form_title' => $form_title,
						'form_email_subject' => $form_subject,
						'form_email_to' => $form_to,
						'form_email_from' => $form_from,
						'form_email_from_name' => $form_from_name,
						'form_email_from_override' => $form_from_override,
						'form_email_from_name_override' => $form_from_name_override,
						'form_success_type' => $form_success_type,
						'form_success_message' => $form_success_message,
						'form_notification_setting' => $form_notification_setting,
						'form_notification_email_name' => $form_notification_email_name,
						'form_notification_email_from' => $form_notification_email_from,
						'form_notification_email' => $form_notification_email,
						'form_notification_subject' => $form_notification_subject,
						'form_notification_message' => $form_notification_message,
						'form_notification_entry' => $form_notification_entry,
						'form_label_alignment' => $form_label_alignment
					);
					
					$where = array(
						'form_id' => $form_id
					);
					
					/* Update form details */
					$wpdb->update( $this->form_table_name, $newdata, $where );
					
					/* Initialize field sequence */
					$field_sequence = 0;
					
					/* Loop through each field and update all at once */
					if ( !empty( $_REQUEST['field_id'] ) ) {
						foreach ( $_REQUEST['field_id'] as $id ) {
							$field_name = ( isset( $_REQUEST['field_name-' . $id] ) ) ? esc_html( $_REQUEST['field_name-' . $id] ) : '';
							$field_key = sanitize_title( $field_name, $id );
							$field_desc = ( isset( $_REQUEST['field_description-' . $id] ) ) ? esc_html( $_REQUEST['field_description-' . $id] ) : '';
							$field_options = ( isset( $_REQUEST['field_options-' . $id] ) ) ? serialize( array_map( 'esc_html', $_REQUEST['field_options-' . $id] ) ) : '';
							$field_validation = ( isset( $_REQUEST['field_validation-' . $id] ) ) ? esc_html( $_REQUEST['field_validation-' . $id] ) : '';
							$field_required = ( isset( $_REQUEST['field_required-' . $id] ) ) ? esc_html( $_REQUEST['field_required-' . $id] ) : '';
							$field_size = ( isset( $_REQUEST['field_size-' . $id] ) ) ? esc_html( $_REQUEST['field_size-' . $id] ) : '';
							$field_css = ( isset( $_REQUEST['field_css-' . $id] ) ) ? esc_html( $_REQUEST['field_css-' . $id] ) : '';
							$field_layout = ( isset( $_REQUEST['field_layout-' . $id] ) ) ? esc_html( $_REQUEST['field_layout-' . $id] ) : '';
							$field_default = ( isset( $_REQUEST['field_default-' . $id] ) ) ? esc_html( $_REQUEST['field_default-' . $id] ) : '';
							
							$field_data = array(
								'field_key' => $field_key,
								'field_name' => $field_name,
								'field_description' => $field_desc,
								'field_options' => $field_options,
								'field_validation' => $field_validation,
								'field_required' => $field_required,
								'field_size' => $field_size,
								'field_css' => $field_css,
								'field_layout' => $field_layout,
								'field_sequence' => $field_sequence,
								'field_default' => $field_default
							);
							
							$where = array(
								'form_id' => $_REQUEST['form_id'],
								'field_id' => $id
							);
							
							/* Update all fields */
							$wpdb->update( $this->field_table_name, $field_data, $where );
							
							$field_sequence++;
						}
						
						/* Check if a submit field type exists for backwards compatibility upgrades */
						$is_verification = $wpdb->get_var( "SELECT field_id FROM $this->field_table_name WHERE field_type = 'verification' AND form_id = $form_id" );
						$is_secret = $wpdb->get_var( "SELECT field_id FROM $this->field_table_name WHERE field_type = 'secret' AND form_id = $form_id" );
						$is_recaptcha = $wpdb->get_var( "SELECT field_id FROM $this->field_table_name WHERE field_type = 'recaptcha' AND form_id = $form_id" );
						$is_submit = $wpdb->get_var( "SELECT field_id FROM $this->field_table_name WHERE field_type = 'submit' AND form_id = $form_id" );
						
						/* Decrement sequence */
						$field_sequence--;
						
						/* If this form doesn't have a verification field, add one */
						if ( $is_verification == NULL ) {
							/* Adjust the sequence */
							$verification_fieldset = array(
								'form_id' => $form_id,
								'field_key' => 'verification',
								'field_type' => 'verification',
								'field_name' => 'Verification',
								'field_sequence' => $field_sequence
							);
							
							/* Insert the verification fieldset */ 
							$wpdb->insert( $this->field_table_name, $verification_fieldset );
							
							$verification_id = $wpdb->insert_id;
						}
						
						/* If the verification field was inserted, use that ID as a parent otherwise set no parent */
						$verify_fieldset_parent_id = ( $verification_id !== false ) ? $verification_id : 0;
						
						/* If this form doesn't have a secret field, add one */
						if ( $is_secret == NULL && $is_recaptcha == NULL ) {
							
						  //Default anti-spam question is for any two digit number.
							/* Adjust the sequence */
							$secret = array(
								'form_id' => $form_id,
								'field_key' => 'secret',
								'field_type' => 'secret',
								'field_name' => 'Please enter any two digits with no spaces (Example: 12)',
								'field_size' => 'medium',
								'field_required' => 'yes',
								'field_parent' => $verify_fieldset_parent_id,
								'field_sequence' => ++$field_sequence
							);
							
							/* Insert the submit field */ 
							$wpdb->insert( $this->field_table_name, $secret );
						}
						
						/* If this form doesn't have a submit field, add one */
						if ( $is_submit == NULL ) {
							
							/* Make the submit last in the sequence */
							$submit = array(
								'form_id' => $form_id,
								'field_key' => 'submit',
								'field_type' => 'submit',
								'field_name' => 'Submit',
								'field_parent' => $verify_fieldset_parent_id,
								'field_sequence' => ++$field_sequence
							);
							
							/* Insert the submit field */ 
							$wpdb->insert( $this->field_table_name, $submit );
						}
						else {
							/* Only update the Submit's parent ID if the Verification field is new */
							$data = ( $is_verification == NULL ) ? array( 'field_parent' => $verify_fieldset_parent_id, 'field_sequence' => ++$field_sequence ) : array( 'field_sequence' => $field_sequence	);
							$where = array(
								'form_id' => $form_id,
								'field_id' => $is_submit
							);
										
							/* Update the submit field */
							$wpdb->update( $this->field_table_name, $data, $where );
						}
					}
					
				break;
				
				case 'delete_form' :
					$id = absint( $_REQUEST['form'] );
					
					check_admin_referer( 'delete-form-' . $id );
					
					/* Delete form and all fields */
					$wpdb->query( $wpdb->prepare( "DELETE FROM $this->form_table_name WHERE form_id = %d", $id ) );
					$wpdb->query( $wpdb->prepare( "DELETE FROM $this->field_table_name WHERE form_id = %d", $id ) );
					
					/* Redirect to keep the URL clean (use AJAX in the future?) */
					wp_redirect( add_query_arg( 'action', 'deleted', 'options-general.php?page=visual-form-builder' ) );
					exit();
					
				break;
				
				case 'copy_form' :
					$id = absint( $_REQUEST['form'] );
					
					check_admin_referer( 'copy-form-' . $id );
					
					/* Get all fields and data for the request form */
					$fields_query = "SELECT * FROM $this->field_table_name WHERE form_id = $id";
					$forms_query = "SELECT * FROM $this->form_table_name WHERE form_id = $id";
					$emails = "SELECT form_email_from_override, form_notification_email FROM $this->form_table_name WHERE form_id = $id";
					
					$fields = $wpdb->get_results( $fields_query );
					$forms = $wpdb->get_results( $forms_query );
					$override = $wpdb->get_var( $emails );
					$notify = $wpdb->get_var( $emails, 1 );
					
					/* Copy this form and force the initial title to denote a copy */
					foreach ( $forms as $form ) {
						$data = array(
							'form_key' => sanitize_title( $form->form_key . ' copy' ),
							'form_title' => $form->form_title . ' Copy',
							'form_email_subject' => $form->form_email_subject,
							'form_email_to' => $form->form_email_to,
							'form_email_from' => $form->form_email_from,
							'form_email_from_name' => $form->form_email_from_name,
							'form_email_from_override' => $form->form_email_from_override,
							'form_email_from_name_override' => $form->form_email_from_name_override,
							'form_success_type' => $form->form_success_type,
							'form_success_message' => $form->form_success_message,
							'form_notification_setting' => $form->form_notification_setting,
							'form_notification_email_name' => $form->form_notification_email_name,
							'form_notification_email_from' => $form->form_notification_email_from,
							'form_notification_email' => $form->form_notification_email,
							'form_notification_subject' => $form->form_notification_subject,
							'form_notification_message' => $form->form_notification_message,
							'form_notification_entry' => $form->form_notification_entry,
							'form_label_alignment' => $form->form_label_alignment
						);
						
						$wpdb->insert( $this->form_table_name, $data );
					}
					
					/* Get form ID to add our first field */
					$new_form_selected = $wpdb->insert_id;
					
					/* Copy each field and data */
					foreach ( $fields as $field ) {
						$data = array(
							'form_id' => $new_form_selected,
							'field_key' => $field->field_key,
							'field_type' => $field->field_type,
							'field_name' => $field->field_name,
							'field_description' => $field->field_description,
							'field_options' => $field->field_options,
							'field_sequence' => $field->field_sequence,
							'field_validation' => $field->field_validation,
							'field_required' => $field->field_required,
							'field_size' => $field->field_size,
							'field_css' => $field->field_css,
							'field_layout' => $field->field_layout,
							'field_parent' => $field->field_parent
						);
						
						$wpdb->insert( $this->field_table_name, $data );

						/* If a parent field, save the old ID and the new ID to update new parent ID */
						if ( in_array( $field->field_type, array( 'fieldset', 'section', 'verification' ) ) )
							$parents[ $field->field_id ] = $wpdb->insert_id;
						
						if ( $override == $field->field_id )
							$wpdb->update( $this->form_table_name, array( 'form_email_from_override' => $wpdb->insert_id ), array( 'form_id' => $new_form_selected ) );
						
						if ( $notify == $field->field_id )
							$wpdb->update( $this->form_table_name, array( 'form_notification_email' => $wpdb->insert_id ), array( 'form_id' => $new_form_selected ) );
					}
					
					/* Loop through our parents and update them to their new IDs */
					foreach ( $parents as $k => $v ) {
						$wpdb->update( $this->field_table_name, array( 'field_parent' => $v ), array( 'form_id' => $new_form_selected, 'field_parent' => $k ) );	
					}
					
				break;
			}
		}
	}	
	
	/**
	 * The jQuery field sorting callback
	 * 
	 * @since 1.0
	 */
	public function process_sort_callback() {
		global $wpdb;
		
		$data = array();

		foreach ( $_REQUEST['order'] as $k ) {
			if ( 'root' !== $k['item_id'] ) {
				$data[] = array(
					'field_id' => $k['item_id'],
					'parent' => $k['parent_id']
					);
			}
		}

		foreach ( $data as $k => $v ) {
			/* Update each field with it's new sequence and parent ID */
			$wpdb->update( $this->field_table_name, array( 'field_sequence' => $k, 'field_parent' => $v['parent'] ), array( 'field_id' => $v['field_id'] ) );
		}

		die(1);
	}
	
	/**
	 * The jQuery create field callback
	 * 
	 * @since 1.9
	 */
	public function create_field_callback() {
		global $wpdb;
		
		$data = array();
		$field_options = '';
		
		foreach ( $_REQUEST['data'] as $k ) {
			$data[ $k['name'] ] = $k['value'];
		}
		
		if ( isset( $_REQUEST['page'] ) && $_REQUEST['page'] == 'settings_page_visual-form-builder' && isset( $_REQUEST['action'] ) && $_REQUEST['action'] == 'visual_form_builder_create_field' ) {
			$form_id = absint( $data['form_id'] );
			$field_key = sanitize_title( $_REQUEST['field_type'] );
			$field_name = esc_html( $_REQUEST['field_type'] );
			$field_type = strtolower( sanitize_title( $_REQUEST['field_type'] ) );
			
			/* Set defaults for validation */
			switch ( $field_type ) {
				case 'email' :
				case 'url' :
				case 'phone' :
					$field_validation = $field_type;
				break;
				case 'currency' :
					$field_validation = 'number';
				break;
				case 'number' :
					$field_validation = 'digits';
				break;
				case 'time' :
					$field_validation = 'time-12';
				break;
				case 'file-upload' :
					$field_options = serialize( array( 'png|jpe?g|gif' ) );
				break;
			}
			
			check_ajax_referer( 'create-field-' . $data['form_id'], 'nonce' );
			
			/* Get the last row's sequence that isn't a Verification */
			$sequence_last_row = $wpdb->get_row( "SELECT field_sequence FROM $this->field_table_name WHERE form_id = $form_id AND field_type = 'verification' ORDER BY field_sequence DESC LIMIT 1" );
			
			/* If it's not the first for this form, add 1 */
			$field_sequence = ( !empty( $sequence_last_row ) ) ? $sequence_last_row->field_sequence : 0;

			$newdata = array(
				'form_id' => absint( $data['form_id'] ),
				'field_key' => $field_key,
				'field_name' => $field_name,
				'field_type' => $field_type,
				'field_options' => $field_options,
				'field_sequence' => $field_sequence,
				'field_validation' => $field_validation
			);
			
			/* Create the field */
			$wpdb->insert( $this->field_table_name, $newdata );
			$insert_id = $wpdb->insert_id;
			$update_these = array( 'verification', 'secret', 'recaptcha', 'submit' );
			
			foreach ( $update_these as $update ) {
				$where = array(
					'form_id' => absint( $data['form_id'] ),
					'field_type' => $update
				);
				
				$wpdb->update( $this->field_table_name, array( 'field_sequence' => $field_sequence + 1 ), $where );
				$field_sequence++;
			}
			
			echo $this->field_output( $data['form_id'], $insert_id );
		}
		
		die(1);
	}
	
	/**
	 * The jQuery delete field callback
	 * 
	 * @since 1.9
	 */
	public function delete_field_callback() {
		global $wpdb;

		if ( isset( $_REQUEST['page'] ) && $_REQUEST['page'] == 'settings_page_visual-form-builder' && isset( $_REQUEST['action'] ) && $_REQUEST['action'] == 'visual_form_builder_delete_field' ) {
			$form_id = absint( $_REQUEST['form'] );
			$field_id = absint( $_REQUEST['field'] );
			
			check_ajax_referer( 'delete-field-' . $form_id, 'nonce' );
			
			if ( isset( $_REQUEST['child_ids'] ) ) {
				foreach ( $_REQUEST['child_ids'] as $children ) {
					$parent = absint( $_REQUEST['parent_id'] );
					
					/* Update each child item with the new parent ID */
					$wpdb->update( $this->field_table_name, array( 'field_parent' => $parent ), array( 'field_id' => $children ) );
				}
			}
			
			/* Delete the field */
			$wpdb->query( $wpdb->prepare( "DELETE FROM $this->field_table_name WHERE field_id = %d", $field_id ) );
		}
		
		die(1);
	}
	
	/**
	 * The jQuery form settings callback
	 * 
	 * @since 2.2
	 */
	public function form_settings_callback() {
		global $current_user;
		get_currentuserinfo();
		
		if ( isset( $_REQUEST['action'] ) && $_REQUEST['action'] == 'visual_form_builder_form_settings' ) {
			$form_id = absint( $_REQUEST['form'] );
			$status = isset( $_REQUEST['status'] ) ? $_REQUEST['status'] : 'opened';
			$accordion = isset( $_REQUEST['accordion'] ) ? $_REQUEST['accordion'] : 'general-settings';
			$user_id = $current_user->ID;
			
			$form_settings = get_user_meta( $user_id, 'vfb-form-settings', true );
			
			$array = array(
				'form_setting_tab' => $status,
				'setting_accordion' => $accordion
			);
			
			/* Set defaults if meta key doesn't exist */	
			if ( !$form_settings || $form_settings == '' ) {
				$meta_value[ $form_id ] = $array;
				
				update_user_meta( $user_id, 'vfb-form-settings', $meta_value );
			}
			else {
				$form_settings[ $form_id ] = $array;
				
				update_user_meta( $user_id, 'vfb-form-settings', $form_settings );
			}
		}
		
		die(1);
	}

	/**
	 * Build field output in admin
	 * 
	 * @since 1.9
	 */
	public function field_output( $form_nav_selected_id, $field_id = NULL ) {
		global $wpdb;
		
		$field_where = ( isset( $field_id ) && !is_null( $field_id ) ) ? "AND field_id = $field_id" : '';
		/* Display all fields for the selected form */
		$query_fields = "SELECT * FROM $this->field_table_name WHERE form_id = $form_nav_selected_id $field_where ORDER BY field_sequence ASC";
		$fields = $wpdb->get_results( $query_fields );
		
		$depth = 1;
		$parent = $last = 0;
		
		/* Loop through each field and display */
		foreach ( $fields as $field ) :		
			/* If we are at the root level */
			if ( !$field->field_parent && $depth > 1 ) {
				/* If we've been down a level, close out the list */
				while ( $depth > 1 ) {
					echo '</li>
						</ul>';
					$depth--;
				}
				
				/* Close out the root item */
				echo '</li>';
			}
			/* first item of <ul>, so move down a level */
			elseif ( $field->field_parent && $field->field_parent == $last ) {
				echo '<ul class="parent">';
				$depth++;				
			}
			/* Close up a <ul> and move up a level */
			elseif ( $field->field_parent && $field->field_parent != $parent ) {
				echo '</li>
					</ul>
				</li>';
				$depth--;
			}
			/* Same level so close list item */
			elseif ( $field->field_parent && $field->field_parent == $parent )
				echo '</li>';
			
			/* Store item ID and parent ID to test for nesting */										
			$last = $field->field_id;
			$parent = $field->field_parent;
	?>
			<li id="form_item_<?php echo $field->field_id; ?>" class="form-item<?php echo ( in_array( $field->field_type, array( 'submit', 'secret', 'verification' ) ) ) ? ' ui-state-disabled' : ''; ?><?php echo ( !in_array( $field->field_type, array( 'fieldset', 'section', 'verification' ) ) ) ? ' ui-nestedSortable-no-nesting' : ''; ?>">
					<dl class="menu-item-bar">
						<dt class="menu-item-handle<?php echo ( $field->field_type == 'fieldset' ) ? ' fieldset' : ''; ?>">
							<span class="item-title"><?php echo stripslashes( htmlspecialchars_decode( $field->field_name ) ); ?><?php echo ( $field->field_required == 'yes' ) ? ' <span class="is-field-required">*</span>' : ''; ?></span>
                            <span class="item-controls">
								<span class="item-type"><?php echo strtoupper( str_replace( '-', ' ', $field->field_type ) ); ?></span>
								<a href="#" title="<?php _e( 'Edit Field Item' , 'visual-form-builder'); ?>" id="edit-<?php echo $field->field_id; ?>" class="item-edit"><?php _e( 'Edit Field Item' , 'visual-form-builder'); ?></a>
							</span>
						</dt>
					</dl>
		
					<div id="form-item-settings-<?php echo $field->field_id; ?>" class="menu-item-settings field-type-<?php echo $field->field_type; ?>" style="display: none;">
						<?php if ( in_array( $field->field_type, array( 'fieldset', 'section', 'verification' ) ) ) : ?>
						
							<p class="description description-wide">
								<label for="edit-form-item-name-<?php echo $field->field_id; ?>"><?php echo ( in_array( $field->field_type, array( 'fieldset', 'verification' ) ) ) ? 'Legend' : 'Name'; ?>
                                <span class="vfb-tooltip" rel="For Fieldsets, a Legend is simply the name of that group. Use general terms that describe the fields included in this Fieldset." title="About Legend">(?)</span>
                                    <br />
									<input type="text" value="<?php echo stripslashes( $field->field_name ); ?>" name="field_name-<?php echo $field->field_id; ?>" class="widefat" id="edit-form-item-name-<?php echo $field->field_id; ?>" maxlength="255" />
								</label>
							</p>
                            <p class="description description-wide">
                                <label for="edit-form-item-css-<?php echo $field->field_id; ?>">
                                    <?php _e( 'CSS Classes' , 'visual-form-builder'); ?>
                                    <span class="vfb-tooltip" rel="For each field, you can insert your own CSS class names which can be used in your own stylesheets." title="About CSS Classes">(?)</span>
                                    <br />
                                    <input type="text" value="<?php echo stripslashes( htmlspecialchars_decode( $field->field_css ) ); ?>" name="field_css-<?php echo $field->field_id; ?>" class="widefat" id="edit-form-item-css-<?php echo $field->field_id; ?>" />
                                </label>
                            </p>
						
				       <?php elseif( $field->field_type == 'recaptcha' ) : ?>
				         <!-- reCAPTCHA -->
				         <input type="hidden" value="reCAPTCHA" name="field_name-<?php echo $field->field_id; ?>" class="widefat" id="edit-form-item-name-<?php echo $field->field_id; ?>" maxlength="255" />

				         <p class="description description-wide">
				           <label for="edit-form-item-description-<?php echo $field->field_id; ?>">
				             <?php _e( 'Public Key' , 'visual-form-builder'); ?>
				             <span class="vfb-tooltip" title="About Public Key" rel="This is the public key that Google provides when you register to use reCAPTCHAs on your site. It is required to display reCAPTCHA images.">(?)</span>
                                             <br />
					     <input type="text" value="<?php echo stripslashes( htmlspecialchars_decode( $field->field_description ) ); ?>" name="field_description-<?php echo $field->field_id; ?>" class="widefat" id="edit-form-item-description-<?php echo $field->field_id; ?>" maxlength="255" />
					   </label>
					 </p>
					 <p class="description description-wide">
					   <label for="edit-form-item-default-<?php echo $field->field_id; ?>">
                                	     <?php _e( 'Private Key', 'visual-form-builder' ); ?>
                                	     <span class="vfb-tooltip" title="About Private Key" rel="This is the private key that Google provides when you register to use reCAPTCHAs on your site. It is required to validate users' answers.">(?)</span>
					     <br />
					     <input type="text" value="<?php echo stripslashes( htmlspecialchars_decode( $field->field_default ) ); ?>" name="field_default-<?php echo $field->field_id; ?>" class="widefat" id="edit-form-item-default-<?php echo $field->field_id; ?>" maxlength="255" />
					   </label>
					 </p>
					 <p class="description description-wide">
					    <label for="edit-form-item-css-<?php echo $field->field_id; ?>">
					     <?php _e( 'Theme' , 'visual-form-builder'); ?>
                                	     <span class="vfb-tooltip" title="About Theme" rel="This dictates the theme and styling that will be used for the reCAPTCHA. There are four standard themes: red (default), white, blackglass, and clean. You can also choose a custom recaptcha that allows you to style it yourself.">(?)</span>
					     <br />
						<select name="field_css-<?php echo $field->field_id; ?>" class="widefat" id="edit-form-item-css-<?php echo $field->field_id; ?>">
  						  <option value="red" <?php selected( $field->field_css, 'red' ); ?>><?php _e( 'Red' , 'visual-form-builder'); ?></option>
			                          <option value="white" <?php selected( $field->field_css, 'white' ); ?>><?php _e( 'White' , 'visual-form-builder'); ?></option>
			                          <option value="blackglass" <?php selected( $field->field_css, 'blackglass' ); ?>><?php _e( 'Black Glass' , 'visual-form-builder'); ?></option>
			                          <option value="clean" <?php selected( $field->field_css, 'clean' ); ?>><?php _e( 'Clean' , 'visual-form-builder'); ?></option>
			                          <option value="custom" <?php selected( $field->field_css, 'custom' ); ?>><?php _e( 'Custom' , 'visual-form-builder'); ?></option>
					        </select>
					   </label>
					 </p>
						
						<?php elseif( $field->field_type == 'instructions' ) : ?>
							<!-- Instructions -->
							<p class="description description-wide">
								<label for="edit-form-item-name-<?php echo $field->field_id; ?>">
										<?php _e( 'Name' , 'visual-form-builder'); ?>
                                        <span class="vfb-tooltip" title="About Name" rel="A field's name is the most visible and direct way to describe what that field is for.">(?)</span>
                                        <br />
										<input type="text" value="<?php echo stripslashes( htmlspecialchars_decode( $field->field_name ) ); ?>" name="field_name-<?php echo $field->field_id; ?>" class="widefat" id="edit-form-item-name-<?php echo $field->field_id; ?>" maxlength="255" />
								</label>
							</p>
							<p class="description description-wide">
								<label for="edit-form-item-description-<?php echo $field->field_id; ?>">
                                	<?php _e( 'Description (HTML tags allowed)', 'visual-form-builder' ); ?>
                                	<span class="vfb-tooltip" title="About Instructions Description" rel="The Instructions field allows for long form explanations, typically seen at the beginning of Fieldsets or Sections. HTML tags are allowed.">(?)</span>
                                    <br />
									<textarea name="field_description-<?php echo $field->field_id; ?>" class="widefat" id="edit-form-item-description-<?php echo $field->field_id; ?>" /><?php echo stripslashes( $field->field_description ); ?></textarea>
								</label>
							</p>
							<p class="description description-wide">
								<label for="edit-form-item-css-<?php echo $field->field_id; ?>">
									<?php _e( 'CSS Classes' , 'visual-form-builder'); ?>
                                    <span class="vfb-tooltip" title="About CSS Classes" rel="For each field, you can insert your own CSS class names which can be used in your own stylesheets.">(?)</span>
                                    <br />
									<input type="text" value="<?php echo stripslashes( htmlspecialchars_decode( $field->field_css ) ); ?>" name="field_css-<?php echo $field->field_id; ?>" class="widefat" id="edit-form-item-css-<?php echo $field->field_id; ?>" maxlength="255" />
								</label>
							</p>
						
						<?php else: ?>
							
							<!-- Name -->
							<p class="description description-wide">
								<label for="edit-form-item-name-<?php echo $field->field_id; ?>">
									<?php _e( 'Name' , 'visual-form-builder'); ?>
                                    <span class="vfb-tooltip" title="About Name" rel="A field's name is the most visible and direct way to describe what that field is for.">(?)</span>
                                    <br />
									<input type="text" value="<?php echo stripslashes( htmlspecialchars_decode( $field->field_name ) ); ?>" name="field_name-<?php echo $field->field_id; ?>" class="widefat" id="edit-form-item-name-<?php echo $field->field_id; ?>" maxlength="255" />
								</label>
							</p>
							<?php if ( $field->field_type !== 'submit' ) : ?>
								<!-- Description -->
								<p class="description description-wide">
									<label for="edit-form-item-description-<?php echo $field->field_id; ?>">
										<?php _e( 'Description' , 'visual-form-builder'); ?>
                                         <span class="vfb-tooltip" title="About Description" rel="A description is an optional piece of text that further explains the meaning of this field. Descriptions are displayed below the field. HTML tags are allowed.">(?)</span>
                                        <br />
										<input type="text" value="<?php echo stripslashes( $field->field_description ); ?>" name="field_description-<?php echo $field->field_id; ?>" class="widefat" id="edit-form-item-description-<?php echo $field->field_id; ?>" />
									</label>
								</p>
								
								<?php
									/* Display the Options input only for radio, checkbox, and select fields */
									if ( in_array( $field->field_type, array( 'radio', 'checkbox', 'select' ) ) ) : ?>
									<!-- Options -->
									<p class="description description-wide">
										<?php _e( 'Options' , 'visual-form-builder'); ?>
                                        <span class="vfb-tooltip" title="About Options" rel="This property allows you to set predefined options to be selected by the user.  Use the plus and minus buttons to add and delete options.  At least one option must exist.">(?)</span>
                                        <br />
									<?php
										/* If the options field isn't empty, unserialize and build array */
										if ( !empty( $field->field_options ) ) {
											if ( is_serialized( $field->field_options ) )
												$opts_vals = ( is_array( unserialize( $field->field_options ) ) ) ? unserialize( $field->field_options ) : explode( ',', unserialize( $field->field_options ) );
										}
										/* Otherwise, present some default options */
										else
											$opts_vals = array( 'Option 1', 'Option 2', 'Option 3' );
										
										/* Basic count to keep track of multiple options */
										$count = 1;
										
										/* Loop through the options */
										foreach ( $opts_vals as $options ) {
									?>
									<div id="clone-<?php echo $field->field_id . '-' . $count; ?>" class="option">
										<label for="edit-form-item-options-<?php echo $field->field_id . "-$count"; ?>" class="clonedOption">
											<input type="radio" value="<?php echo $count; ?>" name="field_default-<?php echo $field->field_id; ?>" <?php checked( $field->field_default, $count ); ?> />
											<input type="text" value="<?php echo stripslashes( $options ); ?>" name="field_options-<?php echo $field->field_id; ?>[]" class="widefat" id="edit-form-item-options-<?php echo $field->field_id . "-$count"; ?>" />
										</label>
										
										<a href="#" class="addOption" title="Add an Option">Add</a> <a href="#" class="deleteOption" title="Delete Option">Delete</a>
									</div>
									   <?php 
											$count++;
										}
										?>
									</p>
								<?php
									/* Unset the options for any following radio, checkboxes, or selects */
									unset( $opts_vals );
									endif;
								?>
                                
								<?php
									/* Display the Options input only for radio, checkbox, select, and autocomplete fields */
									if ( in_array( $field->field_type, array( 'file-upload' ) ) ) :
								?>
                                	<!-- File Upload Accepts -->
									<p class="description description-wide">
                                        <?php
										$opts_vals = array( '' );
										
										/* If the options field isn't empty, unserialize and build array */
										if ( !empty( $field->field_options ) ) {
											if ( is_serialized( $field->field_options ) )
												$opts_vals = ( is_array( unserialize( $field->field_options ) ) ) ? unserialize( $field->field_options ) : unserialize( $field->field_options );
										}

										/* Loop through the options */
										foreach ( $opts_vals as $options ) {
									?>
										<label for="edit-form-item-options-<?php echo $field->field_id; ?>">
											<?php _e( 'Accepted File Extensions' , 'visual-form-builder'); ?>
                                            <span class="vfb-tooltip" title="About Accepted File Extensions" rel="Control the types of files allowed.  Enter extensions without periods and separate multiples using the pipe character ( | ).">(?)</span>
                                    		<br />
                                            <input type="text" value="<?php echo stripslashes( $options ); ?>" name="field_options-<?php echo $field->field_id; ?>[]" class="widefat" id="edit-form-item-options-<?php echo $field->field_id; ?>" />
										</label>
                                    </p>
                                <?php
										}
									/* Unset the options for any following radio, checkboxes, or selects */
									unset( $opts_vals );
									endif;
								?>
								<!-- Validation -->
								<p class="description description-thin">
									<label for="edit-form-item-validation">
										<?php _e( 'Validation' , 'visual-form-builder'); ?>
                                        <span class="vfb-tooltip" title="About Validation" rel="Ensures user-entered data is formatted properly. For more information on Validation, refer to the Help tab at the top of this page.">(?)</span>
                                        <br />
									   <select name="field_validation-<?php echo $field->field_id; ?>" class="widefat" id="edit-form-item-validation-<?php echo $field->field_id; ?>"<?php echo ( in_array( $field->field_type, array( 'radio', 'select', 'checkbox', 'address', 'date', 'textarea', 'html', 'file-upload', 'secret' ) ) ) ? ' disabled="disabled"' : ''; ?>>
											<?php if ( $field->field_type == 'time' ) : ?>
											<option value="time-12" <?php selected( $field->field_validation, 'time-12' ); ?>><?php _e( '12 Hour Format' , 'visual-form-builder'); ?></option>
											<option value="time-24" <?php selected( $field->field_validation, 'time-24' ); ?>><?php _e( '24 Hour Format' , 'visual-form-builder'); ?></option>
											<?php else : ?>
											<option value="" <?php selected( $field->field_validation, '' ); ?>><?php _e( 'None' , 'visual-form-builder'); ?></option>
											<option value="email" <?php selected( $field->field_validation, 'email' ); ?>><?php _e( 'Email' , 'visual-form-builder'); ?></option>
											<option value="url" <?php selected( $field->field_validation, 'url' ); ?>><?php _e( 'URL' , 'visual-form-builder'); ?></option>
											<option value="date" <?php selected( $field->field_validation, 'date' ); ?>><?php _e( 'Date' , 'visual-form-builder'); ?></option>
											<option value="number" <?php selected( $field->field_validation, 'number' ); ?>><?php _e( 'Number' , 'visual-form-builder'); ?></option>
											<option value="digits" <?php selected( $field->field_validation, 'digits' ); ?>><?php _e( 'Digits' , 'visual-form-builder'); ?></option>
											<option value="phone" <?php selected( $field->field_validation, 'phone' ); ?>><?php _e( 'Phone' , 'visual-form-builder'); ?></option>
											<?php endif; ?>
										</select>
									</label>
								</p>
								
								<!-- Required -->
								<p class="field-link-target description description-thin">
									<label for="edit-form-item-required">
										<?php _e( 'Required' , 'visual-form-builder'); ?>
                                        <span class="vfb-tooltip" title="About Required" rel="Requires the field to be completed before the form is submitted. By default, all fields are set to No.">(?)</span>
                                        <br />
										<select name="field_required-<?php echo $field->field_id; ?>" class="widefat" id="edit-form-item-required-<?php echo $field->field_id; ?>">
											<option value="no" <?php selected( $field->field_required, 'no' ); ?>><?php _e( 'No' , 'visual-form-builder'); ?></option>
											<option value="yes" <?php selected( $field->field_required, 'yes' ); ?>><?php _e( 'Yes' , 'visual-form-builder'); ?></option>
										</select>
									</label>
								</p>
							   
								<?php if ( !in_array( $field->field_type, array( 'radio', 'checkbox', 'time' ) ) ) : ?>
									<!-- Size -->
									<p class="description description-thin">
										<label for="edit-form-item-size">
											<?php _e( 'Size' , 'visual-form-builder'); ?>
                                            <span class="vfb-tooltip" title="About Size" rel="Control the size of the field.  By default, all fields are set to Medium.">(?)</span>
                                            <br />
											<select name="field_size-<?php echo $field->field_id; ?>" class="widefat" id="edit-form-item-size-<?php echo $field->field_id; ?>">
                                            	<option value="small" <?php selected( $field->field_size, 'small' ); ?>><?php _e( 'Small' , 'visual-form-builder'); ?></option>
												<option value="medium" <?php selected( $field->field_size, 'medium' ); ?>><?php _e( 'Medium' , 'visual-form-builder'); ?></option>
												<option value="large" <?php selected( $field->field_size, 'large' ); ?>><?php _e( 'Large' , 'visual-form-builder'); ?></option>
											</select>
										</label>
									</p>

								<?php elseif ( in_array( $field->field_type, array( 'radio', 'checkbox', 'time' ) ) ) : ?>
									<!-- Options Layout -->
									<p class="description description-thin">
										<label for="edit-form-item-size">
											<?php _e( 'Options Layout' , 'visual-form-builder'); ?>
                                            <span class="vfb-tooltip" title="About Options Layout" rel="Control the layout of radio buttons or checkboxes.  By default, options are arranged in One Column.">(?)</span>
                                            <br />
											<select name="field_size-<?php echo $field->field_id; ?>" class="widefat" id="edit-form-item-size-<?php echo $field->field_id; ?>"<?php echo ( $field->field_type == 'time' ) ? ' disabled="disabled"' : ''; ?>>
												<option value="" <?php selected( $field->field_size, '' ); ?>><?php _e( 'One Column' , 'visual-form-builder'); ?></option>
                                                <option value="two-column" <?php selected( $field->field_size, 'two-column' ); ?>><?php _e( 'Two Columns' , 'visual-form-builder'); ?></option>
												<option value="three-column" <?php selected( $field->field_size, 'three-column' ); ?>><?php _e( 'Three Columns' , 'visual-form-builder'); ?></option>
                                                <option value="auto-column" <?php selected( $field->field_size, 'auto-column' ); ?>><?php _e( 'Auto Width' , 'visual-form-builder'); ?></option>
											</select>
										</label>
									</p>
                                
								<?php endif; ?>
									<!-- Field Layout -->
									<p class="description description-thin">
										<label for="edit-form-item-layout">
											<?php _e( 'Field Layout' , 'visual-form-builder'); ?>
                                            <span class="vfb-tooltip" title="About Field Layout" rel="Used to create advanced layouts. Align fields side by side in various configurations.">(?)</span>
                                            <br />
											<select name="field_layout-<?php echo $field->field_id; ?>" class="widefat" id="edit-form-item-layout-<?php echo $field->field_id; ?>">
                                            	
												<option value="" <?php selected( $field->field_layout, '' ); ?>><?php _e( 'Default' , 'visual-form-builder'); ?></option>
                                                <optgroup label="------------">
                                                <option value="left-half" <?php selected( $field->field_layout, 'left-half' ); ?>><?php _e( 'Left Half' , 'visual-form-builder'); ?></option>
                                                <option value="right-half" <?php selected( $field->field_layout, 'right-half' ); ?>><?php _e( 'Right Half' , 'visual-form-builder'); ?></option>
                                                </optgroup>
                                                <optgroup label="------------">
												<option value="left-third" <?php selected( $field->field_layout, 'left-third' ); ?>><?php _e( 'Left Third' , 'visual-form-builder'); ?></option>
                                                <option value="middle-third" <?php selected( $field->field_layout, 'middle-third' ); ?>><?php _e( 'Middle Third' , 'visual-form-builder'); ?></option>
                                                <option value="right-third" <?php selected( $field->field_layout, 'right-third' ); ?>><?php _e( 'Right Third' , 'visual-form-builder'); ?></option>
                                                </optgroup>
                                                <optgroup label="------------">
                                                <option value="left-two-thirds" <?php selected( $field->field_layout, 'left-two-thirds' ); ?>><?php _e( 'Left Two Thirds' , 'visual-form-builder'); ?></option>
                                                <option value="right-two-thirds" <?php selected( $field->field_layout, 'right-two-thirds' ); ?>><?php _e( 'Right Two Thirds' , 'visual-form-builder'); ?></option>
                                                </optgroup>
											</select>
										</label>
									</p>
								<?php if ( !in_array( $field->field_type, array( 'radio', 'select', 'checkbox', 'time', 'address' ) ) ) : ?>
								<!-- Default Value -->
								<p class="description description-wide">
                                    <label for="edit-form-item-default-<?php echo $field->field_id; ?>">
                                        <?php _e( 'Default Value' , 'visual-form-builder-pro'); ?>
                                        <span class="vfb-tooltip" title="About Default Value" rel="Set a default value that will be inserted automatically.">(?)</span>
                                    	<br />
                                        <input type="text" value="<?php echo stripslashes( htmlspecialchars_decode( $field->field_default ) ); ?>" name="field_default-<?php echo $field->field_id; ?>" class="widefat" id="edit-form-item-default-<?php echo $field->field_id; ?>" maxlength="255" />
                                    </label>
								</p>
								<?php elseif( in_array( $field->field_type, array( 'address' ) ) ) : ?>
								<!-- Default Country -->
								<p class="description description-wide">
                                    <label for="edit-form-item-default-<?php echo $field->field_id; ?>">
                                        <?php _e( 'Default Country' , 'visual-form-builder-pro'); ?>
                                        <span class="vfb-tooltip" title="About Default Country" rel="Select the country you would like to be displayed by default.">(?)</span>
                                    	<br />
                                        <select name="field_default-<?php echo $field->field_id; ?>" class="widefat" id="edit-form-item-default-<?php echo $field->field_id; ?>">
                                        <?php
                                        foreach ( $this->countries as $country ) {
											echo '<option value="' . $country . '" ' . selected( $field->field_default, $country, 0 ) . '>' . $country . '</option>';
										}
										?>
										</select>
                                    </label>
								</p>
								<?php endif; ?>
								<!-- CSS Classes -->
								<p class="description description-wide">
                                    <label for="edit-form-item-css-<?php echo $field->field_id; ?>">
                                        <?php _e( 'CSS Classes' , 'visual-form-builder'); ?>
                                        <span class="vfb-tooltip" title="About CSS Classes" rel="For each field, you can insert your own CSS class names which can be used in your own stylesheets.">(?)</span>
                                        <br />
                                        <input type="text" value="<?php echo stripslashes( htmlspecialchars_decode( $field->field_css ) ); ?>" name="field_css-<?php echo $field->field_id; ?>" class="widefat" id="edit-form-item-css-<?php echo $field->field_id; ?>" maxlength="255" />
                                    </label>
								</p>

							<?php endif; ?>
						<?php endif; ?>
						
						<?php if ( !in_array( $field->field_type, array( 'verification', 'secret', 'recaptcha', 'submit' ) ) ) : ?>
							<div class="menu-item-actions description-wide submitbox">
								<a href="<?php echo esc_url( wp_nonce_url( admin_url('options-general.php?page=visual-form-builder&amp;action=delete_field&amp;form=' . $form_nav_selected_id . '&amp;field=' . $field->field_id ), 'delete-field-' . $form_nav_selected_id ) ); ?>" class="item-delete submitdelete deletion"><?php _e( 'Remove' , 'visual-form-builder'); ?></a>
							</div>
						<?php endif; ?>
						
					<input type="hidden" name="field_id[<?php echo $field->field_id; ?>]" value="<?php echo $field->field_id; ?>" />
					</div>
	<?php
		endforeach;
		
		/* This assures all of the <ul> and <li> are closed */
		if ( $depth > 1 ) {
			while( $depth > 1 ) {
				echo '</li>
					</ul>';
				$depth--;
			}
		}
		
		/* Close out last item */
		echo '</li>';
	}
	
	/**
	 * Builds the options settings page
	 * 
	 * @since 1.0
	 */
	public function admin() {
		global $wpdb, $entries_list, $entries_detail;

		/* Set variables depending on which tab is selected */
		$form_nav_selected_id = ( isset( $_REQUEST['form'] ) ) ? $_REQUEST['form'] : '0';
		$action = ( isset( $_REQUEST['form'] ) && $_REQUEST['form'] !== '0' ) ? 'update_form' : 'create_form';
		$details_meta = ( isset( $_REQUEST['details'] ) ) ? $_REQUEST['details'] : 'email';
		
		/* Query to get all forms */
		$order = sanitize_sql_orderby( 'form_id DESC' );
		$query = "SELECT * FROM $this->form_table_name ORDER BY $order";
		
		/* Build our forms as an object */
		$forms = $wpdb->get_results( $query );
		
		/* Loop through each form and assign a form id, if any */
		foreach ( $forms as $form ) {
			$form_id = ( $form_nav_selected_id == $form->form_id ) ? $form->form_id : '';
			
			/* If we are on a form, set the form name for the shortcode box */
			if ( $form_nav_selected_id == $form->form_id )
				$form_name = stripslashes( $form->form_title );	
		}
		
	?>
	
		<div class="wrap">
			<?php screen_icon( 'options-general' ); ?>
			<h2>
				<?php
					_e('Visual Form Builder', 'visual-form-builder');
					echo ( isset( $_REQUEST['s'] ) && !empty( $_REQUEST['s'] ) && in_array( $_REQUEST['page'], array( 'visual-form-builder' ) ) ) ? '<span class="subtitle">' . sprintf( __( 'Search results for "%s"' , 'visual-form-builder'), $_REQUEST['s'] ) : '';
				?>
			</h2>            
            <ul class="subsubsub">
                <li><a<?php echo ( !isset( $_REQUEST['view'] ) ) ? ' class="current"' : ''; ?> href="<?php echo admin_url( 'options-general.php?page=visual-form-builder' ); ?>"><?php _e( 'Forms' , 'visual-form-builder'); ?></a> |</li>
                <li><a<?php echo ( isset( $_REQUEST['view'] ) && in_array( $_REQUEST['view'], array( 'entries' ) ) ) ? ' class="current"' : ''; ?> href="<?php echo add_query_arg( 'view', 'entries', admin_url( 'options-general.php?page=visual-form-builder' ) ); ?>"><?php _e( 'Entries' , 'visual-form-builder'); ?></a></li>
            </ul>
            
            <?php
				/* Display the Entries */
				if ( isset( $_REQUEST['view'] ) && in_array( $_REQUEST['view'], array( 'entries' ) ) ) : 
										
					if ( isset( $_REQUEST['action'] ) && in_array( $_REQUEST['action'], array( 'view' ) ) ) :
						$entries_detail->entries_detail();
					else :
						$entries_list->prepare_items();
			?>
                <form id="entries-filter" method="post" action="">
                    <?php
                    	$entries_list->search_box( 'search', 'search_id' );
                    	$entries_list->display();
                    ?>
                </form>
            <?php
				endif;
				
				/* Display the Forms */
				else:	
					echo ( isset( $this->message ) ) ? $this->message : ''; ?>          
            <div id="nav-menus-frame">
                <div id="menu-settings-column" class="metabox-holder<?php echo ( empty( $form_nav_selected_id ) ) ? ' metabox-holder-disabled' : ''; ?>">
                    <div id="side-sortables" class="metabox-holder">

                    <form id="form-items" class="nav-menu-meta" method="post" action="">
                        <input name="action" type="hidden" value="create_field" />
						<input name="form_id" type="hidden" value="<?php echo $form_nav_selected_id; ?>" />
                        <?php
							/* Security nonce */
							wp_nonce_field( 'create-field-' . $form_nav_selected_id );
							
							/* Disable the left box if there's no active form selected */
                        	$disabled = ( empty( $form_nav_selected_id ) ) ? ' disabled="disabled"' : '';
						?>
                            <div class="postbox">
                                <h3 class="hndle"><span><?php _e( 'Form Items' , 'visual-form-builder'); ?></span></h3>
                                <div class="inside" >
                                    <div class="taxonomydiv">
                                        <p><strong><?php _e( 'Click' , 'visual-form-builder'); ?></strong> <?php _e( 'to Add a Field' , 'visual-form-builder'); ?> <img id="add-to-form" alt="" src="<?php echo admin_url( '/images/wpspin_light.gif' ); ?>" class="waiting" /></p>
                                        <ul>
                                            <li><input type="submit" id="form-element-fieldset" class="button-secondary" name="field_type" value="Fieldset"<?php echo $disabled; ?> /></li>
                                            <li><input type="submit" id="form-element-section" class="button-secondary" name="field_type" value="Section"<?php echo $disabled; ?> /></li>
                                            <li><input type="submit" id="form-element-text" class="button-secondary" name="field_type" value="Text"<?php echo $disabled; ?> /></li>
                                            <li><input type="submit" id="form-element-textarea" class="button-secondary" name="field_type" value="Textarea"<?php echo $disabled; ?> /></li>
                                            <li><input type="submit" id="form-element-checkbox" class="button-secondary" name="field_type" value="Checkbox"<?php echo $disabled; ?> /></li>
                                            <li><input type="submit" id="form-element-radio" class="button-secondary" name="field_type" value="Radio"<?php echo $disabled; ?> /></li>
                                            <li><input type="submit" id="form-element-select" class="button-secondary" name="field_type" value="Select"<?php echo $disabled; ?> /></li>
                                            <li><input type="submit" id="form-element-address" class="button-secondary" name="field_type" value="Address"<?php echo $disabled; ?> /></li>
                                            <li><input type="submit" id="form-element-datepicker" class="button-secondary" name="field_type" value="Date"<?php echo $disabled; ?> /></li>
                                            <li><input type="submit" id="form-element-email" class="button-secondary" name="field_type" value="Email"<?php echo $disabled; ?> /></li>
                                            <li><input type="submit" id="form-element-url" class="button-secondary" name="field_type" value="URL"<?php echo $disabled; ?> /></li>
                                            <li><input type="submit" id="form-element-currency" class="button-secondary" name="field_type" value="Currency"<?php echo $disabled; ?> /></li>
                                            <li><input type="submit" id="form-element-digits" class="button-secondary" name="field_type" value="Number"<?php echo $disabled; ?> /></li>
                                            <li><input type="submit" id="form-element-time" class="button-secondary" name="field_type" value="Time"<?php echo $disabled; ?> /></li>
                                            <li><input type="submit" id="form-element-phone" class="button-secondary" name="field_type" value="Phone"<?php echo $disabled; ?> /></li>
                                            <li><input type="submit" id="form-element-html" class="button-secondary" name="field_type" value="HTML"<?php echo $disabled; ?> /></li>
                                            <li><input type="submit" id="form-element-file" class="button-secondary" name="field_type" value="File Upload"<?php echo $disabled; ?> /></li>
                                            <li><input type="submit" id="form-element-instructions" class="button-secondary" name="field_type" value="Instructions"<?php echo $disabled; ?> /></li>
					    <!-- //TODO: Add a button for a recaptcha. -->
                                        </ul>
                                    </div>
                                </div>
                            </div>
                      </form>
                            <div class="postbox">
                                <h3 class="hndle"><span><?php _e( 'Form Output' , 'visual-form-builder'); ?></span></h3>
                                <div class="inside">
                                    <div id="customlinkdiv" class="customlinkdiv">
                                        <p><?php _e( 'Copy this shortcode and paste into any Post or Page.' , 'visual-form-builder'); ?> <?php echo ( $form_nav_selected_id !== '0') ? "This will display the <strong>$form_name</strong> form." : ''; ?></p>
                                        <p id="menu-item-url-wrap">
                                		<form action="">      
                                            <label class="howto">
                                                <span>Shortcode</span>
                                                <input id="form-copy-to-clipboard" type="text" class="code menu-item-textbox" value="<?php echo ( $form_nav_selected_id !== '0') ? "[vfb id=$form_nav_selected_id]" : ''; ?>"<?php echo $disabled; ?> style="width:75%;" />
                                            </label>
                               			 </form>
                                        </p>
                                    </div>
                                </div>
                            </div> 
                	</div>
            	</div>
                
                <div id="menu-management-liquid">
                    <div id="menu-management">
                       	<div class="nav-tabs-nav">
                        	<div class="nav-tabs-arrow nav-tabs-arrow-left"><a>&laquo;</a></div>
                            <div class="nav-tabs-wrapper">
                                <div class="nav-tabs">
                                    <?php
										/* Loop through each for and build the tabs */
										foreach ( $forms as $form ) {
											
											/* Control selected tab */
											if ( $form_nav_selected_id == $form->form_id ) :
												echo '<span class="nav-tab nav-tab-active">' . stripslashes( $form->form_title ) . '</span>';
												$form_id = $form->form_id;
												$form_title = stripslashes( $form->form_title );
												$form_subject = stripslashes( $form->form_email_subject );
												$form_email_from_name = stripslashes( $form->form_email_from_name );
												$form_email_from = stripslashes( $form->form_email_from);
												$form_email_from_override = stripslashes( $form->form_email_from_override);
												$form_email_from_name_override = stripslashes( $form->form_email_from_name_override);
												$form_email_to = ( is_array( unserialize( $form->form_email_to ) ) ) ? unserialize( $form->form_email_to ) : explode( ',', unserialize( $form->form_email_to ) );
												$form_success_type = stripslashes( $form->form_success_type );
												$form_success_message = stripslashes( $form->form_success_message );
												$form_notification_setting = stripslashes( $form->form_notification_setting );
												$form_notification_email_name = stripslashes( $form->form_notification_email_name );
												$form_notification_email_from = stripslashes( $form->form_notification_email_from );
												$form_notification_email = stripslashes( $form->form_notification_email );
												$form_notification_subject = stripslashes( $form->form_notification_subject );
												$form_notification_message = stripslashes( $form->form_notification_message );
												$form_notification_entry = stripslashes( $form->form_notification_entry );
												
												/* Only show required text fields for the sender name override */
												$sender_query 	= "SELECT * FROM $this->field_table_name WHERE form_id = $form_nav_selected_id AND field_type='text' AND field_validation = '' AND field_required = 'yes'";
												$senders = $wpdb->get_results( $sender_query );
												
												/* Only show required email fields for the email override */
												$email_query = "SELECT * FROM $this->field_table_name WHERE (form_id = $form_nav_selected_id AND field_type='text' AND field_validation = 'email' AND field_required = 'yes') OR (form_id = $form_nav_selected_id AND field_type='email' AND field_validation = 'email' AND field_required = 'yes')";
												$emails = $wpdb->get_results( $email_query );
											
											else :
												echo '<a href="' . esc_url( add_query_arg( array( 'form' => $form->form_id ), admin_url( 'options-general.php?page=visual-form-builder' ) ) ) . '" class="nav-tab" id="' . $form->form_key . '">' . stripslashes( $form->form_title ) . '</a>';
											endif;
											
										}
										
										/* Displays the build new form tab */
										if ( '0' == $form_nav_selected_id ) :
									?>
                                    	<span class="nav-tab menu-add-new nav-tab-active"><?php printf( '<abbr title="%s">+</abbr>', esc_html__( 'Add form' , 'visual-form-builder') ); ?></span>
									<?php else : ?>
                                    	<a href="<?php echo esc_url( add_query_arg( array( 'form' => 0 ), admin_url( 'options-general.php?page=visual-form-builder' ) ) ); ?>" class="nav-tab menu-add-new"><?php printf( '<abbr title="%s">+</abbr>', esc_html__( 'Add form' , 'visual-form-builder') ); ?></a>
									<?php endif; ?>
                                </div>
                            </div>
                            <div class="nav-tabs-arrow nav-tabs-arrow-right"><a>&raquo;</a></div>
                        </div>

                        <div class="menu-edit">
                        	<form method="post" id="visual-form-builder-update" action="">
                            	<input name="action" type="hidden" value="<?php echo $action; ?>" />
								<input name="form_id" type="hidden" value="<?php echo $form_nav_selected_id; ?>" />
                                <?php wp_nonce_field( "$action-$form_nav_selected_id" ); ?>
                            	<div id="nav-menu-header">
                                	<div id="submitpost" class="submitbox">
                                    	<div class="major-publishing-actions">
                                        	<label for="form-name" class="menu-name-label howto open-label">
                                                <span class="sender-labels"><?php _e( 'Form Name' , 'visual-form-builder'); ?></span>
                                                <input type="text" value="<?php echo ( isset( $form_title ) ) ? $form_title : ''; ?>" placeholder="Enter form name here" class="menu-name regular-text menu-item-textbox required" id="form-name" name="form_title" />
                                            </label>
                                            <?php 
												/* Display sender details and confirmation message if we're on a form, otherwise just the form name */
												if ( $form_nav_selected_id !== '0' ) : 
											?>
                                            <br class="clear" />
											
											<?php
												/* Get the current user */
												global $current_user;
												get_currentuserinfo();
												
												/* Save current user ID */
												$user_id = $current_user->ID;
												
												/* Get the Form Setting drop down and accordion settings, if any */
												$user_form_settings = get_user_meta( $user_id, 'vfb-form-settings' );
												
												/* Setup defaults for the Form Setting tab and accordion */
												$settings_tab = 'closed';
												$settings_accordion = 'general-settings';
												
												/* Loop through the user_meta array */
												foreach( $user_form_settings as $set ) {
													/* If form settings exist for this form, use them instead of the defaults */
													if ( isset( $set[ $form_id ] ) ) {
														$settings_tab = $set[ $form_id ]['form_setting_tab'];
														$settings_accordion = $set[ $form_id ]['setting_accordion'];
													}
												}
												
												/* If tab is opened, set current class */
												$opened_tab = ( $settings_tab == 'opened' ) ? 'current' : '';
											?>
                                            
                                            <div class="button-group">
												<a href="#form-settings" id="form-settings-button" class="vfb-button vfb-first <?php echo $opened_tab; ?>"><?php _e( 'Form Settings' , 'visual-form-builder'); ?><span class="button-icon arrow"></span></a>
                                                <a href="<?php echo esc_url( wp_nonce_url( admin_url('options-general.php?page=visual-form-builder&amp;action=copy_form&amp;form=' . $form_nav_selected_id ), 'copy-form-' . $form_nav_selected_id ) ); ?>" class="vfb-button vfb-duplicate"><?php _e( 'Duplicate Form' , 'visual-form-builder'); ?><span class="button-icon plus"></span></a>
                                                <a href="<?php echo esc_url( wp_nonce_url( admin_url('options-general.php?page=visual-form-builder&amp;action=delete_form&amp;form=' . $form_nav_selected_id ), 'delete-form-' . $form_nav_selected_id ) ); ?>" class="vfb-button vfb-delete vfb-last menu-delete"><?php _e( 'Delete Form' , 'visual-form-builder'); ?><span class="button-icon delete"></span></a>
                                            </div>
                                                                                        
                                            <div id="form-settings" class="<?php echo $opened_tab; ?>">
                                                <!-- General settings section -->
                                                <a href="#general-settings" class="settings-links<?php echo ( $settings_accordion == 'general-settings' ) ? ' on' : ''; ?>">1. General<span class="arrow"></span></a>
                                                <div id="general-settings" class="form-details<?php echo ( $settings_accordion == 'general-settings' ) ? ' on' : ''; ?>">
                                                    <!-- Label Alignment -->
                                                    <p class="description description-wide">
                                                    <label for="form-label-alignment">
                                                        <?php _e( 'Label Alignment' , 'visual-form-builder'); ?>
                                                        <span class="vfb-tooltip" title="About Label Alignment" rel="Set the field labels for this form to be aligned either on top, to the left, or to the right.  By default, all labels are aligned on top of the inputs.">(?)</span>
                                    					<br />
                                                     </label>
                                                        <select name="form_label_alignment" id="form-label-alignment" class="widefat">
                                                            <option value="" <?php selected( $form_label_alignment, '' ); ?>><?php _e( 'Top Aligned' , 'visual-form-builder'); ?></option>
                                                            <option value="left-label" <?php selected( $form_label_alignment, 'left-label' ); ?>><?php _e( 'Left Aligned' , 'visual-form-builder'); ?></option>
                                                            <option value="right-label" <?php selected( $form_label_alignment, 'right-label' ); ?>><?php _e( 'Right Aligned' , 'visual-form-builder'); ?></option>                                                        
                                                        </select>
                                                    </p>
                                                    <br class="clear" />
                                                </div>
                                                
                                                
                                                <!-- Email section -->
                                                <a href="#email-details" class="settings-links<?php echo ( $settings_accordion == 'email-details' ) ? ' on' : ''; ?>">2. Email<span class="arrow"></span></a>
                                                <div id="email-details" class="form-details<?php echo ( $settings_accordion == 'email-details' ) ? ' on' : ''; ?>">
                                                    
                                                    <p><em><?php _e( 'The forms you build here will send information to one or more email addresses when submitted by a user on your site.  Use the fields below to customize the details of that email.' , 'visual-form-builder'); ?></em></p>
    
                                                    <!-- E-mail Subject -->
                                                    <p class="description description-wide">
                                                    <label for="form-email-subject">
                                                        <?php _e( 'E-mail Subject' , 'visual-form-builder'); ?>
                                                        <span class="vfb-tooltip" title="About E-mail Subject" rel="This option sets the subject of the email that is sent to the emails you have set in the E-mail(s) To field.">(?)</span>
                                    					<br />
                                                        <input type="text" value="<?php echo stripslashes( $form_subject ); ?>" class="widefat" id="form-email-subject" name="form_email_subject" />
                                                    </label>
                                                    </p>
                                                    <br class="clear" />
    
                                                    <!-- Sender Name -->
                                                    <p class="description description-thin">
                                                    <label for="form-email-sender-name">
                                                        <?php _e( 'Your Name or Company' , 'visual-form-builder'); ?>
                                                        <span class="vfb-tooltip" title="About Your Name or Company" rel="This option sets the From display name of the email that is sent to the emails you have set in the E-mail(s) To field.">(?)</span>
                                    					<br />
                                                        <input type="text" value="<?php echo $form_email_from_name; ?>" class="widefat" id="form-email-sender-name" name="form_email_from_name"<?php echo ( $form_email_from_name_override != '' ) ? ' readonly="readonly"' : ''; ?> />
                                                    </label>
                                                    </p>
                                                    <p class="description description-thin">
                                                    	<label for="form_email_from_name_override">
                                                        	<?php _e( "User's Name (optional)" , 'visual-form-builder'); ?>
                                                            <span class="vfb-tooltip" title="About User's Name" rel="Select a required text field from your form to use as the From display name in the email.">(?)</span>
                                    						<br />
                                                        <select name="form_email_from_name_override" id="form_email_from_name_override" class="widefat">
                                                            <option value="" <?php selected( $form_email_from_name_override, '' ); ?>><?php _e( 'Select a required text field' , 'visual-form-builder'); ?></option>
                                                            <?php 
                                                            foreach( $senders as $sender ) {
                                                                echo '<option value="' . $sender->field_id . '"' . selected( $form_email_from_name_override, $sender->field_id ) . '>' . stripslashes( $sender->field_name ) . '</option>';
                                                            }
                                                            ?>
                                                        </select>
                                                        </label>
                                                    </p>
                                                    <br class="clear" />
                                                    
                                                    <!-- Sender E-mail -->
                                                    <p class="description description-thin">
                                                    <label for="form-email-sender">
                                                        <?php _e( 'Reply-To E-mail' , 'visual-form-builder'); ?>
                                                        <span class="vfb-tooltip" title="About Reply-To Email" rel="Manually set the email address that users will reply to.">(?)</span>
                                    					<br />
                                                        <input type="text" value="<?php echo $form_email_from; ?>" class="widefat" id="form-email-sender" name="form_email_from"<?php echo ( $form_email_from_override != '' ) ? ' readonly="readonly"' : ''; ?> />
                                                    </label>
                                                    </p>
                                                    <p class="description description-thin">
                                                        <label for="form_email_from_override">
                                                        	<?php _e( "User's E-mail (optional)" , 'visual-form-builder'); ?>
                                                            <span class="vfb-tooltip" title="About User's Email" rel="Select a required email field from your form to use as the Reply-To email.">(?)</span>
                                    						<br />
                                                        <select name="form_email_from_override" id="form_email_from_override" class="widefat">
                                                            <option value="" <?php selected( $form_email_from_override, '' ); ?>><?php _e( 'Select a required email field' , 'visual-form-builder'); ?></option>
                                                            <?php 
                                                            foreach( $emails as $email ) {
                                                                echo '<option value="' . $email->field_id . '"' . selected( $form_email_from_override, $email->field_id ) . '>' . stripslashes( $email->field_name ) . '</option>';
                                                            }
                                                            ?>
                                                        </select>
                                                        </label>
                                                    </p>
                                                    <br class="clear" />
    												
                                                    <!-- E-mail(s) To -->
                                                    <?php
                                                        /* Basic count to keep track of multiple options */
                                                        $count = 1;
                                                        
                                                        /* Loop through the options */
                                                        foreach ( $form_email_to as $email_to ) {
                                                    ?>
                                                    <div id="clone-email-<?php echo $count; ?>" class="option">
                                                        <p class="description description-wide">
                                                            <label for="form-email-to-<?php echo "$count"; ?>" class="clonedOption">
                                                            <?php _e( 'E-mail(s) To' , 'visual-form-builder'); ?>
                                                            <span class="vfb-tooltip" title="About E-mail(s) To" rel="This option sets single or multiple emails to send the submitted form data to. At least one email is required.">(?)</span>
                                    					<br />
                                                                <input type="text" value="<?php echo stripslashes( $email_to ); ?>" name="form_email_to[]" class="widefat" id="form-email-to-<?php echo "$count"; ?>" />
                                                            </label>
                                                            
                                                            <a href="#" class="addEmail" title="Add an Email">Add</a> <a href="#" class="deleteEmail" title="Delete Email">Delete</a>
                                                            
                                                        </p>
                                                        <br class="clear" />
                                                    </div>
                                                    <?php 
                                                            $count++;
                                                        }
                                                    ?>
                                                </div>
                                                
                                                <!-- Confirmation section -->
                                                <a href="#confirmation" class="settings-links<?php echo ( $settings_accordion == 'confirmation' ) ? ' on' : ''; ?>">3. Confirmation<span class="arrow"></span></a>
                                                <div id="confirmation-message" class="form-details<?php echo ( $settings_accordion == 'confirmation' ) ? ' on' : ''; ?>">
                                                    <p><em><?php _e( "After someone submits a form, you can control what is displayed. By default, it's a message but you can send them to another WordPress Page or a custom URL." , 'visual-form-builder'); ?></em></p>
                                                    <label for="form-success-type-text" class="menu-name-label open-label">
                                                        <input type="radio" value="text" id="form-success-type-text" class="form-success-type" name="form_success_type" <?php checked( $form_success_type, 'text' ); ?> />
                                                        <span><?php _e( 'Text' , 'visual-form-builder'); ?></span>
                                                    </label>
                                                    <label for="form-success-type-page" class="menu-name-label open-label">
                                                        <input type="radio" value="page" id="form-success-type-page" class="form-success-type" name="form_success_type" <?php checked( $form_success_type, 'page' ); ?>/>
                                                        <span><?php _e( 'Page' , 'visual-form-builder'); ?></span>
                                                    </label>
                                                    <label for="form-success-type-redirect" class="menu-name-label open-label">
                                                        <input type="radio" value="redirect" id="form-success-type-redirect" class="form-success-type" name="form_success_type" <?php checked( $form_success_type, 'redirect' ); ?>/>
                                                        <span><?php _e( 'Redirect' , 'visual-form-builder'); ?></span>
                                                    </label>
                                                    <br class="clear" />
                                                    <p class="description description-wide">
                                                    <?php
                                                    /* If there's no text message, make sure there is something displayed by setting a default */
                                                    if ( $form_success_message === '' )
                                                        $default_text = sprintf( '<p id="form_success">%s</p>', __( 'Your form was successfully submitted. Thank you for contacting us.' , 'visual-form-builder') );
                                                    ?>
                                                    <textarea id="form-success-message-text" class="form-success-message<?php echo ( 'text' == $form_success_type ) ? ' active' : ''; ?>" name="form_success_message_text"><?php echo $default_text; ?><?php echo ( 'text' == $form_success_type ) ? $form_success_message : ''; ?></textarea>
                                                    
                                                    <?php
                                                    /* Display all Pages */
                                                    wp_dropdown_pages( array(
                                                        'name' => 'form_success_message_page', 
                                                        'id' => 'form-success-message-page',
                                                        'class' => 'widefat',
                                                        'show_option_none' => __( 'Select a Page' , 'visual-form-builder'),
                                                        'selected' => $form_success_message
                                                    ));
                                                    ?>
                                                    <input type="text" value="<?php echo ( 'redirect' == $form_success_type ) ? $form_success_message : ''; ?>" id="form-success-message-redirect" class="form-success-message regular-text<?php echo ( 'redirect' == $form_success_type ) ? ' active' : ''; ?>" name="form_success_message_redirect" placeholder="http://" />
                                                    </p>
                                                <br class="clear" />
    
                                                </div>
                                            
                                                <!-- Notification section -->
                                                <a href="#notification" class="settings-links<?php echo ( $settings_accordion == 'notification' ) ? ' on' : ''; ?>">4. Notification<span class="arrow"></span></a>
                                                <div id="notification" class="form-details<?php echo ( $settings_accordion == 'notification' ) ? ' on' : ''; ?>">
                                                    <p><em><?php _e( "When a user submits their entry, you can send a customizable notification email." , 'visual-form-builder'); ?></em></p>
                                                    <label for="form-notification-setting">
                                                        <input type="checkbox" value="1" id="form-notification-setting" class="form-notification" name="form_notification_setting" <?php checked( $form_notification_setting, '1' ); ?> style="margin-top:-1px;margin-left:0;"/>
                                                        <?php _e( 'Send Confirmation Email to User' , 'visual-form-builder'); ?>
                                                    </label>
                                                    <br class="clear" />
                                                    <div id="notification-email">
                                                        <p class="description description-wide">
                                                        <label for="form-notification-email-name">
                                                            <?php _e( 'Sender Name or Company' , 'visual-form-builder'); ?>
                                                            <span class="vfb-tooltip" title="About Sender Name or Company" rel="Enter the name you would like to use for the email notification.">(?)</span>
                                    						<br />
                                                            <input type="text" value="<?php echo $form_notification_email_name; ?>" class="widefat" id="form-notification-email-name" name="form_notification_email_name" />
                                                        </label>
                                                        </p>
                                                        <br class="clear" />
                                                        <p class="description description-wide">
                                                        <label for="form-notification-email-from">
                                                            <?php _e( 'Reply-To E-mail' , 'visual-form-builder'); ?>
                                                            <span class="vfb-tooltip" title="About Reply-To Email" rel="Manually set the email address that users will reply to.">(?)</span>
                                    						<br />
                                                            <input type="text" value="<?php echo $form_notification_email_from; ?>" class="widefat" id="form-notification-email-from" name="form_notification_email_from" />
                                                        </label>
                                                        </p>
                                                        <br class="clear" />
                                                        <p class="description description-wide">
                                                            <label for="form-notification-email">
                                                                <?php _e( 'E-mail To' , 'visual-form-builder'); ?>
                                                                <span class="vfb-tooltip" title="About E-mail To" rel="Select a required email field from your form to send the notification email to.">(?)</span>
                                    							<br />
                                                                <select name="form_notification_email" id="form-notification-email" class="widefat">
                                                                    <option value="" <?php selected( $form_notification_email, '' ); ?>><?php _e( 'Select a required email field' , 'visual-form-builder'); ?></option>
                                                                    <?php 
                                                                    foreach( $emails as $email ) {
                                                                        echo '<option value="' . $email->field_id . '"' . selected( $form_notification_email, $email->field_id ) . '>' . $email->field_name . '</option>';
                                                                    }
                                                                    ?>
                                                                </select>
                                                            </label>
                                                        </p>
                                                        <br class="clear" />
                                                        <p class="description description-wide">
                                                        <label for="form-notification-subject">
                                                           <?php _e( 'E-mail Subject' , 'visual-form-builder'); ?>
                                                           <span class="vfb-tooltip" title="About E-mail Subject" rel="This option sets the subject of the email that is sent to the emails you have set in the E-mail To field.">(?)</span>
                                    						<br />
                                                            <input type="text" value="<?php echo $form_notification_subject; ?>" class="widefat" id="form-notification-subject" name="form_notification_subject" />
                                                        </label>
                                                        </p>
                                                        <br class="clear" />
                                                        <p class="description description-wide">
                                                        <label for="form-notification-message"><?php _e( 'Message' , 'visual-form-builder'); ?></label>
                                                        <span class="vfb-tooltip" title="About Message" rel="Insert a message to the user. This will be inserted into the beginning of the email body.">(?)</span>
                                    					<br />
                                                        <textarea id="form-notification-message" class="form-notification-message widefat" name="form_notification_message"><?php echo $form_notification_message; ?></textarea>
                                                        </p>
                                                        <br class="clear" />
                                                        <label for="form-notification-entry">
                                                        <input type="checkbox" value="1" id="form-notification-entry" class="form-notification" name="form_notification_entry" <?php checked( $form_notification_entry, '1' ); ?> style="margin-top:-1px;margin-left:0;"/>
                                                        <?php _e( "Include a Copy of the User's Entry" , 'visual-form-builder'); ?>
                                                    </label>
                                                    </div>
                                                </div>                                               
                                            </div>
                                            <?php endif; ?>

                                            <div class="publishing-action">
                                                <input type="submit" value="<?php echo ( $action == 'create_form' ) ? __( 'Create Form' , 'visual-form-builder') : __( 'Save Form' , 'visual-form-builder'); ?>" class="button-primary menu-save" id="save_form" name="save_form" />
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div id="post-body">
                                    <div id="post-body-content">
                                <?php if ( '0' == $form_nav_selected_id ) : ?>
                                        <div class="post-body-plain">
                                            <h3><?php _e( 'Getting Started' , 'visual-form-builder'); ?></h3>
                                            <ol>
                                                <li><?php _e( 'Enter a name in the Form Name input above and click Create Form.' , 'visual-form-builder'); ?></li>
                                                <li><?php _e( 'Click form items from the Form Item box on the left to add to your form.' , 'visual-form-builder'); ?></li>
                                                <li><?php _e( 'After adding an item, drag and drop to put them in the order you want.' , 'visual-form-builder'); ?></li>
                                                <li><?php _e( 'Click the down arrow on each item to reveal configuration options.' , 'visual-form-builder'); ?></li>
                                                <li><?php _e( 'Configure the Email Details section.' , 'visual-form-builder'); ?></li>
                                                <li><?php _e( 'When you have finished building your form, click the Save Form button.' , 'visual-form-builder'); ?></li>
                                            </ol>
                                            
                                            <h3><?php _e( 'Need more help?' , 'visual-form-builder'); ?></h3>
                                            <ol>
                                            	<li><?php _e( 'Click on the Help tab at the top of this page.' , 'visual-form-builder'); ?></li>
                                                <li><a href="http://wordpress.org/extend/plugins/visual-form-builder/faq/">Visual Form Builder FAQ</a></li>
                                                <li><a href="http://wordpress.org/tags/visual-form-builder?forum_id=10">Visual Form Builder Support Forums</a></li>
                                            </ol>
                                            
                                            <h3><?php _e( 'Help Promote Visual Form Builder' , 'visual-form-builder'); ?></h3>
                                            <ul id="promote-vfb">
                                            	<li id="twitter"><?php _e( 'Follow me on Twitter' , 'visual-form-builder'); ?>: <a href="http://twitter.com/#!/matthewmuro">@matthewmuro</a></li>
                                                <li id="star"><a href="http://wordpress.org/extend/plugins/visual-form-builder/"><?php _e( 'Rate Visual Form Builder on WordPress.org' , 'visual-form-builder'); ?></a></li>
                                                <li id="paypal">
                                                    <a href="https://www.paypal.com/cgi-bin/webscr?cmd=_donations&business=G87A9UN9CLPH4&lc=US&item_name=Visual%20Form%20Builder&currency_code=USD&bn=PP%2dDonationsBF%3abtn_donate_SM%2egif%3aNonHosted"><img alt="" border="0" src="https://www.paypalobjects.com/en_US/i/btn/btn_donate_SM.gif" width="74" height="21"></a>
                                                </li>
                                            </ul>
                                        </div>
                                        <div class="vfb-pro-upgrade">
                                        	<h3>Upgrade to <a href="http://vfb.matthewmuro.com">Visual Form Builder Pro</a> for only $10</h3>
                                            <p>Attention Visual Form Builder users!  I am happy to announce <a href="http://vfb.matthewmuro.com">Visual Form Builder Pro</a>, available now for only <strong>$10</strong>.</p>
                                            <h3><?php _e( 'New Features of Visual Form Builder Pro' , 'visual-form-builder'); ?></h3>
                                            <ul>
                                                <li><?php _e( 'Drag and Drop to add new form fields' , 'visual-form-builder'); ?></li>
                                                <li><?php _e( '10 new Form Fields (Username, Password, Color Picker, Autocomplete, Hidden, and more)' , 'visual-form-builder'); ?></li>
                                                <li><?php _e( 'Edit and Update Entries' , 'visual-form-builder'); ?></li>
                                                <li><?php _e( 'Quality HTML Email Template' , 'visual-form-builder'); ?></li>
                                                <li><?php _e( 'Plain Text Email Option' , 'visual-form-builder'); ?></li>
                                                <li><?php _e( 'Email Designer' , 'visual-form-builder'); ?></li>
                                                <li><?php _e( 'Analytics' , 'visual-form-builder'); ?></li>
                                                <li><?php _e( 'Data &amp; Form Migration' , 'visual-form-builder'); ?></li>
                                                <li><?php _e( 'PayPal Integration' , 'visual-form-builder'); ?></li>
                                                <li><?php _e( 'Form Paging' , 'visual-form-builder'); ?></li>
                                                <li><?php _e( 'No License Key' , 'visual-form-builder'); ?></li>
                                                <li><?php _e( 'Unlimited Use' , 'visual-form-builder'); ?></li>
                                                <li><?php _e( 'Automatic Updates' , 'visual-form-builder'); ?></li>
                                            </ul>
                                            
                                            <p><a href="http://matthewmuro.com/2012/02/07/introducing-visual-form-builder-pro/"><?php _e( 'Learn more about some of these features' , 'visual-form-builder'); ?></a>.</p>
                                            <p class="vfb-pro-call-to-action"><a href="http://visualformbuilder.fetchapp.com/sell/dahdaeng"><span class="cta-sign-up"><?php _e( 'Buy Now' , 'visual-form-builder'); ?></span><span class="cta-price"><?php _e( 'Only $10' , 'visual-form-builder'); ?></span></a></p>
                                        </div>
                               	<?php else : 
								
								if ( !empty( $form_nav_selected_id ) && $form_nav_selected_id !== '0' ) :
									/* Display help text for adding fields */
									printf( '<div class="post-body-plain" id="menu-instructions"><p>%s</p></div>', __( 'Note: to ensure your form displays and functions correctly, be sure a Fieldset is the first field.' , 'visual-form-builder') );

									/* Output the fields for each form */
									echo '<ul id="menu-to-edit" class="menu ui-sortable droppable">';
									
									echo $this->field_output( $form_nav_selected_id );

									echo '</ul>';
									
								endif;
								?>
                                    
								<?php endif; ?>
                                    </div>
                                    <br class="clear" />
                                 </div>
                                <div id="nav-menu-footer">
                                	<div class="major-publishing-actions">
                                        <div class="publishing-action">
                                            <input type="submit" value="<?php echo ( $action == 'create_form' ) ? __( 'Create Form' , 'visual-form-builder') : __( 'Save Form' , 'visual-form-builder'); ?>" class="button-primary menu-save" id="save_form" name="save_form" />
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
		</div>
	<?php
		endif;
	}
	
	/**
	 * Handle confirmation when form is submitted
	 * 
	 * @since 1.3
	 */
	function confirmation(){
		global $wpdb;
		
		$form_id = ( isset( $_REQUEST['form_id'] ) ) ? $_REQUEST['form_id'] : '';
		
		if ( isset( $_REQUEST['visual-form-builder-submit'] ) && wp_verify_nonce( $_REQUEST['_wpnonce'], 'visual-form-builder-nonce' ) ) {
			/* Get forms */
			$order = sanitize_sql_orderby( 'form_id DESC' );
			$query 	= "SELECT * FROM $this->form_table_name WHERE form_id = $form_id ORDER BY $order";
			
			$forms 	= $wpdb->get_results( $query );
			
			foreach ( $forms as $form ) {
				/* If text, return output and format the HTML for display */
				if ( 'text' == $form->form_success_type )
					return stripslashes( html_entity_decode( wp_kses_stripslashes( $form->form_success_message ) ) );
				/* If page, redirect to the permalink */
				elseif ( 'page' == $form->form_success_type ) {
					$page = get_permalink( $form->form_success_message );
					wp_redirect( $page );
					exit();
				}
				/* If redirect, redirect to the URL */
				elseif ( 'redirect' == $form->form_success_type ) {
					wp_redirect( $form->form_success_message );
					exit();
				}
			}
		}
	}
	
	/**
	 * Output form via shortcode
	 * 
	 * @since 1.0
	 */
	public function form_code( $atts ) {
		global $wpdb;
		
		/* Extract shortcode attributes, set defaults */
		extract( shortcode_atts( array(
			'id' => ''
			), $atts ) 
		);
		
		/* Get form id.  Allows use of [vfb id=1] or [vfb 1] */
		$form_id = ( isset( $id ) && !empty( $id ) ) ? $id : $atts[0];
		
		$open_fieldset = $open_section = false;
		
		/* Default the submit value */
		$submit = 'Submit';
		
		/* If form is submitted, show success message, otherwise the form */
		if ( isset( $_REQUEST['visual-form-builder-submit'] ) && wp_verify_nonce( $_REQUEST['_wpnonce'], 'visual-form-builder-nonce' ) && isset( $_REQUEST['form_id'] ) && $_REQUEST['form_id'] == $form_id ) {
			$output = $this->confirmation();
		}
		else {
			/* Get forms */
			$order = sanitize_sql_orderby( 'form_id DESC' );
			$query 	= "SELECT * FROM $this->form_table_name WHERE form_id = $form_id ORDER BY $order";
			
			$forms 	= $wpdb->get_results( $query );
			
			/* Get fields */
			$order_fields = sanitize_sql_orderby( 'field_sequence ASC' );
			$query_fields = "SELECT * FROM $this->field_table_name WHERE form_id = $form_id ORDER BY $order_fields";
			
			$fields = $wpdb->get_results( $query_fields );

			/* Setup count for fieldset and ul/section class names */
			$count = 1;
			
			$verification = '';

			foreach ( $forms as $form ) :
				$label_alignment = ( $form->form_label_alignment !== '' ) ? " $form->form_label_alignment" : '';
				$output = '<form id="' . $form->form_key . '" class="visual-form-builder' . $label_alignment . '" method="post" enctype="multipart/form-data">
							<input type="hidden" name="form_id" value="' . $form->form_id . '" />';
				$output .= wp_nonce_field( 'visual-form-builder-nonce', '_wpnonce', false, false );

				foreach ( $fields as $field ) {
					/* If field is required, build the span and add setup the 'required' class */
					$required_span = ( !empty( $field->field_required ) && $field->field_required === 'yes' ) ? ' <span>*</span>' : '';
					$required = ( !empty( $field->field_required ) && $field->field_required === 'yes' ) ? ' required' : '';
					$validation = ( !empty( $field->field_validation ) ) ? " $field->field_validation" : '';
					$css = ( !empty( $field->field_css ) ) ? " $field->field_css" : '';
					$layout = ( !empty( $field->field_layout ) ) ? " $field->field_layout" : '';
					$default = ( !empty( $field->field_default ) ) ? html_entity_decode( stripslashes( $field->field_default ) ) : '';
					
					/* Close each section */
					if ( $open_section == true ) {
						/* If this field's parent does NOT equal our section ID */
						if ( $sec_id && $sec_id !== $field->field_parent ) {
							$output .= '</div><div class="vfb-clear"></div>';
							$open_section = false;
						}
					}
					
					/* Force an initial fieldset and display an error message to strongly encourage user to add one */
					if ( $count === 1 && $field->field_type !== 'fieldset' ) {
						$output .= '<fieldset class="fieldset"><div class="legend" style="background-color:#FFEBE8;border:1px solid #CC0000;"><h3>Oops! Missing Fieldset</h3><p style="color:black;">If you are seeing this message, it means you need to <strong>add a Fieldset to the beginning of your form</strong>. Your form may not function or display properly without one.</p></div><ul class="section section-' . $count . '">';
						
						$count++;
					}
					
					if ( $field->field_type == 'fieldset' ) {
						/* Close each fieldset */
						if ( $open_fieldset == true )
							$output .= '</ul><br /></fieldset>';
						
						$output .= '<fieldset class="fieldset fieldset-' . $count . ' ' . $field->field_key . $css . '"><div class="legend"><h3>' . stripslashes( $field->field_name ) . '</h3></div><ul class="section section-' . $count . '">';
						$open_fieldset = true;
						$count++;
					}
					elseif ( $field->field_type == 'section' ) {
						$output .= '<div class="section-div vfb-' . esc_html( $field->field_key ) . '-' . $field->field_id . '"><h4>' . stripslashes( $field->field_name ) . '</h4>';
						
						/* Save section ID for future comparison */
						$sec_id = $field->field_id;
						$open_section = true;
					}
					elseif ( !in_array( $field->field_type, array( 'verification', 'secret', 'submit', 'recaptcha' ) ) ) {
						
						$columns_choice = ( in_array( $field->field_type, array( 'radio', 'checkbox' ) ) ) ? " $field->field_size" : '';
						
						if ( $field->field_type !== 'hidden' ) {
							$output .= '<li class="item item-' . $field->field_type . $columns_choice . $layout . '"><label for="vfb-' . esc_html( $field->field_key ) . '-' . $field->field_id . '" class="desc">'. stripslashes( $field->field_name ) . $required_span . '</label>';
						}
					}
					elseif ( in_array( $field->field_type, array( 'verification', 'secret', 'recaptcha' ) ) ) {
						
						if ( $field->field_type == 'verification' )
							$verification .= '<fieldset class="fieldset fieldset-' . $count . ' ' . $field->field_key . $css . $page . '"><div class="legend"><h3>' . stripslashes( $field->field_name ) . '</h3></div><ul class="section section-' . $count . '">';
						
						if ( $field->field_type == 'secret' || $field->field_type == 'recaptcha' ) {
							/* Default logged in values */
							$logged_in_display = '';
							$logged_in_value = '';

							/* If the user is logged in, fill the field in for them */
							if ( is_user_logged_in() ) {
								/* Hide the secret field if logged in */
								$logged_in_display = ' style="display:none;"';
								$logged_in_value = 14;
								
								/* Get logged in user details */
								$user = wp_get_current_user();
								$user_identity = ! empty( $user->ID ) ? $user->display_name : '';
								
								/* Display a message for logged in users */
								$verification .= '<li class="item">' . sprintf( __( 'Logged in as <a href="%1$s">%2$s</a>. Verification not required.', 'visual-form-builder' ), admin_url( 'profile.php' ), $user_identity ) . '</li>';
							}
							
							$validation = ' {digits:true,maxlength:2,minlength:2}';

							// Support the use of captchas if the user would like.
							if( $field->field_type == 'recaptcha' && $logged_in_display == '') {
							  $verification .= '<li class"item item-' .$field->field_type . '>';
							  $verification .= '<input type="hidden" name="_vfb-secret" value="vfb-' . esc_html($field->field_name) . '" />';

							  if($field->field_css != '') {
							    switch ($field->field_css) {
							        case 'custom':
								  // TODO: Allow the user to specify their custom reCAPTCHA via a textarea.s
  							          $verification .= '<script type="text/javascript"> var RecaptchaOptions = { theme: "custom", custom_theme_widget: "recaptcha_widget" }; </script>
 <div id="recaptcha_widget" style="display:none">
    <div id="recaptcha_area" class="custom_theme">
      <div id="recaptcha_image"></div>
      <div id="recaptcha_controls">
        <div class="recaptcha_input_area">
          <label for="recaptcha_response_field" class="recaptcha_input_area_text">
            <span id="recaptcha_instructions_image" class="recaptcha_only_if_image recaptcha_only_if_no_incorrect_sol">Type the two words:</span>
            <span id="recaptcha_instructions_audio" class="recaptcha_only_if_no_incorrect_sol recaptcha_only_if_audio">Type what you hear:</span>
            <span id="recaptcha_instructions_error" class="recaptcha_only_if_incorrect_sol">Incorrect. Try again.</span>
          </label>
          <br>
          <input name="recaptcha_response_field" id="recaptcha_response_field" type="text" autocorrect="off" autocapitalize="off">
        </div>
        <div id="recaptcha_buttons">
          <div class="recaptcha_button"><a id="recaptcha_reload_btn" title="Get a new challenge" href="javascript:Recaptcha.reload();"><img id="recaptcha_reload" width="25" height="17" src="http://www.google.com/recaptcha/api/img/white/refresh.gif" alt="Get a new challenge"></a></div>
          <div class="recaptcha_button"><a id="recaptcha_switch_audio_btn" class="recaptcha_only_if_image" title="Get an audio challenge" href="javascript:Recaptcha.switch_type(\'audio\');"><img id="recaptcha_switch_audio" width="25" height="16" alt="Get an audio challenge" src="http://www.google.com/recaptcha/api/img/white/audio.gif"></a><a id="recaptcha_switch_img_btn" class="recaptcha_only_if_audio" title="Get a visual challenge" href="javascript:Recaptcha.switch_type(\'image\');"><img id="recaptcha_switch_img" width="25" height="16" alt="Get a visual challenge" src="http://www.google.com/recaptcha/api/img/white/text.gif"></a></div>
          <div class="recaptcha_button"><a id="recaptcha_whatsthis_btn" title="Help" href="http://www.google.com/recaptcha/help?c=03AHJ_VuusYIeXoWUrMHkqRArLBCeUIv29RwY7iJPFx9asrUOqgeekFGPTwdlPTo0OyUfye2rkN8t_k9mZr5JHUfAs_j75vxXUKtImpv2JcDXsn5Uv4aECgErhEtfzlEYJUv-cblP5xbf6sSBm1Cu2nlzm8ufecNQkxtu4swFVGUYKPAZm02vy6b8&amp;hl=en" target="_blank"><img id="recaptcha_whatsthis" width="25" height="16" src="http://www.google.com/recaptcha/api/img/white/help.gif" alt="Help"></a></div>
        </div>
      </div>
    </div>';
								  break;
							        default:
  							          $verification .= '<script type="text/javascript"> var RecaptchaOptions = { theme : "' . $field->field_css . '" }; </script>';
							    }
							  }

							  require_once(plugin_dir_path( __FILE__ ) . 'recaptcha-php-1.11/recaptchalib.php');
							  // Public keys are currently stored in the field's description because adding extra columns for this 
							  // field type would have been a can be taken as a separate task to reduce risk.
							  $publickey = $field->field_description; // you got this from the signup page
							  $verification .= recaptcha_get_html($publickey);
							} else {
							$verification .= '<li class="item item-' . $field->field_type . '"' . $logged_in_display . '><label for="vfb-' . esc_html( $field->field_key ) . '-' . $field->field_id . '" class="desc">'. stripslashes( $field->field_name ) . $required_span . '</label>';
							
							/* Set variable for testing if required is Yes/No */
							if ( $required == '' )
								$verification .= '<input type="hidden" name="_vfb-required-secret" value="0" />';
							
							$verification .= '<input type="hidden" name="_vfb-secret" value="vfb-' . esc_html( $field->field_key ) . '-' . $field->field_id . '" />';
							
							if ( !empty( $field->field_description ) )
								$verification .= '<span><input type="text" name="vfb-' . esc_html( $field->field_key ) . '-' . $field->field_id . '" id="vfb-' . esc_html( $field->field_key )  . '-' . $field->field_id . '" value="' . $logged_in_value . '" class="text ' . $field->field_size . $required . $validation . $css . '" /><label>' . html_entity_decode( stripslashes( $field->field_description ) ) . '</label></span>';
							else
								$verification .= '<input type="text" name="vfb-' . esc_html( $field->field_key ) . '-' . $field->field_id . '" id="vfb-' . esc_html( $field->field_key )  . '-' . $field->field_id . '" value="' . $logged_in_value . '" class="text ' . $field->field_size . $required . $validation . $css . '" />';
							}
						}
					}
					
					
					
					switch ( $field->field_type ) {
						case 'text' :
						case 'email' :
						case 'url' :
						case 'currency' :
						case 'number' :
						case 'phone' :
							
							if ( !empty( $field->field_description ) )
								$output .= '<span><input type="text" name="vfb-' . esc_html( $field->field_key ) . '-' . $field->field_id . '" id="vfb-' . esc_html( $field->field_key )  . '-' . $field->field_id . '" value="' . $default . '" class="text ' . $field->field_size . $required . $validation . $css . '" /><label>' . html_entity_decode( stripslashes( $field->field_description ) ) . '</label></span>';
							else
								$output .= '<input type="text" name="vfb-' . esc_html( $field->field_key ) . '-' . $field->field_id . '" id="vfb-' . esc_html( $field->field_key )  . '-' . $field->field_id . '" value="' . $default . '" class="text ' . $field->field_size . $required . $validation . $css . '" />';
								
						break;
						
						case 'textarea' :
							
							if ( !empty( $field->field_description ) )
								$output .= '<span><label>' . html_entity_decode( stripslashes( $field->field_description ) ) . '</label></span>';
	
							$output .= '<textarea name="vfb-'. esc_html( $field->field_key ) . '-' . $field->field_id . '" id="vfb-'. esc_html( $field->field_key ) . '-' . $field->field_id . '" class="textarea ' . $field->field_size . $required . $css . '">' . $default . '</textarea>';
								
						break;
						
						case 'select' :
							if ( !empty( $field->field_description ) )
								$output .= '<span><label>' . html_entity_decode( stripslashes( $field->field_description ) ) . '</label></span>';
									
							$output .= '<select name="vfb-'. esc_html( $field->field_key ) . '-' . $field->field_id . '" id="vfb-'. esc_html( $field->field_key ) . '-' . $field->field_id . '" class="select ' . $field->field_size . $required . $css . '">';
							
							$options = ( is_array( unserialize( $field->field_options ) ) ) ? unserialize( $field->field_options ) : explode( ',', unserialize( $field->field_options ) );
							
							/* Loop through each option and output */
							foreach ( $options as $option => $value ) {
								$output .= '<option value="' . trim( stripslashes( $value ) ) . '"' . selected( $default, ++$option, 0 ) . '">'. trim( stripslashes( $value ) ) . '</option>';
							}
							
							$output .= '</select>';
							
						break;
						
						case 'radio' :
							
							if ( !empty( $field->field_description ) )
								$output .= '<span><label>' . html_entity_decode( stripslashes( $field->field_description ) ) . '</label></span>';
							
							$options = ( is_array( unserialize( $field->field_options ) ) ) ? unserialize( $field->field_options ) : explode( ',', unserialize( $field->field_options ) );
							
							$output .= '<div>';
							
							/* Loop through each option and output */
							foreach ( $options as $option => $value ) {
								$output .= '<span>
												<input type="radio" name="vfb-'. $field->field_key . '-' . $field->field_id . '" id="vfb-'. $field->field_key . '-' . $field->field_id . '-' . $option . '" value="'. trim( stripslashes( $value ) ) . '" class="radio' . $required . $css . '"' . checked( $default, ++$option, 0 ) . ' " />'. 
											' <label for="vfb-' . $field->field_key . '-' . $field->field_id . '-' . $option . '" class="choice">' . trim( stripslashes( $value ) ) . '</label>' .
											'</span>';
							}
							
							$output .= '<div style="clear:both"></div></div>';
							
						break;
						
						case 'checkbox' :
							
							if ( !empty( $field->field_description ) )
								$output .= '<span><label>' . html_entity_decode( stripslashes( $field->field_description ) ) . '</label></span>';
							
							$options = ( is_array( unserialize( $field->field_options ) ) ) ? unserialize( $field->field_options ) : explode( ',', unserialize( $field->field_options ) );
							
							$output .= '<div>';

							/* Loop through each option and output */
							foreach ( $options as $option => $value ) {
								
								$output .= '<span><input type="checkbox" name="vfb-'. $field->field_key . '-' . $field->field_id . '[]" id="vfb-'. $field->field_key . '-' . $field->field_id . '-' . $option . '" value="'. trim( stripslashes( $value ) ) . '" class="checkbox' . $required . $css . '"' . checked( $default, ++$option, 0 ) . ' />'. 
									' <label for="vfb-' . $field->field_key . '-' . $field->field_id . '-' . $option . '" class="choice">' . trim( stripslashes( $value ) ) . '</label></span>';
							}
							
							$output .= '<div style="clear:both"></div></div>';
						
						break;
						
						case 'address' :
							
							if ( !empty( $field->field_description ) )
								$output .= '<span><label>' . html_entity_decode( stripslashes( $field->field_description ) ) . '</label></span>';
								
							$output .= '<div>
								<span class="full">
					
									<input type="text" name="vfb-' . esc_html( $field->field_key ) . '-' . $field->field_id . '[address]" id="vfb-' . esc_html( $field->field_key )  . '-' . $field->field_id . '-address" maxlength="150" class="text medium' . $required . $css . '" />
									<label for="vfb-' . esc_html( $field->field_key ) . '-' . $field->field_id . '-address">Address</label>
								</span>
								<span class="full">
									<input type="text" name="vfb-' . esc_html( $field->field_key ) . '-' . $field->field_id . '[address-2]" id="vfb-' . esc_html( $field->field_key )  . '-' . $field->field_id . 'address-2" maxlength="150" class="text medium' . $css . '" />
									<label for="vfb-' . esc_html( $field->field_key ) . '-' . $field->field_id . '-address-2">Address Line 2</label>
								</span>
								<span class="left">
					
									<input type="text" name="vfb-' . esc_html( $field->field_key ) . '-' . $field->field_id . '[city]" id="vfb-' . esc_html( $field->field_key )  . '-' . $field->field_id . '-city" maxlength="150" class="text medium' . $required . $css . '" />
									<label for="vfb-' . esc_html( $field->field_key ) . '-' . $field->field_id . '-city">City</label>
								</span>
								<span class="right">
									<input type="text" name="vfb-' . esc_html( $field->field_key ) . '-' . $field->field_id . '[state]" id="vfb-' . esc_html( $field->field_key )  . '-' . $field->field_id . '-state" maxlength="150" class="text medium' . $required . $css . '" />
									<label for="vfb-' . esc_html( $field->field_key ) . '-' . $field->field_id . '-state">State / Province / Region</label>
								</span>
								<span class="left">
					
									<input type="text" name="vfb-' . esc_html( $field->field_key ) . '-' . $field->field_id . '[zip]" id="vfb-' . esc_html( $field->field_key )  . '-' . $field->field_id . '-zip" maxlength="150" class="text medium' . $required . $css . '" />
									<label for="vfb-' . esc_html( $field->field_key ) . '-' . $field->field_id . '-zip">Postal / Zip Code</label>
								</span>
								<span class="right">
								<select class="select' . $required . $css . '" name="vfb-' . esc_html( $field->field_key ) . '-' . $field->field_id . '[country]" id="vfb-' . esc_html( $field->field_key )  . '-' . $field->field_id . '-country">
								<option selected="selected" value=""></option>';
								
								foreach ( $this->countries as $country ) {
									$output .= "<option value='$country' " . selected( $default, $country, 0 ) . ">$country</option>";
								}
								
								$output .= '</select>
									<label for="vfb-' . esc_html( $field->field_key ) . '-' . $field->field_id . '-country">Country</label>
								</span>
							</div>';

						break;
						
						case 'date' :
							
							if ( !empty( $field->field_description ) )
								$output .= '<span><input type="text" name="vfb-' . esc_html( $field->field_key ) . '-' . $field->field_id . '" id="vfb-' . esc_html( $field->field_key )  . '-' . $field->field_id . '" value="' . $default . '" class="text vfb-date-picker ' . $field->field_size . $required . $css . '" /><label>' . html_entity_decode( stripslashes( $field->field_description ) ) . '</label></span>';
							else
								$output .= '<input type="text" name="vfb-' . esc_html( $field->field_key ) . '-' . $field->field_id . '" id="vfb-' . esc_html( $field->field_key )  . '-' . $field->field_id . '" value="" class="text vfb-date-picker ' . $field->field_size . $required . $css . '" />';
							
						break;
						
						case 'time' :
							if ( !empty( $field->field_description ) )
								$output .= '<span><label>' . html_entity_decode( stripslashes( $field->field_description ) ) . '</label></span>';

							/* Get the time format (12 or 24) */
							$time_format = str_replace( 'time-', '', $validation );
							/* Set whether we start with 0 or 1 and how many total hours */
							$hour_start = ( $time_format == '12' ) ? 1 : 0;
							$hour_total = ( $time_format == '12' ) ? 12 : 23;
							
							/* Hour */
							$output .= '<span class="time"><select name="vfb-'. $field->field_key . '-' . $field->field_id . '[hour]" id="vfb-'. $field->field_key . '-' . $field->field_id . '-hour" class="select' . $required . $css . '">';
							for ( $i = $hour_start; $i <= $hour_total; $i++ ) {
								/* Add the leading zero */
								$hour = ( $i < 10 ) ? "0$i" : $i;
								$output .= "<option value='$hour'>$hour</option>";
							}
							$output .= '</select><label for="vfb-' . esc_html( $field->field_key ) . '-' . $field->field_id . '-hour">HH</label></span>';
							
							/* Minute */
							$output .= '<span class="time"><select name="vfb-'. $field->field_key . '-' . $field->field_id . '[min]" id="vfb-'. $field->field_key . '-' . $field->field_id . '-min" class="select' . $required . $css . '">';
							for ( $i = 0; $i <= 55; $i+=5 ) {
								/* Add the leading zero */
								$min = ( $i < 10 ) ? "0$i" : $i;
								$output .= "<option value='$min'>$min</option>";
							}
							$output .= '</select><label for="vfb-' . esc_html( $field->field_key ) . '-' . $field->field_id . '-min">MM</label></span>';
							
							/* AM/PM */
							if ( $time_format == '12' )
								$output .= '<span class="time"><select name="vfb-'. $field->field_key . '-' . $field->field_id . '[ampm]" id="vfb-'. $field->field_key . '-' . $field->field_id . '-ampm" class="select' . $required . $css . '"><option value="AM">AM</option><option value="PM">PM</option></select><label for="vfb-' . esc_html( $field->field_key ) . '-' . $field->field_id . '-ampm">AM/PM</label></span>';
							$output .= '<div class="clear"></div>';		
						break;
						
						case 'html' :
							
							if ( !empty( $field->field_description ) )
								$output .= '<span><label>' . html_entity_decode( stripslashes( $field->field_description ) ) . '</label></span>';

							$output .= '<script type="text/javascript">edToolbar("vfb-' . $field->field_key . '-' . $field->field_id . '");</script>';
							$output .= '<textarea name="vfb-'. $field->field_key . '-' . $field->field_id . '" id="vfb-'. $field->field_key . '-' . $field->field_id . '" class="textarea vfbEditor ' . $field->field_size . $required . $css . '"></textarea>';
								
						break;
						
						case 'file-upload' :
							
							$options = ( is_array( unserialize( $field->field_options ) ) ) ? unserialize( $field->field_options ) : unserialize( $field->field_options );
							$accept = ( !empty( $options[0] ) ) ? " {accept:'$options[0]'}" : '';

							if ( !empty( $field->field_description ) )
								$output .= '<span><input type="file" size="35" name="vfb-' . esc_html( $field->field_key ) . '-' . $field->field_id . '" id="vfb-' . esc_html( $field->field_key )  . '-' . $field->field_id . '" value="' . $default . '" class="text ' . $field->field_size . $required . $validation . $accept . $css . '" /><label>' . stripslashes( $field->field_description ) . '</label></span>';
							else
								$output .= '<input type="file" size="35" name="vfb-' . esc_html( $field->field_key ) . '-' . $field->field_id . '" id="vfb-' . esc_html( $field->field_key )  . '-' . $field->field_id . '" value="' . $default . '" class="text ' . $field->field_size . $required . $validation . $accept . $css . '" />';
						
									
						break;
						
						case 'instructions' :
							
							$output .= html_entity_decode( stripslashes( $field->field_description ) );
						
						break;
						
						case 'submit' :
							
							$submit = stripslashes( $field->field_name );
							
						break;
						
						default:
							echo '';
					}

					/* Closing </li> */
					$output .= ( !in_array( $field->field_type , array( 'verification', 'secret', 'recaptcha', 'submit', 'fieldset', 'section' ) ) ) ? '</li>' : '';
				}
				
				/* Close user-added fields */
				$output .= '</ul><br /></fieldset>';
				
				/* Make sure the verification displays even if they have not updated their form */
				if ( $verification == '' ) {
				  // Default verification is to ask for any two digit number.
					$verification = '<fieldset class="fieldset verification">
							<div class="legend">
								<h3>' . __( 'Verification' , 'visual-form-builder') . '</h3>
							</div>
							<ul class="section section-' . $count . '">
								<li class="item item-text">
									<label for="vfb-secret" class="desc">' . __( 'Please enter any two digits with' , 'visual-form-builder') . ' <strong>' . __( 'no' , 'visual-form-builder') . '</strong> ' . __( 'spaces (Example: 12)' , 'visual-form-builder') . '<span>*</span></label>
									<div>
										<input type="text" name="vfb-secret" id="vfb-secret" class="text medium" />
									</div>
								</li>';
				}
				
				/* Output our security test */
				$output .= $verification . '<li style="display:none;">
									<label for="vfb-spam">' . __( 'This box is for spam protection' , 'visual-form-builder') . ' - <strong>' . __( 'please leave it blank' , 'visual-form-builder') . '</strong>:</label>
									<div>
										<input name="vfb-spam" id="vfb-spam" />
									</div>
								</li>

								<li class="item item-submit">
									<input type="submit" name="visual-form-builder-submit" value="' . $submit . '" class="submit" id="sendmail" />' . $total_page . '
								</li>
							</ul>
						</fieldset></form>';

			endforeach;
		}
		
		return $output;
	}
	
	/**
	 * Handle emailing the content
	 * 
	 * @since 1.0
	 * @uses wp_mail() E-mails a message
	 */
	public function email() {
		global $wpdb, $post;
		
		$required = ( isset( $_REQUEST['_vfb-required-secret'] ) && $_REQUEST['_vfb-required-secret'] == '0' ) ? false : true;
		$secret_field = ( isset( $_REQUEST['_vfb-secret'] ) ) ? $_REQUEST['_vfb-secret'] : '';

		/* If the verification is set to required, run validation check */
		if ( true == $required && !empty( $secret_field ) )
		  if ( $secret_field == 'vfb-reCAPTCHA' ) {
  $form_id = absint( $_REQUEST['form_id'] );

  require_once(plugin_dir_path( __FILE__ ) . 'recaptcha-php-1.11/recaptchalib.php');
  // Public keys are currently stored in the field's description because adding extra columns for this 
  // field type would have been a can be taken as a separate task to reduce risk.
  $privatekey = $wpdb->get_col( "SELECT fields.field_default FROM $this->field_table_name AS fields WHERE fields.form_id = $form_id AND field_type = 'recaptcha'", 0 );
  if(!isset($privatekey)) {
    wp_die( __("Unable to validate the reCAPTCHA input at this time. Please try again later") );
    // TODO: Log an error here, because this indicates that the form is not properly configured and/or not behaving properly.
  }

  $resp = recaptcha_check_answer ($privatekey[0],
                                $_SERVER["REMOTE_ADDR"],
                                $_POST["recaptcha_challenge_field"],
                                $_POST["recaptcha_response_field"]);

  if (!$resp->is_valid) {
    // What happens when the CAPTCHA was entered incorrectly
    wp_die( __("Security check: Incorrect reCAPTCHA input. Please try again!") );
  }
		  } else {
			if ( !is_numeric( $_REQUEST[ $secret_field ] ) && strlen( $_REQUEST[ $secret_field ] ) !== 2 )
				wp_die( __( 'Security check: failed secret question. Please try again!' , 'visual-form-builder') );
		  }
		
		/* Test if it's a known SPAM bot */
		if ( $this->isBot() )
			wp_die( __( 'Security check: looks like you are a SPAM bot. If you think this is an error, please email the site owner.' , 'visual-form-builder') );
		
		/* Basic security check before moving any further */
		if ( isset( $_REQUEST['visual-form-builder-submit'] ) && $_REQUEST['vfb-spam'] == '' ) :
			$nonce = $_REQUEST['_wpnonce'];
			
			/* Security check to verify the nonce */
			if ( ! wp_verify_nonce( $nonce, 'visual-form-builder-nonce' ) )
				wp_die( __( 'Security check: unable to verify nonce value.' , 'visual-form-builder') );
			
			/* Set submitted action to display success message */
			$this->submitted = true;
			
			/* Tells us which form to get from the database */
			$form_id = absint( $_REQUEST['form_id'] );
			
			/* Query to get all forms */
			$order = sanitize_sql_orderby( 'form_id DESC' );
			
			/* Build our forms as an object */
			$forms = $wpdb->get_results( "SELECT * FROM $this->form_table_name WHERE form_id = $form_id ORDER BY $order" );
			
			/* Get sender and email details */
			foreach ( $forms as $form ) {
				$form_title = stripslashes( html_entity_decode( $form->form_title, ENT_QUOTES, 'UTF-8' ) );
				$form_subject = stripslashes( html_entity_decode( $form->form_email_subject, ENT_QUOTES, 'UTF-8' ) );
				$form_to = ( is_array( unserialize( $form->form_email_to ) ) ) ? unserialize( $form->form_email_to ) : explode( ',', unserialize( $form->form_email_to ) );
				$form_from = stripslashes( $form->form_email_from );
				$form_from_name = stripslashes( $form->form_email_from_name );
				$form_notification_setting = stripslashes( $form->form_notification_setting );
				$form_notification_email_name = stripslashes( $form->form_notification_email_name );
				$form_notification_email_from = stripslashes( $form->form_notification_email_from );
				$form_notification_email = stripslashes( $form->form_notification_email );
				$form_notification_subject = stripslashes( $form->form_notification_subject );
				$form_notification_message = stripslashes( $form->form_notification_message );
				$form_notification_entry = stripslashes( $form->form_notification_entry );
			}
			
			/* Sender name override query */
			$senders = $wpdb->get_results( "SELECT fields.field_id, fields.field_key FROM $this->form_table_name AS forms LEFT JOIN $this->field_table_name AS fields ON forms.form_email_from_name_override = fields.field_id WHERE forms.form_id = $form_id" );

			/* Sender email override query */
			$emails = $wpdb->get_results( "SELECT fields.field_id, fields.field_key FROM $this->form_table_name AS forms LEFT JOIN $this->field_table_name AS fields ON forms.form_email_from_override = fields.field_id WHERE forms.form_id = $form_id" );
			
			/* Notification send to email override query */
			$notification = $wpdb->get_results( "SELECT fields.field_id, fields.field_key FROM $this->form_table_name AS forms LEFT JOIN $this->field_table_name AS fields ON forms.form_notification_email = fields.field_id WHERE forms.form_id = $form_id" );
			
			/* Loop through name results and assign sender name to override, if needed */
			foreach( $senders as $sender ) {
				if ( !empty( $sender->field_key ) )
					$form_from_name = $_POST[ 'vfb-' . $sender->field_key . '-' . $sender->field_id ];
			}

			/* Loop through email results and assign sender email to override, if needed */
			foreach ( $emails as $email ) {
				if ( !empty( $email->field_key ) )
					$form_from = $_POST[ 'vfb-' . $email->field_key . '-' . $email->field_id ];
			}
			
			/* Loop through email results and assign as blind carbon copy, if needed */
			foreach ( $notification as $notify ) {
				if ( !empty( $notify->field_key ) )
					$copy_email = $_POST[ 'vfb-' . $notify->field_key . '-' . $notify->field_id ];
			}

			/* Query to get all forms */
			$order = sanitize_sql_orderby( 'field_sequence ASC' );
			
			/* Build our forms as an object */
			$fields = $wpdb->get_results( "SELECT field_id, field_key, field_name, field_type, field_options, field_parent FROM $this->field_table_name WHERE form_id = $form_id ORDER BY $order" );
			
			/* Setup counter for alt rows */
			$i = $points = 0;
			
			/* Setup HTML email vars */
			$header = $body = $message = $footer = $html_email = $auto_response_email = '';
			
			/* Prepare the beginning of the content */
			$header = '<html>
						<head>
						<meta content="text/html; charset=utf-8" http-equiv="Content-Type" />
						<title>HTML Email</title>
						</head>
						<body><table rules="all" style="border-color: #666;" cellpadding="10">' . "\n";
			
			/* Loop through each form field and build the body of the message */
			foreach ( $fields as $field ) {
				/* Handle attachments */
				if ( $field->field_type == 'file-upload' ) {
					$value = $_FILES[ 'vfb-' . $field->field_key . '-' . $field->field_id ];
					
					if ( $value['size'] > 0 ) {
						/* 25MB is the max size allowed */
						$max_attach_size = 25 * 1048576;
						
						/* Display error if file size has been exceeded */
						if ( $value['size'] > $max_attach_size )
							wp_die( __( 'File size exceeds 25MB. Most email providers will reject emails with attachments larger than 25MB. Please decrease the file size and try again.', 'visual-form-builder' ), '', array( 'back_link' => true ) );
						
						/* Options array for the wp_handle_upload function. 'test_form' => false */
						$upload_overrides = array( 'test_form' => false ); 
						
						/* We need to include the file that runs the wp_handle_upload function */
						require_once( ABSPATH . 'wp-admin/includes/file.php' );
						
						/* Handle the upload using WP's wp_handle_upload function. Takes the posted file and an options array */
						$uploaded_file = wp_handle_upload( $value, $upload_overrides );
						
						/* If the wp_handle_upload call returned a local path for the image */
						if ( isset( $uploaded_file['file'] ) ) {
							/* Retrieve the file type from the file name. Returns an array with extension and mime type */
							$wp_filetype = wp_check_filetype( basename( $uploaded_file['file'] ), null );
							
							/* Return the current upload directory location */
 							$wp_upload_dir = wp_upload_dir();
							
							$media_upload = array(
								'guid' => $wp_upload_dir['baseurl'] . _wp_relative_upload_path( $uploaded_file['file'] ), 
								'post_mime_type' => $wp_filetype['type'],
								'post_title' => preg_replace( '/\.[^.]+$/', '', basename( $uploaded_file['file'] ) ),
								'post_content' => '',
								'post_status' => 'inherit'
							);
							
							/* Insert attachment into Media Library and get attachment ID */
							$attach_id = wp_insert_attachment( $media_upload, $uploaded_file['file'] );
							
							/* Include the file that runs wp_generate_attachment_metadata() */
							require_once( ABSPATH . 'wp-admin/includes/image.php' );
							
							/* Setup attachment metadata */
							$attach_data = wp_generate_attachment_metadata( $attach_id, $uploaded_file['file'] );
							
							/* Update the attachment metadata */
							wp_update_attachment_metadata( $attach_id, $attach_data );
							
							$attachments[ 'vfb-' . $field->field_key . '-' . $field->field_id ] = $uploaded_file['file'];

							$data[] = array(
								'id' => $field->field_id,
								'slug' => $field->field_key,
								'name' => $field->field_name,
								'type' => $field->field_type,
								'options' => $field->field_options,
								'parent_id' => $field->field_parent,
								'value' => $uploaded_file['url']
							);
							
							$body .= '<tr><td><strong>' . stripslashes( $field->field_name ) . ': </strong></td><td><a href="' . $uploaded_file['url'] . '">' . $uploaded_file['url'] . '</a></td></tr>' . "\n";
						}
					}
					else {
						$value = $_POST[ 'vfb-' . $field->field_key . '-' . $field->field_id ];
						$body .= '<tr><td><strong>' . stripslashes( $field->field_name ) . ': </strong></td><td>' . $value . '</td></tr>' . "\n";
					}
				}
				/* Everything else */
				else {
					$value = $_POST[ 'vfb-' . $field->field_key . '-' . $field->field_id ];
					
					/* If time field, build proper output */
					if ( is_array( $value ) && array_key_exists( 'hour', $value ) && array_key_exists( 'min', $value ) )
						$value = ( array_key_exists( 'ampm', $value ) ) ? substr_replace( implode( ':', $value ), ' ', 5, 1 ) : implode( ':', $value );
					/* If address field, build proper output */
					elseif ( is_array( $value ) && array_key_exists( 'address', $value ) && array_key_exists( 'address-2', $value ) ) {
						$address = '';
						
						if ( !empty( $value['address'] ) )
							$address .= $value['address'];
						
						if ( !empty( $value['address-2'] ) ) {
							if ( !empty( $address ) )
								$address .= '<br>';
							$address .= $value['address-2'];
						}
						
						if ( !empty( $value['city'] ) ) {
							if ( !empty( $address ) )
								$address .= '<br>';
							$address .= $value['city'];
						}
						if ( !empty( $value['state'] ) ) {
							if ( !empty( $address ) && empty( $value['city'] ) )
								$address .= '<br>';
							else if ( !empty( $address ) && !empty( $value['city'] ) )
								$address .= ', ';
							$address .= $value['state'];
						}
						if ( !empty( $value['zip'] ) ) {
							if ( !empty( $address ) && ( empty( $value['city'] ) && empty( $value['state'] ) ) )
								$address .= '<br>';
							else if ( !empty( $address ) && ( !empty( $value['city'] ) || !empty( $value['state'] ) ) )
								$address .= '. ';
							$address .= $value['zip'];
						}
						if ( !empty( $value['country'] ) ) {
							if ( !empty( $address ) )
								$address .= '<br>';
							$address .= $value['country'];
						}
						
						$value = $address;
					}
					/* If multiple values, build the list */
					elseif ( is_array( $value ) )
						$value = implode( ', ', $value );
					/* Lastly, handle single values */
					else
						$value = html_entity_decode( stripslashes( esc_html( $value ) ), ENT_QUOTES, 'UTF-8' );
					
					/* Setup spam catcher RegEx */
					$exploits = '/(content-type|bcc:|cc:|document.cookie|onclick|onload|javascript|alert)/i';
					$profanity = '/(beastial|bestial|blowjob|clit|cock|cum|cunilingus|cunillingus|cunnilingus|cunt|ejaculate|fag|felatio|fellatio|fuck|fuk|fuks|gangbang|gangbanged|gangbangs|hotsex|jism|jiz|kock|kondum|kum|kunilingus|orgasim|orgasims|orgasm|orgasms|phonesex|phuk|phuq|porn|pussies|pussy|spunk|xxx)/i';
					$spamwords = '/(viagra|phentermine|tramadol|adipex|advai|alprazolam|ambien|ambian|amoxicillin|antivert|blackjack|backgammon|texas|holdem|poker|carisoprodol|ciara|ciprofloxacin|debt|dating|porn)/i';
					
					/* Add up points for each spam hit */
					if ( preg_match( $exploits, $value ) )
						$points += 2;
					elseif ( preg_match( $profanity, $value ) )
						$points += 1;
					elseif ( preg_match( $spamwords, $value ) )
						$points += 1;
					
					/* Validate input */
					$this->validate_input( $value, $field->field_type );
					
					//if ( $field->field_type !== 'submit' ) {
					if ( !in_array( $field->field_type , array( 'verification', 'secret', 'submit' ) ) ) {
						if ( $field->field_type == 'fieldset' )
							$body .= '<tr style="background-color:#393E40;color:white;font-size:14px;"><td colspan="2">' . stripslashes( $field->field_name ) . '</td></tr>' . "\n";
						elseif ( $field->field_type == 'section' )
							$body .= '<tr style="background-color:#6E7273;color:white;font-size:14px;"><td colspan="2">' . stripslashes( $field->field_name ) . '</td></tr>' . "\n";
						else
							$body .= '<tr><td><strong>' . stripslashes( $field->field_name ) . ': </strong></td><td>' . $value . '</td></tr>' . "\n";
					}
				
					$data[] = array(
						'id' => $field->field_id,
						'slug' => $field->field_key,
						'name' => $field->field_name,
						'type' => $field->field_type,
						'options' => $field->field_options,
						'parent_id' => $field->field_parent,
						'value' => $value
					);
				}
			}
			
			/* Setup our entries data */
			$entry = array(
				'form_id' => $form_id,
				'data' => serialize( $data ),
				'subject' => $form_subject,
				'sender_name' => $form_from_name,
				'sender_email' => $form_from,
				'emails_to' => serialize( $form_to ),
				'date_submitted' => date_i18n( 'Y-m-d G:i:s' ),
				'ip_address' => $_SERVER['REMOTE_ADDR']
			);
			
			/* Insert this data into the entries table */
			$wpdb->insert( $this->entries_table_name, $entry );

			/* Close out the content */
			$footer .= '<tr><td class="footer" height="61" align="left" valign="middle" colspan="2"><p style="font-size: 12px; font-weight: normal; margin: 0; line-height: 16px; padding: 0;">This email was built and sent using <a href="http://wordpress.org/extend/plugins/visual-form-builder/" style="font-size: 12px;">Visual Form Builder</a>.</p></td></tr></table></body></html>' . "\n";
			
			/* Build complete HTML email */
			$message = $header . $body . $footer;
			
			/* Initialize header filter vars */
			$this->header_from_name = stripslashes( $form_from_name );
			$this->header_from = $form_from;
			$this->header_content_type = 'text/html';
			
			/* Set wp_mail header filters to send an HTML email */
			add_filter( 'wp_mail_from_name', array( &$this, 'mail_header_from_name' ) );
			add_filter( 'wp_mail_from', array( &$this, 'mail_header_from' ) );
			add_filter( 'wp_mail_content_type', array( &$this, 'mail_header_content_type' ) );
			
			/* Send the mail */
			foreach ( $form_to as $email ) {
				wp_mail( $email, esc_html( $form_subject ), $message, '', $attachments );
			}
			
			/* Kill the values stored for header name and email */
			unset( $this->header_from_name );
			unset( $this->header_from );
			
			/* Remove wp_mail header filters in case we need to override for notifications */
			remove_filter( 'wp_mail_from_name', array( &$this, 'mail_header_from_name' ) );
			remove_filter( 'wp_mail_from', array( &$this, 'mail_header_from' ) );
			
			/* Send auto-responder email */
			if ( $form_notification_setting !== '' ) :
				
				/* Assign notify header filter vars */
				$this->header_from_name = stripslashes( $form_notification_email_name );
				$this->header_from = $form_notification_email_from;
				
				/* Set the wp_mail header filters for notification email */
				add_filter( 'wp_mail_from_name', array( &$this, 'mail_header_from_name' ) );
				add_filter( 'wp_mail_from', array( &$this, 'mail_header_from' ) );
				
				/* Decode HTML for message so it outputs properly */
				$notify_message = ( $form_notification_message !== '' ) ? html_entity_decode( $form_notification_message ) : '';
				
				/* Either prepend the notification message to the submitted entry, or send by itself */
				/* Either prepend the notification message to the submitted entry, or send by itself */				
				if ( $form_notification_entry !== '' )
					$auto_response_email = $header . '<p style="font-size: 12px; font-weight: normal; margin: 14px 0 14px 0; color: black; padding: 0;">' . $notify_message . '</p>' . $body . $footer;
				else
					$auto_response_email = $header . '<table cellspacing="0" border="0" cellpadding="0" width="100%"><tr><td colspan="2" class="mainbar" align="left" valign="top" width="600"><p style="font-size: 12px; font-weight: normal; margin: 14px 0 14px 0; color: black; padding: 0;">' . $notify_message . '</p></td></tr>' . $footer;
				
				$attachments = ( $form_notification_entry !== '' ) ? $attachments : '';
				
				/* Send the mail */
				wp_mail( $copy_email, esc_html( $form_notification_subject ), $auto_response_email, '', $attachments );
			endif;
			
		elseif ( isset( $_REQUEST['visual-form-builder-submit'] ) ) :
			/* If any of the security checks fail, provide some user feedback */
			if ( $_REQUEST['vfb-spam'] !== '' || !is_numeric( $_REQUEST['vfb-secret'] ) || strlen( $_REQUEST['vfb-secret'] ) !== 2 )
				wp_die( __( 'Ooops! Looks like you have failed the security validation for this form. Please go back and try again.' , 'visual-form-builder') );
		endif;
	}
	
	/**
	 * Validate the input
	 * 
	 * @since 2.2
	 */
	public function validate_input( $data, $type ) {
		if ( strlen( $data ) > 0 ) :
			switch( $type ) {
				
				case 'email' :
					if ( !is_email( $data ) )
						wp_die( __( 'Not a valid email address', 'visual-form-builder' ), '', array( 'back_link' => true ) );
				break;
				
				case 'number' :
				case 'currency' :
					if ( !is_numeric( $data ) )
						wp_die( __( 'Not a valid number.', 'visual-form-builder' ), '', array( 'back_link' => true ) );
				break;
				
				case 'phone' :
					if ( strlen( $data ) > 9 && preg_match( '/^((\+)?[1-9]{1,2})?([-\s\.])?((\(\d{1,4}\))|\d{1,4})(([-\s\.])?[0-9]{1,12}){1,2}$/', $data ) )
						return true; 
					else
						wp_die( __( 'Not a valid phone number. Most US/Canada and International formats accepted.', 'visual-form-builder' ), '', array( 'back_link' => true ) );
				break;
				
				case 'url' :
					if ( !preg_match( '|^http(s)?://[a-z0-9-]+(.[a-z0-9-]+)*(:[0-9]+)?(/.*)?$|i', $data ) )
						wp_die( __( 'Not a valid URL.', 'visual-form-builder' ), '', array( 'back_link' => true ) );
				break;
				
				default :
					return true;
				break;
			}
		endif;
	}
	
	/**
	 * Make sure the User Agent string is not a SPAM bot
	 * 
	 * @since 1.3
	 */
	public function isBot() {
		$bots = array( 'Indy', 'Blaiz', 'Java', 'libwww-perl', 'Python', 'OutfoxBot', 'User-Agent', 'PycURL', 'AlphaServer', 'T8Abot', 'Syntryx', 'WinHttp', 'WebBandit', 'nicebot');
	 
		$isBot = false;
		
		foreach ( $bots as $bot ) {
			if ( strpos( $_SERVER['HTTP_USER_AGENT'], $bot ) !== false )
				$isBot = true;
		}
	 
		if ( empty($_SERVER['HTTP_USER_AGENT'] ) || $_SERVER['HTTP_USER_AGENT'] == ' ' )
			$isBot = true;
	 
		return $isBot;
	}
	
	/**
	 * Set the wp_mail_from_name
	 * 
	 * @since 1.7
	 */
	public function mail_header_from_name() {
		return $this->header_from_name;		
	}
	
	/**
	 * Set the wp_mail_from
	 * 
	 * @since 1.7
	 */
	public function mail_header_from() {
		return $this->header_from;		
	}
	
	/**
	 * Set the wp_mail_content_type
	 * 
	 * @since 1.7
	 */
	public function mail_header_content_type() {
		return $this->header_content_type;		
	}
}

/* On plugin activation, install the databases and add/update the DB version */
register_activation_hook( __FILE__, array( 'Visual_Form_Builder', 'install_db' ) );
?>
