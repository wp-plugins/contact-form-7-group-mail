<?php
/*
Plugin Name: Contact Form 7 Group Mail
Plugin URI: http://www.u3b.com.br/contact-form-7-group-mail
Description: Send 'Contact Form 7' mails to all users of any group (superadmins, admins, editors, authors, contributors, subscribers).
Version: 0.8
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
    
add_filter( 'wpcf7_mail_components', 'wpcf7_group_mail_components', 10, 2 );


function wpcf7_group_mail_components( $components, $wpcf7 ) {
    $nomes = array("superadmin", "administrator", "editor", "author", "contributor", "subscriber");

    for($i = 0; $i <= 5; $i++){
        if (get_option($nomes[$i]) == "on"){
        $group_users = get_users('blog_id=1&orderby=nicename&role='.$nomes[$i]);
        foreach ($group_users as $user) {
            $components['recipient'] .= ', ' . $user->user_email ;
        };
    }}

    return $components;
}


add_action('admin_menu', 'wpcf7_group_mail_create_menu');

function wpcf7_group_mail_create_menu() {
    add_submenu_page('wpcf7', 'Group Mail', 'Group Mail','administrator', __FILE__, 'wpcf7_group_mail_settings_page');
    add_action( 'admin_init', 'wpcf7_group_mail_register_settings' );
}


function wpcf7_group_mail_register_settings() {
    register_setting( 'wpcf7_group_mail-settings-group', 'superadmin' );
    register_setting( 'wpcf7_group_mail-settings-group', 'administrator' );
    register_setting( 'wpcf7_group_mail-settings-group', 'editor' );
    register_setting( 'wpcf7_group_mail-settings-group', 'author' );
    register_setting( 'wpcf7_group_mail-settings-group', 'contributor' );
    register_setting( 'wpcf7_group_mail-settings-group', 'subscriber' );
}

function wpcf7_group_mail_settings_page() {
?>
    <div class="wrap">
    <h2>Contact Form 7 Group Mail</h2>

    Select groups to receive 'Contact Form 7' messages
    <form method="post" action="options.php">
			<?php settings_fields( 'wpcf7_group_mail-settings-group' ); ?>
			<table class="form-table">

			<?php $nomes = array("superadmin", "administrator", "editor", "author", "contributor", "subscriber");

				for($i = 0; $i <= 5; $i++){?>
					<tr valign="top">
            	<th scope="row"><?=$nomes[$i]?></th>
            	<td><input type="checkbox" name="<?=$nomes[$i]?>" <?php if (get_option($nomes[$i]) == "on") echo 'checked="checked"'; ?>/></td>
            	</tr>
				<?php }	?>

        </table>        
        <?php submit_button(); ?>
    </form>
    </div>
<?php } ?>