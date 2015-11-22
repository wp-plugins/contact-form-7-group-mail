<?php
/*
Plugin Name: Contact Form 7 Group Mail
Plugin URI: http://www.u3b.com.br/plugins/contact-form-7-group-mail
Description: Send 'Contact Form 7' mails to all users of any group (admins, editors, authors, contributors, subscribers and custom roles).
Version: 1.7.2
Author: Augusto Bennemann
Author URI: http://www.u3b.com.br
License: GPL2
*/

/*  Copyright 2012-2015 Augusto Bennemann (email: gutobenn at gmail.com)

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

// i18n
function wpcf7gm_plugins_loaded() {
	load_plugin_textdomain( 'contact-form-7-group-mail', false, 'contact-form-7-group-mail/languages' );
}
add_action( 'plugins_loaded', 'wpcf7gm_plugins_loaded' );


function wpcf7gm_get_roles() {
    global $wp_roles;

    $all_roles = $wp_roles->roles;
    $editable_roles = apply_filters( 'editable_roles' , $all_roles );
    
    $roles = array();
    
    foreach ( $editable_roles as $slug => $role ) {
    	$roles[$slug] = $role['name'];
    }

    return $roles;
}    


function wpcf7gm_components( $components, $wpcf7 ) {
    
    $settings = get_post_meta( $wpcf7->id, 'wpcf7gm', true );
    $values = array();

   	foreach ($settings['roles'] as $slug => $role) {

        $group_users = get_users( "orderby=nicename&role={$role}" );
        
        foreach ($group_users as $user) {
        	$values[] = $user->user_email;
        };
    }

    if ($settings['mode'] == "cc") {
    	$components['additional_headers'] .= "\nCc: " . implode( ', ', $values); 
    } elseif ($settings['mode'] == "bcc") {
    	$components['additional_headers'] .= "\nBcc: " . implode( ', ', $values); 
    } else { 
    	$components['recipient'] .= ', ' . implode( ', ', $values);
    }

    return $components;
}
add_filter( 'wpcf7_mail_components', 'wpcf7gm_components', 10, 2 );


/**
 * Add panels in Contact Form 7 4.2+
 *
 * @param array $panels registered tabs in Form Editor
 *
 * @return array tabs with CF7GM added
 */
function wpcf7gm_editor_panels( $panels = array() ) {

	if ( wpcf7_admin_has_edit_cap() ) {
		$panels['cf7gm'] = array(
			'title'    => __( 'Group Mail', 'contact-form-7-group-mail' ),
			'callback' => 'wpcf7gm_metabox'
		);
	}

	return $panels;
}
add_action( 'wpcf7_editor_panels', 'wpcf7gm_editor_panels' );


function wpcf7gm_metabox( $post ) {

	$roles = wpcf7gm_get_roles();
	$settings = get_post_meta( $post->id, 'wpcf7gm', true );
    if ( !isset ( $settings['mode']) ) $settings['mode'] == 'normal';
    ?>
    
    <div style="margin-bottom:6px;">
        <label for="wpcf7gm_mode"><?php _e( 'Mode', 'contact-form-7-group-mail' ) ?></label>
        <select id="wpcf7gm_mode" name="wpcf7gm_mode" style="display:inline-block;">
            <option value="normal" <?php if( $settings['mode'] == "normal" ) echo 'selected="selected"'; ?>><?php _e( 'Normal', 'contact-form-7-group-mail' ) ?></option>
            <option value="cc" <?php if( $settings['mode'] == "cc" )echo 'selected="selected"'; ?>><?php _e( 'Cc', 'contact-form-7-group-mail' ) ?></option>
            <option value="bcc" <?php if( $settings['mode'] == "bcc" )echo 'selected="selected"'; ?>><?php _e( 'Bcc', 'contact-form-7-group-mail' ) ?></option>
        </select>
	</div>
            
    <?php foreach( $roles as $slug => $name ) : ?>

        <div style="margin-bottom:6px;">
            <input type="checkbox" style="margin-bottom:-1px; margin-right:4px;" name="wpcf7gm_role_<?=$slug?>" id="wpcf7gm_role_<?=$slug?>" <?php if ( !empty( $settings['roles'] ) ) if( in_array( $slug, $settings['roles'] ) ) echo 'checked="checked"'; ?>/>
            <label for="wpcf7gm_role_<?=$slug?>"><?=translate_user_role( $name ) ?></label>
        </div>

    <?php endforeach;
}


/**
 * 
 * @param WPCF7_ContactForm $wpcf7 Object with the form informations. 
 */
function wpcf7gm_save( $wpcf7 ) {
	wpcf7_group_update_meta( $wpcf7->id );
}
add_action( 'wpcf7_after_save', 'wpcf7gm_save' );



/**
 * 
 * @param WPCF7_ContactForm $new The new form object.
 * @param WPCF7_ContactForm $old The copied form object.
 */
function wpcf7gm_copy( $new, $old ) {
	
    wpcf7_group_update_meta( $new->id );

}
apply_filters( 'wpcf7_copy', 'wpcf7gm_copy' );



function wpcf7_group_update_meta( $wpcf7_id ) {

	$settings = array();
	$settings['mode'] = $_POST['wpcf7gm_mode'];

	$settings['roles'] = array();
	$roles = wpcf7gm_get_roles();
	foreach( $roles as $slug => $role ) {
	
		if( isset( $_POST["wpcf7gm_role_{$slug}"] ) && $_POST["wpcf7gm_role_{$slug}"] == "on"  ) {
			$settings['roles'][] = $slug;
		}
	}
	
	update_post_meta( $wpcf7_id, 'wpcf7gm', $settings );
}
