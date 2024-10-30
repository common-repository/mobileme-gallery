<?php
/*
Plugin Name:  MobileMe Gallery
Plugin URI: http://www.vjcatkick.com/?page_id=16017
Description: Display Apple\'s MobileMe Gallery on your blog.
Version: 0.0.2
Author: V.J.Catkick
Author URI: http://www.vjcatkick.com/
*/

/*
License: GPL
Compatibility: WordPress 2.6 with Widget-plugin.

Installation:
Place the widget_single_photo folder in your /wp-content/plugins/ directory
and activate through the administration panel, and then go to the widget panel and
drag it to where you would like to have it!
*/

/*  Copyright V.J.Catkick - http://www.vjcatkick.com/

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
Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/


/* Changelog
* Feb 11 2010 - v0.0.1
- Initial release
* Feb 20 2010 - v0.0.2
- bug fixed
*/


/*
utility function section
*/

define( 'MAGPIE_CACHE_AGE', 60 * 5 );
add_filter( 'wp_feed_cache_transient_lifetime', create_function( '$a', 'return 300;' ) );

function mobileme_gallery_get_rss_feed_items( $url, $count ) {
	@include_once( ABSPATH . WPINC . '/feed.php' );

//	$rss = fetch_rss( $url );

	$rss = fetch_feed( $url );
	if( is_wp_error( $rss ) ) {
		echo 'currently MobileMe Gallery plugin does not support this server: error [ fetch_feed ].';

		return( false );
	}else{
		$maxitems = $rss->get_item_quantity( $count );
		$rss_items = $rss->get_items( 0, $maxitems );
	} /* if else */

	return( $rss_items );
} /* mobileme_gallery_get_rss_feed_items() */

function mobileme_gallery_convert_rss_item_to_data( $item ) {
	$retArray = array();

	if( !$item ) return( false );

	$item_desc = $item->get_description();

// removed 0.0.2
//	if( preg_match_all( '/https?:\/\/[^\"]+/is', $item_desc, $matches ) ) {
//		$retArray[ 'href' ] = $matches[0][0];
//		$retArray[ 'src' ] = $matches[0][1];
//	} /* if */

	// 0.0.2
	$retArray[ 'href' ] = $item->get_link();
	$retArray[ 'src' ] = $retArray[ 'href' ] . '/web.jpg';

	return( $retArray );
} /* mobileme_gallery_convert_rss_item_to_data() */

function mobileme_gallery_create_sq_thumbnail( $filename, $size ) {
		$imagesize = getimagesize( $filename );
		if( $imagesize[0] < $imagesize[1] ) {	// vertical
			$resizerate = $size / $imagesize[0];
			$resize_h = (int)($resizerate * $imagesize[1]);
			$resize_w = $size;
			$resize_x = 0;
			$resize_y = (int)(($imagesize[1] - $imagesize[0]) / 2);
		}else{	// horizontal
			$resizerate = $size / $imagesize[1];
			$resize_h = $size;
			$resize_w = (int)($resizerate * $imagesize[0]);
			$resize_x = (int)(($imagesize[0] - $imagesize[1]) / 2);
			$resize_y = 0;
		} /* if else */

		if( !function_exists( 'imagecreatetruecolor' ) && !function_exists( 'imagecopyresampled' ) ) {
			$img = imagecreate( $size, $size );
			$imagefrom = imagecreatefromjpeg( $filename );
			imagecopyresized( $img, $imagefrom, 0, 0, $resize_x, $resize_y, $resize_w, $resize_h, $imagesize[0], $imagesize[1] );
		}else{	//GD2.0 or later version exist
			$img = imagecreatetruecolor( $size, $size );
			$imagefrom = imagecreatefromjpeg( $filename );
			imagecopyresampled( $img, $imagefrom, 0, 0, $resize_x, $resize_y, $resize_w, $resize_h, $imagesize[0], $imagesize[1] );
		} /* if else */
		return $img;
		imagedestroy( $imagefrom );
		imagedestroy( $img );
} /* mobileme_gallery_create_sq_thumbnail() */

function mobileme_gallery_output_one_item_with_html( $urls, $options, $on_sidebar ) {
	$output = '';
	if( $on_sidebar )
		$boxsize = $options[ 'mobileme_gallery_sb_eachrectsize' ];
	else
		$boxsize = $options[ 'mobileme_gallery_et_eachrectsize' ];
	$sizebox = $boxsize . 'px';

// removed 0.0.2
//	preg_match( '/\/(.*)\.[jpg|jpeg]+\?/is', $urls[ 'src' ], $matches );
//	$w_string = explode( '/', $matches[ 0 ] );
//	$fn = $w_string[ count( $w_string ) - 1 ];
//	$fn = preg_replace( '/\.[jpg|jpeg]+\?/is', '', $fn );

	// 0.0.2
	$w_string = explode( '/', $urls[ 'href' ] );
	$fn = $w_string[ count( $w_string ) - 1 ];

	$dirname = 'mobileme_thumb';
	if( !file_exists( $dirname ) ) {
		mkdir( $dirname );
	} /* if */

	$tmpfilename = $dirname . '/' . $fn . '_thumb.jpg';
	if( !file_exists( $tmpfilename ) ) {
		$the_size = $options[ 'mobileme_gallery_et_eachrectsize' ];
		if( $the_size < $options[ 'mobileme_gallery_sb_eachrectsize' ] ) $the_size = $options[ 'mobileme_gallery_sb_eachrectsize' ];

		$t_img = mobileme_gallery_create_sq_thumbnail( $urls[ 'src' ], $the_size );
		imagejpeg( $t_img, $tmpfilename, 90 );
	} /* if */

	$output .= '<div class="mobileme_gallery_eachrect" style="float: left; width: ' . $sizebox . '; height: ' . $sizebox . '; padding: 3px; margin-right: 3px; margin-bottom: 2px; background-color: #DDD;" >';
	$output .= '<a href="' . $urls[ 'href' ] . '" target="_blank" >';
	$output .= '<img src="' . $tmpfilename . '" border="0" style="width: ' . $sizebox . '; height: ' . $sizebox . '; " />';
	$output .= '</a>';
	$output .= '</div>';

	return( $output );
} /* mobileme_gallery_output_one_item_with_html()*/





/*
entry section
*/

function mobileme_gallery_get_feed_data( $theUrl, $on_sidebar ) {
	$options = mobileme_gallery_get_options();
	$output = '';
	if( $on_sidebar ) $num_items = $options[ 'mobileme_gallery_sb_numberitem' ];
	else $num_items = $options[ 'mobileme_gallery_et_numberitem' ];

	$items = mobileme_gallery_get_rss_feed_items( $theUrl, $num_items );

	if( !$on_sidebar ) {
		if( $options[ 'mobileme_gallery_et_displaytitle' ] ) {
			$output .= '<h2 class="mobileme_gallery_et_title" style="margin-top: 14px; margin-bottom: 10px;" >' . $options[ 'mobileme_gallery_et_title' ] . '</h2>';
		} /* if */
	} /* if */
	$output .= '<div class="mobileme_gallery_wrap" style="width: 100%; float: left; padding-left: 2px; margin-bottom: 0.4em; text-align: center;" >';
	if( !$on_sidebar ) {
		$output .= '<div class="mobileme_gallery_wrap_inner" style="margin-left: auto; margin-right: auto; " >';
	} /* if */

	foreach( $items as $item ) {
		$theData = mobileme_gallery_convert_rss_item_to_data( $item );

		if( $theData ) $output .= mobileme_gallery_output_one_item_with_html( $theData, $options, $on_sidebar );
	} /* foreach */

	$base_url = $theUrl;
	$base_url = str_replace( 'photocast.me.com', 'gallery.me.com', $base_url );
	$base_url = str_replace( '/rss', '', $base_url );
	$urlstr_array = explode( '/', $base_url );
	$base_url = $urlstr_array[0] . '//' . $urlstr_array[2] . '/' . $urlstr_array[3] . '';

	if( !$on_sidebar ) {
		$output .= '</div>';	// mobileme_gallery_wrap_inner

		if( $options[ 'mobileme_gallery_et_displaylink2mg' ] ) {
			$output .= '<br clear="all" />';
			$output .= '<div class="mobileme_gallery_link2toppage" style="text-align: right; margin-right: 8px; font-size: 8px; height: 11px;" >';
			$output .= '<a href="' . $base_url . '" target="_blank" >Jump to MobileMe Gallery</a>';
			$output .= '</div>';
		} /* if */
	}else{
		if( $options[ 'mobileme_gallery_sb_displaylink2mg' ] ) {
			$output .= '<div class="mobileme_gallery_link2toppage" style="width: 100%; float: left; text-align: center; font-size: 8px; height: 11px;" >';
			$output .= '<a href="' . $base_url . '" target="_blank" >Jump to MobileMe Gallery</a>';
			$output .= '</div>';
		} /* if */
	} /* if else */

	$output .= '</div>';	// mobileme_gallery_wrap

	if( !$on_sidebar ) {
		$output .= '<br clear="all" />';
	} /* if */

	return( $output );
} /* mobileme_gallery_get_feed_data() */


function mobileme_gallery_place_mobileme_rss( $mobileme_gallery_url, $on_sidebar ) {

	$target_rss_url = $mobileme_gallery_url;

	if( $mobileme_gallery_url ) {
		return( mobileme_gallery_get_feed_data( $target_rss_url, $on_sidebar ) );
	}else{
		return;
	} /* if else */
} /* mobileme_gallery_place_mobileme_rss() */

// is there much smarter way?
$is_loaded_first_time;

function mobileme_gallery_get_the_gallery( $arg ) {
	global $is_loaded_first_time;

	if( !$is_loaded_first_time ) {
		$options = mobileme_gallery_get_options();

		if( $options[ 'mobileme_gallery_et_master' ] && is_front_page() && !$_GET[ 'paged' ] ) {
			echo mobileme_gallery_place_mobileme_rss( $options[ 'mobileme_gallery_url' ], false );
		} /* if */

		$is_loaded_first_time = $is_loaded_first_time + 1;
	} /* if */

	return( $arg );
} /* mobileme_gallery_get_the_gallery() */

add_action( 'loop_start', 'mobileme_gallery_get_the_gallery' );
add_action( 'admin_menu', 'mobileme_gallery_options' );

function mobileme_gallery_options() {
	add_options_page( 'MobileMe Gallery', 'MobileMe Gallery', 8, 'mobileme_gallery_options', 'mobileme_gallery_options_page' );
} /* mobileme_gallery_options() */


function mobileme_gallery_options_page() {
	$output = '';

	$options = $newoptions = mobileme_gallery_get_options();
	if ( $_POST[ 'mobileme_gallery_option_submit' ] ) {
		$newoptions[ 'mobileme_gallery_url' ] = htmlspecialchars( $_POST[ 'mobileme_gallery_url' ] );

		$newoptions[ 'mobileme_gallery_et_master' ] = (int)( $_POST[ 'mobileme_gallery_et_master' ] );
		$newoptions[ 'mobileme_gallery_et_title' ] = htmlspecialchars( $_POST[ 'mobileme_gallery_et_title' ] );
		$newoptions[ 'mobileme_gallery_et_displaytitle' ] = (int)( $_POST[ 'mobileme_gallery_et_displaytitle' ] );
		$newoptions[ 'mobileme_gallery_et_numberitem' ] = (int)( $_POST[ 'mobileme_gallery_et_numberitem' ] );
		$newoptions[ 'mobileme_gallery_et_displaylink2mg' ] = (int)( $_POST[ 'mobileme_gallery_et_displaylink2mg' ] );
		$newoptions[ 'mobileme_gallery_et_eachrectsize' ] = (int)( $_POST[ 'mobileme_gallery_et_eachrectsize' ] );

		$newoptions[ 'mobileme_gallery_sb_master' ] = (int)( $_POST[ 'mobileme_gallery_sb_master' ] );
		$newoptions[ 'mobileme_gallery_sb_title' ] = htmlspecialchars( $_POST[ 'mobileme_gallery_sb_title' ] );
		$newoptions[ 'mobileme_gallery_sb_numberitem' ] = (int)( $_POST[ 'mobileme_gallery_sb_numberitem' ] );
		$newoptions[ 'mobileme_gallery_sb_displaylink2mg' ] = (int)( $_POST[ 'mobileme_gallery_sb_displaylink2mg' ] );
		$newoptions[ 'mobileme_gallery_sb_eachrectsize' ] = (int)( $_POST[ 'mobileme_gallery_sb_eachrectsize' ] );
	} /* if */
	if ( $options != $newoptions ) {
		$options = $newoptions;
		update_option( 'mobileme_gallery', $options);
	} /* if */

	$mobileme_gallery_url = $options[ 'mobileme_gallery_url' ];

	$mobileme_gallery_et_master = $options[ 'mobileme_gallery_et_master' ];
	$mobileme_gallery_et_title = $options[ 'mobileme_gallery_et_title' ];
	$mobileme_gallery_et_displaytitle = $options[ 'mobileme_gallery_et_displaytitle' ];
	$mobileme_gallery_et_numberitem = $options[ 'mobileme_gallery_et_numberitem' ];
	$mobileme_gallery_et_displaylink2mg = $options[ 'mobileme_gallery_et_displaylink2mg' ];
	$mobileme_gallery_et_eachrectsize = $options[ 'mobileme_gallery_et_eachrectsize' ];

	$mobileme_gallery_sb_master = $options[ 'mobileme_gallery_sb_master' ];
	$mobileme_gallery_sb_title = $options[ 'mobileme_gallery_sb_title' ];
	$mobileme_gallery_sb_numberitem = $options[ 'mobileme_gallery_sb_numberitem' ];
	$mobileme_gallery_sb_displaylink2mg = $options[ 'mobileme_gallery_sb_displaylink2mg' ];
	$mobileme_gallery_sb_eachrectsize = $options[ 'mobileme_gallery_sb_eachrectsize' ];

	$rect_style = 'border: 1px solid #DDD; background-color: white; padding: 10px; margin-bottom: 0.8em; ';

	$output .= '<h2>MobileMe Gallery Settings</h2>';
	$output .= '<form action="" method="post" id="mobileme_gallery_submit" style="margin: auto; width: 600px; ">';

	$output .= '<div style="' . $rect_style . '" >';
	$output .= '<h3>MobileMe information</h3>';

	$output .= '<p>Gallery RSS URL:<br /><input style="" id="mobileme_gallery_url" name="mobileme_gallery_url" type="text" value="' . $mobileme_gallery_url . '" size="60" /><br />';
	$output .= '*something like: \'http://photocast.me.com/username/100001/rss\'</p>';
	$output .= '</div>';


	$output .= '<div style="' . $rect_style . '" >';
	$output .= '<h3>Index page settings</h3>';

	if( $mobileme_gallery_et_master ) $checked = 'checked'; else $checked = '';
	$output .= '<input type="checkbox" id="mobileme_gallery_et_master" name="mobileme_gallery_et_master" value="1" ' . $checked . ' /> Use gallery at index page';
	$output .= '<p>Title:<br /><input style="" id="mobileme_gallery_et_title" name="mobileme_gallery_et_title" type="text" value="' . $mobileme_gallery_et_title . '" size="30" /></p>';
	if( $mobileme_gallery_et_displaytitle ) $checked = 'checked'; else $checked = '';
	$output .= '<input type="checkbox" id="mobileme_gallery_et_displaytitle" name="mobileme_gallery_et_displaytitle" value="1" ' . $checked . ' /> Display title at top of gallery';
	$output .= '<p>Image to display:<br /><input style="" id="mobileme_gallery_et_numberitem" name="mobileme_gallery_et_numberitem" type="text" value="' . $mobileme_gallery_et_numberitem . '" size="5" /> images</p>';
	if( $mobileme_gallery_et_displaylink2mg ) $checked = 'checked'; else $checked = '';
	$output .= '<input type="checkbox" id="mobileme_gallery_et_displaylink2mg" name="mobileme_gallery_et_displaylink2mg" value="1" ' . $checked . ' /> Display link to gallery index page';
	$output .= '<p>Size of each images:<br /><input style="" id="mobileme_gallery_et_eachrectsize" name="mobileme_gallery_et_eachrectsize" type="text" value="' . $mobileme_gallery_et_eachrectsize . '" size="5" /> px</p>';
	$output .= '</div>';


	$output .= '<div style="' . $rect_style . '" >';
	$output .= '<h3>Sidebar widget settings</h3>';

	if( $mobileme_gallery_sb_master ) $checked = 'checked'; else $checked = '';
	$output .= '<input type="checkbox" id="mobileme_gallery_sb_master" name="mobileme_gallery_sb_master" value="1" ' . $checked . ' /> Use sidebar widget';
	$output .= '<p>Title:<br /><input style="" id="mobileme_gallery_sb_title" name="mobileme_gallery_sb_title" type="text" value="' . $mobileme_gallery_sb_title . '" size="30"/></p>';
	$output .= '<p>Image to display:<br /><input style="" id="mobileme_gallery_sb_numberitem" name="mobileme_gallery_sb_numberitem" type="text" value="' . $mobileme_gallery_sb_numberitem . '" size="5" /> images</p>';
	if( $mobileme_gallery_sb_displaylink2mg ) $checked = 'checked'; else $checked = '';
	$output .= '<input type="checkbox" id="mobileme_gallery_sb_displaylink2mg" name="mobileme_gallery_sb_displaylink2mg" value="1" ' . $checked . ' /> Display link to gallery index page';
	$output .= '<p>Size of each images:<br /><input style="" id="mobileme_gallery_sb_eachrectsize" name="mobileme_gallery_sb_eachrectsize" type="text" value="' . $mobileme_gallery_sb_eachrectsize . '" size="5" /> px</p>';
	$output .= '</div>';


	$output .= '<p class="submit"><input type="submit" name="mobileme_gallery_option_submit" value="'. 'Update options &raquo;' .'" /></p>';

	$output .= '<p>Documentation of this plugin is <a href="http://www.vjcatkick.com/?page_id=16017" target="_blank" >here</a>.</p>';
	$output .= '</form>';


	echo $output;
} /* mobileme_gallery_options_page() */



function mobileme_gallery_get_options() {
	$options = get_option( 'mobileme_gallery' );

	// for entry top options
	if( $options[ 'mobileme_gallery_et_master' ] !== 0 ) $options[ 'mobileme_gallery_et_master' ] = 1;
	if( !$options[ 'mobileme_gallery_et_title' ] ) $options[ 'mobileme_gallery_et_title' ] = 'MobileMe Gallery';
	if( $options[ 'mobileme_gallery_et_displaytitle' ] !== 0 ) $options[ 'mobileme_gallery_et_displaytitle' ] = 1;
	if( !$options[ 'mobileme_gallery_et_numberitem' ] ) $options[ 'mobileme_gallery_et_numberitem' ] = 5;
	if( $options[ 'mobileme_gallery_et_displaylink2mg' ] !== 0 ) $options[ 'mobileme_gallery_et_displaylink2mg' ] = 1;
	if( !$options[ 'mobileme_gallery_et_eachrectsize' ] ) $options[ 'mobileme_gallery_et_eachrectsize' ] = 120;

	// for sidebar widget options
	if( $options[ 'mobileme_gallery_sb_master' ] !== 0 ) $options[ 'mobileme_gallery_sb_master' ] = 1;
	if( !$options[ 'mobileme_gallery_sb_title' ] ) $options[ 'mobileme_gallery_sb_title' ] = 'MobileMe Gallery';
	if( !$options[ 'mobileme_gallery_sb_numberitem' ] ) $options[ 'mobileme_gallery_sb_numberitem' ] = 4;
	if( $options[ 'mobileme_gallery_sb_displaylink2mg' ] !== 0 ) $options[ 'mobileme_gallery_sb_displaylink2mg' ] = 1;
	if( !$options[ 'mobileme_gallery_sb_eachrectsize' ] ) $options[ 'mobileme_gallery_sb_eachrectsize' ] = 80;


	return( $options );
} /* mobileme_gallery_get_options() */



/*
sidebar widget section
*/

function mobileme_gallery_init() {
	if( !function_exists( 'register_sidebar_widget' ) ) return;

	function mobileme_gallery( $args ) {
		extract($args);
		$options = mobileme_gallery_get_options();

		if( $options[ 'mobileme_gallery_sb_master' ] ) {
			$mobileme_gallery_sb_title = $options[ 'mobileme_gallery_sb_title' ];

			$output = '<div id="mobileme_gallery"><ul>';
			$output .= mobileme_gallery_place_mobileme_rss( $options[ 'mobileme_gallery_url' ], true );
			$output .= '</ul></div>';

			echo $before_widget . $before_title . $mobileme_gallery_sb_title . $after_title;
			echo $output;
			echo $after_widget;
		} /* if */
	} /* mobileme_gallery() */

	function mobileme_gallery_control() {
		$options = $newoptions = mobileme_gallery_get_options();

		if ( $_POST[ 'mobileme_gallery_sb_submit' ] ) {
			$newoptions[ 'mobileme_gallery_sb_title' ] = htmlspecialchars( $_POST[ 'mobileme_gallery_sb_title' ] );
		} /* if */
		if ( $options != $newoptions ) {
			$options = $newoptions;
			update_option( 'mobileme_gallery', $options);
		} /* if */

		$mobileme_gallery_sb_title = htmlspecialchars( $options[ 'mobileme_gallery_sb_title' ], ENT_QUOTES );

?>

	    <?php _e('Title:'); ?> <input style="width: 170px;" id="mobileme_gallery_sb_title" name="mobileme_gallery_sb_title" type="text" value="<?php echo $mobileme_gallery_sb_title; ?>" /><br />

		<p style="margin-top: 1.0em;" >*click <a href="<?php echo get_bloginfo( 'wpurl' ) . '/wp-admin/options-general.php?page=mobileme_gallery_options' ; ?>" >here</a> to set other options.</p>

  	    <input type="hidden" id="mobileme_gallery_sb_submit" name="mobileme_gallery_sb_submit" value="1" />

<?php
	} /* mobileme_gallery_control() */

	register_sidebar_widget( 'MobileMe Gallery', 'mobileme_gallery' );
	register_widget_control( 'MobileMe Gallery', 'mobileme_gallery_control' );
} /* mobileme_gallery_init() */

add_action( 'plugins_loaded', 'mobileme_gallery_init' );



?>