<?php
/*
Plugin Name: Contact Form 7 Group Mail
Plugin URI: http://www.u3b.com.br/plugins/contact-form-7-group-mail
Description: Send 'Contact Form 7' mails to all users of any group (admins, editors, authors, contributors, subscribers and custom roles).
Version: 1.0
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

    return $editable_roles;
}

add_action('admin_menu', 'wpcf7_group_mail_create_menu');

function wpcf7_group_mail_create_menu() {
    add_submenu_page('wpcf7', 'Group Mail', 'Group Mail','administrator', __FILE__, 'wpcf7_group_mail_settings_page');
    add_action( 'admin_init', 'wpcf7_group_mail_register_settings' );
}

function wpcf7_group_mail_register_settings() {
    register_setting( 'wpcf7_group_mail-settings-group', 'mode' );
    $roles_keys = array_keys(get_c_roles());
    for($i = 0; $i < sizeof($roles_keys); $i++){
        register_setting( 'wpcf7_group_mail-settings-group', $roles_keys[$i] );
    }
} 

function wpcf7_group_mail_settings_page() {
?>
    <div class="wrap">
    <h2>Contact Form 7 Group Mail</h2>

    Select groups to receive 'Contact Form 7' messages
    <form method="post" action="options.php">

        <?php settings_fields( 'wpcf7_group_mail-settings-group' ); ?>
        <table class="form-table">

            <?php 
            $nomes = get_c_roles();
            $nomes_keys = array_keys($nomes);
            ?>
            <tr valign="top">
                <th scope="row">Mode</th>
                <td>
                    <select name="mode">
                        <option value="normal" <?php if(get_option("mode") == "normal")echo 'selected="selected"'; ?>>Normal</option>
                        <option value="cc" <?php if(get_option("mode") == "cc")echo 'selected="selected"'; ?> >Cc</option>
                        <option value="cco" <?php if(get_option("mode") == "cco")echo 'selected="selected"'; ?> >Cco</option>
                    </select>
                </td>
            </tr>
            
            <?php for($i = 0; $i < sizeof($nomes); $i++){?>
                <tr valign="top">
                    <th scope="row"><?=$nomes[$nomes_keys[$i]]['name']?></th>
                    <td><input type="checkbox" name="<?=$nomes_keys[$i]?>" <?php if (get_option($nomes_keys[$i]) == "on") echo 'checked="checked"'; ?>/></td>
                </tr>
            <?php } ?>

        </table>        
        <?php submit_button(); ?>
    </form>
    </div>
<?php } 
    

add_filter( 'wpcf7_mail_components', 'wpcf7_group_mail_components', 10, 2 );


function wpcf7_group_mail_components( $components, $wpcf7 ) {
    
    $nomes = get_c_roles();
    $nomes_keys = array_keys($nomes);
    $values = '';

    for($i = 0; $i < sizeof($nomes_keys); $i++){
        if (get_option($nomes_keys[$i]) == "on"){
        $group_users = get_users('blog_id=1&orderby=nicename&role='.$nomes_keys[$i]);
        foreach ($group_users as $user) {
                $values .= ', ' . $user->user_email ;
        };
    }}

    if (get_option("mode") == "cc" ){ $exploded = explode(',', $values, 2);$components['additional_headers'] .= "Cc: ".$exploded[1];}
    else if (get_option("mode") == "cco"){ $exploded = explode(',', $values, 2);$components['additional_headers'] .= "Cco: ".$exploded[1]; }
    else{ $components['recipient'] .= $values;}


    return $components;
}?>