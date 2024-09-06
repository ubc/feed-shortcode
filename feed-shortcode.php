<?php
/**
 * Plugin Name: Feed Shortcode
 * Plugin URI:
 * Description: A [feed] shortcode plugin.
 * Version: 1.0.3
 * Author: UBC CMS
 * Author URI: http://ctlt.ubc.ca
 *
 * This program is free software; you can redistribute it and/or modify it under the terms of the GNU
 * General Public License as published by the Free Software Foundation; either version 2 of the License,
 * or ( at your option ) any later version.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without
 * even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 *
 * You should have received a copy of the GNU General Public License along with this program; if not, write
 * to the Free Software Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA 02110-1301 USA
 *
 * @license http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * @package FeedShortcode
 **/

/**
 *
 * CTLT_Feed_Shortcode Class
 *
 * @category Class
 * @package FeedShortcode
 * @author CTLT
 */
class CTLT_Feed_Shortcode {

	public static $counter = 0;
	/**
	 * Init function.
	 *
	 * @access public
	 * @return void
	 */
	public static function init() {
		add_action( 'init', array( __CLASS__, 'register_shortcode' ) );
	}
	/**
	 * register_shortcode function.
	 *
	 * @access public
	 * @return void
	 */
	public static function register_shortcode() {
		self::add_shortcode( 'feed', 'feed_shortcode' );
	}

	/**
	 * has_shortcode function.
	 *
	 * @access public
	 * @param mixed $shortcode
	 * @return bool
	 */
	static function has_shortcode( $shortcode ) {
		global $shortcode_tags;
		/* don't do anything if the shortcode exists already */
		return ( in_array( $shortcode, array_keys( $shortcode_tags ) ) ? true : false );
	}

	/**
	 * add_shortcode function.
	 *
	 * @access public
	 * @param mixed $shortcode
	 * @param mixed $shortcode_function
	 * @return void
	 */
	static function add_shortcode( $shortcode, $shortcode_function ) {

		if ( ! self::has_shortcode( $shortcode ) ) {
			add_shortcode( $shortcode, array( __CLASS__, $shortcode_function ) );
		}
	}

	/**
	 * update_ubc_events_feed function.
	 *
	 * @access public
	 * @static
	 * @param mixed $url
	 * @param mixed $events_url
	 * @return url
	 */
	static function update_ubc_events_feed( $url, $events_url ) {
		// $url_parse = parse_url( );
		$rest      = substr( $url, strlen( $events_url ) );
		$url_parse = explode( '&', $rest );

		$path = array();
		foreach ( $url_parse as $value ) :
			if ( self::starts_with( $value, '#038;calPath' ) ||
				self::starts_with( $value, 'amp;calPath' ) ||
				self::starts_with( $value, 'amp;catTag' ) ||
				self::starts_with( $value, '#038;catTag' ) ||
				self::starts_with( $value, '?amp;catTag' ) ||
				self::starts_with( $value, '?#038;catTag' ) ) {
				$path[] = $value;
			}

		endforeach;

		$new_url = $events_url . '?mode=rss&' . implode( '&', $path ) . '';
		if ( ! isset( $_GET['current'] ) ) :
			$new_url .= '&month=current';
		else :
			$current = (int) $_GET['current'];

			if ( $current > 0 ) {
				$new_url .= '&month=current+' . $current;
			} elseif ( 0 == $current ) {
				$new_url .= '&month=current';
			} else {
				$new_url .= '&month=current' . $current;
			}
		endif;

		return esc_url( $new_url ); // $url.
	}

	/**
	 * starts_with function.
	 *
	 * @access public
	 * @param mixed $string
	 * @param mixed $test_string
	 * @return string
	 */
	static function starts_with( $string, $test_string ) {

		return ( ! strncmp( $string, $test_string, strlen( $test_string ) ) ? true : false );
	}

	/**
	 * get_string function.
	 *
	 * @access public
	 * @param string $string
	 * @param string $start
	 * @param string $end
	 * @return string
	 */
	static function get_string( $string, $start, $end ) {
		$string = ' ' . $string;
		$pos    = strpos( $string, $start );
		if ( $pos == 0 ) {
			return '';
		}
		$pos += strlen( $start );
		$len  = strpos( $string, $end, $pos ) - $pos;
		return substr( $string, $pos, $len );
	}

	/**
	 * feed_shortcode function.
	 *
	 * @access public
	 * @static
	 * @param mixed $atts
	 * @param mixed $content
	 * @return feed
	 */
	public static function feed_shortcode( $atts, $content ) {

		global $post;

		extract(
			shortcode_atts(
				array(
					'url'            => '',
					'num'            => '',
					'excerpt'        => true,
					'target'         => '_self',
					'date_format'    => 'M d, Y',
					'view'           => 'default',
					'empty'          => '',
					'excerpt_length' => 0,
					'time_zone'      => null,
					'show_author'    => '', // YC, Oct 2012 - add parameter; value true/false.
					'show_date'      => '', // - add parameter; value updated/true/false
					'order_by_date'  => 1, // LC - option to turn off order_by_date so that it uses feed's ordering.
					'month'          => null,
					'year'           => null,
					'mednet'         => false,
				),
				$atts
			)
		);

		$num            = ( $num > 0 ? $num : 15 );
		$ubc_events_url = 'http://services.calendar.events.ubc.ca/cgi-bin/rssCache.pl';
		// Make ubc events and calendar work well together.
		if ( in_array( $view, array( 'cal', 'calendar' ) ) && self::starts_with( $url, $ubc_events_url ) ) :
			$url = self::update_ubc_events_feed( $url, $ubc_events_url );
		endif;

		if ( empty( $url ) && is_singular() ) :
			$url = get_post_meta( $post->ID, 'feed-url', true );
		endif;

		$url = html_entity_decode( $url );

		$target = ( empty( $target ) ? '' : "target='" . esc_attr( $target ) . "'" );

		$excerpt = ( $excerpt != false && $excerpt != 'false' ? true : false );

		$feed = fetch_feed( $url ); // All the hard work is done here.

		if ( is_wp_error( $feed ) && $empty == '' ) {
			return false; // Fail silenly.
		} elseif ( is_wp_error( $feed ) ) {
			return $empty;
		}

		// Begin: YC, Oct 2012 - show author, show_date, used only in view=rsswidget.
		$show_author = ( $show_author != false && $show_author != 'false' ? true : false );
		$show_date   = ( $show_date != false && $show_date != 'false' ? true : false );

		// Figure out how many total items there are.
		$maxitems = $feed->get_item_quantity();

		// Change the timezone.
		if ( empty( $time_zone ) && parse_url( $url, PHP_URL_HOST ) === 'events.ubc.ca' ) {
			date_default_timezone_set( 'America/Vancouver' );
		}

		if ( $order_by_date < 1 ) {
			$feed->order_by_date = false;// I think this line makes it so that it DOESN't resort stuff.
		}

		// Build an array of all the items, starting with element 0 ( first element ).
		$rss_items = $feed->get_items( 0, $maxitems );

		if ( empty( $rss_items ) && $empty == '' && ! in_array( $view, array( 'cal', 'calendar' ) ) ) :
			return false;
		elseif ( empty( $rss_items ) && ! in_array( $view, array( 'cal', 'calendar' ) ) ) :
			return "<span class='feed-shorcode feed-empty'>" . $empty . '</span>';
		endif;

		ob_start();

		switch ( $view ) {

			case 'default':
			case 'list':
				$rss_items = array_slice( $rss_items, 0, $num );
				?>
			<ul class="feed-shortcode feed_widget feed-view-default">

				<?php
				$count = 0;
				foreach ( (array) $rss_items as $item ) :
					$odd_or_even = ( $count % 2 ) ? 'odd' : 'even';
					++$count;
					?>
				<li class="feed_entry <?php echo $odd_or_even; ?>">
					<a class="feed_title" href="<?php echo esc_url( $item->get_permalink() ); ?>"
						<?php echo $target; ?> ><?php echo esc_html( $item->get_title() ); ?>
					</a>
					<?php if ( $excerpt ) : ?>
					<div class="feed_excerpt">
						<?php
						if ( is_numeric( $excerpt_length ) && $excerpt_length > 0 ) {
							echo $item->get_description();
						} else {
							echo self::strshorten( $item->get_description(), $excerpt_length );
						}
						?>
					</div>
					<a class="feed_readmore" href="<?php echo esc_url( $item->get_permalink() ); ?>" <?php echo $target; ?> ><span class="feed_readmore_text">Read More</span> <span class="feed_arrow_icon">&raquo;</span></a>
				<?php endif; ?>
			<?php endforeach; ?>
		</ul >
				<?php
				break;

			case 'rsswidget':
				$rss_items = array_slice( $rss_items, 0, $num );
				?>
				<ul class="feed-shortcode feed_view feed-view-rsswidget">

				<?php
				$count        = 0;
				$author       = ''; // YC, Oct 2012 - author.
				$updated_date = ''; // YC, Oct 2012 - updated_date.

					// Begin: YC Oct 2012.
				if ( 'updated' == $show_date ) :

					usort(
						$rss_items,
						function ( $a, $b ) {
							return $a->get_date() < $b->get_date();
						}
					);

					usort(
						$rss_items,
						function ( $a, $b ) {
							return $a->get_id() < $b->get_id();
						}
					);

				endif;// End.

				foreach ( (array) $rss_items as $item ) :
					$odd_or_even = ( $count % 2 ) ? 'odd' : 'even';
					++$count;

					// Begin: YC, Oct 2012 - add option to display post item's author and date format.
					if ( $show_author ) :
						$author = $item->get_author();

						if ( $author ) {
							$author = ' <cite class="author">' . esc_html( strip_tags( $author->get_name() ) ) . '</cite>';
						}

					endif;// End display post item's author.

					// Begin: YC, Oct 2012 - show posts' last updated date instead of published date.
					if ( $show_date ) :
						$updated_date = $item->get_date();
						$updated_date = date( $date_format, strtotime( $updated_date ) ); // Adjust to current time-zone.
					endif; // End: YC Oct 2012 - sort by and show posts' last modify date instead of publish date.

					?>
					<li class="<?php echo $odd_or_even; ?>">
						<a class="rsstitle" href="<?php echo esc_url( $item->get_permalink() ); ?>" <?php echo $target; ?> ><?php echo esc_html( $item->get_title() ); ?></a>
						<!-- YC, Oct 2012 - show the posts' author and last updated date-->
						<?php
						echo $author;

						if ( $updated_date ) :

							echo '<span class="rss-bracket">(</span> <span class="rss-date">' . $updated_date . '</span><span class="rss-bracket">)</span>';
						endif;
						if ( $excerpt ) :
							?>
						<div class="feed_excerpt">
							<?php
							if ( is_numeric( $excerpt_length ) && $excerpt_length > 0 ) {
								echo $item->get_description();
							} else {
								echo self::strshorten( $item->get_description(), $excerpt_length );
							}
							?>
						</div>
						<a class="feed_readmore" href="<?php echo esc_url( $item->get_permalink() ); ?>"  <?php echo $target; ?> ><span class="feed_readmore_text">Read More</span> <span class="feed_arrow_icon">&raquo;</span></a>
					<?php endif; ?>
				</li>
			<?php endforeach; ?>
			</ul>
				<?php
				break;

			case 'events':
				$entries = array();

				foreach ( $rss_items as $item ) :
					$entries[ $item->get_date( $date_format ) ][] = $item;
				endforeach;

				?>
		<ul class="feed-shortcode feed_view feed-view-events">
				<?php

				foreach ( $entries as $date => $items ) :
					if ( $count > $num - 1 ) {
						break;
					}

					?>
				<li class="event-rss-date"><span><?php echo $date; ?></span>
					<ul class="event-rss-item">
						<?php
						foreach ( $items as $item ) :
							++$count;
							?>
						<li><a href='<?php echo $item->get_permalink(); ?>' title='<?php echo 'Posted ' . $item->get_date( 'j F Y | g:i a' ); ?>'><?php echo $item->get_title(); ?></a></li>
					<?php endforeach; ?>
				</ul>
			</li>
					<?php
			endforeach;
				?>
		</ul>
				<?php
				break;

			case 'listevents':
				$entries = array();
				foreach ( $rss_items as $item ) :
					$entries[ $item->get_date( 'U' ) ][] = $item;
				endforeach;
				if ( $order_by_date != 0 ) {
					$entries = array_reverse( $entries );
				}
				?>

		<div class="feed-shortcode feed_widget feed-view-listevents">
				<?php
				foreach ( $entries as $date => $items ) :
					if ( $count > $num - 1 ) {
						break;
					}

					foreach ( $items as $item ) :
						++$count;
						?>
				<h2 class=""><?php echo $item->get_title(); ?></h2>
				<h3><?php echo $item->get_date( 'F j, Y' ); ?></h3>
								<?php
								echo $item->get_content();
								?>
				<hr/>
						<?php
				endforeach;
				endforeach;
				?>
				</div>
				<?php
				break;

			case 'upcoming':
				$entries = array();

				foreach ( $rss_items as $item ) :
					$entries[ $item->get_date( $date_format ) ][] = $item;
				endforeach;

				if ( $order_by_date != 0 ) {
					$entries = array_reverse( $entries );
				}

				?>
				<ul class="feed-shortcode feed_widget feed-view-upcoming">
					<?php

					foreach ( $entries as $date => $items ) :
						if ( $count > $num - 1 ) {
							break;
						}

						?>
						<li class="event-rss-date"><span><?php echo $date; ?></span>
							<ul class="event-rss-item">
								<?php
								foreach ( $items as $item ) :
									++$count;
									?>
								<li><a href='<?php echo $item->get_permalink(); ?>' title='<?php echo 'Posted ' . $item->get_date( 'j F Y | g:i a' ); ?>'><?php echo $item->get_title(); ?></a></li>
							<?php endforeach; ?>
						</ul>
					</li>
						<?php

					endforeach;
					?>
				</ul>
				<?php
				break;

			case 'archive':
				$rss_items = array_slice( $rss_items, 0, $num );
				?>
				<div class="feed-shortcode feed-view-archive">

					<?php
					$count = 0;
					foreach ( (array) $rss_items as $item ) :
						$odd_or_even = ( $count % 2 ) ? 'odd' : 'even';
						++$count;

						?>
					<div class="hentry post publish <?php echo $odd_or_even; ?>">


						<h2 class="entry-title"><a rel="bookmark" title="<?php echo esc_attr( $item->get_title() ); ?>" href="<?php echo esc_url( $item->get_permalink() ); ?>" <?php echo $target; ?>><?php echo esc_html( $item->get_title() ); ?></a></h2>
						<p class="byline">Posted on <?php echo $item->get_date( $date_format ); ?></p>
						<div class="entry-summary">
							<?php
							if ( is_numeric( $excerpt_length ) && $excerpt_length > 0 ) {
								echo $item->get_description();
							} else {
								echo self::strshorten( $item->get_description(), $excerpt_length );
							}
							?>
						</div><!-- .entry-summary -->
					</div>
				<?php endforeach; ?>
			</div>
				<?php
				break;

			case 'blog':
				$rss_items = array_slice( $rss_items, 0, $num );
				?>
				<div class="feed-shortcode feed-view-blog">

				<?php
				$count = 0;
				foreach ( (array) $rss_items as $item ) :
					$odd_or_even = ( $count % 2 ) ? 'odd' : 'even';
					++$count;
					?>
					<div class="hentry post publish <?php echo $odd_or_even; ?>">
					<h2 class="entry-title"><a rel="bookmark" title="<?php echo esc_attr( $item->get_title() ); ?>" href="<?php echo esc_url( $item->get_permalink() ); ?>" <?php echo $target; ?>><?php echo esc_html( $item->get_title() ); ?></a></h2>
					<p class="byline">Posted on <?php echo $item->get_date( $date_format ); ?></p>
					<div class="entry-summary">
						<?php
						if ( is_numeric( $excerpt_length ) && $excerpt_length > 0 ) {
							echo self::strshorten( $item->get_description(), $excerpt_length );
						} else {
							echo $item->get_content();
						}
						?>
					</div><!-- .entry-summary -->
				</div>
			<?php endforeach; ?>
		</div>
				<?php
				break;

			case 'timeline':
				$entries = array();

				foreach ( $rss_items as $item ) :
					$entries[ $item->get_date( 'F Y' ) ][] = $item;
				endforeach;

				if ( $order_by_date != 0 ) {
					$entries = array_reverse( $entries );
				}

				?>
		<ul class="feed-shortcode feed_widget feed-view-timeline">
				<?php

				foreach ( $entries as $date => $items ) :
					if ( $count > $num - 1 ) {
						break;
					}

					?>
				<li class="event-rss-date"><span><?php echo $date; ?></span>
					<ul class="event-rss-item">
						<?php
						foreach ( $items as $item ) :
							++$count;
							?>
						<li><span class="date"><?php echo $item->get_date( 'j F ' ); ?> </span> <a href='<?php echo $item->get_permalink(); ?>' title='<?php echo 'Posted ' . $item->get_date( 'j F Y | g:i a' ); ?>'><?php echo $item->get_title(); ?></a></li>
					<?php endforeach; ?>
				</ul>
			</li>
					<?php
			endforeach;
				?>
		</ul>
				<?php
				break;

			case 'calendar':
			case 'cal':
				// $current = ( empty( $_GET['current'] )? "": $_GET['current'] );
				$current        = ( empty( $_GET['current'] ) ? 0 : $_GET['current'] );
				$current_new    = ( $current > 0 ? '+' . $current : $current );
				$current_month  = 'current' . ( empty( $current_new ) ? '' : $current_new );
				$next_month     = ( empty( $current ) ? 1 : $current + 1 );
				$previous_month = ( empty( $current ) ? -1 : $current - 1 );
				foreach ( (array) $rss_items as $item ) :
					$data[ $item->get_date( 'Y' ) ][ $item->get_date( 'n' ) ][ $item->get_date( 'j' ) ][] = $item;
				endforeach;
				// Get year, eg 2006.
				if ( empty( $year ) ) {
					$year = (int) date( 'Y' );
				}
				// Get month, eg 04.
				/*add month here*/
				if ( empty( $month ) ) {
					$month = (int) date( 'n' ) + $current;
				} elseif ( ! is_numeric( $month ) ) {
						// Add 1 to start month count on the 1st, works for feb.
						$month = (int) date( 'm', strtotime( $month, 1 ) );
				}
				if ( $month > 12 ) :
					$year  = (int) ( $month / 12 ) + $year;
					$month = ( $month % 12 );
			elseif ( $month < 0 ) :
				$str_date = strtotime( absint( $current ) . ' months ago' );
				$year     = date( 'Y', ( $str_date ) );
				$month    = date( 'n', ( $str_date ) );

			endif;

			// Get day, eg 3.
			$day = date( 'j' );

			// Get number of days in month, eg 28.
			$daysInMonth = date( 't', mktime( 0, 0, 0, $month, 1, $year ) );

			// Get first day of the month, eg 4.
			$firstDay = date( 'w', mktime( 0, 0, 0, $month, 1, $year ) );

			// Calculate total spaces needed in array.
			$tempDays = $firstDay + $daysInMonth;

			// Calculate total rows needed.
			$weeksInMonth = ceil( $tempDays / 7 );

			for ( $j = 0; $j < $weeksInMonth; $j++ ) {

				for ( $i = 0; $i < 7; $i++ ) {
					++$counter;
					$week[ $j ][ $i ] = $counter;
				}
			}

			?>

		<div class="feed-shortcode feed-view-calendar">
			<h3><?php echo date( 'F', mktime( 0, 0, 0, $month, 1, $year ) ) . ' ' . $year; ?> </h3>
			<table class="table table-bordered">
				<tr>
					<th>Sun</th>
					<th>Mon</th>
					<th>Tue</th>
					<th>Wed</th>
					<th>Thu</th>
					<th>Fri</th>
					<th>Sat</th>
				</tr>
				<?php
				$date = 0;
				foreach ( $week as $key => $val ) :
					echo '<tr>';
					for ( $i = 0;$i < 7; $i++ ) {
						++$date;
						$current_day = $date - $firstDay;
						$content     = '';
						if ( $current_day <= 0 || $current_day > $daysInMonth ) {
							$current_day = '';
						}

						if ( is_array( $data[ $year ][ $month ][ $current_day ] ) ) :
							$content .= "<div class='feed-links'>";
							foreach ( (array) $data[ $year ][ $month ][ $current_day ] as $feed_item ) :
								if ( ! $mednet ) {
									$content .= "<a href='" . $feed_item->get_permalink() . "' $target>" . $feed_item->get_title() . '</a><br />';
								} else {
									$title        = $feed_item->get_title();
									$item_content = $feed_item->get_description();
									$item_link    = self::get_string( $item_content, '<b>Link:</b> <a href="', '">http' );
									if ( empty( trim( $item_link ) ) ) {
										$item_link = trim( self::get_string( $item_content, '<b>Link:</b> ', '</div>' ) );
									}
									$content .= "<a href='" . $item_link . "' $target>" . $title . '</a></br />';
								}
							endforeach;
							$content .= '</div>';
						endif;

						if ( $current_date <= $daysInMonth ) :
							if ( $content != '' ) :
								echo "<td align='center' class='feed-day-shell'><div class='feed-day-inner'><span class='feed-date has-events'>" . $current_day . "</span>$content</div></td>";
							else :
								echo '<td align="center" ><span class="feed-date" >' . $current_day . '</span></td>';
							endif;

							else :
								echo "<td align='center'> </td>";
							endif;
					}
						echo '</tr>';
						endforeach;
				?>
					</table>
					<?php if ( $current < 20 && $current > -20 ) { ?>
					<p>
						<a href="?current=<?php echo $previous_month; ?>" rel="nofollow" class="button btn"><i class="icon-chevron-left"></i> Previous Month</a>
						<a href="?current=<?php echo $next_month; ?>" rel="nofollow" class="button btn">Next Month <i class="icon-chevron-right"></i></a>
					</p>
				<?php } ?>
				</div>
				<?php
				break;

			case 'flickr':
				static $flickr_instance = 0;
				++$flickr_instance;
				$columns   = 5;
				$rss_items = array_slice( $rss_items, 0, $num );
				$i         = 0;
				?>
				<style type="text/css">
				#flickr-gallery-<?php echo $flickr_instance; ?> {
					margin: auto;
				}
				#flickr-gallery-<?php echo $flickr_instance; ?> .gallery-item {
					float: left;
					margin-top: 10px;
					text-align: center;
					width: 20%;
				}
				#flickr-gallery-<?php echo $flickr_instance; ?> img {
					border: 2px solid #cfcfcf;
				}
				#flickr-gallery-<?php echo $flickr_instance; ?> .gallery-caption {
					margin-left: 0;
				}
				</style>
				<div id="flickr-gallery-<?php echo $flickr_instance; ?>" class="gallery gallery-columns-4 gallery-size-thumbnail">
					<?php
					foreach ( $rss_items as $item ) :
						if ( $enclosure = $item->get_enclosure() ) :
							?>
					<dl class="gallery-item">
						<dt class="gallery-icon">
							<a title="<?php echo esc_attr( $item->get_title() ); ?>" href="<?php echo esc_url( $enclosure->link ); ?>" rel="gallery-0"><img width="75" height="75" title="<?php echo esc_attr( $item->get_title() ); ?>" alt="<?php echo esc_attr( $item->get_title() ); ?>" class="attachment-thumbnail" src="<?php echo esc_url( $enclosure->thumbnails[0] ); ?>"></a>
						</dt>
					</dl>
							<?php
							if ( $columns > 0 && ++$i % $columns == 0 ) {
									echo '<br style="clear: both" />';}

					endif;
					endforeach;
					?>
				</div>
				<?php
				break;
		}

			$tz = date_default_timezone_get();
			date_default_timezone_set( $tz );
			$output_string = ob_get_contents();
			ob_end_clean();
		// Date_default_timezone_set( $tz );.
			return str_replace( "\r\n", '', $output_string );
	}


	/**
	 * strshorten function.
	 *
	 * @access public
	 * @param mixed $string
	 * @param mixed $length
	 * @return string
	 */
	static function strshorten( $string, $length ) {
		// By default, an ellipsis will be appended to the end of the text.
		$suffix = '...';

		// Convert 'smart' punctuation to 'dumb' punctuation, strip the HTML tags,
		// and convert all tabs and line-break characters to single spaces.
		$short_desc = trim( str_replace( array( "\r", "\n", "\t" ), ' ', strip_tags( $string ) ) );
		// Cut the string to the requested length, and strip any extraneous spaces.
		// from the beginning and end.
		$desc = trim( substr( $short_desc, 0, $length ) );

		// Find out what the last displayed character is in the shortened string.
		$lastchar = substr( $desc, -1, 1 );

		// If the last character is a period, an exclamation point, or a question.
		// Mark, clear out the appended text.
		if ( $lastchar == '.' || $lastchar == '!' || $lastchar == '?' ) {
			$suffix = '';
		}

		// Append the text.
		$desc .= $suffix;

		// Send the new description back to the page.
		return $desc;
	}
}

CTLT_Feed_Shortcode::init();

/**
 *
 * CTLT_Twitter_Feed_Shortcode class.
 */
class CTLT_Twitter_Feed_Shortcode {

	public static $counter    = 0;
	public static $slider_ids = null;
	public static $token      = null;
	public static $twitter_data;

	/**
	 * init function.
	 *
	 * @access public
	 * @return void
	 */
	public static function init() {

		add_action( 'init', array( __CLASS__, 'register_shortcode' ) );
		add_action( 'init', array( __CLASS__, 'register_scripts' ) );
		add_action( 'wp_footer', array( __CLASS__, 'print_script' ) );
	}
	/**
	 * register_shortcode function.
	 *
	 * @access public
	 * @return void
	 */
	static function register_shortcode() {
		self::add_shortcode( 'twitter', 'twitter_shortcode' );
	}

	/**
	 * register_scripts function.
	 *
	 * @access public
	 * @return void
	 */
	static function register_scripts() {
		wp_register_script( 'feed-shortcode-slider', plugins_url( 'js/feed-slider.js', __FILE__ ), array( 'jquery' ), '1.0', true );
	}

	/**
	 * print_script function.
	 *
	 * @access public
	 * @return void
	 */
	static function print_script() {
		if ( ! self::$slider_ids ) {
			return;
		}

		wp_localize_script( 'feed-shortcode-slider', 'feed_slider', self::$slider_ids );
		wp_print_scripts( 'feed-shortcode-slider' );
	}


	/**
	 * twitter_shortcode function.
	 *
	 * @access public
	 * @param mixed $attr
	 * @return void
	 */
	static function twitter_shortcode( $atts ) {
		global $post;
		extract(
			shortcode_atts(
				array(
					'user'            => '',
					'search'          => '',
					'secret'          => true,
					'key'             => '',
					'exclude_replies' => '',
					'target'          => '_self',
					'date_format'     => 'M d, Y',
					'view'            => 'default',
					'num'             => 10,
					'empty'           => '',
					'excerpt_length'  => 0,
					'time_zone'       => null,
					'show_author'     => '',  // YC, Oct 2012 - add parameter; value true/false
					'show_date'       => '', // - add parameter; value updated/true/false.
				),
				$atts
			)
		);

			// Sets the $token variable.
		self::get_tweets_bearer_token( $key, $secret );

			// Sets $twitter_data;.
		self::get_tweets( $search, $user, $num, $exclude_replies );

		if ( empty( self::$twitter_data ) ) {
			return $empty;
		}

		return self::view( $view );
	}

	/**
	 * has_shortcode function.
	 *
	 * @access public
	 * @param mixed $shortcode
	 * @return void
	 */
	static function has_shortcode( $shortcode ) {
		global $shortcode_tags;
		/* don't do anything if the shortcode exists already */
		return ( in_array( $shortcode, array_keys( $shortcode_tags ) ) ? true : false );
	}


	/**
	 * add_shortcode function.
	 *
	 * @access public
	 * @param mixed $shortcode
	 * @param mixed $shortcode_function
	 * @return void
	 */
	static function add_shortcode( $shortcode, $shortcode_function ) {

		if ( ! self::has_shortcode( $shortcode ) ) { /** @phpstan-ignore-line */
			add_shortcode( $shortcode, array( __CLASS__, $shortcode_function ) );
		}
	}


	/**
	 * get_tweets_bearer_token function.
	 *
	 * @access public
	 * @param mixed $consumer_key
	 * @param mixed $consumer_secret
	 * @return void
	 */
	static function get_tweets_bearer_token( $consumer_key, $consumer_secret ) {
		$consumer_key    = rawurlencode( $consumer_key );
		$consumer_secret = rawurlencode( $consumer_secret );

		self::$token = get_option( 'feedshortcode_twitter_token' );

		if ( ! is_array( self::$token ) || empty( self::$token ) || self::$token['consumer_key'] != $consumer_key || empty( self::$token['access_token'] ) ) {

			$args           = array(
				'headers' => array(
					'Authorization' => 'Basic ' . base64_encode( $consumer_key . ':' . $consumer_secret ),
				),
				'body'    => array(
					'grant_type' => 'client_credentials',
				),
			);
			$twitter_result = wp_remote_post( 'https://api.twitter.com/oauth2/token', $args );
			$result         = json_decode( $twitter_result['body'] );

			self::$token = array(
				'consumer_key' => $consumer_key,
				'access_token' => $result->access_token,
			);
			update_option( 'feedshortcode_twitter_token', self::$token );

		}
	}


	/**
	 * get_tweets function.
	 *
	 * @access public
	 * @static
	 * @param mixed $search ( default: null )
	 * @param mixed $user ( default: null )
	 * @param int   $number ( default: 10 ).
	 * @param bool  $exclude_replies ( default: true )
	 * @return void
	 */
	static function get_tweets( $search = null, $user = null, $number = 10, $exclude_replies = true ) {

		if ( $user ) {

			$url = 'https://api.twitter.com/1.1/statuses/user_timeline.json?screen_name=' . rawurlencode( $user ) . '&count=' . $number;
			if ( $exclude_replies ) {
				$url .= '&exclude_replies=true';
			}
		} elseif ( $search ) {

			$url = 'https://api.twitter.com/1.1/search/tweets.json?q=' . rawurlencode( $search ) . '&count=' . $number;
		} else {
			return true;
		}

		$args = array(
			'headers' => array(
				'Authorization' => 'Bearer ' . self::$token['access_token'],
			),
		);

		$result = wp_remote_get( $url, $args );

		if ( is_wp_error( $result ) ) {
			return false;
		}

		self::$twitter_data = json_decode( $result['body'] );
	}

	/**
	 * view function.
	 *
	 * @access public
	 * @param mixed $type
	 * @return mixed
	 */
	static function view( $type ) {
		if ( empty( self::$twitter_data ) ) {
			return '';
		}

		if ( is_array( self::$twitter_data->statuses ) ) :
			self::$twitter_data = self::$twitter_data->statuses;
		endif;

		// Return '';.
		ob_start();

		switch ( $type ) {
			case 'slider':
				$id                 = 'twitter-feed-id-' . self::get_counter();
				self::$slider_ids[] = $id;
				?>
			<div class="feed-shortcode feed-view-twitter-slider" id="<?php echo $id; ?>">
				<div class="slider-action">
					<a href="#next" class="next-slide"><i class="icon-chevron-right"></i> <span>next tweet</span></a>
					<a href="#previous" class="previous-slide"><i class="icon-chevron-left"></i> <span>previous tweet</span></a>
				</div>
				<div class="feed-slider-shell feed-tweet-shell" >
					<?php
					$count = 0;
					foreach ( (array) self::$twitter_data as $item ) :
						$odd_or_even = ( $count % 2 ) ? 'odd' : 'even';
						++$count;
						?>
					<div class="feed-tweet feed-slide <?php echo $odd_or_even; ?>">
						<?php if ( ! empty( $item->user ) ) : ?>
						<div class="twitter-user">
							<img src="<?php echo esc_url( $item->user->profile_image_url ); ?>" />
						</div>
					<?php endif; ?>
					<div class="tweet-content">
						<div class="tweet-summary">
							<?php echo self::twitter_content( $item->text ); ?>
						</div><!-- .tweet-summary -->
						<em class="tweet-date"><?php echo self::nice_time( $item->created_at ); ?></em>
					</div><!-- .tweet-content -->
				</div><!-- .feed-tweet -->
			<?php endforeach; ?>
		</div><!-- end of feed-slider-shell -->
	</div>

	<style type="text/css">
	.feed-view-twitter-slider{
		width: 100%;
		overflow: hidden;
	}
	.slider-action{
		position: relative;
		overflow: hidden;
	}
	.slider-action a{
		padding: 0 7px;
		text-decoration: none;
	}
	.slider-action span{
		visibility: hidden;
	}
	.next-slide,
	.previous-slide{
		float: right;
		width: 6px;
		height: 20px;
		background: #EEE;
		display: block;
		margin-right: 2px;
	}
	.twitter-user{
		float: left;
		margin-right: 10px;
	}
	.feed-tweet-shell{
		clear: both;
		position: relative;
		margin-top: 10px;
	}
	.feed-tweet{
		float: left;
	}

	.tweet-content{
		margin-left: 60px;
	}
	</style>
				<?php
				break;
			default:
			case 'default':
			case 'list':
				?>
			<div class="feed-shortcode feed-view-twitter " >
				<?php
				$count = 0;
				foreach ( (array) self::$twitter_data as $item ) :
					$odd_or_even = ( $count % 2 ) ? 'odd' : 'even';
					++$count;
					?>
			<div class="feed-tweet <?php echo $odd_or_even; ?>">
					<?php if ( ! empty( $item->user ) ) : ?>
			<div class="twitter-user">
				<img src="<?php echo esc_url( $item->user->profile_image_url ); ?>" />
			</div>
		<?php endif; ?>
		<div class="tweet-content">
			<div class="tweet-summary">
					<?php echo self::twitter_content( $item->text ); ?>
			</div><!-- .tweet-summary -->
			<em class="tweet-date"><?php echo self::nice_time( $item->created_at ); ?></em>
		</div><!-- .tweet-content -->
	</div><!-- .feed-tweet -->
	<?php endforeach; ?>

</div>
<style type="text/css">
.feed-view-twitter .feed-tweet{
	margin-top: 10px;
}
.twitter-user{
	float: left;
	margin-right: 10px;
}
.feed-tweet-shell{
	position: relative;
	margin-top: 10px;
}
.tweet-content{
	margin-left: 60px;
}
</style>
				<?php
				break;
		}// end of switch
		$output_string = ob_get_contents();
		ob_end_clean();
		// Date_default_timezone_set( $tz );.
		return str_replace( "\r\n", '', $output_string );
	}

	/**
	 * twitter_content function.
	 *
	 * @access public
	 * @param mixed $content
	 * @param mixed $user
	 * @return string
	 */
	static function twitter_content( $content ) {
		$maxLen = 16;
		// Split long words.
		$pattern = '/[^\s\t]{' . $maxLen . '}[^\s\.\,\+\-\_]+/';
		$content = preg_replace( $pattern, '$0 ', $content );

		$pattern = '/\w{2,4}\:\/\/[^\s\"]+/';
		$content = preg_replace( $pattern, '<a href="$0" title="" target="_blank">$0</a>', $content );

		// Search.
		$pattern = '/\#( [a-zA-Z0-9_-]+ )/';
		$content = preg_replace( $pattern, '<a href="https://twitter.com/search?q=%23$1&src=hash" title="" target="_blank">$0</a>', $content );
		// User.
		$pattern = '/\@( [a-zA-Z0-9_-]+ )/';
		$content = preg_replace( $pattern, '<a href="https://twitter.com/#!/$1" title="" target="_blank">$0</a>', $content );

		return $content;
	}

	/**
	 * get_counter function.
	 *
	 * @access public
	 * @return int
	 */
	static function get_counter() {
		++self::$counter;
		return self::$counter;
	}

	/**
	 * nice_time function.
	 *
	 * @access public
	 * @param mixed $time
	 * @return j M, y time
	 */
	static function nice_time( $time ) {
		$time = strtotime( $time );

		$delta = time() - $time;
		if ( $delta < 60 ) {
			return 'less than a minute ago.';
		} elseif ( $delta < 120 ) {
			return 'about a minute ago.';
		} elseif ( $delta < ( 45 * 60 ) ) {
			return floor( $delta / 60 ) . ' min ago.';
		} elseif ( $delta < ( 90 * 60 ) ) {
			return 'about an hour ago.';
		} elseif ( $delta < ( 24 * 60 * 60 ) ) {
			return 'about ' . floor( $delta / 3600 ) . ' hours ago.';
		} elseif ( $delta < ( 48 * 60 * 60 ) ) {
			return '1 day ago.';
		} elseif ( $delta < ( 48 * 60 * 60 * 5 ) ) {
			return floor( $delta / 86400 ) . ' days ago.';
		} elseif ( time() == date( 'Y', $time ) ) {
			return date( 'j M', $time );
		} else {
			return date( 'j M, y', $time );
		}
	}
}

CTLT_Twitter_Feed_Shortcode::init();
