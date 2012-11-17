<?php
/*
Plugin Name: Contact Form 7 Group Mail
Plugin URI: http://www.u3b.com.br/plugins/contact-form-7-group-mail
Description: Send 'Contact Form 7' mails to all users of any group (admins, editors, authors, contributors, subscribers and custom roles).
Version: 1.1.5
Author: Augusto Bennemann
Author URI: http://www.u3b.com.br
License: GPL2
*/

/*  Copyright 2012 Augusto Bennemann (email: gutobenn at gmail.com)

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

function get_c_roles() {
    global $wp_roles;

    $all_roles = $wp_roles->roles;
    $editable_roles = apply_filters('editable_roles', $all_roles);
    
    $roles = array();
    
    foreach ( $editable_roles as $slug => $role) {
    	$roles[$slug] = $role['name'];
    }

    return $roles;
}    

function wpcf7_group_mail_components( $components, $wpcf7 ) {
    
    $settings = get_post_meta( $wpcf7->id, 'wpcf7_group_mail', true );
    $values = array();

   	foreach( $settings['roles'] as $slug => $role ) {
        $group_users = get_users( "orderby=nicename&role={$role}" );
        
        foreach ($group_users as $user) {
        	$values[] = $user->user_email ;
        };
    }

    if( $settings['mode'] == "cc" ) {
    	$components['additional_headers'] .= 'Cc: ' . implode( ', ', $values); 
    } elseif( $settings['mode'] == "cco") {
    	$components['additional_headers'] .= 'Cco: ' . implode( ', ', $values); 
    } else { 
    	$components['recipient'] .= ', ' . implode( ', ', $values);
    }

    return $components;
}
add_filter( 'wpcf7_mail_components', 'wpcf7_group_mail_components', 10, 2 );

function wpcf7_group_mail_add_box_metabox( $post_id ) {
	
	add_meta_box( 
		'wpcf7_group_mail_metabox', 
		'Group Mail', 
		'wpcf7_group_mail_metabox',
		null,
		'additional_settings',
		'low'
    );
}
add_action( 'wpcf7_add_meta_boxes', 'wpcf7_group_mail_add_box_metabox' );

function wpcf7_group_mail_metabox( $post, $metabox ) {

            	$roles = get_c_roles();
            	$settings = get_post_meta( $post->id, 'wpcf7_group_mail', true );
            ?>
            	<div style="margin-bottom:6px;">
		<label for="wpcf7_group_mail_mode">Mode</label>
                    <select id="wpcf7_group_mail_mode" name="wpcf7_group_mail_mode" style="display:inline-block;">
                        <option value="normal" <?php if( $settings['mode'] == "normal" )echo 'selected="selected"'; ?>>Normal</option>
                        <option value="cc" <?php if( $settings['mode'] == "cc" )echo 'selected="selected"'; ?> >Cc</option>
                        <option value="cco" <?php if( $settings['mode'] == "cco" )echo 'selected="selected"'; ?> >Cco</option>
                    </select>
		</div>
            
            <?php foreach( $roles as $slug => $name ) : ?>
                <div style="margin-bottom:6px;">
                    <input type="checkbox" style="margin-bottom:-1px; margin-right:4px;" name="wpcf7_group_mail_role_<?=$slug?>" id="wpcf7_group_mail_role_<?=$slug?>" 
                    	<?php if ( in_array( $slug, $settings['roles'] ) ) echo 'checked="checked"'; ?>/>
		    <label for="wpcf7_group_mail_role_<?=$slug?>"><?=$name?></label>
                </div>
            <?php endforeach;
}

/**
 * 
 * @param WPCF7_ContactForm $wpcf7 Object with the form informations. 
 */
function wpcf7_group_mail_save( $wpcf7 ) {
	wpcf7_group_update_meta( $wpcf7->id );
}
add_action( 'wpcf7_after_save', 'wpcf7_group_mail_save' );

/**
 * 
 * @param WPCF7_ContactForm $new The new form object.
 * @param WPCF7_ContactForm $old The copied form object.
 */
function wpcf7_group_mail_copy( $new, $old ) {
	wpcf7_group_update_meta( $new->id );
}
apply_filters( 'wpcf7_copy', 'wpcf7_group_mail_copy' );

function wpcf7_group_update_meta( $wpcf7_id ) {

	$settings = array();

	$settings['mode'] = $_POST['wpcf7_group_mail_mode'];
	
	
	
	$settings['roles'] = array();
	$roles = get_c_roles();
	foreach( $roles as $slug => $role ) {
	
		if( isset( $_POST["wpcf7_group_mail_role_{$slug}"] ) && $_POST["wpcf7_group_mail_role_{$slug}"] == "on"  ) {
			$settings['roles'][] = $slug;
		}
	}
	
	update_post_meta( $wpcf7_id, 'wpcf7_group_mail', $settings	);
}



/* detect old config (1.0 or older) and automatically reconfigure after installation. */
function wpcf7_group_mail_reconfigure(){
	if( get_option("mode") ){
	$roles = get_c_roles();
	$settings = array();

	$settings['mode'] = get_option("mode");

	foreach ($roles as $slug => $role ){
		if( get_option($slug) == "on" ) $settings['roles'][]=$slug;
	}

	$posts_array = get_posts( array('post_type' => 'wpcf7_contact_form') );
	foreach( $posts_array as $post ){
		update_post_meta( $post->ID, 'wpcf7_group_mail', $settings	);
	}

	

	delete_option("mode");
	}
}add_action('init','wpcf7_group_mail_reconfigure',1);

//register_activation_hook( __FILE__, 'wpcf7_group_mail_reconfigure' );
//add_action('update_plugin_complete_actions', 'wpcf7_group_mail_reconfigure');
?>
