<?php
/**
 * Include for WordPress Importer to be able to fully import users from PivotX from generated WXR file.
 * Put this file in the same folder as wordpress-importer.php
 * Change the same file around line 355 to:
            } else if ( $create_users ) {
                if ( ! empty($_POST['user_new'][$i]) ) {
                    // add extra code for PivotX user import
                    include dirname( __FILE__ ) . '/pivx_wp_import_users.php';
                    $user_id = wp_create_user( $_POST['user_new'][$i], wp_generate_password() );
 */
// Check if there are only users in xml file
if (count($this->posts) == 0 && count($this->categories) == 0 && count($this->tags) == 0 && count($this->terms) == 0) { 
    //echo 'PivotX include for import of users started<br/>';
    //echo print_r($_POST) . '<br/>';
    //echo print_r($this->authors[$_POST['user_new'][$i]]) . '<br/>';
    $newuser = $this->authors[$_POST['user_new'][$i]];
    // using ID sets Wordpress importer in update mode -- so disregard for now
    $user_new_data = array(
        //'ID' => $newuser['author_id'],
        'user_login' => $newuser['author_login'],
        'user_pass' => wp_generate_password(),
        'user_email' => $newuser['author_email'],
        'display_name' => $newuser['author_display_name'],
        'first_name' => $newuser['author_first_name'],
        'last_name' => $newuser['author_last_name'],
    );
    $newuser_id = wp_insert_user( $user_new_data );
    if ( is_wp_error( $newuser_id ) ) {
        echo 'PIVX Creation failed ' . $newuser['author_login'] . '<br/>';
    } else {
        echo 'PIVX User created ' . $newuser['author_login'] . '<br/>';
        echo 'PIVX This user will be reported following by WP importer as already existing<br/>';
    }
}
// continue with regular code in wordpress-importer.php ==> will result in error messages that all users are already created