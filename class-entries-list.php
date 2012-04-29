<?php

/* Include the wp_list_table class if running <WP 3.1 */
if( !class_exists( 'WP_List_Table' ) ) {
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

/**
 * Class that builds our Entries table
 * 
 * @since 1.2
 */
class VisualFormBuilder_Entries_List extends WP_List_Table {

	function __construct(){
		global $status, $page, $wpdb;
		
		/* Setup global database table names */
		$this->field_table_name = $wpdb->prefix . 'visual_form_builder_fields';
		$this->form_table_name = $wpdb->prefix . 'visual_form_builder_forms';
		$this->entries_table_name = $wpdb->prefix . 'visual_form_builder_entries';
		
		/* Set parent defaults */
		parent::__construct( array(
			'singular'  => 'entry',
			'plural'    => 'entries',
			'ajax'      => false
		) );
		
		/* Handle our bulk actions */
		$this->process_bulk_action();
	}

	/**
	 * Display column names. We'll handle the Form column separately.
	 * 
	 * @since 1.2
	 * @returns $item string Column name
	 */
	function column_default( $item, $column_name ){
		switch ( $column_name ) {
			case 'subject':
			case 'sender_name':
			case 'sender_email':
			case 'emails_to':
			case 'date':
			case 'ip_address':
				return $item[ $column_name ];
		}
	}
	
	/**
	 * Builds the on:hover links for the Form column
	 * 
	 * @since 1.2
	 */
	function column_form( $item ){
		 
		/* Build row actions */
		$actions = array(
			'view' => sprintf( '<a href="?page=%s&view=%s&action=%s&entry=%s" id="%4$s" class="view-entry">View</a>', $_REQUEST['page'], $_REQUEST['view'], 'view', $item['entry_id'] ),
			'delete' => sprintf( '<a href="?page=%s&view=%s&action=%s&entry=%s">Delete</a>', $_REQUEST['page'], $_REQUEST['view'], 'delete', $item['entry_id'] ),
		);
	
		return sprintf( '%1$s %2$s', $item['form'], $this->row_actions( $actions ) );
	}
	
	/**
	 * Used for checkboxes and bulk editing
	 * 
	 * @since 1.2
	 */
	function column_cb( $item ){
		return sprintf( '<input type="checkbox" name="%1$s[]" value="%2$s" />', $this->_args['singular'], $item['entry_id'] );
	}
	
	/**
	 * Builds the actual columns
	 * 
	 * @since 1.2
	 */
	function get_columns(){
		$columns = array(
			'cb' => '<input type="checkbox" />', //Render a checkbox instead of text
			'form' => __( 'Form' , 'visual-form-builder'),
			'subject' => __( 'Email Subject' , 'visual-form-builder'),
			'sender_name' => __( 'Sender Name' , 'visual-form-builder'),
			'sender_email' => __( 'Sender Email' , 'visual-form-builder'),
			'emails_to' => __( 'Emailed To' , 'visual-form-builder'),
			'ip_address' => __( 'IP Address' , 'visual-form-builder'),
			'date' => __( 'Date Submitted' , 'visual-form-builder')
		);
		
		return $columns;
	}
	
	/**
	 * A custom function to get the entries and sort them
	 * 
	 * @since 1.2
	 * @returns array() $cols SQL results
	 */
	function get_entries( $orderby = 'date', $order = 'ASC', $per_page, $offset = 0, $search = '' ){
		global $wpdb;
		
		/* Set OFFSET for pagination */
		$offset = ( $offset > 0 ) ? "OFFSET $offset" : '';
 		
 		switch ( $orderby ) {
			case 'date':
				$order_col = 'date_submitted';
			break;
			case 'form':
				$order_col = 'form_title';
			break;
			case 'subject':
			case 'ip_address':
			case 'sender_name':
			case 'sender_email':
				$order_col = $orderby;
			break;
		}
		
		$where = '';
		
		/* If the form filter dropdown is used */
		if ( $this->current_filter_action() )
			$where .= 'AND forms.form_id = ' . $this->current_filter_action();
		
		/* Get the month and year from the dropdown */
		$m = isset( $_REQUEST['m'] ) ? (int) $_REQUEST['m'] : 0;
		
		/* If a month/year has been selected, parse out the month/year and build the clause */
		if ( $m > 0 ) {
			$year = substr( $m, 0, 4 );
			$month = substr( $m, -2 );
			
			$where .= " AND YEAR(date_submitted) = $year AND MONTH(date_submitted) = $month";
		}

		$sql_order = sanitize_sql_orderby( "$order_col $order" );
		$query = "SELECT forms.form_title, entries.entries_id, entries.form_id, entries.subject, entries.sender_name, entries.sender_email, entries.emails_to, entries.date_submitted, entries.ip_address FROM $this->form_table_name AS forms INNER JOIN $this->entries_table_name AS entries ON entries.form_id = forms.form_id $where $search ORDER BY $sql_order LIMIT $per_page $offset";
		
		$cols = $wpdb->get_results( $query );
		
		return $cols;
	}
	
	/**
	 * Setup which columns are sortable. Default is by Date.
	 * 
	 * @since 1.2
	 * @returns array() $sortable_columns Sortable columns
	 */
	function get_sortable_columns() {		
		$sortable_columns = array(
			'form' => array( 'form', false ),
			'subject' => array( 'subject', false ),
			'sender_name' => array( 'sender_name', false ),
			'sender_email' => array( 'sender_email', false ),
			'date' => array( 'date', true )
		);
		
		return $sortable_columns;
	}
	
	/**
	 * Define our bulk actions
	 * 
	 * @since 1.2
	 * @returns array() $actions Bulk actions
	 */
	function get_bulk_actions() {
		$actions = array(
			'delete' => __( 'Delete' , 'visual-form-builder'),
			'export-all' => __( 'Export All' , 'visual-form-builder'),
			'export-selected' => __( 'Export Selected' , 'visual-form-builder')
		);
		
		return $actions;
	}
	
	/**
	 * Process our bulk actions
	 * 
	 * @since 1.2
	 */
	function process_bulk_action() {
		switch( $this->current_action() ) {
			case 'export-all' :
				$this->export_entries();
			break;
			
			case 'export-selected' :
				$entry_id = ( isset( $_REQUEST['entry'] ) && is_array( $_REQUEST['entry'] ) ) ? $_REQUEST['entry'] : array( $_REQUEST['entry'] );
				$this->export_entries( $entry_id );
			break;
			
			case 'delete' :
				$entry_id = ( isset( $_REQUEST['entry'] ) && is_array( $_REQUEST['entry'] ) ) ? $_REQUEST['entry'] : array( $_REQUEST['entry'] );
				
				global $wpdb;
					
				foreach ( $entry_id as $id ) {
					$id = absint( $id );
					$wpdb->query( "DELETE FROM $this->entries_table_name WHERE entries_id = $id" );
				}
			break;
		}
	}
	
	/**
	 * Handle the entries CSV export
	 * 
	 * @since 1.4
	 */
	function export_entries( $selected = NULL ) {		
		global $wpdb;
		
		/* Setup our query to accept selected entry IDs */	
		if ( is_array( $selected ) && !empty( $selected ) )
			$selected = " WHERE entries.entries_id IN (" . implode( ',', $selected ) . ")";
	
		$entries = $wpdb->get_results( "SELECT entries.*, forms.form_title FROM $this->entries_table_name AS entries JOIN $this->form_table_name AS forms USING(form_id) $selected ORDER BY entries_id DESC" );
		
		/* If there's entries returned, do our CSV stuff */
		if ( $entries ) :
			
			/* Setup our default columns */
			$cols = array(
				'entries_id' => array(
					'header' => __( 'Entries ID' , 'visual-form-builder'),
					'data' => array()
					),
				'form_title' => array(
					'header' => __( 'Form' , 'visual-form-builder'),
					'data' => array()
					),
				'date_submitted' => array(
					'header' => __( 'Date Submitted' , 'visual-form-builder'),
					'data' => array()
					),
				'ip_address' => array(
					'header' => __( 'IP Address' , 'visual-form-builder'),
					'data' => array()
					),
				'subject' => array(
					'header' => __( 'Email Subject' , 'visual-form-builder'),
					'data' => array()
					),
				'sender_name' => array(
					'header' => __( 'Sender Name' , 'visual-form-builder'),
					'data' => array()
					),
				'sender_email' => array(
					'header' => __( 'Sender Email' , 'visual-form-builder'),
					'data' => array()
					),
				'emails_to' => array(
					'header' => __( 'Emailed To' , 'visual-form-builder'),
					'data' => array()
					)
			);
			
			/* Initialize row index at 0 */
			$row = 0;
			
			/* Loop through all entries */
			foreach ( $entries as $entry ) {
				/* Loop through each entry and its fields */
				foreach ( $entry as $key => $value ) {
					/* Handle each column in the entries table */
					switch ( $key ) {
						case 'entries_id':
						case 'form_title':
						case 'date_submitted':
						case 'ip_address':
						case 'subject':
						case 'sender_name':
						case 'sender_email':
							$cols[ $key ][ 'data' ][ $row ] = $value;
						break;
						
						case 'emails_to':
							$cols[ $key ][ 'data' ][ $row ] = implode( ',', maybe_unserialize( $value ) );
						break;
						
						case 'data':
							/* Unserialize value only if it was serialized */
							$fields = maybe_unserialize( $value );
							
							/* Loop through our submitted data */
							foreach ( $fields as $field_key => $field_value ) {
								if ( !is_array( $field_value ) ) {

									/* Replace quotes for the header */
									$header = str_replace( '"', '""', ucwords( $field_key ) );

									/* Replace all spaces for each form field name */
									$field_key = preg_replace( '/(\s)/i', '', $field_key );
									
									/* Find new field names and make a new column with a header */
									if ( !array_key_exists( $field_key, $cols ) ) {
										$cols[ $field_key ] = array(
											'header' => $header,
											'data' => array()
											);									
									}
									
									/* Get rid of single quote entity */
									$field_value = str_replace( '&#039;', "'", $field_value );
									
									/* Load data, row by row */
									$cols[ $field_key ][ 'data' ][ $row ] = str_replace( '"', '""', stripslashes( html_entity_decode( $field_value ) ) );
								}
								else {
									/* Cast each array as an object */
									$obj = (object) $field_value;

									switch ( $obj->type ) {
										case 'fieldset' :
										case 'section' :
										case 'instructions' :
										case 'verification' :
										case 'secret' :
										case 'submit' :
										break;
										
										default :
											/* Replace quotes for the header */
											$header = str_replace( '"', '""', $obj->name );

											/* Find new field names and make a new column with a header */
											if ( !array_key_exists( $obj->name, $cols ) ) {
												
												$cols[$obj->name] = array(
													'header' => $header,
													'data' => array()
													);									
											}
											
											/* Get rid of single quote entity */
											$obj->value = str_replace( '&#039;', "'", $obj->value );
											
											/* Load data, row by row */
											$cols[ $obj->name ][ 'data' ][ $row ] = str_replace( '"', '""', stripslashes( html_entity_decode( $obj->value ) ) );

										break;
									}
								}
							}
						break;
					}
						
				}
				
				$row++;
			}
			
			/* Setup our CSV vars */
			$csv_headers = NULL;
			$csv_rows = array();
			
			/* Loop through each column */
			foreach ( $cols as $data ) {
				/* End our header row, if needed */
				if ( $csv_headers )
					$csv_headers .= ',';
				
				/* Build our headers */
				$csv_headers .= "{$data['header']}";
				
				/* Loop through each row of data and add to our CSV */
				for ( $i = 0; $i < $row; $i++ ) {
					/* End our row of data, if needed */
					if ( array_key_exists( $i, $csv_rows ) && !empty( $csv_rows[ $i ] ) )
						$csv_rows[ $i ] .= ',';
					elseif ( !array_key_exists( $i, $csv_rows ) )
						$csv_rows[ $i ] = '';
					
					/* Add a starting quote for this row's data */
					$csv_rows[ $i ] .= '"';
					
					/* If there's data at this point, add it to the row */
					if ( array_key_exists( $i, $data[ 'data' ] ) )
						$csv_rows[ $i ] .=  $data[ 'data' ][ $i ];
					
					/* Add a closing quote for this row's data */
					$csv_rows[ $i ] .= '"';				
				}			
			}
			
			/* Change our header so the browser spits out a CSV file to download */
			ob_start();
			header('Content-type: text/csv');
			header('Content-Disposition: attachment; filename="' . date( 'Y-m-d' ) . '-entries.csv"');
			ob_clean();
			
			/* Print headers for the CSV */
			echo $csv_headers . "\n";
			
			/* Print each row of data for the CSV */
			foreach ( $csv_rows as $row ) {
				echo $row . "\n";
			}
				
			die();
			
		endif;	
	}
	
	/**
	 * Adds our forms filter dropdown
	 * 
	 * @since 1.2
	 */
	function extra_tablenav( $which ) {
		global $wpdb;
		
		$cols = $wpdb->get_results( "SELECT DISTINCT forms.form_title, forms.form_id FROM $this->form_table_name AS forms ORDER BY forms.form_title ASC" );
		
		/* Only display the dropdown on the top of the table */
		if ( 'top' == $which ) {
			echo '<div class="alignleft actions">';
				$this->months_dropdown();
			echo '<select id="form-filter" name="form-filter">
				<option value="-1"' . selected( $this->current_filter_action(), -1 ) . '>' . __( 'View all forms' , 'visual-form-builder') . '</option>';
			
			foreach ( $cols as $form ) {
				echo '<option value="' . $form->form_id . '"' . selected( $this->current_filter_action(), $form->form_id ) . '>' . $form->form_title . '</option>';
			}
			
			echo '</select>
				<input type="submit" value="' . __( 'Filter' , 'visual-form-builder') . '" class="button-secondary" />
				</div>';
		}
	}
	
	/**
	 * Display Year/Month filter
	 * 
	 * @since 2.3.1
	 */
	function months_dropdown() {
		global $wpdb, $wp_locale;
		
	    $months = $wpdb->get_results( $wpdb->prepare( "
			SELECT DISTINCT YEAR( date_submitted ) AS year, MONTH( date_submitted ) AS month
			FROM $this->entries_table_name
			ORDER BY date_submitted DESC
		" ) );

		$month_count = count( $months );

		if ( !$month_count || ( 1 == $month_count && 0 == $months[0]->month ) )
			return;
		
		$m = isset( $_REQUEST['m'] ) ? (int) $_REQUEST['m'] : 0;
?>
		<select name='m'>
			<option<?php selected( $m, 0 ); ?> value='0'><?php _e( 'Show all dates' ); ?></option>
<?php
		foreach ( $months as $arc_row ) {
			if ( 0 == $arc_row->year )
				continue;
			
			$month = zeroise( $arc_row->month, 2 );
			$year = $arc_row->year;

			printf( "<option %s value='%s'>%s</option>\n",
				selected( $m, $year . $month, false ),
				esc_attr( $arc_row->year . $month ),
				sprintf( __( '%1$s %2$d' ), $wp_locale->get_month( $month ), $year )
			);
		}
?>
		</select>
<?php
	}
	
	/**
	 * Set our forms filter action
	 * 
	 * @since 1.2
	 * @returns int Form ID
	 */
	function current_filter_action() {
		if ( isset( $_REQUEST['form-filter'] ) && -1 != $_REQUEST['form-filter'] )
			return $_REQUEST['form-filter'];
	
		return false;
	}
	
	/**
	 * Display Search box
	 * 
	 * @since 1.4
	 * @returns html Search Form
	 */
	function search_box( $text, $input_id ) {
	    parent::search_box( $text, $input_id );
	}
	
	/**
	 * Prepares our data for display
	 * 
	 * @since 1.2
	 */
	function prepare_items() {
		global $wpdb;
		
		/* Get screen options from the wp_options table */
		$options = get_option( 'visual-form-builder-screen-options' );
		
		/* Get the date/time format that is saved in the options table */
		$date_format = get_option('date_format');
		$time_format = get_option('time_format');
		
		/* How many to show per page */
		$per_page = $options['per_page'];
		
		/* What page are we looking at? */
		$current_page = $this->get_pagenum();
		
		/* Use offset for pagination */
		$offset = ( $current_page - 1 ) * $per_page;
		
		/* Get column headers */
		$columns = $this->get_columns();
		$hidden = array();
		
		/* Get sortable columns */
		$sortable = $this->get_sortable_columns();
		
		/* Build the column headers */
		$this->_column_headers = array($columns, $hidden, $sortable);
		
		/* Get entries search terms */
		$search_terms = ( !empty( $_REQUEST['s'] ) ) ? explode( ' ', $_REQUEST['s'] ) : array();
		
		/* Loop through search terms and build query */
		foreach( $search_terms as $term ) {
			$term = esc_sql( like_escape( $term ) );
			
			$search .= "{$searchand}((entries.subject LIKE '%{$term}%') OR (entries.sender_name LIKE '%{$term}%') OR (entries.sender_email LIKE '%{$term}%') OR (entries.emails_to LIKE '%{$term}%') OR (entries.data LIKE '%{$term}%'))";
			$searchand = ' AND ';
		}
		
		$search = ( !empty($search) ) ? " AND ({$search}) " : '';
				
		/* Set our ORDER BY and ASC/DESC to sort the entries */
		$orderby = ( !empty( $_REQUEST['orderby'] ) ) ? $_REQUEST['orderby'] : 'date';
		$order = ( !empty( $_REQUEST['order'] ) ) ? $_REQUEST['order'] : 'desc';
		
		/* Get the sorted entries */
		$entries = $this->get_entries( $orderby, $order, $per_page, $offset, $search );
		
		$data = array();

		/* Loop trough the entries and setup the data to be displayed for each row */
		foreach ( $entries as $entry ) {
			$data[] = 
				array(
					'entry_id' => $entry->entries_id,
					'form' => stripslashes( $entry->form_title ),
					'subject' => stripslashes( $entry->subject ),
					'sender_name' => stripslashes( $entry->sender_name ),
					'sender_email' => stripslashes( $entry->sender_email ),
					'emails_to' => implode( ',', unserialize( stripslashes( $entry->emails_to ) ) ),
					'date' => date( "$date_format $time_format", strtotime( $entry->date_submitted ) ),
					'ip_address' => $entry->ip_address
			);
		}

		$where = '';
		
		/* If the form filter dropdown is used */
		if ( $this->current_filter_action() )
			$where .= 'AND form_id = ' . $this->current_filter_action();
		
		/* Get the month/year from the dropdown */
		$m = isset( $_REQUEST['m'] ) ? (int) $_REQUEST['m'] : 0;
		
		/* Parse month/year and build the clause */
		if ( $m > 0 ) {
			$year = substr( $m, 0, 4 );
			$month = substr( $m, -2 );
			
			$where .= " AND YEAR(date_submitted) = $year AND MONTH(date_submitted) = $month";
		}
		
		/* How many entries do we have? */
		$total_items = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $this->entries_table_name WHERE 1=1 $where" ) );

		/* Add sorted data to the items property */
		$this->items = $data;

		/* Register our pagination */
		$this->set_pagination_args( array(
			'total_items' => $total_items,
			'per_page'    => $per_page,
			'total_pages' => ceil( $total_items / $per_page )
		) );
	}
	
	/**
	 * Display the pagination.
	 * Customize default function to work with months and form drop down filters
	 *
	 * @since 3.1.0
	 * @access protected
	 */
	function pagination( $which ) {
		if ( empty( $this->_pagination_args ) )
			return;

		extract( $this->_pagination_args, EXTR_SKIP );

		$output = '<span class="displaying-num">' . sprintf( _n( '1 item', '%s items', $total_items ), number_format_i18n( $total_items ) ) . '</span>';

		$current = $this->get_pagenum();

		$current_url = ( is_ssl() ? 'https://' : 'http://' ) . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];

		$current_url = remove_query_arg( array( 'hotkeys_highlight_last', 'hotkeys_highlight_first' ), $current_url );

		$page_links = array();
		
		/* Added to pick up the months dropdown */
		$m = isset( $_REQUEST['m'] ) ? (int) $_REQUEST['m'] : 0;
		
		$disable_first = $disable_last = '';
		if ( $current == 1 )
			$disable_first = ' disabled';
		if ( $current == $total_pages )
			$disable_last = ' disabled';

		$page_links[] = sprintf( "<a class='%s' title='%s' href='%s'>%s</a>",
			'first-page' . $disable_first,
			esc_attr__( 'Go to the first page' ),
			esc_url( remove_query_arg( 'paged', $current_url ) ),
			'&laquo;'
		);
		
		/* Modified the add_query_args to include my custom dropdowns */
		$page_links[] = sprintf( "<a class='%s' title='%s' href='%s'>%s</a>",
			'prev-page' . $disable_first,
			esc_attr__( 'Go to the previous page' ),
			esc_url( add_query_arg( array( 'paged' => max( 1, $current-1 ), 'm' => $m, 'form-filter' => $this->current_filter_action() ), $current_url ) ),
			'&lsaquo;'
		);

		if ( 'bottom' == $which )
			$html_current_page = $current;
		else
			$html_current_page = sprintf( "<input class='current-page' title='%s' type='text' name='paged' value='%s' size='%d' />",
				esc_attr__( 'Current page' ),
				$current,
				strlen( $total_pages )
			);

		$html_total_pages = sprintf( "<span class='total-pages'>%s</span>", number_format_i18n( $total_pages ) );
		$page_links[] = '<span class="paging-input">' . sprintf( _x( '%1$s of %2$s', 'paging' ), $html_current_page, $html_total_pages ) . '</span>';

		$page_links[] = sprintf( "<a class='%s' title='%s' href='%s'>%s</a>",
			'next-page' . $disable_last,
			esc_attr__( 'Go to the next page' ),
			esc_url( add_query_arg( array( 'paged' => min( $total_pages, $current+1 ), 'm' => $m, 'form-filter' => $this->current_filter_action() ), $current_url ) ),
			'&rsaquo;'
		);
		
		/* Modified the add_query_args to include my custom dropdowns */
		$page_links[] = sprintf( "<a class='%s' title='%s' href='%s'>%s</a>",
			'last-page' . $disable_last,
			esc_attr__( 'Go to the last page' ),
			esc_url( add_query_arg( array( 'paged' => $total_pages, 'm' => $m, 'form-filter' => $this->current_filter_action() ), $current_url ) ),
			'&raquo;'
		);

		$pagination_links_class = 'pagination-links';
		if ( ! empty( $infinite_scroll ) )
			$pagination_links_class = ' hide-if-js';
		$output .= "\n<span class='$pagination_links_class'>" . join( "\n", $page_links ) . '</span>';

		if ( $total_pages )
			$page_class = $total_pages < 2 ? ' one-page' : '';
		else
			$page_class = ' no-pages';

		$this->_pagination = "<div class='tablenav-pages{$page_class}'>$output</div>";

		echo $this->_pagination;
	}
}
?>