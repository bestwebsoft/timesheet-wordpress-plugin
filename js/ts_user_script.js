(function($){
	$( document ).ready( function() {
		/* Date picker */
		tmsht_datetime_options = tmsht_datetime_options || {};

		tmsht_datetime_options = $.extend({
			'timepicker'        : false,
			'format'            : ( Boolean( tmsht_datetime_options['timepicker'] ) ) ? 'Y-m-d H:s' : 'Y-m-d',
			'closeOnDateSelect' : true,
			'onSelectDate'      : function( $dtp, current, input ) {
				var input_id = current.attr('id'),
					date_target = ( input_id == 'tmsht_ts_user_date_from' ) ? 'date_from' : 'date_to',
					$input_date_from = $( '#tmsht_ts_user_date_from' ),
					$input_date_to = $( '#tmsht_ts_user_date_to' );

				if ( date_target == 'date_from' && $input_date_from.val() > $input_date_to.val() ) {
					$input_date_to.val( $input_date_from.val() );
				}

				if ( date_target == 'date_to' && $input_date_from.val() > $input_date_to.val() ) {
					$input_date_from.val( $input_date_to.val() );
				}
			}
		}, tmsht_datetime_options );

		$.datetimepicker.setLocale( tmsht_datetime_options['locale'] );

		$( ".tmsht_date_datepicker_input" ).datetimepicker( tmsht_datetime_options );

		$( ".tmsht_date_datepicker_input" ).on( 'click touchstart', function(e) {
			$( this ).datetimepicker('show');
		}).on( 'keydown', function() {
			return false;
		});
		/* Date picker */

		/* Fake selector legends */
		$( '.tmsht_ts_user_legend' ).tmsht_ts_user_select_legend();

		/* Show details table */
		$( '#tmsht_ts_user_table' ).tmsht_ts_user_table_handler();

		var get_legend = function( legend_id ) {
			var legend_id = legend_id || false,
				$ts_legend = $( '.tmsht_ts_user_legend' ),
				$ts_legend_option = null;

			if ( legend_id !== false ) {
				$ts_legend_option = $ts_legend.find( 'option[value="' + legend_id + '"]' );
			} else {
				$ts_legend_option = $ts_legend.find( 'option:selected' );
			}

			return {
				'id'    : $ts_legend_option.val(),
				'title' : $ts_legend_option.text(),
				'color' : $ts_legend_option.attr( 'data-color' )
			}
		}

		$( '#tmsht_ts_user_table tbody' ).selectable({
			filter   : 'td',
			cancel   : '.tmsht_ts_user_table_td_readonly',
			appendTo : '#tmsht_ts_user_table_area',
			selecting: function( event, ui ) {

				$( '#tmsht_ts_user_context_menu' ).trigger( 'hide_context_menu' );

				/* Fix select area */
				$( '.ui-selectable-helper' ).css({
					'margin-top'  : -1 * ( parseInt( ( $( '#tmsht_ts_user_table' ).offset().top ) ) - 2 ),
					'margin-left' : -1 * ( parseInt( ( $( '#tmsht_ts_user_table' ).offset().left ) ) - 2 )
				});

				if ( $( ui.selecting ).is( '.tmsht_ts_user_table_td_time' ) ) {
					var $td = $( ui.selecting ),
						$tr = $td.parent(),
						prev_legend_id = $td.attr( 'data-legend-id' ),
						$td_fill = $td.find( '.tmsht_ts_user_table_td_fill' ),
						legend = get_legend();


					if ( $td.hasClass( 'tmsht_ts_user_table_td_readonly' ) ) {
						return false;
					}

					$td
						.attr( 'data-legend-id', legend.id )
						.attr( 'data-prev-legend-id', prev_legend_id )
						.removeAttr( 'title' )
						.removeAttr( 'data-td-group' );

					$td_fill.css( 'background-color', legend.color );

					if ( legend.id > 0 ) {
						$td.addClass( 'tmsht_ts_user_table_td_selected' );
					} else {
						$td.removeClass( 'tmsht_ts_user_table_td_selected' );
					}

					$tr.find( '.tmsht_tr_date[disabled="disabled"]' ).attr( 'disabled', false );
				}
			},
			unselecting: function( event, ui ) {
				if ( $( ui.unselecting ).is( '.tmsht_ts_user_table_td_time' ) ) {
					var $td = $( ui.unselecting ),
						$tr = $td.parent(),
						prev_legend_id = $td.attr( 'data-prev-legend-id' ),
						$td_fill = $td.find( '.tmsht_ts_user_table_td_fill' ),
						legend = get_legend( prev_legend_id );

					$td
						.attr( 'data-legend-id', legend.id )
						.removeAttr( 'data-prev-legend-id' );

					$td_fill.css( 'background-color', legend.color );

					if ( legend.id > 0 ) {
						$td.addClass( 'tmsht_ts_user_table_td_selected' );
					} else {
						$td.removeClass( 'tmsht_ts_user_table_td_selected' );
					}
				}
			},
			selected: function( event, ui ) {
				$( ui.selected ).removeClass( 'ui-selected' );
			},
			stop: function( event, ui ) {
				$( '#tmsht_ts_user_table' ).tmsht_ts_user_table_handler( 'show_details' );
			}
		});

		$( '#tmsht_transposition_tbl' ).on( 'click', function(e) {
			var $table = $( '#tmsht_ts_user_table' ),
				count = $table.find( 'tr:first td' ).length - 1,
				data = {};

			for ( $i = 0; $i <= count; $i++ ) {
				data[ $i ] = $table.find( 'thead, tbody' ).children().find( 'td:eq(' + $i + ')' );
			}

			$table.find( 'thead, tbody, tfoot' ).empty();

			for ( $i = 0; $i <= count; $i++ ) {
				if ( $i == 0 ) {
					$table.find( 'thead' ).append( $( '<tr/>' ).append( data[ $i ] ) );
				} else {
					$table.find( 'tbody' ).append( $( '<tr/>' ).append( data[ $i ] ) );
				}
			}

			$tfoot = $table.find( 'thead tr' ).clone();
			$tfoot.find( 'input' ).remove();
			$table.find( 'tfoot' ).append( $tfoot );

			$table.attr( 'class', function() {
				var classes = $( this ).attr( 'class' );

				if ( $( this ).hasClass( 'tmsht_ts_user_table_head_timeline' ) ) {
					return classes.replace( 'tmsht_ts_user_table_head_timeline', 'tmsht_ts_user_table_head_dateline' );
				} else {
					return classes.replace( 'tmsht_ts_user_table_head_dateline', 'tmsht_ts_user_table_head_timeline' );;
				}
			});
			e.preventDefault();
			return false;
		});


		/* On right click */
		$( '#tmsht_ts_user_table' ).on( 'contextmenu', '.tmsht_ts_user_table_td_time', function( e ) {
			var $td = $( this );

			if ( ! $td.hasClass( 'tmsht_ts_user_table_td_readonly' ) ) {
				if ( $td.is( '[data-legend-id!="-1"]' ) ) {
					$( '#tmsht_ts_user_context_menu' ).trigger( 'show_context_menu', e );
					$td.addClass( 'tmsht_context_menu' );
				} else {
					$( '#tmsht_ts_user_context_menu' ).trigger( 'hide_context_menu' );
				}
			}
			e.preventDefault();
			return false;
		});

		/* On taphold */
		var touch_timer;
		$( '#tmsht_ts_user_table tbody' ).on( 'touchstart', '.tmsht_ts_user_table_td_time', function( e ) {
			var $td = $( this );

			if ( ! $td.hasClass( 'tmsht_ts_user_table_td_readonly' ) ) {
				touch_timer = setTimeout( function() {
					$( '#tmsht_ts_user_context_menu' ).trigger( 'show_context_menu', e );
					$td.addClass( 'tmsht_context_menu' );
				}, 600 );
			}
		}).on( 'touchend touchmove', '.tmsht_ts_user_table_td_time', function() {
			if ( touch_timer ) {
				clearTimeout( touch_timer );
			}
		});

		/* Context menu */
		$( '#tmsht_ts_user_context_menu' ).on( 'show_context_menu', function( event, e ) {
			var $context_menu = $( this ),
				$wp_bar = $( '#wpadminbar' ),
				width = $context_menu.width(),
				ts_table_offset_left = parseInt( $( '#tmsht_ts_user_table' ).offset().left ),
				margin_left = 0,
				coorX = coorY = 0;

			if ( e.type == 'touchstart' ) { /* mobile */
				var touch = e.originalEvent.touches[0] || e.originalEvent.changedTouches[0];
				coorX = touch.clientX + window.scrollX;
				coorY = touch.clientY + window.scrollY;
			} else { /* desktop */
				coorX = e.clientX + window.scrollX;
				coorY = e.clientY + window.scrollY;
			}

			coorX = coorX - ts_table_offset_left;

			if ( $wp_bar.css( 'position' ) == 'fixed' ) {
				coorY = coorY - parseInt( $wp_bar.height() );
			}

			margin_left = ( $( window ).width() > coorX + width + ts_table_offset_left ) ? 0 : -1 * width;

			$context_menu
				.trigger( 'hide_context_menu' )
				.css({
					'left'             : coorX,
					'top'              : coorY,
					'margin-left'      : margin_left + 4,
					'margin-top'       : '2px'
				})
				.show( 100 )
				.attr( 'data-visible', 'true' );
		}).on( 'hide_context_menu', function() {
			var $context_menu = $( this ),
				$td = $( '#tmsht_ts_user_table tbody td.tmsht_context_menu' );

			$td.removeClass( 'tmsht_context_menu' );
			$context_menu
				.hide()
				.attr( 'data-visible', 'false' );
		});

		$( window ).on( 'resize', function() {
			$( '#tmsht_ts_user_context_menu' ).trigger( 'hide_context_menu' );
		});

		$( document ).on( 'click', function( e ) {
			if ( e.button != 0 || $( e.target ).closest( '#tmsht_ts_user_context_menu' ).length ) {
				return;
			}

			$( '#tmsht_ts_user_context_menu' ).trigger( 'hide_context_menu' );
		});

		$( '.tmsht_ts_user_context_menu_item.tmsht_ts_user_context_menu_item_enabled' ).on( 'click', function() {
			var $context_menu_item = $( this ),
				action = $context_menu_item.attr( 'data-action' ),
				$td = $( '#tmsht_ts_user_table tbody td.tmsht_context_menu' ),
				$tr = $td.parent(),
				$td_fill = $td.find( '.tmsht_ts_user_table_td_fill' ),
				legend = get_legend( -1 );

			switch( action ) {
				case 'delete':
					$td
						.attr( 'data-legend-id', legend.id )
						.removeAttr( 'data-prev-legend-id' )
						.removeAttr( 'title' )
						.removeAttr( 'data-td-group' )
						.removeClass( 'tmsht_ts_user_table_td_selected' );

					$td_fill.css( 'background-color', legend.color );

					$tr.find( '.tmsht_tr_date[disabled="disabled"]' ).attr( 'disabled', false );

					$( '#tmsht_ts_user_context_menu' ).trigger( 'hide_context_menu' );
					$( '#tmsht_ts_user_table' ).tmsht_ts_user_table_handler( 'show_details' );
					break;
				default:
					break;
			}
		});
	});

	/* start Legend selector*/
	$.fn.tmsht_ts_user_select_legend = function( target ) {

		var escapeHtml = function( text ) {
			var map = {
				'&': '&amp;',
				'<': '&lt;',
				'>': '&gt;',
				'"': '&quot;',
				"'": '&#039;'
			};

			return text.replace( /[&<>"']/g, function( m ) { return map[ m ]; } );
		}

		$( document ).on( 'click', function( e ) {
			if ( $( e.target ).closest( '.tmsht_select_legend' ).length ) {
				return;
			}

			$( '.tmsht_select_legend' ).filter( '[data-status="open"]' ).trigger( 'select.close' );
		});

		return this.each( function( select_index ) {
			if ( ! $( this ).is( 'select' ) ) {
				return;
			}

			var $this_select = $( this );

			target = target || select_index;

			$this_select.attr( 'data-target', 'tmsht_select_legend_' + target ).on( 'change', function() {
				var index = $( this ).find( 'option:selected' ).index(),
					color = $( this ).find( 'option:selected' ).data( 'color' ),
					name = escapeHtml( $( this ).find( 'option:selected' ).text() ),
					target = $( this ).data( 'target' ),
					$target_select = $( '.' + target ),
					$target_option = $target_select.find( '.tmsht_select_legend_option' ).eq( index );

				$target_select.find( '.tmsht_select_legend_label_color' ).css( 'background-color', color );
				$target_select.find( '.tmsht_select_legend_label_name' ).html( name );
			});

			var	$select = $( '<div/>', {
					'class'       : 'tmsht_select_legend tmsht_select_legend_' + target + ' tmsht_select_legend_hidden tmsht_unselectable',
					'data-status' : 'close'
				}).bind( 'select.open', function () {
					$( this ).trigger( 'select.close' );
					$( this ).removeClass( 'tmsht_select_legend_hidden' ).addClass( 'tmsht_select_legend_visible' );
					$( this ).find( '.tmsht_select_legend_arrow' ).removeClass( 'tmsht_select_legend_arrow_down' ).addClass( 'tmsht_select_legend_arrow_up' );
					$( this ).attr( 'data-status', 'open' );
				}).bind( 'select.close', function () {
					$( '.tmsht_select_legend' ).filter( '[data-status="open"]' ).removeClass( 'tmsht_select_legend_visible' ).addClass( 'tmsht_select_legend_hidden' );
					$( '.tmsht_select_legend' ).filter( '[data-status="open"]' ).find( '.tmsht_select_legend_arrow' ).removeClass( 'tmsht_select_legend_arrow_up' ).addClass( 'tmsht_select_legend_arrow_down' );
					$( '.tmsht_select_legend' ).attr( 'data-status', 'close' );
				}).on( 'click', function() {
					if ( $( this ).attr( 'data-status' ) == 'close' ) {
						$( this ).trigger( 'select.open' );
					} else {
						$( this ).trigger( 'select.close' );
					}
				}).data( 'status', 'close' );

			var $display = $( '<div/>', {
				'class' : 'tmsht_select_legend_display'
			}).appendTo( $select );

			var $label = $( '<div/>', {
				'class' : 'tmsht_select_legend_label',
			}).appendTo( $display );

			var $label_name = $( '<div/>', {
				'class' : 'tmsht_select_legend_label_name',
				'html'  : escapeHtml( $this_select.find( 'option:selected' ).text() )
			}).appendTo( $label );

			var $label_color = $( '<div/>', {
				'class' : 'tmsht_select_legend_label_color',
				'style' : 'background-color: ' + $this_select.find( 'option:selected' ).data( 'color' )
			}).appendTo( $label );

			var $arrow = $( '<span/>', {
				'class' : 'tmsht_select_legend_arrow ' + 'tmsht_select_legend_arrow_down',
			}).insertAfter( $label );

			var $options_wrap = $( '<ul/>', {
				'class' : 'tmsht_select_legend_options_wrap'
			}).insertAfter( $display );

			$this_select.find( 'option' ).each( function( index_option ) {
				var $this_option = $( this );

				$( '<li/>', {
					'class' 	 : 'tmsht_select_legend_option',
					'data-index' : index_option,
					'data-color' : $this_option.data( 'color' ),
					'data-name'  : $this_option.text(),
					'title'      : $this_option.text(),
					'html'       : $( '<span class="tmsht_select_legend_option_label_color" style="background-color: ' + $this_option.data( 'color' ) + ';"></span><div class="tmsht_select_legend_option_label_name">' + escapeHtml( $this_option.text() ) + '</div>' )
				}).on( 'mouseenter', function() {
					$( this ).addClass( 'tmsht_select_legend_option_hover' );
				}).on( 'mouseleave', function() {
					$( this ).removeClass( 'tmsht_select_legend_option_hover' );
				}).on( 'click', function() {
					var index = $( this ).data( 'index' );

					$this_select.find( 'option' ).eq( index ).attr( 'selected', true ).trigger( 'change' );
				}).appendTo( $options_wrap );
			});

			$this_select.hide();
			$this_select.after( $select );
		});
	};
	/* end Legend selector*/

	/* start Legend selector*/
	$.fn.tmsht_ts_user_table_handler = (function( method ) {
		var methods = {
			'init' : function( options ) {
				return this.each( function ( table_index ) {
					$( this ).tmsht_ts_user_table_handler( 'show_details' );
				});
			},
			'show_details' : function() {
				return this.each( function () {
					var $ts_table = $( this ),
						tbl_data = {};
						key = 0;

					$( '.tmsht_tr_date' ).each( function() {
						var tr_date = $( this ).val();

						if ( $ts_table.find( 'td.tmsht_ts_user_table_td_time[data-td-date="' + tr_date + '"]' ).filter( '[data-legend-id!="-1"]' ).length == 0 ) {
							return true;
						}

						var $tds = $ts_table.find( 'td.tmsht_ts_user_table_td_time[data-td-date="' + tr_date + '"]' );

						$tds.each( function( index, elem ) {
							var $td = $( this ),
								legend_id = $td.attr( 'data-legend-id' );
								next_legend_id = $( $tds[ index + 1 ] ).attr( 'data-legend-id' );
								date = $td.attr( 'data-td-date' );

							if ( legend_id < 0 ) {
								return true;
							}

							tbl_data[ legend_id ] = tbl_data[ legend_id ] || {},
							tbl_data[ legend_id ][ date ] = tbl_data[ legend_id ][ date ] || {};

							tbl_data[ legend_id ][ date ][ key ] = tbl_data[ legend_id ][ date ][ key ] || [];
							tbl_data[ legend_id ][ date ][ key ].push( parseInt( $td.attr( 'data-td-time' ) ) );

							$td.attr( 'data-td-group', key );
							if ( legend_id != next_legend_id ) {
								key++;
							}
						});
					});

					$( '.tmsht_ts_user_advanced_box.tmsht_maybe_hidden, .tmsht_ts_user_advanced_box .tmsht_maybe_hidden' ).addClass( 'tmsht_hidden' );
					$( '.tmsht_ts_user_advanced_box.tmsht_maybe_hidden .tmsht_ts_user_advanced_box_interval' ).remove();

					Number.prototype.toFormat = function() {
						var n = this;

						return n > 9 ? "" + n: "0" + n;
					}

					for ( var legend_id in tbl_data ) {
						var $box = $( '.tmsht_ts_user_advanced_box[data-box-id="' + legend_id + '"]' ).removeClass( 'tmsht_hidden' );

						for ( var date in tbl_data[ legend_id ] ) {
							var $details = $box.find( '.tmsht_ts_user_advanced_box_details[data-box-details-date="' + date + '"]' ),
								$wrap = $details.find( '.tmsht_ts_user_advanced_box_interval_wrap' );

							$details.removeClass( 'tmsht_hidden' );
							for ( var time in tbl_data[ legend_id ][ date ] ) {
								var $interval_template = $( '#tmsht_ts_user_advanced_box_details_template .tmsht_ts_user_advanced_box_interval' ).clone(),
									time_from = tbl_data[ legend_id ][ date ][ time ][0],
									time_to = ( tbl_data[ legend_id ][ date ][ time ][ tbl_data[ legend_id ][ date ][ time ].length - 1 ] + 1 ),
									group = time,
									index = $box.find( '.tmsht_ts_user_advanced_box_interval' ).length,
									interval_html = $interval_template.html()
														.replace( /%index%/g, index )
														.replace( /%legend_id%/g, legend_id )
														.replace( /%date%/g, date )
														.replace( /%time_from%/g, time_from.toFormat() )
														.replace( /%time_to%/g, ( time_to ).toFormat() )
														.replace( /%input_time_from%/g, time_from.toFormat() + ':00:00' )
														.replace( /%input_time_to%/g, ( time_to != 24 ) ? time_to.toFormat() + ':00:00' : '23:59:59' )
														.replace( /data-hidden-name/g, 'name' );

								$interval_template
									.html( interval_html )
									.appendTo( $wrap )
									.attr( 'data-tr-date', date )
									.attr( 'data-details-group', group )
									.on( 'mouseenter', function () {
										var $interval = $( this ),
											group = $interval.attr( 'data-details-group' ),
											$td_fill = $ts_table.find( 'td.tmsht_ts_user_table_td_time[data-td-group="' + group + '"] .tmsht_ts_user_table_td_fill' );

										$td_fill.addClass( 'tmsht_ts_user_highlight' );
									}).on( 'mouseleave', function () {
										var $td_fill = $( '.tmsht_ts_user_highlight' );

										$td_fill.removeClass( 'tmsht_ts_user_highlight' );
									});

								$ts_table.find( 'td.tmsht_ts_user_table_td_time[data-td-group="' + group + '"]' ).each( function() {
									var $td = $( this );
										group_legend_id = $td.attr( 'data-legend-id' ),
										group_legend_name = $( '.tmsht_ts_user_legend option[value="' + group_legend_id + '"]' ).text(),
										time_from = $interval_template.find( '.tmsht_ts_user_advanced_box_interval_from_text' ).text(),
										time_to = $interval_template.find( '.tmsht_ts_user_advanced_box_interval_to_text' ).text();
									if ( group_legend_id > 0 ) {
										$td.attr( 'title', group_legend_name + ' (' + time_from + ' - ' + time_to + ')' );
									}
								});
							}
						}
					}
				});
			}
		}

		if ( methods[ method ] ) {
			return methods[ method ].apply( this, Array.prototype.slice.call( arguments, 1 ) );
		} else if ( typeof method === 'object' || ! method ) {
			return methods.init.apply( this, arguments );
		} else {
			$.error( 'Method ' + method + ' not found!' );
		}
	});

})(jQuery);