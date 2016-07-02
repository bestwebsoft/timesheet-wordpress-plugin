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
					date_target = ( input_id == 'tmsht_ts_report_date_from' ) ? 'date_from' : 'date_to',
					$input_date_from = $( '#tmsht_ts_report_date_from' ),
					$input_date_to = $( '#tmsht_ts_report_date_to' );

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
		$( '.tmsht_ts_report_legend' ).tmsht_ts_report_select_legend();

		/* start User selector */
		$( 'noscript.tmsht_ts_report_user_list_container_noscript' ).before( $( 'noscript.tmsht_ts_report_user_list_container_noscript' ).text() );

		$( '.tmsht_ts_report_user_list' ).addClass( 'tmsht_ts_report_user_list_closed' );

		$( '.tmsht_ts_report_search_user' ).on( 'keyup', function() {
			var value = $( this ).val(),
				$checkbox_all = $( '.tmsht_ts_report_user_checkbox_all' );

			$( '.tmsht_ts_report_user' ).show();

			if ( value.length > 0 ) {
				$( '.tmsht_ts_report_user' ).not( '[data-username^="' + value + '"]' ).hide();
			}

			if ( $( '.tmsht_ts_report_user:visible' ).length == 0 ) {
				$( '.tmsht_ts_report_user_search_results' ).removeClass( 'tmsht_hidden' );
				$checkbox_all.attr( 'disabled', true );
				$checkbox_all.parent().css( 'opacity', 0.7 );
			} else {
				$( '.tmsht_ts_report_user_search_results' ).addClass( 'tmsht_hidden' );
				$checkbox_all.attr( 'disabled', false )
				$checkbox_all.parent().css( 'opacity', 1 );
			}

			var	$checkboxes = $( '.tmsht_ts_report_user_checkbox:visible' ),
				$checkboxes_checked = $checkboxes.filter( ':visible:checked' );

			if ( $checkboxes.length != $checkboxes_checked.length || $checkboxes.length == 0 ) {
				$checkbox_all.attr( 'checked', false );
			} else {
				$checkbox_all.attr( 'checked', true );
			}

		}).on( 'focus', function() {
			$( '.tmsht_ts_report_user_list' )
				.removeClass( 'tmsht_ts_report_user_list_closed' )
				.addClass( 'tmsht_ts_report_user_list_opened' );
		});

		$( document ).on( 'click', function( e ) {
			if ( $( e.target ).closest( '.tmsht_ts_report_search_user, .tmsht_ts_report_user_list_container' ).length ) {
				return;
			}

			$( '.tmsht_ts_report_user_list' )
				.removeClass( 'tmsht_ts_report_user_list_opened' )
				.addClass( 'tmsht_ts_report_user_list_closed' );
		});

		$( '.tmsht_ts_report_user_checkbox_all' ).on( 'change', function() {
			var $this = $( this );

			$( '.tmsht_ts_report_user_checkbox:visible' ).attr( 'checked', function() {
				return $this.is( ':checked' );
			}).trigger( 'count' );
		});

		$( '.tmsht_ts_report_user_checkbox' ).on( 'change', function() {
			var filter = $( '.tmsht_ts_report_user_list' ).hasClass( 'tmsht_ts_report_user_list_closed' ) ?  '' : ':visible',
				$checkboxes = $( '.tmsht_ts_report_user_checkbox' + filter ),
				$checkboxes_checked = $checkboxes.filter( filter + ':checked' ),
				$checkbox_all = $( '.tmsht_ts_report_user_checkbox_all' );
				$( this ).trigger( 'count' );

			$checkbox_all.attr( 'checked', function() {
				return $checkboxes.length == $checkboxes_checked.length;
			});
		}).on( 'count', function() {
			var $checkboxes = $( '.tmsht_ts_report_user_checkbox' ),
				$checkboxes_checked = $checkboxes.filter( ':checked' );

			$( '.tmsht_ts_report_selected_users_container' ).empty();
			$checkboxes_checked.each( function() {
				var $selected_user = $( '<span/>', {
					'id'    : 'tmsht_ts_report_user_selected_' + $( this ).val(),
					'class' : 'tmsht_ts_report_user_selected',
					'html'  : $( this ).parent().text() + '<label class="tmsht_ts_report_user_uncheck notice-dismiss" for="tmsht_ts_report_user_id_' + $( this ).val() + '"></label>'
				});

				$( '.tmsht_ts_report_selected_users_container' ).append( $selected_user );
			});
		});
		/* end User selector */

		/* start td helper */
		$( '.tmsht_ts_report_table_has_data td.tmsht_ts_report_table_td_time' ).on( 'mouseenter', function() {
			var $td = $( this ),
				td_index = $( this ).attr( 'data-td-index' );

			$( '.tmsht_ts_report_table_has_data tr:hover .tmsht_ts_report_table_td_helper' ).addClass( 'tmsht_ts_report_table_td_helper_hover' );
			$( '.tmsht_ts_report_table_has_data tr .tmsht_ts_report_table_td_helper_' + td_index ).addClass( 'tmsht_ts_report_table_td_helper_hover' );

		}).on( 'mouseleave', function() {
			$( '#tmsht_ts_report_table tbody tr td.tmsht_ts_report_table_td_time' ).find( '.tmsht_ts_report_table_td_helper_hover' ).removeClass( 'tmsht_ts_report_table_td_helper_hover' );
		});
		/* end td helper */
	});

	/* start Legend selector */
	$.fn.tmsht_ts_report_select_legend = function() {

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

			$this_select.attr( 'data-target', 'tmsht_select_legend_' + select_index ).on( 'change', function() {
				var index = $( this ).find( 'option:selected' ).index(),
					color = $( this ).find( 'option:selected' ).data( 'color' ),
					name = $( this ).find( 'option:selected' ).text(),
					target = $( this ).data( 'target' ),
					$target_select = $( '.' + target ),
					$target_option = $target_select.find( '.tmsht_select_legend_option' ).eq( index );

				$target_select.find( '.tmsht_select_legend_label_color' ).css( 'background-color', color );
				$target_select.find( '.tmsht_select_legend_label_name' ).html( name );
			});

			var	$select = $( '<div/>', {
					'class'       : 'tmsht_select_legend tmsht_select_legend_' + select_index + ' tmsht_select_legend_hidden tmsht_unselectable',
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
				'html'  : $this_select.find( 'option:selected' ).text()
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
					'html'       : $( '<span class="tmsht_select_legend_option_label_color" style="background-color: ' + $this_option.data( 'color' ) + ';"></span><div class="tmsht_select_legend_option_label_name">' + $this_option.text() + '</div>' )
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
	/* end Legend selector */
})(jQuery);