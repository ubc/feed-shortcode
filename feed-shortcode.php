<?php
/*
* Plugin Name: Feed Shortcode
* Plugin URI:
* Description: A [feed] shortcode plugin. 
* Version: 1.0
* Author: UBC CMS
* Author URI:
*
*
* This program is free software; you can redistribute it and/or modify it under the terms of the GNU
* General Public License as published by the Free Software Foundation; either version 2 of the License,
* or (at your option) any later version.
*
* This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without
* even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*
* You should have received a copy of the GNU General Public License along with this program; if not, write
* to the Free Software Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA 02110-1301 USA
*
* @license http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
*/

class CTLT_Feed_Shortcode {

	/**
	 * init function.
	 *
	 * @access public 
	 * @return void
	 */
	public function init() {
		add_action('init', array(__CLASS__, 'register_shortcode'));
		
	}
	/**
	 * register_shortcode function.
	 * 
	 * @access public
	 * @return void
	 */
	function register_shortcode(){
		self::add_shortcode( 'feed',  'feed_shortcode' );
	}
	/**
	 * has_shortcode function.
	 * 
	 * @access public
	 * @param mixed $shortcode
	 * @return void
	 */
	function has_shortcode( $shortcode ){
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
	function add_shortcode( $shortcode, $shortcode_function ){
	
		if( !self::has_shortcode( $shortcode ) )
			add_shortcode( $shortcode, array( __CLASS__, $shortcode_function ) );
		
	}
	
	
	/**
	 * feed_shortcode function.
	 * 
	 * @access public
	 * @static
	 * @param mixed $atts
	 * @param mixed $content
	 * @return void
	 */
	public static function feed_shortcode( $atts, $content ) {

		global $clf_base_options,$post;
		extract(shortcode_atts(array(  
		    "url" 			=> '',  
			"num" 			=> '',
			"excerpt" 		=> true,
			"target"		=> '_self',
			'date_format' 	=> 'M d, Y',
			'view'			=> 'default',
			'empty'			=> '',
			'excerpt_length'=> 0,
			'time_zone' 	=> null,
			'show_author' 	=> '',  //YC, Oct 2012 - add parameter; value true/false
			'show_date' 	=> '' // - add parameter; value updated/true/false
		), $atts));
		
		$num = ( $num > 0 ? $num : 15 );
		
		if(empty($url) && is_singular())
			$url =	get_post_meta($post->ID, 'feed-url', true);
			
		$url = html_entity_decode($url); 
		
		$target = (empty($target)? "": "target='".esc_attr($target)."'");
		
		$excerpt = ($excerpt != false && $excerpt != "false" ? true: false );
		
		$feed = fetch_feed($url); // all the hard work is done here
		
		if (is_wp_error( $feed ) && $empty == '') 
			return false; // fail silenly 
		elseif( is_wp_error( $feed ) )
			return $empty;
		
		// Begin: YC, Oct 2012 - show author, show_updated_date, used only in view=rsswidget
		$show_author = ($show_author != false && $show_author != "false" ? true: false );
		$show_date = ( $show_date != false && $show_updated_date != "false" ? $show_date: false );
		
		
		// Figure out how many total items there are
		$maxitems = $feed->get_item_quantity(); 
		
		// change the timezone 
		if(parse_url ($url, PHP_URL_HOST) == "services.calendar.events.ubc.ca" && empty($time_zone) )
			$time_zone = "America/Vancouver";
			
		$tz = date_default_timezone_get();
		date_default_timezone_set($time_zone); // "America/Vancouver"
	
		// Build an array of all the items, starting with element 0 (first element).
		$rss_items = $feed->get_items( 0, $maxitems );
		if(empty($rss_items) && $empty == '')
			return false;
		elseif(empty($rss_items))	
			return "<span class='feed-shorcode feed-empty'>".$empty."</span>";
		
		ob_start();
		switch($view) {
			
			case "default":
			case "list":
				$rss_items = array_slice( $rss_items, 0, $num );
				?>
			<ul class="feed-shortcode feed_widget feed-view-default">
					
			<?php 
			$count = 0;
			foreach ( (array) $rss_items as $item ): 
				$odd_or_even =	($count%2) ? "odd" : "even"; $count++;
			?>
				<li class="feed_entry <?php echo $odd_or_even; ?>"><a class="feed_title" href="<?php echo esc_url( $item->get_permalink() ); ?>" <?php echo $target; ?> ><?php echo esc_html($item->get_title());?></a>
				<?php if ($excerpt):  ?>
					<div class="feed_excerpt"><?php 
					if(is_numeric($excerpt_length) && $excerpt_length >0 )
						echo $item->get_description();  
					else
						echo self::strshorten( $item->get_description(), $excerpt_length );
					?></div>
					<a class="feed_readmore" href="<?php echo esc_url( $item->get_permalink() ); ?>"  <?php echo $target; ?> ><span class="feed_readmore_text">Read More</span> <span class="feed_arrow_icon">&raquo;</span></a>
				<?php endif; ?>
			<?php endforeach; ?> 
			</ul>
			<?php	
			break;
			
			case "rsswidget":
				$rss_items = array_slice( $rss_items, 0, $num );
				?>
				<ul class="feed-shortcode feed_view feed-view-rsswidget">
					
				<?php 
				$count = 0;
				$author = ''; //YC, Oct 2012 - author
				$updated_date = ''; //YC, Oct 2012 - updated_date
				
				// Begin: YC Oct 2012 - if show_updated_date = true, sort items by last updated date and id instead of published date
				if ( 'updated' == $show_date ):
					function clf_base_date_cmp($a, $b) {
						$item_a = $a->get_item_tags('http://www.w3.org/2005/Atom', 'updated');
						$item_b = $b->get_item_tags('http://www.w3.org/2005/Atom', 'updated');
						return strcmp($item_b[0]['data'], $item_a[0]['data']); 
					}
					
					function clf_base_id_cmp($a, $b) {
						$item_a = $a->get_item_tags('http://www.w3.org/2005/Atom', 'id');
						$item_b = $b->get_item_tags('http://www.w3.org/2005/Atom', 'id');
						return strcmp($item_b[0]['data'],$item_a[0]['data']); 
					}
					usort($rss_items, 'clf_base_date_cmp');
					usort($rss_items, 'clf_base_id_cmp');				
				endif;// End: if show_updated_date = true, sort items by last updated date and id instead of published date
				
				foreach ( (array) $rss_items as $item ): 
					$odd_or_even =	($count%2) ? "odd" : "even"; 
					$count++;
				
					// Begin: YC, Oct 2012 - add option to display post item's author and date format
					if ( $show_author ) :
						$author = $item->get_author();
						
						if ( $author )
							$author = ' <cite class="author">' . esc_html( strip_tags( $author->get_name() ) ) . '</cite>';
						
					endif;// End display post item's author
						
					// Begin: YC, Oct 2012 - show posts' last updated date instead of published date
					if ( $show_date ):
						$updated_date = $item->get_item_tags('http://www.w3.org/2005/Atom', 'updated');
						$updated_date = $updated_date[0]['data'];
						$updated_date = date($date_format, strtotime( $updated_date )); //adjust to current time-zone				
					endif; //End: YC Oct 2012 - sort by and show posts' last modify date instead of publish date
				
				?>
				<li class="<?php echo $odd_or_even; ?>">
					<a class="rsstitle" href="<?php echo esc_url( $item->get_permalink() ); ?>" <?php echo $target; ?> ><?php echo esc_html($item->get_title());?></a>
				<!-- YC, Oct 2012 - show the posts' author and last updated date-->
				<?php echo $author;
				
					if ( $updated_date ):
					
						 echo '<span class="rss-bracket">(</span> <span class="rss-date">' . $updated_date . '</span><span class="rss-bracket">)</span>';
					endif;
					if ($excerpt):  ?>
						<div class="feed_excerpt"><?php 
						if(is_numeric($excerpt_length) && $excerpt_length >0 )
							echo $item->get_description();  
						else
							echo self::strshorten( $item->get_description(), $excerpt_length );
						?></div>
						<a class="feed_readmore" href="<?php echo esc_url( $item->get_permalink() ); ?>"  <?php echo $target; ?> ><span class="feed_readmore_text">Read More</span> <span class="feed_arrow_icon">&raquo;</span></a>
					<?php endif; ?>
					</li>
				<?php endforeach; ?> 
				</ul>
			<?php
			break;
			
			case "events":
				$entries = array();
				
				foreach ( $rss_items as $item ) :
					$entries[$item->get_date($date_format)][] = $item; 
				endforeach;
				
				?>
				<ul class="feed-shortcode feed_view feed-view-events">
				<?php
				
				foreach($entries as $date=>$items):
					if($count > $num - 1)
					break ;	
					
					?>
						<li class="event-rss-date"><span><?php echo $date; ?></span>
						<ul class="event-rss-item">
							<?php 
							foreach($items as $item): 
							$count++;?>
							<li><a href='<?php echo $item->get_permalink(); ?>' title='<?php echo 'Posted '.$item->get_date('j F Y | g:i a'); ?>'><?php echo $item->get_title(); ?></a></li>
							<?php endforeach; ?>
						</ul>
						</li>
					<?php
				endforeach;
				?>
				</ul>
			<?php
			break;
			
			case "listevents":
	 
	            $entries = array();
	 
	            foreach ( $rss_items as $item ) :
	                    $entries[$item->get_date("U")][] = $item;
	            endforeach;
	             
	            $entries = array_reverse($entries); ?>
	 
	            <div class="feed-shortcode feed_widget feed-view-listevents">
	            <?php
	                foreach($entries as $date=>$items):
	                    if($count > $num - 1)
	                        break ; ?><?php
	                     foreach($items as $item):
	                            $count++;?>
	                       <h2 class=""><?php echo $item->get_title(); ?></h2>
	                       <h3><?php echo $item->get_date('F j, Y'); ?></h3><?php
	                       echo $item->get_content();  ?>
	                       <hr/><?php
	                       endforeach;
	                endforeach;?>
	            </div><?php
	            break;
	                            		
			case "upcoming":
				$entries =array();
				
				foreach ( $rss_items as $item ) :
					$entries[$item->get_date($date_format)][] = $item; 
				endforeach;
				
				$entries = array_reverse($entries);
				
				?>
				<ul class="feed-shortcode feed_widget feed-view-upcoming">
					<?php
					
					foreach($entries as $date=>$items):
						if($count > $num - 1)
							break ;	
						
					?>
						<li class="event-rss-date"><span><?php echo $date; ?></span>
						<ul class="event-rss-item">
							<?php 
							foreach($items as $item): 
								$count++; ?>
							<li><a href='<?php echo $item->get_permalink(); ?>' title='<?php echo 'Posted '.$item->get_date('j F Y | g:i a'); ?>'><?php echo $item->get_title(); ?></a></li>
							<?php endforeach; ?>
						</ul>
						</li>
					<?php
			
					endforeach;
					?>
				</ul>
			<?php
			break;
	
			
			case "archive": 
				$rss_items = array_slice( $rss_items, 0, $num );
				?>
				<div class="feed-shortcode feed-view-archive">
					
				<?php 
				$count = 0;
				foreach ( (array) $rss_items as $item ): 
					$odd_or_even =	($count%2) ? "odd" : "even"; $count++;
				
				?>
					<div class="hentry post publish <?php echo $odd_or_even; ?>">
		
						
						<h2 class="entry-title"><a rel="bookmark" title="<?php echo esc_attr($item->get_title());?>"  href="<?php echo esc_url( $item->get_permalink() ); ?>" <?php echo $target; ?>><?php echo esc_html($item->get_title());?></a></h2>
						<p class="byline">Posted on <?php echo $item->get_date($date_format); ?></p>
						<div class="entry-summary">
							<?php 
							if(is_numeric($excerpt_length) && $excerpt_length >0 )
								echo $item->get_description();  
							else
								echo self::strshorten( $item->get_description(), $excerpt_length );
							  ?>
						</div><!-- .entry-summary -->
					</div>
			<?php endforeach;  ?>
			</div>
			<?php
			break;
			
			case "blog":
					$rss_items = array_slice( $rss_items, 0, $num );
				?>
				<div class="feed-shortcode feed-view-blog">
					
				<?php 
				$count = 0;
				foreach ( (array) $rss_items as $item ): 
					$odd_or_even =	($count%2) ? "odd" : "even"; 
					$count++;
					?>
					<div class="hentry post publish <?php echo $odd_or_even; ?>">
						<h2 class="entry-title"><a rel="bookmark" title="<?php echo esc_attr($item->get_title());?>"  href="<?php echo esc_url( $item->get_permalink() ); ?>" <?php echo $target; ?>><?php echo esc_html($item->get_title());?></a></h2>
						<p class="byline">Posted on <?php echo $item->get_date($date_format); ?></p>
						<div class="entry-summary">
							<?php 
							if(is_numeric($excerpt_length) && $excerpt_length >0 )
								echo self::strshorten( $item->get_description(), $excerpt_length );
							else
								echo $item->get_content();
							  ?>
						</div><!-- .entry-summary -->
					</div>
			<?php endforeach;  ?>
			</div>
			<?php
	
			break;	
			
			case "timeline":
				$entries = array();
				
				foreach ( $rss_items as $item ) :
					$entries[$item->get_date("F Y")][] = $item; 
				endforeach;
				
				$entries = array_reverse($entries);
				
				?>
				<ul class="feed-shortcode feed_widget feed-view-timeline">
				<?php
				
				foreach($entries as $date=>$items):
				if($count > $num - 1)
					break ;	
					
				?>
					<li class="event-rss-date"><span><?php echo $date; ?></span>
					<ul class="event-rss-item">
						<?php 
						foreach($items as $item): 
						$count++;?>
						<li><span class="date"><?php echo $item->get_date('j F '); ?> </span> <a href='<?php echo $item->get_permalink(); ?>' title='<?php echo 'Posted '.$item->get_date('j F Y | g:i a'); ?>'><?php echo $item->get_title(); ?></a></li>
						<?php endforeach; ?>
					</ul>
					</li>
				<?php
				endforeach;
				?>
				</ul>
				<?php
			break;
			
			case "calendar":
			case "cal":
				
				// $current = ( empty( $_GET['current'] ) ? "" :  $_GET['current'] );
				$current = (empty( $_GET['current'] )? 0 : $_GET['current']);
				$current_new = ( $current>0? "+". $current: $current );
				$current_month = "current". (empty($current_new)? "" : $current_new);
				$next_month = (empty($current)? 1 : $current+1);
				$previous_month = (empty($current)? -1 : $current-1);
				
				
				
				foreach((array) $rss_items as $item):				
					$data[$item->get_date('Y')][$item->get_date('n')][$item->get_date('j')][] = $item;
				endforeach;
				
				// get year, eg 2006
				$year = (int)date('Y');
				// get month, eg 04
				$month = (int)date('n')+$current;
				
				if($month > 12):
					$year  = (int)($month/12)+$year;
					$month = ($month%12);			
				endif;
				
				// get day, eg 3
				$day = date('j');
				
				// get number of days in month, eg 28
				$daysInMonth = date("t",mktime(0,0,0,$month,1,$year));
				
				// get first day of the month, eg 4
				$firstDay = date("w", mktime(0,0,0,$month,1,$year));
				
				// calculate total spaces needed in array
				$tempDays = $firstDay + $daysInMonth;
				
				// calculate total rows needed
				$weeksInMonth = ceil($tempDays/7);
				
				for( $j=0; $j < $weeksInMonth; $j++ ) {
				
					for( $i=0; $i<7; $i++ ) {
					    $counter++;
					    $week[$j][$i] = $counter;
					}
				}
							
				?>
				<div class="feed-shortcode feed-view-calendar">
				<h3><?php echo date('F', mktime(0,0,0,$month,1,$year)).' '.$year; ?></h3>
				<table>	
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
				foreach ($week as $key => $val) : 
				echo "<tr>";
				for ($i=0;$i<7;$i++) {
					$date++;
					$current_day = $date-$firstDay;
					$content = "";
					if($current_day <=0 || $current_day > $daysInMonth)
						$current_day = "";
					
					if(is_array($data[$year][$month][$current_day])):
					$content .="<div class='feed-links'>";
					foreach( (array) $data[$year][$month][$current_day] as $feed_item):
					
					$content .= "<a href='".$feed_item->get_permalink()."' $target >".$feed_item->get_title()."</a>";
					
					endforeach;
					$content .="</div>";
					
					endif;
					
					if($current_date <= $daysInMonth):
						if($content != ''):
							echo "<td align='center' class='feed-day-shell'><div class='feed-day-inner'><span class='feed-date has-events'>".$current_day."</span>$content</div></td>";
						else:
							echo "<td align='center'><span class='feed-date'>".$current_day."</span></td>";
						endif;
						
					else:
						echo "<td align='center'> </td>";
					endif;
				}
				echo "</tr>";
				endforeach;
				?>
				</table> 
				<p>
				<a href="?current=<?php echo $previous_month; ?>" class="button">Previous Month</a>
				<a href="?current=<?php echo $next_month; ?>" class="button">Next Month</a>
				</p>
				</div>
				<?php
				
			break;
			
			case "flickr": 
				static $flickr_instance = 0;
				$flickr_instance++;
				$columns =5; 
				$rss_items = array_slice( $rss_items, 0, $num );
				$i = 0;
			?><style type="text/css">
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
				<?php foreach( $rss_items as $item): 
					if($enclosure = $item->get_enclosure() ): 
					
					?>
					<dl class="gallery-item">
					<dt class="gallery-icon">
									<a title="<?php echo esc_attr($item->get_title()); ?>" href="<?php echo esc_url($enclosure->link); ?>"  rel="gallery-0"><img width="75" height="75" title="<?php echo esc_attr($item->get_title()); ?>" alt="<?php echo esc_attr($item->get_title()); ?>" class="attachment-thumbnail" src="<?php echo esc_url($enclosure->thumbnails[0]); ?>"></a>
								</dt>
					</dl>
					<?php if ( $columns > 0 && ++$i % $columns == 0 )
						echo '<br style="clear: both" />';
						
						endif;
					 endforeach; ?>
				</div>
			<?php 
			break;

		}// end of switch
	 
		date_default_timezone_set($tz);
		return str_replace("\r\n", '', ob_get_clean() );
	
	}
	
	/**
	 * strshorten function.
	 * 
	 * @access public
	 * @param mixed $string
	 * @param mixed $length
	 * @return void
	 */
	function strshorten( $string, $length ) {
	    // By default, an ellipsis will be appended to the end of the text.
	    $suffix = '...';
	 
	    // Convert 'smart' punctuation to 'dumb' punctuation, strip the HTML tags,
	    // and convert all tabs and line-break characters to single spaces.
	    $short_desc = trim(str_replace(array("\r","\n", "\t"), ' ', strip_tags($string)));
	 
	    // Cut the string to the requested length, and strip any extraneous spaces 
	    // from the beginning and end.
	    $desc = trim(substr($short_desc, 0, $length));
	 
	    // Find out what the last displayed character is in the shortened string
	    $lastchar = substr($desc, -1, 1);
	 
	    // If the last character is a period, an exclamation point, or a question 
	    // mark, clear out the appended text.
	    if ($lastchar == '.' || $lastchar == '!' || $lastchar == '?') $suffix='';
	 
	    // Append the text.
	    $desc .= $suffix;
	 
	    // Send the new description back to the page.
	    return $desc;
	}

}

CTLT_Feed_Shortcode::init();
