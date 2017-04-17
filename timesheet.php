<?php
/*
Plugin Name: Timesheet by BestWebSoft
Plugin URI: https://bestwebsoft.com/products/wordpress/plugins/timesheet/
Description: Best timesheet plugin for WordPress. Track employee time, streamline attendance and generate reports.
Author: BestWebSoft
Text Domain: timesheet
Domain Path: /languages
Version: 0.1.5
Author URI: https://bestwebsoft.com/
License: Proprietary
*/

/*  © Copyright 2017  BestWebSoft  ( https://support.bestwebsoft.com )

	This program is free software; you can redistribute it and/or modify
	it under the terms of the GNU General Public License, version 2, as
	published by the Free Software Foundation.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with this program; if not, write to the Free Software
	Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

if ( ! function_exists( 'tmsht_admin_menu' ) ) {
	function tmsht_admin_menu() {
		global $tmsht_options, $tmsht_current_user;

		bws_general_menu();

		$settings_page_hook = add_submenu_page( 'bws_panel', 'Timesheet', 'Timesheet', 'manage_options', 'timesheet_settings', 'tmsht_settings_page' );
		add_action( 'load-' . $settings_page_hook, 'tmsht_add_tabs' );

		if ( ! $tmsht_options ) {
			tmsht_register_options();
		}

		$tmsht_current_user = wp_get_current_user();

		if ( isset( $tmsht_options['display_pages']['ts_user']['user_roles'] ) ) {
			$display_timesheet_page = false;

			if ( is_multisite() && is_super_admin( $tmsht_current_user->ID ) ) {
				if ( in_array( 'administrator', $tmsht_options['display_pages']['ts_user']['user_roles'] ) ) {
					$display_timesheet_page = true;
				}
			} else {
				foreach ( $tmsht_current_user->caps as $role => $value ) {
					if ( in_array( $role, $tmsht_options['display_pages']['ts_user']['user_roles'] ) ) {
						$display_timesheet_page = true;
						break;
					}
				}
			}

			if ( $display_timesheet_page ) {
				$ts_user_page_hook = add_menu_page( 'Timesheet', 'Timesheet', 'read', 'timesheet_ts_user', 'tmsht_ts_user_page', 'dashicons-clock' );
				add_action( 'load-' . $ts_user_page_hook, 'tmsht_add_tabs' );
			}
		}

		if ( isset( $tmsht_options['display_pages']['ts_report']['user_roles'] ) ) {
			$display_report_page = false;

			if ( is_multisite() && is_super_admin( $tmsht_current_user->ID ) ) {
				if ( in_array( 'administrator', $tmsht_options['display_pages']['ts_user']['user_roles'] ) ) {
					$display_report_page = true;
				}
			} else {
				foreach ( $tmsht_current_user->caps as $role => $value ) {
					if ( in_array( $role, $tmsht_options['display_pages']['ts_report']['user_roles'] ) ) {
						$display_report_page = true;
						break;
					}
				}
			}

			if ( $display_report_page ) {
				if ( isset( $ts_user_page_hook ) ) {
					$ts_report_page_hook = add_submenu_page( 'timesheet_ts_user', __( 'Report', 'timesheet' ), __( 'Report', 'timesheet' ), 'read', 'timesheet_ts_report', 'tmsht_ts_report_page' );
				} else {
					$ts_report_page_hook = add_menu_page( 'Timesheet ' . __( 'Report', 'timesheet' ), 'Timesheet ' . __( 'Report', 'timesheet' ), 'read', 'timesheet_ts_report', 'tmsht_ts_report_page', 'dashicons-clock' );
				}
				add_action( 'load-' . $ts_report_page_hook, 'tmsht_add_tabs' );
			}
		}
	}
}

/* add help tab  */
if ( ! function_exists( 'tmsht_add_tabs' ) ) {
	function tmsht_add_tabs() {
		$screen = get_current_screen();
		$args = array(
			'id' 			=> 'tmsht',
			'section' 		=> '202101246'
		);
		bws_help_tab( $screen, $args );
	}
}

if ( ! function_exists( 'tmsht_plugins_loaded' ) ) {
	function tmsht_plugins_loaded() {
		/* Internationalization */
		load_plugin_textdomain( 'timesheet', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
	}
}

if ( ! function_exists( 'tmsht_init' ) ) {
	function tmsht_init() {
		global $tmsht_plugin_info;

		$plugin_basename = plugin_basename( __FILE__ );

		require_once( dirname( __FILE__ ) . '/bws_menu/bws_include.php' );
		bws_include_init( $plugin_basename );

		if ( empty( $tmsht_plugin_info ) ) {
			if ( ! function_exists( 'get_plugin_data' ) ) {
				require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
			}
			$tmsht_plugin_info = get_plugin_data( __FILE__ );
		}

		/* check WordPress version */
		bws_wp_min_version_check( $plugin_basename, $tmsht_plugin_info, '3.8' );

		$tmsht_plugin_pages = array(
			'timesheet_settings',
			'timesheet_ts_user',
			'timesheet_ts_report'
		);

		if ( isset( $_GET['page'] ) && in_array( $_GET['page'], $tmsht_plugin_pages ) ) {
			tmsht_register_options();
		}
	}
}

if ( ! function_exists( 'tmsht_admin_init' ) ) {
	function tmsht_admin_init() {
		global $bws_plugin_info, $tmsht_plugin_info;
		/* Add variable for bws_menu */
		if ( empty( $bws_plugin_info ) )
			$bws_plugin_info = array( 'id' => '606', 'version' => $tmsht_plugin_info["Version"] );
	}
}

if ( ! function_exists( 'tmsht_create_tables' ) ) {
	function tmsht_create_tables() {
		global $wpdb;

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		/* Table with legends */
		if ( ! $wpdb->query( "SHOW TABLES LIKE '{$wpdb->prefix}tmsht_legends';" ) ) {
			$sql = "CREATE TABLE `{$wpdb->prefix}tmsht_legends` (
				`id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
				`name` varchar(255) NOT NULL,
				`color` char(7) NOT NULL,
				`disabled` BOOLEAN NOT NULL DEFAULT '0',
				PRIMARY KEY  ( `id` )
				) ENGINE=InnoDB DEFAULT CHARSET=utf8;";
			dbDelta( $sql );

			$default_legends = array(
				array(
					'id'       => 1,
					'name'     => __( 'Work In Office', 'timesheet' ),
					'color'    => '#94e091',
					'disabled' => 0
				),
				array(
					'id'       => 2,
					'name'     => __( 'Work Remotely', 'timesheet' ),
					'color'    => '#eded76',
					'disabled' => 0
				),
				array(
					'id'       => 3,
					'name'     => __( 'Will be absent', 'timesheet' ),
					'color'    => '#dd8989',
					'disabled' => 0
				),
				array(
					'id'       => 4,
					'name'     => __( 'Vacation', 'timesheet' ),
					'color'    => '#8da6bf',
					'disabled' => 0
				),
			);

			foreach ( $default_legends as $legend ) {
				$wpdb->insert(
					"{$wpdb->prefix}tmsht_legends",
					array(
						'id' 		=> $legend['id'],
						'name' 		=> $legend['name'],
						'color' 	=> $legend['color'],
						'disabled' 	=> $legend['disabled']
					),
					array( '%d', '%s', '%s', '%d' )
				);
			}
		}
		/* Table with ts */
		if ( ! $wpdb->query( "SHOW TABLES LIKE '{$wpdb->prefix}tmsht_ts';" ) ) {
			$sql = "CREATE TABLE `{$wpdb->prefix}tmsht_ts` (
				`id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
				`user_id` bigint(20) NOT NULL,
				`time_from` datetime NOT NULL,
				`time_to` datetime NOT NULL,
				`legend_id` int(10) NOT NULL,
				PRIMARY KEY  ( `id` )
				) ENGINE=InnoDB DEFAULT CHARSET=utf8;";
			dbDelta( $sql );
		}
	}
}

if ( ! function_exists( 'tmsht_register_options' ) ) {
	function tmsht_register_options() {
		global $tmsht_plugin_info, $tmsht_options;

		$update_option = false;
		$db_version = '0.1';

		if ( ! get_option( 'tmsht_options' ) ) {
			$default_options = tmsht_get_default_options();
			add_option( 'tmsht_options', $default_options );
		}

		$tmsht_options = get_option( 'tmsht_options' );

		/* Array merge incase this version has added new options */
		if ( ! isset( $tmsht_options['plugin_option_version'] ) || $tmsht_options['plugin_option_version'] != $tmsht_plugin_info["Version"] ) {

			$default_options = tmsht_get_default_options();

			/* START Remove after 01.03.2017 */
			if ( ! isset( $tmsht_options['display_pages']['ts_user']['user_roles'] ) || ! isset( $tmsht_options['display_pages']['ts_report']['user_roles'] ) ) {
				$tmsht_options['display_pages'] = array(
					'ts_user' => array(
						'user_roles'        => isset( $tmsht_options['display_pages']['ts_user']['user_roles'] ) ? $tmsht_options['display_pages']['ts_user']['user_roles'] : $default_options['display_pages']['ts_user']['user_roles'],
						'user_ids'          => array(),
						'has_sub_exception' => array()
					),
					'ts_report' => array(
						'user_roles'        => isset( $tmsht_options['display_pages']['ts_report']['user_roles'] ) ? $tmsht_options['display_pages']['ts_report']['user_roles'] : $default_options['display_pages']['ts_report']['user_roles'],
						'user_ids'          => array(),
						'has_sub_exception' => array()
					)
				);
			}
			/* END Remove after 01.03.2017 */

			$tmsht_options = array_merge( $default_options, $tmsht_options );

			$tmsht_options['plugin_option_version'] = $tmsht_plugin_info["Version"];
			$update_option = true;
		}

		/* Update tables when update plugin and tables changes*/
		if ( ! isset( $tmsht_options['plugin_db_version'] ) || $tmsht_options['plugin_db_version'] != $db_version ) {
			tmsht_create_tables();

			/* update DB version */
			$tmsht_options['plugin_db_version'] = $db_version;
			$update_option = true;
		}

		if ( $update_option ) {
			update_option( 'tmsht_options', $tmsht_options );
		}
	}
}

if ( ! function_exists( 'tmsht_get_default_options' ) ) {
	function tmsht_get_default_options() {
		global $tmsht_plugin_info;

		if ( ! function_exists( 'get_editable_roles' ) )
			require_once( ABSPATH . 'wp-admin/includes/user.php' );

		$user_roles = array_keys( get_editable_roles() );

		$default_options = array(
			'plugin_option_version'	  => $tmsht_plugin_info["Version"],
			'ts_timeline_from'        => 0,
			'ts_timeline_to'          => 24,
			'weekends'                => array( 'sat', 'sun' ),
			'edit_past_days'          => 0,
			'date_format_type'        => 'wp',
			'date_format'             => get_option( 'date_format' ),
			'display_pages'           => array(
				'ts_user' => array(
					'user_roles'        => $user_roles,
					'user_ids'          => array(),
					'has_sub_exception' => array()
				),
				'ts_report' => array(
					'user_roles'        => array( 'administrator' ),
					'user_ids'          => array(),
					'has_sub_exception' => array()
				)
			),
			'display_settings_notice' => 1,
			'suggest_feature_banner'  => 1,
			'reminder_on_email'       => false,
			'day_reminder'            => 'fri',
			'time_reminder'           => '18:00',
			'content_reminder'        => array(
				'subject'	=> __( 'Timesheet Reminder', 'timesheet' ),
				'message'	=> sprintf( "%s, {user_name},\n\n%s:\n\n{list_days}\n\n{{ts_page_link}%s{/ts_page_link}}\n\n%s", __( 'Hi', 'timesheet' ), __( 'Please complete your timesheet for the following days', 'timesheet' ), __( 'Complete Timesheet Now', 'timesheet' ), __( 'Do not reply to this message. This is an automatic mailing.', 'timesheet' ) )
			),
			'first_install'           => strtotime( "now" )
		);
		return $default_options;
	}
}

if ( ! function_exists( 'tmsht_admin_scripts_styles' ) ) {
	function tmsht_admin_scripts_styles() {
		global $tmsht_plugin_info;

		if ( isset( $_GET['page'] ) ) {

			if ( $_GET['page'] == 'timesheet_settings' ) {
				wp_enqueue_script( 'ts_settings_script', plugins_url( 'js/settings.js', __FILE__ ), array( 'jquery', 'jquery-ui-slider', 'wp-color-picker' ), $tmsht_plugin_info['Version'] );
				wp_enqueue_style( 'wp-color-picker' );
				wp_enqueue_style( 'jquery-ui', plugins_url( 'css/jquery-ui.css', __FILE__ ), false );
				wp_enqueue_style( 'ts_settings_styles', plugins_url( 'css/settings.css', __FILE__ ), false, $tmsht_plugin_info['Version'] );
			}

			$locale = explode( '_', get_locale() );
			$datetime_options = array(
				'locale'         => $locale[0],
				'dayOfWeekStart' => get_option( 'start_of_week' )
			);

			if ( $_GET['page'] == 'timesheet_ts_user' ) {
				wp_register_script( 'tmsht_datetimepicker_script', plugins_url( 'js/jquery.datetimepicker.full.min.js', __FILE__ ), array( 'jquery' ) );
				wp_enqueue_script( 'ts_user_script', plugins_url( 'js/ts_user_script.js', __FILE__ ), array( 'jquery', 'jquery-ui-selectable', 'jquery-touch-punch', 'tmsht_datetimepicker_script' ), $tmsht_plugin_info['Version'] );
				wp_localize_script( 'ts_user_script', 'tmsht_datetime_options', $datetime_options );
				wp_enqueue_style( 'ts_user_styles', plugins_url( 'css/ts_user_styles.css', __FILE__ ), false, $tmsht_plugin_info['Version'] );
				wp_enqueue_style( 'tmsht_datetimepicker_styles', plugins_url( 'css/jquery.datetimepicker.css', __FILE__ ), false );
			}

			if ( $_GET['page'] == 'timesheet_ts_report' ) {
				wp_register_script( 'tmsht_datetimepicker_script', plugins_url( 'js/jquery.datetimepicker.full.min.js', __FILE__ ), array( 'jquery' ) );
				wp_enqueue_script( 'ts_report_script', plugins_url( 'js/ts_report_script.js', __FILE__ ), array( 'jquery', 'tmsht_datetimepicker_script' ), $tmsht_plugin_info['Version'] );
				wp_localize_script( 'ts_report_script', 'tmsht_datetime_options', $datetime_options );
				wp_enqueue_style( 'ts_report_styles', plugins_url( 'css/ts_report_styles.css', __FILE__ ), false, $tmsht_plugin_info['Version'] );
				wp_enqueue_style( 'tmsht_datetimepicker_styles', plugins_url( 'css/jquery.datetimepicker.css', __FILE__ ), false );
			}
		}
	}
}

if ( ! function_exists( 'tmsht_generate_color' ) ) {
	function tmsht_generate_color() {
		global $wpdb;

		$get_legends = $wpdb->get_results( "SELECT `color` FROM `{$wpdb->prefix}tmsht_legends`", ARRAY_A );
		$wrong_colors = array( '#ffffff', '#f9f9f9' );

		foreach ( $get_legends as $legend ) {
			$wrong_colors[] = $legend['color'];
		}

		while (1) {
			$color = sprintf( "#%06x", rand( 0,16777215 ) );
			if ( ! in_array( $color, $wrong_colors ) ) {
				break;
			}
		}

		return $color;
	}
}

if ( ! function_exists( 'tmsht_settings_page' ) ) {
	function tmsht_settings_page() {
		global $wpdb, $tmsht_options, $tmsht_plugin_info, $wp_version;

		$message = $error = "";
		$plugin_basename = plugin_basename( __FILE__ );
		$days_arr = array( 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'	);
		$date_formats = array(
			'wp'     => get_option( 'date_format' ),
			'custom' => $tmsht_options['date_format']
		);
		$all_roles = get_editable_roles();
		ksort( $all_roles );

		if ( ( isset( $_POST['tmsht_save_settings'] ) || isset( $_POST['tmsht_add_ts_legend'] ) ) && check_admin_referer( 'tmsht_nonce_save_settings', 'tmsht_nonce_name' ) ) {

			if ( isset( $_POST['tmsht_add_ts_legend'] ) ) {

				$ts_legend_name = ( isset( $_POST['tmsht_add_ts_legend_name'] ) ) ? esc_html( trim( $_POST['tmsht_add_ts_legend_name'] ) ) : '';
				$ts_legend_color = ( isset( $_POST['tmsht_add_ts_legend_color'] ) ) ? esc_html( trim( $_POST['tmsht_add_ts_legend_color'] ) ) : '';

				if ( empty( $ts_legend_name ) ) {
					$error = __( 'Please, input status name', 'timesheet' );
				}

				if ( ! preg_match( '/^#?([a-f0-9]{6}|[a-f0-9]{3})$/', $ts_legend_color ) ) {
					if ( $error != "" ) {
						$error .= '<br>' . __( 'Please, choose status color', 'timesheet' );
					} else {
						$error .= __( 'Please, choose status color', 'timesheet' );
					}
				}

				if ( $error == "" && $wpdb->get_results( "SELECT `id` FROM `{$wpdb->prefix}tmsht_legends` WHERE `name` = '$ts_legend_name' OR `color` = '$ts_legend_color'" ) ) {
					$error = sprintf( __( 'Status with name "%s" or with color %s already exists', 'timesheet' ), $ts_legend_name, $ts_legend_color );
				}

				if ( $error == "" && in_array( $ts_legend_color, array( '#ffffff', '#f9f9f9' ) ) ) {
					$error = sprintf( __( 'The status with the color %s can not be saved. Please choose a different color.', 'timesheet' ), $ts_legend_color );
				}

				if ( $error == "" ) {
					$tmsht_query = $wpdb->insert(
						"{$wpdb->prefix}tmsht_legends",
						array( 'name' => $ts_legend_name,
							'color' => $ts_legend_color ),
						array( '%s', '%s' )
					);

					if ( $tmsht_query ) {
						unset( $ts_legend_name, $ts_legend_color );
						$message = __( 'Status has been successfully added', 'timesheet' );
					} else {
						$error = __( 'Status has not been added', 'timesheet' );
					}
				} else {
					$error .= '<br>' . __( 'Status has not been added', 'timesheet' );
				}
			}

			if ( isset( $_POST['tmsht_save_settings'] ) ) {
				$default_options = tmsht_get_default_options();

				if ( isset( $_POST['bws_hide_premium_options'] ) ) {
					$hide_result = bws_hide_premium_options( $tmsht_options );
					$tmsht_options = $hide_result['options'];
				}

				/* Set timeline */
				$tmsht_options['ts_timeline_from'] = ( isset( $_POST['tmsht_ts_timeline_from'] ) && $_POST['tmsht_ts_timeline_from'] >= 0 && $_POST['tmsht_ts_timeline_from'] <= 23 ) ? intval( $_POST['tmsht_ts_timeline_from'] ) : 0;
				$tmsht_options['ts_timeline_to'] = ( isset( $_POST['tmsht_ts_timeline_to'] ) && $_POST['tmsht_ts_timeline_to'] <= 24 && $_POST['tmsht_ts_timeline_to'] >= 1 ) ? intval( $_POST['tmsht_ts_timeline_to'] ) : 24;

				if ( $tmsht_options['ts_timeline_from'] == $tmsht_options['ts_timeline_to'] ) {
					if ( $tmsht_options['ts_timeline_to'] + 1 <= 24 ) {
						$tmsht_options['ts_timeline_to']++;
					} else {
						$tmsht_options['ts_timeline_from']--;
					}
				}

				/* Set weekends */
				$tmsht_weekends = ( isset( $_POST['tmsht_weekends'] ) ) ? $_POST['tmsht_weekends'] : false;

				if ( is_array( $tmsht_weekends ) ) {
					foreach ( $tmsht_weekends as $tmsht_weekend ) {
						if ( ! in_array( ucfirst( $tmsht_weekend ), $days_arr ) ) {
							unset( $tmsht_weekend );
						}
						$tmsht_options['weekends'] = $tmsht_weekends;
					}
				} else {
					$tmsht_options['weekends'] = array();
				}

				/* Enable/disable legends */
				$ts_legend_ids = ( isset( $_POST['tmsht_ts_legend_id'] ) && is_array( $_POST['tmsht_ts_legend_id'] ) ) ? $_POST['tmsht_ts_legend_id'] : array();
				$ts_legend_ids_hidden = ( isset( $_POST['tmsht_ts_legend_id_hidden'] ) && is_array( $_POST['tmsht_ts_legend_id_hidden'] ) ) ? $_POST['tmsht_ts_legend_id_hidden'] : array();

				foreach ( $ts_legend_ids_hidden as $legend_id ) {
					$color = ( isset( $_POST['tmsht_ts_legend_color'][ $legend_id ] ) && preg_match( '/^#?([a-f0-9]{6}|[a-f0-9]{3})$/', trim( $_POST['tmsht_ts_legend_color'][ $legend_id ] ) ) ) ? trim( $_POST['tmsht_ts_legend_color'][ $legend_id ] ) : false;
					$disabled = ( ! in_array( $legend_id, $ts_legend_ids ) ) ? 1 : 0;

					if ( $color ) {
						$wpdb->update( $wpdb->prefix . "tmsht_legends",
							array(
								'color' 	=> $color,
								'disabled'	=> $disabled
							),
							array( 'id' => $legend_id ),
							array( '%s', '%d' )
						);
					} else {
						$wpdb->update( $wpdb->prefix . "tmsht_legends",
							array(
								'disabled'	=> $disabled
							),
							array( 'id' => $legend_id ),
							array( '%d' )
						);
					}
				}

				/* Set date format */
				if ( isset( $_POST['tmsht_date_format_type'] ) ) {
					switch ( $_POST['tmsht_date_format_type'] ) {
						case 'custom':
							$tmsht_options['date_format_type'] = $_POST['tmsht_date_format_type'];
							$tmsht_options['date_format'] = ( isset( $_POST['tmsht_date_format_code'] ) ) ? esc_html( trim( $_POST['tmsht_date_format_code'] ) ) : '';
							break;
						case 'wp':
							$tmsht_options['date_format_type'] = $_POST['tmsht_date_format_type'];
							$tmsht_options['date_format'] = get_option( 'date_format' );
							break;
						default:
							break;
					}
				}

				$tmsht_options['edit_past_days'] = ( isset( $_POST['tmsht_edit_past_days'] ) && $_POST['tmsht_edit_past_days'] == 1 ) ? 1 : 0;

				/* Display TS user page for */
				$display_ts_user_page_for = ( isset( $_POST['tmsht_display_ts_user_page_for'] ) && is_array( $_POST['tmsht_display_ts_user_page_for'] ) ) ? $_POST['tmsht_display_ts_user_page_for'] : array();
				$tmsht_ts_user_roles = array();
				foreach ( $display_ts_user_page_for as $role ) {
					if ( array_key_exists( $role, $all_roles ) ) {
						$tmsht_ts_user_roles[] = $role;
					}
				}

				$tmsht_options['display_pages']['ts_user']['user_roles'] = $tmsht_ts_user_roles;

				/* Display TS report page for */
				$display_ts_report_page_for = ( isset( $_POST['tmsht_display_ts_report_page_for'] ) && is_array( $_POST['tmsht_display_ts_report_page_for'] ) ) ? $_POST['tmsht_display_ts_report_page_for'] : array();
				$ts_report_roles = array();
				foreach ( $display_ts_report_page_for as $role ) {
					if ( array_key_exists( $role, $all_roles ) ) {
						$ts_report_roles[] = $role;
					}
				}

				$tmsht_options['display_pages']['ts_report']['user_roles'] = $ts_report_roles;

				$tmsht_options['reminder_on_email'] = ( isset( $_POST['tmsht_reminder_on_email'] ) && $_POST['tmsht_reminder_on_email'] == 1 );
				$tmsht_options['day_reminder'] = ( isset( $_POST['tmsht_day_reminder'] ) && in_array( ucfirst( $_POST['tmsht_day_reminder'] ), $days_arr ) ) ? $_POST['tmsht_day_reminder'] : $default_options['day_reminder'];
				$tmsht_options['time_reminder'] = ( isset( $_POST['tmsht_time_reminder'] ) && preg_match( '/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/', $_POST['tmsht_time_reminder'] ) ) ? $_POST['tmsht_time_reminder'] : $default_options['time_reminder'];

				if ( isset( $_POST['tmsht_reminder_change_state'] ) && $_POST['tmsht_reminder_change_state'] == 1 ) {

					wp_clear_scheduled_hook( 'tmsht_reminder_to_email' );

					if ( $tmsht_options['reminder_on_email'] ) {

						if ( ! wp_next_scheduled( 'tmsht_reminder_to_email' ) ) {
							$current_offset = get_option( 'gmt_offset' );
							$tzstring = get_option( 'timezone_string' );

							/* Remove old Etc mappings. Fallback to gmt_offset. */
							if ( false !== strpos( $tzstring,'Etc/GMT' ) ) {
								$tzstring = '';
							}

							/* Create a UTC+- zone if no timezone string exists */
							if ( empty( $tzstring ) ) {
								if ( 0 == $current_offset ) {
									$tzstring = '+0';
								} elseif ( $current_offset < 0 ) {
									$tzstring = $current_offset;
								} else {
									$tzstring = '+' . $current_offset;
								}
							}

							$time = strtotime( sprintf( "%s %s:00 %s", ucfirst( $tmsht_options['day_reminder'] ), $tmsht_options['time_reminder'], $tzstring ) );

							if ( time() > $time ) {
								$time = strtotime( sprintf( "%s %s:00 %s +1 week", ucfirst( $tmsht_options['day_reminder'] ), $tmsht_options['time_reminder'], $tzstring ) );
							}

							wp_schedule_event( $time, 'tmsht_weekly', 'tmsht_reminder_to_email');
						}
					}
				}

				$tmsht_options['content_reminder']['subject'] = ( isset( $_POST['tmsht_reminder_subject'] ) ) ? wp_strip_all_tags( $_POST['tmsht_reminder_subject'] ) : $default_options['content_reminder']['subject'];
				$tmsht_options['content_reminder']['message'] = ( isset( $_POST['tmsht_reminder_message'] ) ) ? wp_strip_all_tags( $_POST['tmsht_reminder_message'] ) : $default_options['content_reminder']['message'];

				/* Save settings if no errors */
				if ( "" == $error ) {
					update_option( 'tmsht_options', $tmsht_options );
					$message = __( 'Settings saved', 'timesheet' );
				}
			}
		}

		$bws_hide_premium_options_check = bws_hide_premium_options_check( $tmsht_options );

		if ( isset( $_REQUEST['bws_restore_confirm'] ) && check_admin_referer( $plugin_basename, 'bws_settings_nonce_name' ) ) {
			$tmsht_options = tmsht_get_default_options();
			update_option( 'tmsht_options', $tmsht_options );
			$wpdb->query( "UPDATE `{$wpdb->prefix}tmsht_legends` SET `disabled` = 1 WHERE `id` NOT IN (1,2,3,4)" );
			$message =  __( 'All plugin settings were restored.', 'timesheet' );
		}

		$legends = $wpdb->get_results( "SELECT * FROM `{$wpdb->prefix}tmsht_legends`", ARRAY_A );

		/* GO PRO */
		if ( isset( $_GET['action'] ) && 'go_pro' == $_GET['action'] ) {
			$go_pro_result = bws_go_pro_tab_check( $plugin_basename, 'tmsht_options' );
			if ( ! empty( $go_pro_result['error'] ) )
				$error = $go_pro_result['error'];
			elseif ( ! empty( $go_pro_result['message'] ) )
				$message = $go_pro_result['message'];
		} ?>
		<div class="wrap tmsht_wrap">
			<h1 style="line-height: normal;"><?php _e( 'Timesheet Settings', 'timesheet' ); ?></h1>
			<h2 class="nav-tab-wrapper">
				<a class="nav-tab<?php if ( ! isset( $_GET['action'] ) ) echo ' nav-tab-active'; ?>"  href="admin.php?page=timesheet_settings"><?php _e( 'Settings', 'timesheet' ); ?></a>
				<a class="nav-tab bws_go_pro_tab<?php if ( isset( $_GET['action'] ) && 'go_pro' == $_GET['action'] ) echo ' nav-tab-active'; ?>" href="admin.php?page=timesheet_settings&amp;action=go_pro"><?php _e( 'Go PRO', 'timesheet' ); ?></a>
			</h2>
			<noscript>
				<div class="error below-h2">
					<p><strong><?php _e( 'WARNING', 'timesheet' ); ?>:</strong> <?php _e( 'The plugin works correctly only if JavaScript is enabled.', 'timesheet' ); ?></p>
				</div>
			</noscript>
			<div class="updated fade below-h2" <?php if ( "" == $message ) echo 'style="display:none"'; ?>><p><strong><?php echo $message; ?></strong></p></div>
			<div class="error below-h2" <?php if ( "" == $error ) echo 'style="display:none"'; ?>><p><strong><?php echo $error; ?></strong></p></div>
			<?php if ( ! empty( $hide_result['message'] ) ) { ?>
				<div class="updated fade below-h2"><p><strong><?php echo $hide_result['message']; ?></strong></p></div>
			<?php }
			bws_show_settings_notice();
			if ( ! isset( $_GET['action'] ) ) {
				if ( isset( $_REQUEST['bws_restore_default'] ) && check_admin_referer( $plugin_basename, 'bws_settings_nonce_name' ) ) {
					bws_form_restore_default_confirm( $plugin_basename );
				} else { ?>
					<form class="bws_form" method="post" action="">
						<table id="tmsht_settings_table" class="form-table">
							<tr class="tmsht_settings_table_tr">
								<th><?php _e( 'Timeline', 'timesheet' ); ?></th>
								<td>
									<div id="tmsht_timeline_settings_wrap">
										<div id="tmsht_timeline_settings"><?php _ex( 'from', 'timeline', 'timesheet' ); ?> <input id="tmsht_ts_timeline_from" type="number" name="tmsht_ts_timeline_from" value="<?php echo $tmsht_options['ts_timeline_from']; ?>" maxlength="2" min="0" max="23"> <?php _ex( 'to', 'timeline', 'timesheet' ); ?> <input id="tmsht_ts_timeline_to" type="number" name="tmsht_ts_timeline_to" value="<?php echo $tmsht_options['ts_timeline_to']; ?>" maxlength="2" min="1" max="24"> <?php _ex( 'hours', 'timeline', 'timesheet' ); ?></div>
										<div id="tmsht_timeline_slider_wrap">
											<div id="tmsht_timeline_slider"></div>
										</div>
									</div>
								</td>
							</tr>
							<tr class="tmsht_settings_table_tr">
								<th><?php _e( 'Weekends', 'timesheet' ); ?></th>
								<td>
									<fieldset>
										<?php foreach ( $days_arr as $day ) { ?>
											<label class="tmsht_label_weekends">
												<input type="checkbox" name="tmsht_weekends[]" value="<?php echo strtolower( $day ); ?>" <?php if ( in_array( strtolower( $day ), $tmsht_options['weekends'] ) ) echo 'checked="checked"'; ?>>
												<?php _e( $day ); ?>
											</label>
										<?php } ?>
									</fieldset>
								</td>
							</tr>
							<tr class="tmsht_settings_table_tr">
								<th><?php _e( 'Statuses', 'timesheet' ); ?></th>
								<td id="tmsht_ts_legends_settings">
									<div id="tmsht_ts_legends_table_wrap">
										<div><input id="tmsht_add_ts_legend_name" class="bws_no_bind_notice" type="text" name="tmsht_add_ts_legend_name" value="<?php if ( isset( $ts_legend_name ) ) echo $ts_legend_name; ?>" maxlength="100" placeholder="<?php _e( 'Name', 'timesheet' ); ?>"></div>
										<div><input id="tmsht_add_ts_legend_color" class="bws_no_bind_notice" type="text" name="tmsht_add_ts_legend_color" value="<?php echo ( isset( $ts_legend_color ) ) ? $ts_legend_color : tmsht_generate_color(); ?>" data-default-color="#000000"></div>
										<div id="tmsht_ts_legend_header_actions">
											<input class="button-secondary bws_no_bind_notice" type="submit" name="tmsht_add_ts_legend" value="<?php _e( 'Add status', 'timesheet' ); ?>">
										</div>
										<table class="widefat striped tmsht_ts_legends_table">
											<thead>
												<tr>
													<td class="tmsht_ts_legend_id_cell"><?php _e( 'Enabled', 'timesheet' ); ?></td>
													<td class="tmsht_ts_legend_name_cell"><?php _ex( 'Name', 'Settings status table header', 'timesheet' ); ?></td>
													<td class="tmsht_ts_legend_color_cell"><?php _ex( 'Color', 'Settings status table header', 'timesheet' ); ?></td>
												</tr>
											</thead>
											<tbody>
												<?php if ( $legends ) {
													foreach ( $legends as $legend ) { ?>
														<tr>
															<td class="tmsht_ts_legend_id_cell" data-column-title="<?php _e( 'Enabled', 'timesheet' ); ?>">
																<input class="tmsht_ts_legend_id" type="checkbox" name="tmsht_ts_legend_id[<?php echo $legend['id']; ?>]" value="<?php echo $legend['id']; ?>" <?php if ( $legend['disabled'] == 0 ) echo 'checked="checked"'; ?>>
																<input type="hidden" name="tmsht_ts_legend_id_hidden[<?php echo $legend['id']; ?>]" value="<?php echo $legend['id']; ?>">
															</td>
															<td class="tmsht_ts_legend_name_cell" data-column-title="<?php _ex( 'Name', 'Settings legend table header', 'timesheet' ); ?>">
																<?php echo $legend['name']; ?>
															</td>
															<td class="tmsht_ts_legend_color_cell" data-column-title="<?php _ex( 'Color', 'Settings legend table header', 'timesheet' ); ?>">
																<input class="tmsht_ts_legend_color" type="text" name="tmsht_ts_legend_color[<?php echo $legend['id']; ?>]" value="<?php echo $legend['color']; ?>" data-default-color="<?php echo $legend['color']; ?>">
															</td>
														</tr>
													<?php }
												} else { ?>
													<tr>
														<td class="tmsht_ts_no_legends" colspan="3" data-column-title="<?php _e( 'Statuses', 'timesheet' ); ?>"><?php _e( 'No statuses', 'timesheet' ); ?></td>
													</tr>
												<?php } ?>
											</tbody>
											<tfoot>
												<tr>
													<td class="tmsht_ts_legend_id_cell"><?php _e( 'Enabled', 'timesheet' ); ?></td>
													<td class="tmsht_ts_legend_name_cell"><?php _ex( 'Name', 'Settings status table header', 'timesheet' ); ?></td>
													<td class="tmsht_ts_legend_color_cell"><?php _ex( 'Color', 'Settings status table header', 'timesheet' ); ?></td>
												</tr>
											</tfoot>
										</table>
									</div>
								</td>
							</tr>
							<tr class="tmsht_settings_table_tr">
								<th><?php _e( 'Date format', 'timesheet' ); ?></th>
								<td>
									<table class="tmsht_format_i18n">
										<tbody>
											<tr>
												<td>
													<label>
														<input id="tmsht_date_format_type_wp" type="radio" name="tmsht_date_format_type" data-date-format-code="<?php echo $date_formats['wp']; ?>" data-date-format-display="<?php echo date_i18n( $date_formats['wp'] ); ?>" value="wp" <?php if ( $tmsht_options['date_format_type'] == 'wp' ) echo 'checked="checked"'; ?>><?php _e( 'WordPress Settings', 'timesheet' ); ?>
													</label>
												</td>
												<td>
													<label for="tmsht_date_format_type_wp">
														<code><?php echo $date_formats['wp']; ?></code>
													</label>
												</td>
											</tr>
											<tr>
												<td>
													<label>
														<input id="tmsht_date_format_type_custom" type="radio" name="tmsht_date_format_type" data-date-format-code="<?php echo $date_formats['custom']; ?>" data-date-format-display="<?php echo date_i18n( $date_formats['custom'] ); ?>" value="custom" <?php if ( $tmsht_options['date_format_type'] == 'custom' ) echo 'checked="checked"'; ?>><?php _e( 'Custom', 'timesheet' ); ?>
													</label>
												</td>
												<td>
													<input id="tmsht_date_format_code" type="text" name="tmsht_date_format_code" max-length="25" value="<?php echo $date_formats['custom']; ?>">
													<span id="tmsht_date_format_display"><?php echo date_i18n( $date_formats['custom'] ); ?></span>
													<span id="tmsht_date_format_spinner" class="spinner"></span>
												</td>
											</tr>
										</tbody>
									</table>
									<span class="bws_info"><a href="https://codex.wordpress.org/Formatting_Date_and_Time" target="_blank"><?php _e( 'Documentation on date and time formatting' ); ?></a></span>
								</td>
							</tr>
							<tr class="tmsht_settings_table_tr">
								<th><?php _e( 'Allow to edit past days', 'timesheet' ); ?></th>
								<td><input id="tmsht_edit_past_days" type="checkbox" name="tmsht_edit_past_days" value="1" <?php if ( $tmsht_options['edit_past_days'] == 1 ) echo 'checked="checked"'; ?>></td>
							</tr>
							<tr class="tmsht_settings_table_tr">
								<th>
									<?php _e( 'Display TS user page for', 'timesheet' ); ?>
								</th>
								<td>
									<div id="tmsht_display_ts_user_page_for_wrap">
										<ul id="tmsht_display_ts_user_page_for">
											<?php foreach ( $all_roles as $role => $details ) {

												$users_in_role = $wpdb->get_results(
													"SELECT users.ID, users.user_login
													FROM `{$wpdb->base_prefix}users` AS users, `{$wpdb->base_prefix}usermeta` AS umeta
													WHERE users.ID = umeta.user_id
													AND umeta.meta_key = '{$wpdb->prefix}capabilities'
													AND umeta.meta_value LIKE '%\"{$role}\"%'
													ORDER BY users.ID ASC
													LIMIT 3",
													OBJECT_K
												);

												if ( $role == 'administrator' && is_multisite() && ! is_main_site() ) {
													$tmsht_super_admins = get_super_admins();

													foreach ( $tmsht_super_admins as $super_admin ) {
														$get_user = get_user_by( 'login', $super_admin );
														if ( $get_user ) {
															$add_user = array(
																'ID'           => $get_user->ID,
																'user_login'   => $get_user->user_login
															);
															$users_in_role[ $get_user->ID ] = (object) $add_user;
														}
													}
												}

												ksort( $users_in_role );

												if ( count( $users_in_role ) > 0 ) { ?>
													<li>
														<label><input type="checkbox" name="tmsht_display_ts_user_page_for[]" value="<?php echo $role; ?>" <?php if ( in_array( $role, $tmsht_options['display_pages']['ts_user']['user_roles'] ) ) echo 'checked="checked"'; ?>><?php echo translate_user_role( $details['name'] ); ?></label>
														<?php if ( ! $bws_hide_premium_options_check ) { ?>
															<div id="tmsht_display_ts_user_page_for_users_wrap">
																<div class="bws_pro_version_bloc">
																	<div class="bws_pro_version_table_bloc">
																		<button type="submit" name="bws_hide_premium_options" class="notice-dismiss bws_hide_premium_options" title="<?php _e( 'Close', 'timesheet' ); ?>"></button>
																		<div class="bws_table_bg"></div>
																		<ul class="tmsht_display_ts_user_page_for_users">
																			<?php foreach ( $users_in_role as $user ) { ?>
																				<li><label><input type="checkbox" <?php if ( in_array( $role, $tmsht_options['display_pages']['ts_user']['user_roles'] ) ) echo 'checked="checked"'; ?> disabled="disabled"><?php echo $user->user_login; ?></label></li>
																			<?php } ?>
																			<li><label><input type="checkbox" <?php if ( in_array( $role, $tmsht_options['display_pages']['ts_user']['user_roles'] ) ) echo 'checked="checked"'; ?> disabled="disabled">...</label></li>
																		</ul>
																	</div>
																	<div class="bws_pro_version_tooltip">
																		<a class="bws_button" href="https://bestwebsoft.com/products/wordpress/plugins/timesheet/?k=3bdf25984ad6aa9d95074e31c5eb9bb3&amp;pn=606&amp;v=<?php echo $tmsht_plugin_info['Version']; ?>&amp;wp_v=<?php echo $wp_version; ?>" target="_blank" title="Timesheet Pro">
																			<?php _e( 'Learn More', 'timesheet' ); ?>
																		</a>
																		<div class="clear"></div>
																	</div>
																</div>
															</div>
														<?php } ?>
													</li>
												<?php }
											} ?>
										</ul>
									</div>
								</td>
							</tr>
							<tr class="tmsht_settings_table_tr">
								<th>
									<?php _e( 'Display TS report page for', 'timesheet' ); ?>
								</th>
								<td>
									<div id="tmsht_display_ts_report_page_for_wrap">
										<ul id="tmsht_display_ts_report_page_for">
											<?php foreach ( $all_roles as $role => $details )  {

												$users_in_role = $wpdb->get_results(
													"SELECT users.ID, users.user_login
													FROM `{$wpdb->base_prefix}users` AS users, `{$wpdb->base_prefix}usermeta` AS umeta
													WHERE users.ID = umeta.user_id
													AND umeta.meta_key = '{$wpdb->prefix}capabilities'
													AND umeta.meta_value LIKE '%\"{$role}\"%'
													ORDER BY users.ID ASC
													LIMIT 3",
													OBJECT_K
												);

												if ( $role == 'administrator' && is_multisite() && ! is_main_site() ) {
													$tmsht_super_admins = get_super_admins();

													foreach ( $tmsht_super_admins as $super_admin ) {
														$get_user = get_user_by( 'login', $super_admin );
														if ( $get_user ) {
															$add_user = array(
																'ID'           => $get_user->ID,
																'user_login'   => $get_user->user_login
															);
															$users_in_role[ $get_user->ID ] = (object) $add_user;
														}
													}
												}

												ksort( $users_in_role );

												if ( count( $users_in_role ) > 0 ) { ?>
													<li>
														<label><input type="checkbox" name="tmsht_display_ts_report_page_for[]" value="<?php echo $role; ?>" <?php if ( in_array( $role, $tmsht_options['display_pages']['ts_report']['user_roles'] ) ) echo 'checked="checked"'; ?>><?php echo translate_user_role( $details['name'] ); ?></label>
														<?php if ( ! $bws_hide_premium_options_check ) { ?>
															<div id="tmsht_display_ts_report_page_for_users_wrap">
																<div class="bws_pro_version_bloc">
																	<div class="bws_pro_version_table_bloc">
																		<button type="submit" name="bws_hide_premium_options" class="notice-dismiss bws_hide_premium_options" title="<?php _e( 'Close', 'timesheet' ); ?>"></button>
																		<div class="bws_table_bg"></div>
																		<ul class="tmsht_display_ts_report_page_for_users">
																			<?php foreach ( $users_in_role as $user ) { ?>
																				<li><label><input type="checkbox" <?php if ( in_array( $role, $tmsht_options['display_pages']['ts_report']['user_roles'] ) ) echo 'checked="checked"'; ?> disabled="disabled"><?php echo $user->user_login; ?></label></li>
																			<?php } ?>
																			<li><label><input type="checkbox" <?php if ( in_array( $role, $tmsht_options['display_pages']['ts_report']['user_roles'] ) ) echo 'checked="checked"'; ?> disabled="disabled">...</label></li>
																		</ul>
																	</div>
																	<div class="bws_pro_version_tooltip">
																		<a class="bws_button" href="https://bestwebsoft.com/products/wordpress/plugins/timesheet/?k=3bdf25984ad6aa9d95074e31c5eb9bb3&amp;pn=606&amp;v=<?php echo $tmsht_plugin_info['Version']; ?>&amp;wp_v=<?php echo $wp_version; ?>" target="_blank" title="Timesheet Pro">
																			<?php _e( 'Learn More', 'timesheet' ); ?>
																		</a>
																		<div class="clear"></div>
																	</div>
																</div>
															</div>
														<?php } ?>
													</li>
												<?php }
											} ?>
										</ul>
									</div>
								</td>
							</tr>
							<tr class="tmsht_settings_table_tr">
								<th><?php _e( 'Email reminder', 'timesheet' ); ?></th>
								<td>
									<label><input id="tmsht_reminder_on_email" type="checkbox" name="tmsht_reminder_on_email" value="1" <?php if ( $tmsht_options['reminder_on_email'] == true ) echo 'checked="checked"'; ?>></label><span class="bws_info"><?php _e( 'This option allows sending an email reminder to a user if his work schedule isn\'t filled out.', 'timesheet' ); ?></span>
									<div class="tmsht_reminder_settings">
										<span><?php _ex( 'every', 'email reminder', 'timesheet' ); ?>&nbsp;</span>
										<select id="tmsht_day_reminder" name="tmsht_day_reminder">
											<?php foreach ( $days_arr as $day ) { ?>
												<option value="<?php echo strtolower( $day ); ?>" <?php if ( strtolower( $day ) == $tmsht_options['day_reminder'] ) echo 'selected="selected"'; ?>><?php _e( $day ); ?></option>
											<?php } ?>
										</select>
										<span>&nbsp;<?php _ex( 'in', 'email reminder', 'timesheet' ); ?>&nbsp;</span>
										<input id="tmsht_time_reminder" type="text" name="tmsht_time_reminder" maxlength="5" value="<?php echo $tmsht_options['time_reminder']; ?>">
										<input id="tmsht_reminder_change_state" type="hidden" name="tmsht_reminder_change_state" value="1"><br><br>
										<div id="tmsht_reminder_content">
											<span class="bws_info"><?php _ex( 'Subject', 'email reminder', 'timesheet' ); ?></span><br>
											<input id="tmsht_reminder_subject" type="text" name="tmsht_reminder_subject" value="<?php echo $tmsht_options['content_reminder']['subject']; ?>"><br><br>
											<span class="bws_info"><?php _ex( 'Message', 'email reminder', 'timesheet' ); ?></span>
											<span class="bws_help_box dashicons dashicons-editor-help">
												<span class="bws_hidden_help_text" style="width: 300px;">
													<span style="font-size: 14px;"><?php _e( 'You can edit the content of reminder letter which will be sent to users. You can use the following shortcodes in the text of the message:', 'timesheet' ); ?></span>
													<ul>
														<li><strong>{user_name}</strong> - <?php _e( 'this shortcode will be replaced with the username', 'timesheet' ); ?>;</li>
														<li><strong>{list_days}</strong> - <?php _e( 'this shortcode will be replaced with days that are not filled by the user', 'timesheet' ); ?>;</li>
														<li><strong>{ts_page}</strong> - <?php _e( 'this shortcode will be replaced with the link to TS user page in the dashboard', 'timesheet' ); ?>;</li>
														<li><strong>{ts_page_link}Your text{/ts_page_link}</strong> - <?php _e( 'this shortcode will be replaced with the link with your text to TS user page in the dashboard', 'timesheet' ); ?>;</li>
													</ul>
												</span>
											</span><br>
											<textarea id="tmsht_reminder_message" name="tmsht_reminder_message"><?php echo $tmsht_options['content_reminder']['message']; ?></textarea>
										</div>
									</div>
								</td>
							</tr>
						</table>
						<p>
							<input id="bws-submit-button" class="button-primary" type="submit" value="<?php _e( 'Save Changes', 'timesheet' ); ?>">
							<input type="hidden" name="tmsht_save_settings" value="submit" />
							<?php wp_nonce_field( 'tmsht_nonce_save_settings', 'tmsht_nonce_name' ); ?>
						</p>
					</form>
					<?php bws_form_restore_default_settings( $plugin_basename );
				}
			} elseif ( 'go_pro' == $_GET['action'] ) {
				bws_go_pro_tab_show( $bws_hide_premium_options_check, $tmsht_plugin_info, $plugin_basename, 'timesheet_settings', 'timesheet_pro_settings', 'timesheet-pro/timesheet-pro.php', 'timesheet', '3bdf25984ad6aa9d95074e31c5eb9bb3', '606', isset( $go_pro_result['pro_plugin_is_activated'] ) );
			}
			bws_plugin_reviews_block( $tmsht_plugin_info['Name'], 'timesheet' ); ?>
		</div>
	<?php }
}

if ( ! function_exists( 'tmsht_ts_user_page' ) ) {
	function tmsht_ts_user_page() {
		global $wpdb, $tmsht_options, $tmsht_plugin_info, $wp_version, $tmsht_current_user;

		$message = $error = "";
		$tmsht_date_format = $tmsht_options['date_format'];
		$tmsht_date_format_default = 'Y-m-d';

		$week_days_arr = array( 'Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat' );
		$day_of_week_start = get_option( 'start_of_week' );

		$date_from = ( isset( $_GET['tmsht_ts_user_date_from'] ) && strtotime( $_GET['tmsht_ts_user_date_from'] ) ) ? $_GET['tmsht_ts_user_date_from'] : date( $tmsht_date_format_default );
		$date_to = ( isset( $_GET['tmsht_ts_user_date_to'] ) && strtotime( $_GET['tmsht_ts_user_date_to'] ) ) ? $_GET['tmsht_ts_user_date_to'] : date( $tmsht_date_format_default, strtotime( "next " . $week_days_arr[ $day_of_week_start ] . " +6 days" ) );

		$date_start = new DateTime( $date_from );
		$date_end = new DateTime( $date_to );
		$date_end = $date_end->modify( '+1 day' );
		$date_period = tmsht_date_period( $date_start, $date_end );

		$timeline_from = $tmsht_options['ts_timeline_from'];
		$timeline_to = $tmsht_options['ts_timeline_to'] - 1;

		$tmsht_legends = $wpdb->get_results( "SELECT * FROM `{$wpdb->prefix}tmsht_legends`", OBJECT_K );
		/* Convert stdClass items of array( $tmsht_legends ) to associative array */
		$tmsht_legends = json_decode( json_encode( $tmsht_legends ), true );
		$tmsht_legends[-1] = array( 'name' => __( 'Please select...', 'timesheet' ), 'color' => 'transparent', 'disabled' => 0 );
		ksort( $tmsht_legends );

		if ( isset( $_POST['tmsht_save_ts'] ) && check_admin_referer( 'tmsht_nonce_save_ts', 'tmsht_nonce_name' ) ) {
			if ( isset( $_POST['tmsht_tr_date'] ) && is_array( $_POST['tmsht_tr_date'] ) ) {

				foreach ( $_POST['tmsht_tr_date'] as $tr_date ) {

					if ( date( $tmsht_date_format_default, strtotime( $tr_date ) ) < date( $tmsht_date_format_default ) && $tmsht_options['edit_past_days'] == 0 ) {
						continue;
					}

					$tmsht_query = $wpdb->query( $wpdb->prepare( "DELETE FROM `{$wpdb->prefix}tmsht_ts` WHERE `user_id` = %d AND date(`time_from`) = %s", $tmsht_current_user->ID, $tr_date ) );

					if ( $tmsht_query === false ) {
						$error = __( 'Data has not been saved', 'timesheet' );
						break;
					}

					if ( isset( $_POST['tmsht_to_db'][ $tr_date ] ) && is_array( $_POST['tmsht_to_db'][ $tr_date ] ) ) {
						foreach ( $_POST['tmsht_to_db'][ $tr_date ] as $ts_interval ) {
							$ts_interval_arr = explode( '@', $ts_interval );
							$ts_interval_from = $ts_interval_arr[0];
							$ts_interval_to = $ts_interval_arr[1];
							$legend_id = $ts_interval_arr[2];

							if ( strtotime( $ts_interval_from ) && strtotime( $ts_interval_to ) && array_key_exists( $legend_id, $tmsht_legends ) ) {

								$tmsht_query = $wpdb->insert(
									"{$wpdb->prefix}tmsht_ts",
									array( 'user_id' => $tmsht_current_user->ID, 'time_from' => $ts_interval_from, 'time_to' => $ts_interval_to, 'legend_id' => $legend_id ),
									array( '%d', '%s', '%s', '%d' )
								);

								if ( $tmsht_query === false ) {
									$error = __( 'Data has not been saved', 'timesheet' );
									break;
								}
							}
						}
					}
				}
				if ( $error == '' ) {
					$message = __( 'Data has been saved', 'timesheet' );
				}
			} else {
				$error = __( 'Data has not been saved, because there was no change', 'timesheet' );
			}
		}

		$ts_data = $wpdb->get_results(
			"SELECT `time_from`, `time_to`, `legend_id`
			FROM `{$wpdb->prefix}tmsht_ts`
			WHERE date(`time_from`) >= '" . $date_from ."'
			AND date(`time_to`) <= '" . $date_to . "'
			AND `user_id` = '" . $tmsht_current_user->ID . "'",
		ARRAY_A );

		foreach ( $ts_data as $key => $value ) {
			$new_key = date( $tmsht_date_format_default, strtotime( $value['time_from'] ) );
			$ts_data[ $new_key ][] = $value;
			unset( $ts_data[ $key ] );
		}

		$bws_hide_premium_options_check = bws_hide_premium_options_check( $tmsht_options ); ?>
		<div class="wrap tmsht_wrap">
			<h1 style="line-height: normal;">Timesheet</h1>
			<noscript>
				<div class="error below-h2">
					<p><strong><?php _e( 'WARNING', 'timesheet' ); ?>:</strong> <?php _e( 'The plugin works correctly only if JavaScript is enabled.', 'timesheet' ); ?></p>
				</div>
			</noscript>
			<div class="updated fade below-h2" <?php if ( "" == $message ) echo 'style="display:none"'; ?>><p><strong><?php echo $message; ?></strong></p></div>
			<div class="error below-h2" <?php if ( "" == $error ) echo 'style="display:none"'; ?>><p><strong><?php echo $error; ?></strong></p></div>
			<div class="tmsht_ts_user_filter">
				<div class="tmsht_ts_user_filter_item tmsht_ts_user_filter_item_datepicker">
					<form method="get" action="">
						<input type="hidden" name="page" value="timesheet_ts_user">
						<div class="tmsht_ts_user_filter_block">
							<div class="tmsht_ts_user_filter_title"><strong><?php _e( 'Date from', 'timesheet' ); ?></strong></div>
							<input id="tmsht_ts_user_date_from" class="tmsht_date_datepicker_input" type="text" name="tmsht_ts_user_date_from" value="<?php echo $date_from; ?>" autocomplete="off">
						</div>
						<div class="tmsht_ts_user_filter_block">
							<div class="tmsht_ts_user_filter_title"><strong><?php _e( 'Date to', 'timesheet' ); ?></strong></div>
							<input id="tmsht_ts_user_date_to" class="tmsht_date_datepicker_input" type="text" name="tmsht_ts_user_date_to" value="<?php echo $date_to; ?>" autocomplete="off">
						</div>
						<div class="tmsht_ts_user_change_dates">
							<input type="submit" class="button-secondary tmsht_date_datepicker_change" value="<?php _e( 'Change date', 'timesheet' ); ?>">
						</div>
					</form>
				</div>
				<div class="tmsht_ts_user_filter_item tmsht_ts_user_filter_item_legend">
					<div class="tmsht_ts_user_filter_title"><strong><?php _e( 'Status', 'timesheet' ); ?></strong></div>
					<select id="tmsht_ts_user_legend" class="tmsht_ts_user_legend" name="tmsht_ts_user_legend">
						<?php $legend_index = 0;
						foreach ( $tmsht_legends as $id => $legend ) {
							if ( $legend['disabled'] == 0 ) { ?>
								<option value="<?php echo $id; ?>" data-color="<?php echo $legend['color']; ?>" <?php if ( $legend_index == 0 ) echo 'selected="selected"'; ?>><?php echo $legend['name']; ?></option>
								<?php $legend_index++;
							}
						} ?>
					</select>
				</div>
				<div class="tmsht_ts_user_filter_item tmsht_ts_user_filter_table_actions">
					<div class="tmsht_ts_user_filter_title">&nbsp;</div>
					<a id="tmsht_transposition_tbl" class="button-secondary hide-if-no-js tmsht_dashicons dashicons dashicons-image-rotate-right" href="#" title="<?php _e( 'Transposition table', 'timesheet' ); ?>"></a>
				</div>
			</div>
			<form method="post" action="">
				<div id="tmsht_ts_user_table_area_wrap">
					<div id="tmsht_ts_user_table_area">
						<table id="tmsht_ts_user_table" class="widefat striped tmsht_ts_user_table tmsht_ts_user_table_head_timeline" cellspacing="0" cellpadding="0">
							<thead>
								<tr>
									<td class="tmsht_ts_user_table_td_dateline">&nbsp;</td>
									<?php for ( $time_value = $timeline_from; $time_value <= $timeline_to; $time_value++ ) { ?>
										<td class="tmsht_ts_user_table_td_timeline"><div class="tmsht_ts_user_time_display"><?php echo ( $time_value > 9 ) ? $time_value : '&nbsp;' . $time_value ; ?></div></td>
									<?php } ?>
								</tr>
							</thead>
							<tbody>
							<?php $tmsht_tr_index = $tmsht_td_index = 0;
								foreach( $date_period as $date ) {
								$tmsht_tr_classes = ( date( $tmsht_date_format_default, strtotime( $date ) ) == date( $tmsht_date_format_default ) ) ? 'tmsht_ts_user_table_tr tmsht_ts_user_table_tr_today' : 'tmsht_ts_user_table_tr';
								$tmsht_td_dateline_classes = ( date( $tmsht_date_format_default, strtotime( $date ) ) == date( $tmsht_date_format_default ) ) ? ' tmsht_ts_user_table_highlight_today' : '';
								$tmsht_td_dateline_classes .= ( in_array( strtolower( date( 'D', strtotime( $date ) ) ), $tmsht_options['weekends'] ) ) ? ' tmsht_ts_user_table_highlight_weekdays' : '';
								$tmsht_td_readonly = ( date( $tmsht_date_format_default, strtotime( $date ) ) < date( $tmsht_date_format_default ) && $tmsht_options['edit_past_days'] == 0 ); ?>
								<tr class="<?php echo $tmsht_tr_classes; ?>" data-tr-date="<?php echo date( $tmsht_date_format_default, strtotime( $date ) ); ?>">
									<td class="tmsht_ts_user_table_td_dateline">
										<div class="tmsht_ts_user_table_td_dateline_group<?php echo $tmsht_td_dateline_classes; ?>" data-datline-date="<?php echo date( $tmsht_date_format_default, strtotime( $date ) ); ?>">
											<div class="tmsht_ts_user_formatted_date"><?php echo date_i18n( $tmsht_date_format, strtotime( $date ) ); ?></div>
											<div class="tmsht_ts_user_weekday"><?php echo date_i18n( 'D', strtotime( $date ) ); ?></div>
										</div>
										<input class="tmsht_tr_date" type="hidden" name="tmsht_tr_date[]" value="<?php echo date( $tmsht_date_format_default, strtotime( $date ) ); ?>" disabled="disabled">
									</td>
									<?php for ( $time_value = $timeline_from; $time_value <= $timeline_to; $time_value++ ) {
										$tmsht_td_timeline_classes = 'tmsht_ts_user_table_td_time';

										if ( $tmsht_td_readonly ) {
											$tmsht_td_timeline_classes .= ' tmsht_ts_user_table_td_readonly';
											$tmsht_td_index = -1;
										}

										if ( $tmsht_td_index == 0 ) {
											$tmsht_td_timeline_classes .= ' tmsht_ts_user_table_td_highlighted';
										} ?>
										<td class="<?php echo $tmsht_td_timeline_classes; ?>" data-tr-index="<?php echo $tmsht_tr_index; ?>" data-td-index="<?php echo $time_value; ?>" data-td-date="<?php echo date( $tmsht_date_format_default, strtotime( $date ) ); ?>" data-td-time-from="<?php printf( "%02d:00", $time_value ); ?>" data-td-time-to="<?php printf( "%02d:00", $time_value + 1 ); ?>">
											<div class="tmsht_ts_user_table_td_fill_group">
												<?php for ( $time_minutes = 0; $time_minutes < 60; $time_minutes += 5 ) {

													$search_date = date( $tmsht_date_format_default, strtotime( $date ) );
													$td_datetime = strtotime( sprintf( "%s %02d:%02d:00", $search_date, $time_value, $time_minutes ) );
													$td_legend_id = -1;
													$td_title = '';

													if ( array_key_exists( $search_date, $ts_data ) ) {
														foreach ( $ts_data[ $search_date ] as $data ) {

															if ( strtotime( $data['time_from'] ) <= $td_datetime && strtotime( $data['time_to'] ) > $td_datetime ) {
																$td_legend_id = $data['legend_id'];
																$time_to_adjustment = ( date( 'i', strtotime( $data['time_to'] ) ) == 59 ) ? '24:00' : date( 'H:i', strtotime( $data['time_to'] ) );
																$td_title = sprintf( "%s (%s - %s)", $tmsht_legends[ $td_legend_id ]['name'], date( 'H:i', strtotime( $data['time_from'] ) ), $time_to_adjustment );
															}
														}
													} ?>
													<div class="tmsht_ts_user_table_td_fill" style="background-color: <?php echo $tmsht_legends[ $td_legend_id ]['color']; ?>;" data-fill-time-from="<?php printf( "%02d:%02d", $time_value, $time_minutes ); ?>" data-fill-time-to="<?php printf( "%02d:%02d", ( $time_minutes < 55 ) ? $time_value : $time_value + 1, ( $time_minutes < 55 ) ? $time_minutes + 5 : 0 ); ?>" data-legend-id="<?php echo $td_legend_id; ?>" title="<?php echo $td_title; ?>"></div>
												<?php } ?>
											</div>
											<?php if ( $tmsht_td_readonly ) { ?>
												<div class="tmsht_ts_user_table_td_readonly_fill"></div>
											<?php } ?>
										</td>
										<?php $tmsht_td_index++;
									} ?>
								</tr>
								<?php $tmsht_tr_index++;
							} ?>
							</tbody>
							<tfoot>
								<tr>
									<td class="tmsht_ts_user_table_td_dateline">&nbsp;</td>
									<?php for ( $time_value = $timeline_from; $time_value <= $timeline_to; $time_value++ ) { ?>
										<td class="tmsht_ts_user_table_td_timeline"><div class="tmsht_ts_user_time_display"><?php echo ( $time_value > 9 ) ? $time_value : '&nbsp;' . $time_value ; ?></div></td>
									<?php } ?>
								</tr>
							</tfoot>
						</table>
						<div id="tmsht_ts_user_table_selection"></div>
					</div>
					<div class="tmsht_ts_user_advanced_container_area">
						<div id="tmsht_ts_user_advanced_container" class="tmsht_ts_user_advanced_container">
							<?php foreach ( $tmsht_legends as $ts_legend_id => $ts_legend ) {
								if ( $ts_legend_id < 0 ) {
									continue;
								}  ?>
								<div class="tmsht_ts_user_advanced_box tmsht_maybe_hidden tmsht_hidden" data-box-id="<?php echo $ts_legend_id; ?>">
									<div class="tmsht_ts_user_advanced_box_title" style="background-color: <?php echo $ts_legend['color']; ?>"><?php echo $ts_legend['name']; ?></div>
									<div class="tmsht_ts_user_advanced_box_content">
										<?php foreach( $date_period as $date ) { ?>
											<div class="tmsht_ts_user_advanced_box_details tmsht_maybe_hidden tmsht_hidden" data-details-date="<?php echo date( $tmsht_date_format_default, strtotime( $date ) ); ?>">
												<div class="tmsht_ts_user_advanced_box_date"><?php echo date_i18n( $tmsht_date_format, strtotime( $date ) ); ?></div>
												<div class="tmsht_ts_user_advanced_box_interval_wrap"></div>
											</div>
										<?php } ?>
									</div>
								</div>
							<?php } ?>
							<div class="tmsht_clearfix"></div>
						</div>

						<div id="tmsht_ts_user_advanced_box_details_template" class="tmsht_hidden">
							<div class="tmsht_ts_user_advanced_box_interval">
								<span class="tmsht_ts_user_advanced_box_interval_from_text">%time_from%</span><input class="tmsht_ts_user_advanced_box_interval_from tmsht_maybe_hidden tmsht_hidden" type="text" value="%time_from%"> - <span class="tmsht_ts_user_advanced_box_interval_to_text">%time_to%</span><input class="tmsht_ts_user_advanced_box_interval_to tmsht_maybe_hidden tmsht_hidden" type="text" value="%time_to%">
								<input type="hidden" data-hidden-name="tmsht_to_db[%date%][]" value="%date% %input_time_from%@%date% %input_time_to%@%legend_id%">
							</div>
						</div>

					</div>
					<div class="tmsht_clearfix"></div>
					<input class="button-primary" type="submit" name="tmsht_save_ts" value="<?php _e( 'Save Changes', 'timesheet' ) ?>">
					<?php wp_nonce_field( 'tmsht_nonce_save_ts', 'tmsht_nonce_name' ); ?>
				</div>
				<ul id="tmsht_ts_user_context_menu" data-visible="false">
					<?php if ( ! $bws_hide_premium_options_check ) { ?>
						<li class="tmsht_ts_user_context_menu_item tmsht_ts_user_context_menu_item_disabled" data-action="false">
							<span class="tmsht_ts_user_context_menu_icon dashicons dashicons-clock"></span><span class="tmsht_ts_user_context_menu_text"><?php _e( 'Edit time', 'timesheet' ); ?> <a class="tmsht_ts_user_context_menu_link" href="https://bestwebsoft.com/products/wordpress/plugins/timesheet/?k=3bdf25984ad6aa9d95074e31c5eb9bb3&amp;pn=606&amp;v=<?php echo $tmsht_plugin_info['Version']; ?>&amp;wp_v=<?php echo $wp_version; ?>" target="_blank">(<?php _e( 'Available in PRO', 'timesheet' ); ?>)</a></span>
						</li>
					<?php } ?>
					<li class="tmsht_ts_user_context_menu_item tmsht_ts_user_context_menu_item_enabled" data-action="delete">
						<span class="tmsht_ts_user_context_menu_icon dashicons dashicons-dismiss"></span><span class="tmsht_ts_user_context_menu_text"><?php _e( 'Delete status', 'timesheet' ); ?></span>
					</li>
					<li class="tmsht_ts_user_context_menu_item tmsht_ts_user_context_menu_item_separator tmsht_ts_user_context_menu_item_disabled"></li>
					<?php foreach ( $tmsht_legends as $id => $legend ) {
						if ( $legend['disabled'] == 0 && $id >= 0 ) { ?>
							<li class="tmsht_ts_user_context_menu_item tmsht_ts_user_context_menu_item_enabled" data-legend-id="<?php echo $id; ?>" data-action="apply_status">
								<span class="tmsht_ts_user_context_menu_icon" style="background: <?php echo $legend['color']; ?>;"></span><span class="tmsht_ts_user_context_menu_text"><?php echo $legend['name']; ?></span>
							</li>
						<?php }
					} ?>
				</ul>
			</form>
		</div>
	<?php }
}

if ( ! function_exists( 'tmsht_ts_report_page' ) ) {
	function tmsht_ts_report_page() {
		global $wpdb, $tmsht_options, $wp_version;

		$message = $error = "";
		$tmsht_date_format = $tmsht_options['date_format'];
		$tmsht_date_format_default = 'Y-m-d';

		$week_days_arr = array( 'Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat' );
		$day_of_week_start = get_option( 'start_of_week' );

		$date_preset_quantity_arr = array( 1, 2, 3 );

		$date_preset_units_arr = array(
			'week'  => __( 'Week', 'timesheet' ),
			'month' => __( 'Month', 'timesheet' )
		);

		$tmsht_ts_report_group_by_arr = array(
			'date'   => _x( 'Date', 'Group by', 'timesheet' ),
			'user'   => _x( 'User', 'Group by', 'timesheet' )
		);

		/* Get legends */
		$tmsht_legends = $wpdb->get_results( "SELECT * FROM `{$wpdb->prefix}tmsht_legends`", OBJECT_K );
		/* Convert stdClass items of array( $tmsht_legends ) to associative array */
		$tmsht_legends = json_decode( json_encode( $tmsht_legends ), true );
		$tmsht_legends[-1] = array( 'name' => __( 'Blank', 'timesheet' ), 'color' => 'transparent', 'disabled' => 1 );
		$tmsht_legends[-2] = array( 'name' => __( 'All statuses', 'timesheet' ), 'color' => '#444444', 'disabled' => 0 );
		ksort( $tmsht_legends );

		/* Get users */
		$tmsht_users = array();
		$tmsht_roles = $tmsht_options['display_pages']['ts_user']['user_roles'];

		foreach( $tmsht_roles as $role ) {
			$users_in_role = $wpdb->get_results(
				"SELECT users.ID, users.user_login
				FROM `{$wpdb->base_prefix}users` AS users, `{$wpdb->base_prefix}usermeta` AS umeta
				WHERE users.ID = umeta.user_id
				AND umeta.meta_key = '{$wpdb->prefix}capabilities'
				AND umeta.meta_value LIKE '%\"{$role}\"%'
				ORDER BY users.ID ASC",
				OBJECT_K
			);

			if ( $role == 'administrator' && is_multisite() && ! is_main_site() ) {
				$tmsht_super_admins = get_super_admins();

				foreach ( $tmsht_super_admins as $super_admin ) {
					$get_user = get_user_by( 'login', $super_admin );
					if ( $get_user ) {
						$add_user = array(
							'ID'         => $get_user->ID,
							'user_login' => $get_user->user_login
						);
						$users_in_role[ $get_user->ID ] = (object) $add_user;
					}
				}
			}

			ksort( $users_in_role );

			if ( count( $users_in_role ) == 0 ) {
				continue;
			}

			foreach ( $users_in_role as $user_id => $user_data ) {
				$tmsht_users[ $user_data->ID ] = $user_data->user_login;
			}
		}

		asort( $tmsht_users );

		/* Get user meta */
		$current_user = wp_get_current_user();

		$ts_report_filters_default = array(
			'date' => array(
				'type'   => 'period',
				'preset' => array()
			),
			'group_by'  => 'date',
			'legend'    => -2,
			'users'     => array_keys( $tmsht_users ),
		);

		if ( ! get_user_meta( $current_user->ID, '_tmsht_ts_report_filters' ) ) {
			add_user_meta( $current_user->ID, '_tmsht_ts_report_filters', $ts_report_filters_default );
		}

		$ts_report_filters = get_user_meta( $current_user->ID, '_tmsht_ts_report_filters', true );

		/* Apply filters */
		if ( isset( $_POST['tmsht_generate_ts_report'] ) ) {
			if (
				( isset( $_POST['tmsht_date_filter_type'] ) && $_POST['tmsht_date_filter_type'] == 'preset' ) &&
				( isset( $_POST['tmsht_date_preset_unit'] ) && array_key_exists( $_POST['tmsht_date_preset_unit'], $date_preset_units_arr ) ) &&
				isset( $_POST['tmsht_date_preset_quantity'] )
			) {
				$ts_report_filters['date'] = array(
					'type'   => 'preset',
					'preset' => array(
						'quantity' => intval( $_POST['tmsht_date_preset_quantity'] ),
						'unit'  => $_POST['tmsht_date_preset_unit']
					)
				);
			} else {
				$ts_report_filters['date'] = array(
					'type'   => 'period',
					'preset' => array()
				);
			}

			$ts_report_filters['group_by'] = ( isset( $_POST['tmsht_ts_report_group_by'] ) && array_key_exists( $_POST['tmsht_ts_report_group_by'], $tmsht_ts_report_group_by_arr ) ) ? $_POST['tmsht_ts_report_group_by'] : 'date';
			$ts_report_filters['legend'] = ( isset( $_POST['tmsht_ts_report_legend'] ) && array_key_exists( $_POST['tmsht_ts_report_legend'], $tmsht_legends ) ) ? $_POST['tmsht_ts_report_legend'] : -2;
			$ts_report_filters['users'] = ( isset( $_POST['tmsht_ts_report_user'] ) && is_array( $_POST['tmsht_ts_report_user'] ) ) ? $_POST['tmsht_ts_report_user'] : array_keys( $tmsht_users );
			update_user_meta( $current_user->ID, '_tmsht_ts_report_filters', $ts_report_filters );
		}

		/* Report generation */
		$date_from = $filter_date_from = ( isset( $_POST['tmsht_ts_report_date_from'] ) && strtotime( $_POST['tmsht_ts_report_date_from'] ) && $ts_report_filters['date']['type'] == 'period' ) ? $_POST['tmsht_ts_report_date_from'] : date( $tmsht_date_format_default );
		$date_to = $filter_date_to = ( isset( $_POST['tmsht_ts_report_date_to'] ) && strtotime( $_POST['tmsht_ts_report_date_to'] ) && $ts_report_filters['date']['type'] == 'period' ) ? $_POST['tmsht_ts_report_date_to'] : date( $tmsht_date_format_default, strtotime( "next " . $week_days_arr[ $day_of_week_start ] . " +6 days" ) );

		if ( $ts_report_filters['date']['type'] == 'preset' ) {
			$date_from = date( $tmsht_date_format_default );
			$date_to = date( $tmsht_date_format_default, strtotime( "+" . $ts_report_filters['date']['preset']['quantity'] . " " . $ts_report_filters['date']['preset']['unit'] ) );
		}

		$date_start = new DateTime( $date_from );
		$date_end = new DateTime( $date_to );

		$date_end = $date_end->modify( '+1 day' );
		$date_period = tmsht_date_period( $date_start, $date_end );

		$timeline_from = $tmsht_options['ts_timeline_from'];
		$timeline_to = $tmsht_options['ts_timeline_to'] - 1;

		$tmsht_ts_report_group_by = $ts_report_filters['group_by'];
		$tmsht_current_legend_id = $ts_report_filters['legend'];
		$tmsht_selected_users = array();

		foreach ( $ts_report_filters['users'] as $user_id ) {
			if ( array_key_exists( $user_id, $tmsht_users ) ) {
				$tmsht_selected_users[] = $user_id;
			}
		}

		$ts_data = array();

		if ( $tmsht_selected_users ) {
			$tmsht_user_ids = implode( ',', $tmsht_selected_users );
			$ts_data_query = "SELECT `id`, `user_id`, `time_from`, `time_to`, `legend_id` FROM `{$wpdb->prefix}tmsht_ts` WHERE date(`time_from`) >= '" . $date_from ."' AND date(`time_to`) <= '" . $date_to . "'";

			if ( $tmsht_current_legend_id > 0 ) {
				$ts_data_query .= " AND `legend_id` = '" . $tmsht_current_legend_id . "'";
			}

			$ts_data_query .=  " AND `user_id` in (" . $tmsht_user_ids . ") ORDER BY `user_id` ASC, `time_from` ASC";
			$ts_get_data = $wpdb->get_results( $ts_data_query, ARRAY_A );

			if ( $tmsht_ts_report_group_by == 'date' ) {

				foreach ( $ts_get_data as $data ) {
					$key_date = date( $tmsht_date_format_default, strtotime( $data['time_from'] ) );
					$key_user_id = $data[ 'user_id' ];
					$ts_data[ $key_date ][ $key_user_id ][] = $data;
				}

				foreach( $date_period as $date ) {
					$date_formated = date( $tmsht_date_format_default, strtotime( $date ) );

					$tmsht_exists_data_for_users = isset( $ts_data[ $date_formated  ] ) ? array_keys( $ts_data[ $date_formated  ] ) : array();
					$tmsht_not_exists_data_for_users = array_diff( $tmsht_selected_users, $tmsht_exists_data_for_users );

					if ( $tmsht_exists_data_for_users ) {
						foreach ( $tmsht_not_exists_data_for_users as $user_id ) {
							$ts_data[ $date_formated ][ $user_id ][] = array();
						}
					} else {
						$ts_data[ $date_formated ] = array(
							-1 => array( array() )
						);
					}

					ksort( $ts_data[ $date_formated ] );
				}

				ksort( $ts_data );

			} else if ( $tmsht_ts_report_group_by == 'user' ) {
				foreach ( $ts_get_data as $data ) {
					$key_date = date( $tmsht_date_format_default, strtotime( $data['time_from'] ) );
					$key_user_id = $data[ 'user_id' ];
					$ts_data[ $key_user_id ][ $key_date ][] = $data;
				}

				foreach ( $tmsht_selected_users as $user_id ) {
					if ( ! isset( $ts_data[ $user_id ] ) ) {
						$ts_data[ $user_id ] = array();
					}

					foreach( $date_period as $date ) {
						$date_formated = date( $tmsht_date_format_default, strtotime( $date ) );

						if ( $ts_data[ $user_id ] && ! isset( $ts_data[ $user_id ][ $date_formated ] ) ) {
							$ts_data[ $user_id ][ $date_formated ][] = array();
						}
					}

					ksort( $ts_data[ $user_id ] );
				}

				ksort( $ts_data );
			}
		} else {
			$error = __( 'Select at least one user', 'timesheet' );
		} ?>
		<div class="wrap tmsht_wrap">
			<h1 style="line-height: normal;">Timesheet <?php _e( 'Report', 'timesheet' ); ?></h1>
			<noscript>
				<div class="error below-h2">
					<p><strong><?php _e( 'WARNING', 'timesheet' ); ?>:</strong> <?php _e( 'The plugin works correctly only if JavaScript is enabled.', 'timesheet' ); ?></p>
				</div>
			</noscript>
			<div class="updated fade below-h2" <?php if ( "" == $message ) echo 'style="display:none"'; ?>><p><strong><?php echo $message; ?></strong></p></div>
			<div class="error below-h2" <?php if ( "" == $error ) echo 'style="display:none"'; ?>><p><strong><?php echo $error; ?></strong></p></div>
			<div class="tmsht_container">
				<form method="post" action="">
					<div class="tmsht_ts_report_filter">
						<div class="tmsht_ts_report_filter_item tmsht_ts_report_filter_item_datepicker">
						<div class="tmsht_ts_report_filter_title"><strong><?php _e( 'Date', 'timesheet' ); ?></strong></div>
							<table>
								<tbody>
									<tr>
										<td>
											<input type="radio" name="tmsht_date_filter_type" value="period" <?php if ( $ts_report_filters['date']['type'] == 'period' ) echo 'checked="checked"'; ?>>
										</td>
										<td data-filter-type="period">
											<div class="tmsht_ts_report_filter_block">
												<span><?php _ex( 'from', 'date', 'timesheet' ); ?></span>
												<input id="tmsht_ts_report_date_from" class="tmsht_date_datepicker_input" type="text" name="tmsht_ts_report_date_from" value="<?php echo $filter_date_from; ?>" autocomplete="off">
											</div>
											<div class="tmsht_ts_report_filter_block">
												<span><?php _ex( 'to', 'date', 'timesheet' ); ?></span>
												<input id="tmsht_ts_report_date_to" class="tmsht_date_datepicker_input" type="text" name="tmsht_ts_report_date_to" value="<?php echo $filter_date_to; ?>" autocomplete="off">
											</div>
										</td>
									</tr>
									<tr>
										<td>
											<input type="radio" name="tmsht_date_filter_type" value="preset" <?php if ( $ts_report_filters['date']['type'] == 'preset' ) echo 'checked="checked"'; ?>>
										</td>
										<td data-filter-type="preset">
											<select id="tmsht_date_preset_quantity" name="tmsht_date_preset_quantity">
												<?php foreach ( $date_preset_quantity_arr as $date_preset_quantity ) { ?>
													<option value="<?php echo $date_preset_quantity; ?>" <?php if ( $ts_report_filters['date']['type'] == 'preset' && $ts_report_filters['date']['preset']['quantity'] == $date_preset_quantity ) echo 'selected="selected"'; ?>><?php echo $date_preset_quantity; ?></option>
												<?php } ?>
											</select>
											<select id="tmsht_date_preset_unit" name="tmsht_date_preset_unit">
												<?php foreach ( $date_preset_units_arr as $date_preset_unit_key => $date_preset_unit_name ) { ?>
													<option value="<?php echo $date_preset_unit_key; ?>" <?php if ( $ts_report_filters['date']['type'] == 'preset' && $ts_report_filters['date']['preset']['unit'] == $date_preset_unit_key ) echo 'selected="selected"'; ?>><?php echo $date_preset_unit_name; ?></option>
												<?php } ?>
											</select>
										</td>
									</tr>
								</tbody>
							</table>
						</div>
						<div class="tmsht_ts_report_filter_item tmsht_ts_report_filter_item_group_by">
							<div class="tmsht_ts_report_filter_title"><strong><?php _e( 'Group by', 'timesheet' ); ?></strong></div>
								<?php foreach( $tmsht_ts_report_group_by_arr as $tmsht_ts_report_group_by_id => $tmsht_ts_report_group_by_type ) { ?>
									<label><input type="radio" name="tmsht_ts_report_group_by" value="<?php echo $tmsht_ts_report_group_by_id; ?>" <?php if ( $tmsht_ts_report_group_by_id == $tmsht_ts_report_group_by ) echo 'checked="checked"'; ?>><?php echo $tmsht_ts_report_group_by_type; ?></label><br>
								<?php } ?>
						</div>
						<div class="tmsht_ts_report_filter_item tmsht_ts_report_filter_item_legend">
							<div class="tmsht_ts_report_filter_title"><strong><?php _e( 'Status', 'timesheet' ); ?></strong></div>
								<fieldset>
								<?php $legend_index = 0;
								foreach ( $tmsht_legends as $id => $legend ) {
									if ( $legend['disabled'] == 0 ) { ?>
										<label class="tmsht_ts_report_legend_label"><input class="tmsht_ts_report_legend" type="radio" name="tmsht_ts_report_legend" value="<?php echo $id; ?>" data-color="<?php echo $legend['color']; ?>" <?php if ( $id == $tmsht_current_legend_id ) echo 'checked="checked"'; ?>><span class="tmsht_ts_report_legend_color" style="background-color: <?php echo $legend['color']; ?>;"></span><span class="tmsht_ts_report_legend_name"><?php echo $legend['name']; ?></span></label><br>
										<?php $legend_index++;
									}
								} ?>
								</fieldset>
						</div>
						<div class="tmsht_ts_report_filter_item tmsht_ts_report_filter_item_user">
							<div class="tmsht_ts_report_filter_title"><strong><?php _e( 'Users', 'timesheet' ); ?></strong></div>
							<div class="tmsht_ts_report_user_list_wrap">
								<div class="tmsht_ts_report_user_list">
									<input class="tmsht_ts_report_search_user hide-if-no-js" type="text" placeholder="<?php _e( 'Search user', 'timesheet' ); ?>">
									<noscript class="tmsht_ts_report_user_list_container_noscript">
										<div class="tmsht_ts_report_user_list_container">
											<?php if ( count( $tmsht_users ) > 0 ) { ?>
												<label class="tmsht_ts_report_user_label hide-if-no-js"><input class="tmsht_ts_report_user_checkbox_all" type="checkbox" value="-1" <?php if ( count( $tmsht_users ) == count( $tmsht_selected_users ) ) echo 'checked="checked"'; ?>><strong><?php _ex( 'All users', 'All users', 'timesheet' ); ?></strong></label>
												<div class="tmsht_ts_report_user_block">
													<ul class="tmsht_ts_report_user_select">
														<?php foreach ( $tmsht_users as $user_id => $user_login ) { ?>
															<li class="tmsht_ts_report_user" data-username="<?php echo $user_login; ?>"><label class="tmsht_ts_report_user_label"><input id="tmsht_ts_report_user_id_<?php echo $user_id; ?>" class="tmsht_ts_report_user_checkbox" type="checkbox" name="tmsht_ts_report_user[]" value="<?php echo $user_id; ?>" <?php if ( in_array( $user_id, $tmsht_selected_users ) ) echo 'checked="checked"'; ?>><?php echo $user_login; ?></label></li>
														<?php } ?>
														<li class="tmsht_ts_report_user_search_results tmsht_hidden"><?php _ex( 'No results', 'Search user', 'timesheet' ); ?></li>
													</ul>
													<div class="tmsht_clearfix"></div>
												</div>
											<?php } else { ?>
												<div class="tmsht_ts_report_user_block">
													<ul class="tmsht_ts_report_user_select">
														<li class="tmsht_ts_report_no_users"><?php _ex( 'No users to select', 'Search user', 'timesheet' ); ?></li>
													</ul>
													<div class="tmsht_clearfix"></div>
												</div>
											<?php } ?>
										</div>
									</noscript>
								</div>
							</div>
							<div class="tmsht_ts_report_selected_users_container hide-if-no-js">
								<?php foreach( $tmsht_selected_users as $selected_user_id ) {
									if ( isset( $tmsht_users[ $selected_user_id ] ) ) {
										$selected_user_login = $tmsht_users[ $selected_user_id ]; ?>
											<span id="tmsht_ts_report_user_selected_<?php echo $selected_user_id; ?>" class="tmsht_ts_report_user_selected"><?php echo $selected_user_login; ?><label class="tmsht_ts_report_user_uncheck" for="tmsht_ts_report_user_id_<?php echo $selected_user_id; ?>"></label></span>
										<?php }
									} ?>
								<div class="tmsht_clearfix"></div>
							</div>
						</div>
						<div class="tmsht_clearfix"></div>
						<div class="tmsht_ts_report_generate">
							<input class="button-primary" type="submit" name="tmsht_generate_ts_report" value="<?php _ex( 'Apply', 'Apply ts report', 'timesheet' ); ?>">
						</div>
					</div>
				</form>
				<table id="tmsht_ts_report_table" class="widefat striped tmsht_ts_report_table tmsht_ts_report_table_head_timeline tmsht_ts_report_table_group_by_<?php echo $tmsht_ts_report_group_by; ?> tmsht_ts_report_table_<?php echo ( $ts_data ) ? 'has_data' : 'no_data'; ?>">
					<thead>
						<tr>
							<td class="tmsht_ts_report_table_td_dateline">&nbsp;</td>
							<td class="tmsht_ts_report_table_td_dateline">&nbsp;</td>
							<?php for ( $time_value = $timeline_from; $time_value <= $timeline_to; $time_value++ ) { ?>
								<td class="tmsht_ts_report_table_td_timeline"><div class="tmsht_ts_report_time_display"><?php echo ( $time_value > 9 ) ? $time_value : '&nbsp;' . $time_value ; ?></div></td>
							<?php } ?>
						</tr>
					</thead>
					<tbody>
						<?php if ( $ts_data ) {
							if ( $tmsht_ts_report_group_by == 'date' ) {
								$pre_date = '';

								foreach ( $ts_data as $date => $data_per_day ) {
									$user_data_1_per_day = $user_data_2_per_day = array();
									$i = 0;
									foreach ( $data_per_day as $user_id => $user_data_per_day ) {
										if ( $i == 0 ) {
											$user_data_1_per_day[ $user_id ] = $user_data_per_day;
										} else {
											$user_data_2_per_day[ $user_id ] = $user_data_per_day;
										}
										$i++;
									}
									$tmsht_is_today = ( date( $tmsht_date_format_default, strtotime( $date ) ) == date( $tmsht_date_format_default ) );
									$prev_date = date( $tmsht_date_format_default, strtotime( $date . ' -1 day' ) );
									$next_date = date( $tmsht_date_format_default, strtotime( $date . ' +1 day' ) );
									$user_data_1_per_day_keys = array_keys( $user_data_1_per_day );

									$roll_up_day = ( $user_data_1_per_day_keys[0] == -1 );

									$tmsht_tr_classes = 'tmsht_ts_report_table_tr ';
									$tmsht_tr_classes .= ( $tmsht_is_today ) ? ' tmsht_ts_report_table_tr_today_top' : '';
									$tmsht_tr_classes .= ( $tmsht_is_today && count( $ts_data[ $date ] ) == 1 ) ? ' tmsht_ts_report_table_tr_today_bottom' : '';
									$tmsht_tr_classes .= ( ! $tmsht_is_today && $prev_date != date( $tmsht_date_format_default ) ) ? ' tmsht_ts_report_table_tr_separate_top' : '';
									$tmsht_tr_classes .= ( ! $tmsht_is_today && $next_date != date( $tmsht_date_format_default ) && count( $ts_data[ $date ] ) == 1 ) ? ' tmsht_ts_report_table_tr_separate_bottom' : '';
									$tmsht_tr_classes .= ( $roll_up_day ) ? ' tmsht_ts_report_table_tr_roll_up' : '';

									$merge_td = ( ! $roll_up_day ) ? sprintf( 'rowspan="%d"', count( $data_per_day ) ) : sprintf( 'colspan="%d"', 2 );

									$tmsht_td_dateline_classes = 'tmsht_ts_report_table_td_dateline';
									$tmsht_td_dateline_classes .= ( $tmsht_is_today ) ? ' tmsht_ts_report_table_highlight_today' : '';
									$tmsht_td_dateline_classes .= ( in_array( strtolower( date( 'D', strtotime( $date ) ) ), $tmsht_options['weekends'] ) ) ? ' tmsht_ts_report_table_highlight_weekdays' : '';
									$tmsht_td_readonly = ( date( $tmsht_date_format_default, strtotime( $date ) ) < date( $tmsht_date_format_default ) && $tmsht_options['edit_past_days'] == 0 ); ?>
									<tr class="<?php echo $tmsht_tr_classes; ?>">
										<?php if ( $pre_date != $date ) { ?>
											<td class="<?php echo $tmsht_td_dateline_classes; ?>" <?php echo $merge_td; ?>>
												<div class="tmsht_ts_report_formatted_date"><?php echo date_i18n( $tmsht_date_format, strtotime( $date ) ); ?></div>
												<div class="tmsht_ts_report_weekday"><?php echo date_i18n( 'D', strtotime( $date ) ); ?></div>
											</td>
											<?php $pre_date = $date;
										}
										if ( $roll_up_day ) { ?>
											<td class="tmsht_ts_report_table_td_roll_up" colspan="<?php echo $timeline_to + 1; ?>">
												(<?php _e( 'No data to view', 'timesheet' ); ?>)
											</td>
										<?php } else {
											foreach ( $user_data_1_per_day as $user_id => $user_data_1 ) { ?>
												<td class="tmsht_ts_report_table_td_user">
													<strong><?php echo $tmsht_users[ $user_id ]; ?></strong>
												</td>
												<?php for ( $time_value = $timeline_from; $time_value <= $timeline_to; $time_value++ ) {
													$tmsht_td_timeline_classes = 'tmsht_ts_report_table_td_time tmsht_ts_report_table_td_time_' . $time_value;
													$tmsht_td_timeline_classes .= ( $tmsht_is_today ) ? ' tmsht_ts_report_table_td_today' : '';
													$tmsht_td_hover_classes = 'tmsht_ts_report_table_td_helper tmsht_ts_report_table_td_helper_' . $time_value; ?>
													<td class="<?php echo $tmsht_td_timeline_classes; ?>" data-td-index="<?php echo $time_value; ?>" data-td-date="<?php echo date( $tmsht_date_format_default ); ?>" data-td-time="<?php printf( "%02d:00", $time_value ); ?>">
														<div class="<?php echo $tmsht_td_hover_classes; ?>"></div>
														<div class="tmsht_ts_report_table_td_fill_group">
															<?php for ( $time_minutes = 0; $time_minutes < 60; $time_minutes += 5 ) {

																$search_date = date( $tmsht_date_format_default, strtotime( $date ) );
																$td_datetime = strtotime( sprintf( "%s %02d:%02d:00", $search_date, $time_value, $time_minutes ) );
																$td_legend_id = -1;
																$td_title = '';

																foreach ( $user_data_1 as $data ) {
																	if ( $data ) {
																		if ( strtotime( $data['time_from'] ) <= $td_datetime && strtotime( $data['time_to'] ) > $td_datetime ) {
																			$td_legend_id = $data['legend_id'];

																			$time_to_adjustment = ( date( 'i', strtotime( $data['time_to'] ) ) == 59 ) ? '24:00' : date( 'H:i', strtotime( $data['time_to'] ) );
																			$td_title = sprintf( "%s (%s - %s)", $tmsht_legends[ $td_legend_id ]['name'], date( 'H:i', strtotime( $data['time_from'] ) ), $time_to_adjustment );
																		}
																	}
																} ?>
																<div class="tmsht_ts_report_table_td_fill" style="background-color: <?php echo $tmsht_legends[ $td_legend_id ]['color']; ?>;" data-fill-time-from="<?php printf( "%02d:%02d", $time_value, $time_minutes ); ?>" data-fill-time-to="<?php printf( "%02d:%02d", ( $time_minutes < 55 ) ? $time_value : $time_value + 1, ( $time_minutes < 55 ) ? $time_minutes + 5 : 0 ); ?>" data-legend-id="<?php echo $td_legend_id; ?>" title="<?php echo $td_title; ?>"></div>
															<?php } ?>
														</div>
														<?php if ( $tmsht_td_readonly ) { ?>
															<div class="tmsht_ts_report_table_td_readonly_fill"></div>
														<?php } ?>
													</td>
												<?php }
											}
										} ?>
									</tr>
									<?php end( $user_data_2_per_day );
									$tmsht_last_user_id = key( $user_data_2_per_day );
									foreach ( $user_data_2_per_day as $user_id => $user_data_2 ) {
										$tmsht_tr_classes = 'tmsht_ts_report_table_tr';
										$tmsht_tr_classes .= ( $tmsht_is_today && $tmsht_last_user_id == $user_id ) ? ' tmsht_ts_report_table_tr_today_bottom' : '';
										$tmsht_tr_classes .= ( ! $tmsht_is_today && $tmsht_last_user_id == $user_id && ! array_key_exists( $next_date, $ts_data ) ) ? ' tmsht_ts_report_table_tr_separate_bottom' : ''; ?>
										<tr class="<?php echo $tmsht_tr_classes; ?>">
											<td class="tmsht_ts_report_table_td_user">
												<strong><?php echo $tmsht_users[ $user_id ]; ?></strong>
											</td>
											<?php for ( $time_value = $timeline_from; $time_value <= $timeline_to; $time_value++ ) {
												$tmsht_td_timeline_classes = 'tmsht_ts_report_table_td_time tmsht_ts_report_table_td_time_' . $time_value;
												$tmsht_td_timeline_classes .= ( $tmsht_is_today ) ? ' tmsht_ts_report_table_td_today' : '';
												$tmsht_td_hover_classes = 'tmsht_ts_report_table_td_helper tmsht_ts_report_table_td_helper_' . $time_value; ?>
												<td class="<?php echo $tmsht_td_timeline_classes; ?>" data-td-index="<?php echo $time_value; ?>" data-td-date="<?php echo date( $tmsht_date_format_default ); ?>" data-td-time="<?php printf( "%02d:00", $time_value ); ?>">
													<div class="<?php echo $tmsht_td_hover_classes; ?>"></div>
													<div class="tmsht_ts_report_table_td_fill_group">
														<?php for ( $time_minutes = 0; $time_minutes < 60; $time_minutes += 5 ) {

															$search_date = date( $tmsht_date_format_default, strtotime( $date ) );
															$td_datetime = strtotime( sprintf( "%s %02d:%02d:00", $search_date, $time_value, $time_minutes ) );
															$td_legend_id = -1;
															$td_title = '';

															foreach ( $user_data_2 as $data ) {
																if ( $data ) {
																	if ( strtotime( $data['time_from'] ) <= $td_datetime && strtotime( $data['time_to'] ) > $td_datetime ) {
																		$td_legend_id = $data['legend_id'];

																		$time_to_adjustment = ( date( 'i', strtotime( $data['time_to'] ) ) == 59 ) ? '24:00' : date( 'H:i', strtotime( $data['time_to'] ) );
																		$td_title = sprintf( "%s (%s - %s)", $tmsht_legends[ $td_legend_id ]['name'], date( 'H:i', strtotime( $data['time_from'] ) ), $time_to_adjustment );
																	}
																}
															} ?>
															<div class="tmsht_ts_report_table_td_fill" style="background-color: <?php echo $tmsht_legends[ $td_legend_id ]['color']; ?>;" data-fill-time-from="<?php printf( "%02d:%02d", $time_value, $time_minutes ); ?>" data-fill-time-to="<?php printf( "%02d:%02d", ( $time_minutes < 55 ) ? $time_value : $time_value + 1, ( $time_minutes < 55 ) ? $time_minutes + 5 : 0 ); ?>" data-legend-id="<?php echo $td_legend_id; ?>" title="<?php echo $td_title; ?>"></div>
														<?php } ?>
													</div>
													<?php if ( $tmsht_td_readonly ) { ?>
														<div class="tmsht_ts_report_table_td_readonly_fill"></div>
													<?php } ?>
												</td>
											<?php } ?>
										</tr>
									<?php }
								}
							} else if ( $tmsht_ts_report_group_by == 'user' ) {
								end( $ts_data );
								$last_user_id = key( $ts_data );
								$pre_user_id = -1;
								foreach ( $ts_data as $user_id => $user_data ) {
										$user_data_1_per_day = $user_data_2_per_day = array();
										$i = 0;
									foreach ( $user_data as $date => $user_data_per_day ) {
										if ( $i == 0 ) {
											$user_data_1_per_day[ $date ] = $user_data_per_day;
										} else {
											$user_data_2_per_day[ $date ] = $user_data_per_day;
										}
										$i++;
									}

									$roll_up_day = ( count( $user_data_1_per_day ) == 0 );

									$tmsht_tr_classes = 'tmsht_ts_report_table_tr tmsht_ts_report_table_tr_separate_top';
									$tmsht_tr_classes .= ( count( $user_data ) == 0 ) ? ' tmsht_ts_report_table_tr_separate_bottom' : '';
									$tmsht_tr_classes .= ( $roll_up_day ) ? ' tmsht_ts_report_table_tr_roll_up' : '';

									$merge_td = ( ! $roll_up_day ) ? sprintf( 'rowspan="%d"', count( $user_data ) ) : sprintf( 'colspan="%d"', 2 ); ?>
									<tr class="<?php echo $tmsht_tr_classes; ?>">
										<?php if ( $pre_user_id != $user_id ) { ?>
											<td class="tmsht_ts_report_table_td_user" <?php echo $merge_td; ?>>
												<strong><?php echo $tmsht_users[ $user_id ]; ?></strong>
											</td>
											<?php $pre_user_id = $user_id;
										}
										if ( $roll_up_day ) { ?>
											<td class="tmsht_ts_report_table_td_roll_up" colspan="<?php echo $timeline_to + 1; ?>">(<?php _e( 'No data to view', 'timesheet' ); ?>)</td>
										<?php } else {
											foreach ( $user_data_1_per_day as $date => $user_data_1 ) {
												$tmsht_is_today = ( date( $tmsht_date_format_default, strtotime( $date ) ) == date( $tmsht_date_format_default ) );
												$tmsht_td_dateline_classes = 'tmsht_ts_report_table_td_dateline';
												$tmsht_td_dateline_classes .= ( $tmsht_is_today ) ? ' tmsht_ts_report_table_highlight_today tmsht_ts_report_table_td_today' : '';
												$tmsht_td_dateline_classes .= ( in_array( strtolower( date( 'D', strtotime( $date ) ) ), $tmsht_options['weekends'] ) ) ? ' tmsht_ts_report_table_highlight_weekdays' : '';
												$tmsht_td_readonly = ( date( $tmsht_date_format_default, strtotime( $date ) ) < date( $tmsht_date_format_default ) && $tmsht_options['edit_past_days'] == 0 ); ?>
												<td class="<?php echo $tmsht_td_dateline_classes; ?>">
													<div class="tmsht_ts_report_formatted_date"><?php echo date_i18n( $tmsht_date_format, strtotime( $date ) ); ?></div>
													<div class="tmsht_ts_report_weekday"><?php echo date_i18n( 'D', strtotime( $date ) ); ?></div>
												</td>
											<?php }
											for ( $time_value = $timeline_from; $time_value <= $timeline_to; $time_value++ ) {
												$tmsht_td_timeline_classes = 'tmsht_ts_report_table_td_time tmsht_ts_report_table_td_time_' . $time_value;
												$tmsht_td_timeline_classes .= ( $tmsht_is_today ) ? ' tmsht_ts_report_table_td_today' : '';
												$tmsht_td_hover_classes = 'tmsht_ts_report_table_td_helper tmsht_ts_report_table_td_helper_' . $time_value; ?>
												<td class="<?php echo $tmsht_td_timeline_classes; ?>" data-td-index="<?php echo $time_value; ?>" data-td-date="<?php echo date( $tmsht_date_format_default ); ?>" data-td-time="<?php printf( "%02d:00", $time_value ); ?>">
													<div class="<?php echo $tmsht_td_hover_classes; ?>"></div>
													<div class="tmsht_ts_report_table_td_fill_group">
														<?php for ( $time_minutes = 0; $time_minutes < 60; $time_minutes += 5 ) {

															$search_date = date( $tmsht_date_format_default, strtotime( $date ) );
															$td_datetime = strtotime( sprintf( "%s %02d:%02d:00", $search_date, $time_value, $time_minutes ) );
															$td_legend_id = -1;
															$td_title = '';

															foreach ( $user_data_1 as $data ) {
																if ( $data ) {
																	if ( strtotime( $data['time_from'] ) <= $td_datetime && strtotime( $data['time_to'] ) > $td_datetime ) {
																		$td_legend_id = $data['legend_id'];

																		$time_to_adjustment = ( date( 'i', strtotime( $data['time_to'] ) ) == 59 ) ? '24:00' : date( 'H:i', strtotime( $data['time_to'] ) );
																		$td_title = sprintf( "%s (%s - %s)", $tmsht_legends[ $td_legend_id ]['name'], date( 'H:i', strtotime( $data['time_from'] ) ), $time_to_adjustment );
																	}
																}
															} ?>
															<div class="tmsht_ts_report_table_td_fill" style="background-color: <?php echo $tmsht_legends[ $td_legend_id ]['color']; ?>;" data-fill-time-from="<?php printf( "%02d:%02d", $time_value, $time_minutes ); ?>" data-fill-time-to="<?php printf( "%02d:%02d", ( $time_minutes < 55 ) ? $time_value : $time_value + 1, ( $time_minutes < 55 ) ? $time_minutes + 5 : 0 ); ?>" data-legend-id="<?php echo $td_legend_id; ?>" title="<?php echo $td_title; ?>"></div>
														<?php } ?>
													</div>
													<?php if ( $tmsht_td_readonly ) { ?>
														<div class="tmsht_ts_report_table_td_readonly_fill"></div>
													<?php } ?>
												</td>
											<?php }
										} ?>
									</tr>
									<?php end( $user_data_2_per_day );
									$last_date = key( $user_data_2_per_day );
									foreach ( $user_data_2_per_day as $date => $user_data_2 ) {

										$tmsht_tr_dateline_classes = 'tmsht_ts_report_table_tr';
										$tmsht_tr_dateline_classes .= ( $date == $last_date && $user_id == $last_user_id ) ? ' tmsht_ts_report_table_tr_separate_bottom' : '';

										$tmsht_td_dateline_classes = 'tmsht_ts_report_table_td_dateline';
										$tmsht_td_dateline_classes .= ( date( $tmsht_date_format_default, strtotime( $date ) ) == date( $tmsht_date_format_default ) ) ? ' tmsht_ts_report_table_highlight_today  tmsht_ts_report_table_td_today' : '';
										$tmsht_td_dateline_classes .= ( in_array( strtolower( date( 'D', strtotime( $date ) ) ), $tmsht_options['weekends'] ) ) ? ' tmsht_ts_report_table_highlight_weekdays' : '';
										$tmsht_td_readonly = ( date( $tmsht_date_format_default, strtotime( $date ) ) < date( $tmsht_date_format_default ) && $tmsht_options['edit_past_days'] == 0 ); ?>
										<tr class="<?php echo $tmsht_tr_dateline_classes; ?>">
											<td class="<?php echo $tmsht_td_dateline_classes; ?>">
												<div class="tmsht_ts_report_formatted_date"><?php echo date_i18n( $tmsht_date_format, strtotime( $date ) ); ?></div>
												<div class="tmsht_ts_report_weekday"><?php echo date_i18n( 'D', strtotime( $date ) ); ?></div>
											</td>
											<?php for ( $time_value = $timeline_from; $time_value <= $timeline_to; $time_value++ ) {
												$tmsht_is_today = ( date( $tmsht_date_format_default, strtotime( $date ) ) == date( $tmsht_date_format_default ) );
												$tmsht_td_timeline_classes = 'tmsht_ts_report_table_td_time tmsht_ts_report_table_td_time_' . $time_value;
												$tmsht_td_timeline_classes .= ( $tmsht_is_today ) ? ' tmsht_ts_report_table_td_today' : '';
												$tmsht_td_hover_classes = 'tmsht_ts_report_table_td_helper tmsht_ts_report_table_td_helper_' . $time_value; ?>
												<td class="<?php echo $tmsht_td_timeline_classes; ?>" data-td-index="<?php echo $time_value; ?>" data-td-date="<?php echo date( $tmsht_date_format_default ); ?>" data-td-time="<?php printf( "%02d:00", $time_value ); ?>">
													<div class="<?php echo $tmsht_td_hover_classes; ?>"></div>
													<div class="tmsht_ts_report_table_td_fill_group">
														<?php for ( $time_minutes = 0; $time_minutes < 60; $time_minutes += 5 ) {

															$search_date = date( $tmsht_date_format_default, strtotime( $date ) );
															$td_datetime = strtotime( sprintf( "%s %02d:%02d:00", $search_date, $time_value, $time_minutes ) );
															$td_legend_id = -1;
															$td_title = '';

															foreach ( $user_data_2 as $data ) {
																if ( $data ) {
																	if ( strtotime( $data['time_from'] ) <= $td_datetime && strtotime( $data['time_to'] ) > $td_datetime ) {
																		$td_legend_id = $data['legend_id'];

																		$time_to_adjustment = ( date( 'i', strtotime( $data['time_to'] ) ) == 59 ) ? '24:00' : date( 'H:i', strtotime( $data['time_to'] ) );
																		$td_title = sprintf( "%s (%s - %s)", $tmsht_legends[ $td_legend_id ]['name'], date( 'H:i', strtotime( $data['time_from'] ) ), $time_to_adjustment );
																	}
																}
															} ?>
															<div class="tmsht_ts_report_table_td_fill" style="background-color: <?php echo $tmsht_legends[ $td_legend_id ]['color']; ?>;" data-fill-time-from="<?php printf( "%02d:%02d", $time_value, $time_minutes ); ?>" data-fill-time-to="<?php printf( "%02d:%02d", ( $time_minutes < 55 ) ? $time_value : $time_value + 1, ( $time_minutes < 55 ) ? $time_minutes + 5 : 0 ); ?>" data-legend-id="<?php echo $td_legend_id; ?>" title="<?php echo $td_title; ?>"></div>
														<?php } ?>
													</div>
													<?php if ( $tmsht_td_readonly ) { ?>
														<div class="tmsht_ts_report_table_td_readonly_fill"></div>
													<?php } ?>
												</td>
											<?php } ?>
										</tr>
									<?php }
								}
							}
						} else { ?>
							<tr class="tmsht_ts_report_table_tr">
								<td class="tmsht_ts_report_table_td_dateline tmsht_ts_report_table_td_no_data"><strong><?php _e( 'No data to view', 'timesheet' ); ?></strong></td>
								<td>&nbsp;</td>
								<?php for ( $time_value = $timeline_from; $time_value <= $timeline_to; $time_value++ ) {
									$td_legend_id = -1; ?>
									<td class="tmsht_ts_report_table_td_time" data-legend-id="<?php echo $td_legend_id; ?>">
										<div class="tmsht_ts_report_table_td_fill" style="background-color: <?php echo $tmsht_legends[ $td_legend_id ]['color']; ?>;"></div>
									</td>
								<?php } ?>
							</tr>
						<?php } ?>
					</tbody>
					<tfoot>
						<tr>
							<td class="tmsht_ts_report_table_td_dateline">&nbsp;</td>
							<td class="tmsht_ts_report_table_td_dateline">&nbsp;</td>
							<?php for ( $time_value = $timeline_from; $time_value <= $timeline_to; $time_value++ ) { ?>
								<td class="tmsht_ts_report_table_td_timeline"><div class="tmsht_ts_report_time_display"><?php echo ( $time_value > 9 ) ? $time_value : '&nbsp;' . $time_value ; ?></div></td>
							<?php } ?>
						</tr>
					</tfoot>
				</table>
			</div>
		</div>
	<?php }
}

if ( ! function_exists( 'tmsht_date_period' ) ) {
	function tmsht_date_period( $date_start, $date_end ) {
		$period = array();

		while( $date_start < $date_end ) {
			$period[] = $date_start->format( 'Y-m-d' );
			$date_start->modify( '+1 day' );
		}

		return $period;
	}
}

if ( ! function_exists( 'tmsht_replacement_content_reminder' ) ) {
	function tmsht_replacement_content_reminder( $string = "", $params = array() ) {

		if ( empty( $string ) || ! $params ) {
			return false;
		}

		$replacement = array(
			'{user_name}' => $params['user']['display_name'],
			'{list_days}' => $params['days'],
			'{ts_page}'   => sprintf( '<a href="%1$s" target="_blank">%1$s</a>', admin_url( 'admin.php?page=timesheet_ts_user' ) ),
		);

		$string = preg_replace( '|\{\{ts_page_link\}(.*)\{\/ts_page_link\}\}|', sprintf( '<a href="%1$s" target="_blank">%2$s</a>', admin_url( 'admin.php?page=timesheet_ts_user' ), '\\1' ), $string );
		$string = str_replace( array_keys( $replacement ), $replacement, $string );

		return nl2br( $string );

	}
}

if ( ! function_exists ( 'tmsht_reminder_to_email' ) ) {
	function tmsht_reminder_to_email() {
		global $wpdb, $wp_version, $tmsht_options;

		if ( ! $tmsht_options ) {
			tmsht_register_options();
		}

		$tmsht_required_days_arr = array( 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun' );
		$tmsht_weekends = ( isset( $tmsht_options['weekends'] ) && is_array( $tmsht_options['weekends'] ) ) ? $tmsht_options['weekends'] : array();

		foreach ( $tmsht_weekends as $weekend ) {
			$key = array_search( ucfirst( $weekend ), $tmsht_required_days_arr );
			if ( is_int( $key ) ) {
				unset( $tmsht_required_days_arr[ $key ] );
			}
		}

		/* Get users */
		$tmsht_users = array();
		$tmsht_roles = $tmsht_options['display_pages']['ts_user']['user_roles'];

		foreach( $tmsht_roles as $role ) {
			$users_in_role = $wpdb->get_results(
				"SELECT users.ID, users.user_login, users.user_email, users.display_name
				FROM `{$wpdb->base_prefix}users` AS users, `{$wpdb->base_prefix}usermeta` AS umeta
				WHERE users.ID = umeta.user_id
				AND umeta.meta_key = '{$wpdb->prefix}capabilities'
				AND umeta.meta_value LIKE '%\"{$role}\"%'
				ORDER BY users.ID ASC",
				OBJECT_K
			);

			if ( $role == 'administrator' && is_multisite() && ! is_main_site() ) {
				$tmsht_super_admins = get_super_admins();

				foreach ( $tmsht_super_admins as $super_admin ) {
					$get_user = get_user_by( 'login', $super_admin );
					if ( $get_user ) {
						$add_user = array(
							'ID'           => $get_user->ID,
							'user_login'   => $get_user->user_login,
							'display_name' => $get_user->display_name,
							'user_email'   => $get_user->user_email
						);
						$users_in_role[ $get_user->ID ] = (object) $add_user;
					}
				}
			}

			ksort( $users_in_role );

			if ( count( $users_in_role ) == 0 ) {
				continue;
			}

			foreach ( $users_in_role as $user_id => $user_data ) {
				$tmsht_users[ $user_data->ID ] = array(
					'login'        => $user_data->user_login,
					'display_name' => $user_data->display_name,
					'email'        => $user_data->user_email
				);
			}
		}

		asort( $tmsht_users );

		$tmsht_date_format = $tmsht_options['date_format'];
		$tmsht_date_format_default = 'Y-m-d';

		$date_from = date( $tmsht_date_format_default, strtotime( "next Mon" ) );
		$date_to = date( $tmsht_date_format_default, strtotime( "next Mon +7 days" ) );

		$date_start = new DateTime( $date_from );
		$date_end = new DateTime( $date_to );
		$date_period = tmsht_date_period( $date_start, $date_end );

		foreach ( $tmsht_users as $user_id => $user ) {

			$tmsht_blank_days = array();

			foreach( $date_period as $date ) {

				$ts_get_data = $wpdb->get_results( "SELECT `id` FROM `{$wpdb->prefix}tmsht_ts` WHERE date(`time_from`) >= '" . date( $tmsht_date_format_default, strtotime( $date ) ) . "' AND `user_id` = " . $user_id, ARRAY_A );

				if ( $ts_get_data ) {
					continue;
				}

				$tmsht_short_day_name = date( 'D', strtotime( $date ) );
				$tmsht_full_day_name = date_i18n( 'l', strtotime( $date ) );
				$tmsht_date = date_i18n( $tmsht_date_format, strtotime( $date ) );

				if ( in_array( $tmsht_short_day_name, $tmsht_required_days_arr ) ) {
					$tmsht_blank_days[] = sprintf( '%s (%s)', $tmsht_date, $tmsht_full_day_name );
				}
			}

			if ( $tmsht_blank_days ) {

				$subject = $tmsht_options['content_reminder']['subject'];

				$tmsht_list_days = '<ul>';
				foreach ( $tmsht_blank_days as $day ) {
					$tmsht_list_days .= sprintf( '<li>%s</li>', $day );
				}
				$tmsht_list_days .= '</ul>';

				$params = array(
					'user' => $user,
					'days' => $tmsht_list_days,
				);

				$message = tmsht_replacement_content_reminder( $tmsht_options['content_reminder']['message'], $params );

				$headers = "MIME-Version: 1.0\r\n";
				$headers .= "Content-type: text/html; charset=utf-8\r\n";

				wp_mail( $user['email'], $subject, $message, $headers );
			}
		}
	}
}

if ( ! function_exists( 'tmsht_action_links' ) ) {
	function tmsht_action_links( $links, $file ) {
		if ( ! is_network_admin() ) {
			/* Static so we don't call plugin_basename on every plugin row. */
			static $this_plugin;
			if ( ! $this_plugin )
				$this_plugin = plugin_basename( __FILE__ );
			if ( $file == $this_plugin ) {
				$settings_link = '<a href="admin.php?page=timesheet_settings">' . __( 'Settings', 'timesheet' ) . '</a>';
				array_unshift( $links, $settings_link );
			}
		}
		return $links;
	}
}

if ( ! function_exists ( 'tmsht_links' ) ) {
	function tmsht_links( $links, $file ) {
		$base = plugin_basename( __FILE__ );
		if ( $file == $base ) {
			if ( ! is_network_admin() )
				$links[]	=	'<a href="admin.php?page=timesheet_settings">' . __( 'Settings', 'timesheet' ) . '</a>';
			$links[]	=	'<a href="https://support.bestwebsoft.com/hc/en-us/sections/202101246" target="_blank">' . __( 'FAQ', 'timesheet' ) . '</a>';
			$links[]	=	'<a href="https://support.bestwebsoft.com">' . __( 'Support', 'timesheet' ) . '</a>';
		}
		return $links;
	}
}

if ( ! function_exists ( 'tmsht_plugin_banner' ) ) {
	function tmsht_plugin_banner() {
		global $hook_suffix, $tmsht_plugin_info;

		if ( 'plugins.php' == $hook_suffix ) {
			global $tmsht_options;

			$tmsht_options = ( empty ( $tmsht_options ) ) ? get_option( 'tmsht_options' ) : $tmsht_options;

			if ( isset( $tmsht_options['first_install'] ) && strtotime( '-1 week' ) > $tmsht_options['first_install'] ) {
				bws_plugin_banner( $tmsht_plugin_info, 'tmsht', 'timesheet', '6316f137e58adf88e055718d7cc85346', '606', 'timesheet' );
			}

			bws_plugin_banner_to_settings( $tmsht_plugin_info, 'tmsht_options', 'timesheet', 'admin.php?page=timesheet_settings' );
		}

		if ( isset( $_GET['page'] ) && 'timesheet_settings' == $_GET['page'] ) {
			bws_plugin_suggest_feature_banner( $tmsht_plugin_info, 'tmsht_options', 'timesheet' );
		}
	}
}

if ( ! function_exists ( 'tmsht_add_weekly' ) ) {
	function tmsht_add_weekly( $schedules ) {
		$schedules['tmsht_weekly'] = array(
			'interval' => 604800,
			'display' => __( 'Once Weekly', 'timesheet' )
		);
		return $schedules;
	}
}

if ( ! function_exists ( 'tmsht_unistall' ) ) {
	function tmsht_unistall() {
		global $wpdb;

		if ( ! function_exists( 'get_plugins' ) )
			require_once( ABSPATH . 'wp-admin/includes/plugin.php' );

		$all_plugins = get_plugins();

		if ( ! array_key_exists( 'timesheet-pro/timesheet-pro.php', $all_plugins ) ) {
			if ( is_multisite() ) {
				$old_blog = $wpdb->blogid;
				/* Get all blog ids */
				$blogids = $wpdb->get_col( "SELECT `blog_id` FROM $wpdb->blogs" );
				foreach ( $blogids as $blog_id ) {
					switch_to_blog( $blog_id );

					$meta_key = '_tmsht_ts_report_filters';
					$users = get_users( array(
						'blog_id' => $blog_id,
						'meta_key' => $meta_key,
					));

					foreach ( $users as $user ) {
						delete_user_meta( $user->ID, $meta_key );
					}

					delete_option( 'tmsht_options' );
					$wpdb->query( "DROP TABLE IF EXISTS `{$wpdb->prefix}tmsht_legends`, `{$wpdb->prefix}tmsht_ts`;" );
				}
				switch_to_blog( $old_blog );
			} else {
				$meta_key = '_tmsht_ts_report_filters';
				$users = get_users( array(
					'blog_id' => get_current_blog_id(),
					'meta_key' => $meta_key,
				));

				foreach ( $users as $user ) {
					delete_user_meta( $user->ID, $meta_key );
				}

				delete_option( 'tmsht_options' );
				$wpdb->query( "DROP TABLE IF EXISTS `{$wpdb->prefix}tmsht_legends`, `{$wpdb->prefix}tmsht_ts`;" );
			}
		}

		require_once( dirname( __FILE__ ) . '/bws_menu/bws_include.php' );
		bws_include_init( plugin_basename( __FILE__ ) );
		bws_delete_plugin( plugin_basename( __FILE__ ) );
	}
}

/* Calling a function add administrative menu. */
add_action( 'admin_menu', 'tmsht_admin_menu' );
/* Initialization */
add_action( 'plugins_loaded', 'tmsht_plugins_loaded' );
add_action( 'init', 'tmsht_init' );
add_action( 'admin_init', 'tmsht_admin_init' );
/* Adding stylesheets */
add_action( 'admin_enqueue_scripts', 'tmsht_admin_scripts_styles' );
/* delete ts data, when user was deleted */
add_action( 'delete_user', 'tmsht_delete_user' );
/* Additional links on the plugin page */
add_filter( 'plugin_action_links', 'tmsht_action_links', 10, 2 );
add_filter( 'plugin_row_meta', 'tmsht_links', 10, 2 );
add_action( 'admin_notices', 'tmsht_plugin_banner' );

add_action( 'tmsht_reminder_to_email', 'tmsht_reminder_to_email' );
add_filter( 'cron_schedules', 'tmsht_add_weekly' );

register_uninstall_hook( __FILE__, 'tmsht_unistall' );