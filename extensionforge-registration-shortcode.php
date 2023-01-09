<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://extensionforge.com
 * @since             1.0.0
 * @package           Extensionforge_Registration_Shortcode
 *
 * @wordpress-plugin
 * Plugin Name:       Registration Form Shortcode
 * Plugin URI:        https://extensionforge.com
 * Description:       Shortcode to Display Registration Form on pages and posts. Displays Wordpress RegForm and Buddypress XProfile
 * Version:           1.0.0
 * Author:            Steve Kraft & Peter Mertzlin
 * Author URI:        https://extensionforge.com
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       extensionforge-registration-shortcode
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define( 'EXTENSIONFORGE_REGISTRATION_SHORTCODE_VERSION', '1.0.0' );

define('EXTENSIONFORGE_REGISTRATION_INCLUDE_URL', plugin_dir_url(__FILE__).'includes/');

add_action( 'template_redirect', 'redirect_to_extensionforge_registration' );
function redirect_to_extensionforge_registration() {
    if ( is_page('registrierung') && ! is_user_logged_in() ) {
      wp_redirect( '/registrierung/', 301 ); 
      exit;
    }
}


function bpdev_ua_check_username() {
    
    if ( ! empty( $_POST["user_name"] ) ) {  $user_name = $_POST["user_name"]; } else {  $user_name ="";}
    if ( ! empty( $_POST["user_email"] ) ) {  $user_email = $_POST["user_email"]; } else {  $user_email ="";}
    
    $errors = extf_bpdev_validate_username($user_name,$user_email);


    if ( count( $errors->get_error_messages() ) <1) {
                $msg = array( "code" => "success", "message" => __( "Dieser Benutzername ist verfügbar.", "buddypress" ) );
            } else {

        $error_msg = "";
         $messages = $errors->get_error_messages();
         foreach ($messages as $message) {
            $error_msg.= $message."<br />";
        }
            $msg = array( "code" => "error", "message" => __( $error_msg, "buddypress" ) );
        }
   
    wp_send_json( $msg );
}


add_action('wp_ajax_nopriv_bpdev_ua_check_username', 'bpdev_ua_check_username');
add_action('wp_ajax_bpdev_ua_check_username', 'bpdev_ua_check_username');

/* helper function to check the username is valid or not, thanks to @apeatling, taken from bp-core/bp-core-signup.php and modified for chacking only the username
 * original:bp_core_validate_user_signup()
 *
 **/

function extf_bpdev_validate_username( $user_name, $user_email ) {
    
    global $wpdb;
    $table_users = $wpdb->prefix . 'users';
    $table_signups = $wpdb->prefix . 'signups';
    $errors = new WP_Error();
    $maybe = array();

        //check for the username in the signups table
       
        $usernameerror = false;
       
        // check email
        $mailerror = false;

        // check waiting for activation

        $emailtesterx = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table_signups where user_email = '%s'", $user_email ) );
            
        if ( ! empty( $emailtesterx ) && ($mailerror==false)) {
             $errors->add( 'user_name', __( 'Diese Emailadresse ist bereits registriert, aber noch nicht aktiviert. Bitte prüfen Sie Ihr Email-Postfach.', 'buddypress' ) );  $mailerror = true;
        }

        $emailtester = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table_users where user_email = '%s'", $user_email ) );
            
        if ( ! empty( $emailtester ) && ($mailerror==false) ) {
             $errors->add( 'user_name', __( 'Diese Emailadresse ist bereits registriert.', 'buddypress' ) );  $mailerror = true;
        }

        

        $user = $wpdb->get_row($wpdb->prepare("SELECT * from $table_signups where user_login = '%s'", $user_name));

        if ( ! empty( $user ) && ($mailerror==false)) {
             $errors->add( 'user_name', __( 'Dieser Benutzername ist bereits vergeben. Bitte wählen Sie einen anderen.', 'buddypress' ) ); $usernameerror = true;
        }

        $userx = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table_users where user_login = '%s'", $user_name ) );
            
        if ( ! empty( $userx ) && ($usernameerror==false) && ($mailerror==false)) {
             $errors->add( 'user_name', __( 'Dieser Benutzername ist bereits vergeben. Bitte wählen Sie einen anderen.', 'buddypress' ) ); 
        }

        if ( empty( $user_name ) ) {
            // must not be empty.
            $errors->add( 'user_name', __( 'Bitte verwenden Sie einen gültigen Benutzernamen.', 'buddypress' ) );
        }

        if ( function_exists( 'buddypress' ) ) {
            $user_name = preg_replace( '/\s+/', '', $user_name );

        }

        // check blacklist.
        $illegal_names = get_site_option( 'illegal_names' );
        if ( in_array( $user_name, (array) $illegal_names ) ) {
            $errors->add( 'user_name', __( 'Dieser Benutzername ist nicht erlaubt.', 'buddypress' ) );
        }

        // see if passed validity check.
        if ( ! validate_username( $user_name ) ) {
            $errors->add( 'user_name', __( 'Der Benutzername darf nur Kleinbuchstaben und Zahlen enthalten.', 'buddypress' ) );
        }

        if ( strlen( $user_name ) < 4 ) {
            $errors->add( 'user_name', __( 'Der Benutzername muss mindestens 4 Zeichen lang sein.', 'buddypress' ) );
        } elseif ( mb_strlen( $user_name ) > 20 ) {
            $errors->add( 'user_login_too_long', __( 'Der Benutzername darf nicht länger als 20 Zeichen sein.', 'buddypress' ) );
        }

        if ( strpos( ' ' . $user_name, '_' ) != false ) {
            $errors->add( 'user_name', __( 'Sorry, usernames may not contain the character "_"!', 'buddypress' ) );
        }

        /* Is the user_name all numeric? */
        $match = array();
        preg_match( '/[0-9]*/', $user_name, $match );

        if ( $match[0] == $user_name ) {
            $errors->add( 'user_name', __( 'Sorry, usernames must have letters too!', 'buddypress' ) );
        }

        /**
         * Filters the list of blacklisted usernames.
         *
         * @param array $usernames Array of blacklisted usernames.
         */
        $illegal_logins = (array) apply_filters( 'illegal_user_logins', array() );

        if ( in_array( strtolower( $user_name ), array_map( 'strtolower', $illegal_logins ) ) ) {
            $errors->add( 'invalid_username', __( 'Sorry, that username is not allowed.', 'buddypress' ) );
        }

    
    return $errors;
}





//add front end css and js
function srf_slider_trigger(){
   
    wp_register_script('srf_caro_css_and_js', EXTENSIONFORGE_REGISTRATION_INCLUDE_URL."font-script.js" );
    wp_enqueue_script('srf_caro_css_and_js');
    wp_enqueue_script('password-strength-meter');
}
add_action('wp_footer','srf_slider_trigger');

// function to registration Shortcode
function extensionforge_registration_shortcode( $atts ) {
    global $wpdb, $status; $error_msg; 
    $firstname="";
    $lastname="";
    $username="";
    $email="";
    $kdnr="";
    $code = "";
    $user_id = 0;
    $interests = array();
    $sinterests = "";
    
    //if looged in rediret to home page
    if ( is_user_logged_in() ) { 
        wp_redirect( get_option('home') );// redirect to home page
        exit;
    }

    if(isset( $_POST['com_submit'])){
        $prenom=sanitize_text_field( $_REQUEST['prenom'] );
        $firstname=$_REQUEST['com_firstname'] ;
        $lastname=$_REQUEST['com_lastname'];
        $username = sanitize_text_field(  $_REQUEST['com_username'] );
        $email = sanitize_text_field(  $_REQUEST['com_email']  );
        $kdnr = sanitize_text_field( $_REQUEST['com_kdnr']);
        $plz = sanitize_text_field( $_REQUEST['com_plz']);
        $password = sanitize_text_field( $_REQUEST['com_password']);
        if(isset($_REQUEST['nldropdown'])){$interests = $_REQUEST['nldropdown'];  } 

        // check if username ok?

        $errors = extf_bpdev_validate_username($username,$email);

        //var_dump($errors);
        //echo "ERRORS: ".count( $errors->get_error_messages())."<br />";
       
        if ( count( $errors->get_error_messages() ) <1) {

        $table_name = $wpdb->prefix . 'signups';
        $user_meta = $wpdb->prefix . 'usermeta';
        $jetzt = wp_date('Y-m-d H:i:s');
        
        $token = wp_generate_password( 32, false );

        $nicename = strtolower($firstname."-".$lastname);
        $displayname = $firstname." ".$lastname;

         $default_newuser = array(
        'user_pass' =>  $password,
        'user_login' => $username,
        'user_email' => $email,
        'first_name' => $firstname,
        'last_name' => $lastname,
        'user_nicename' => $nicename,
        'display_name' => $displayname,
        'user_registered' => $jetzt
        );


        if(count($interests)>0){
        $sinterests = implode("," , $interests); }

        $status = wp_insert_user($default_newuser);
        $user_id = $status;
        $fieldids = "10,13,1,2,15,16";

        $smeta = array("field_10" => $prenom, "field_10_visibility" => "adminsonly",
            "field_13" => $firstname, "field_13_visibility" => "adminsonly",
            "field_1" => $lastname,
            "field_2" => $sinterests, "field_2_visibility" => "adminsonly",
            "field_15" => $kdnr, "field_15_visibility" => "adminsonly",
            "field_16" => $plz, "field_16_visibility" => "adminsonly",
            "profile_field_ids" => $fieldids,
            "password" => $password,
            "sent_date" => $jetzt,
            "count_sent" => 1    
    );
       


        $test=$wpdb->insert($table_name, array(
                'user_login' => $username,
                'user_email' => $email,
                'registered' => $jetzt,
                'activated' => '0000-00-00 00:00:00',
                'active' => 0,
                'activation_key' => $token,
                'meta' => serialize($smeta)
            ), array( '%s', '%s', '%s', '%s', '%d', '%s', '%s') );
        

    if ( $user_id && !is_wp_error( $user_id ) ) {
        $wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->users} SET user_status = 2 WHERE ID = %d", $user_id ) );
        

        delete_user_option( $user_id, 'capabilities' );
        delete_user_option( $user_id, 'user_level'   );

        xprofile_set_field_data("Anrede", $user_id, $prenom);
        xprofile_set_field_data("Vorname", $user_id, $firstname);
        xprofile_set_field_data("Nachname", $user_id, $lastname);
        xprofile_set_field_data("Kundennummer", $user_id, $kdnr);
        xprofile_set_field_data("Postleitzahl", $user_id, $plz);

        $table_name = $wpdb->prefix . 'bp_xprofile_data';
        
        $test = $wpdb->insert($table_name, array(
                'field_id' => 2,
                'user_id' => $user_id,
                'value' => serialize($interests),
                'last_updated' => $jetzt
                 ), array( '%d', '%d', '%s', '%s') );
       
      add_user_meta( $user_id, 'activation_key', $token);
      //$salutation = trim($prenom." ".$firstname." ".$lastname); 

       //wp_mail( $email, 'SUBJECT', 'Activation link : ' . $activation_link );
        bp_core_signup_send_validation_email($user_id,$email,$token,$username);  

        
            $success= __('<div class="vnr_register_success">Vielen Dank für Ihre kostenlose Registrierung im Computerwissen-Club.<br />Ihre Anmeldedaten sind bei uns angekommen.<br /><br />In Kürze erhalten Sie eine E-Mail, in der wir Ihre Angaben zur Registrierung noch einmal für Sie zusammengefasst haben.<br />Der Betreff der E-Mail lautet:<br /> >>> Wichtig: Bitte noch bestätigen: Ihre Registrierung im Computerwissen-Club <<< <br /><br />Damit wir sicherstellen können, dass Sie der Inhaber der angegebenen Adresse sind, müssen Sie diese bitte noch bestätigen mit einem Klick auf den Bestätigungslink.<br /><br />Dann ist Ihre Anmeldung im Club abgeschlossen.<br /><br />Wir freuen uns, Sie bald als neues Club-Mitglied begrüßen zu dürfen.<br />Ihr Team vom Computerwissen-Club</div>',""); 
        }  
    } else {
         $error_msg = "";
         $messages = $errors->get_error_messages();
         foreach ($messages as $message) {
            $error_msg.= $message."<br />";
         }
        // error! no valid username or email or something
      
      
    }
}

$output ='<script>function checkextfformchecker() { test = document.getElementById("pass-strength-result").innerHTML; if (test=="Stark"){
    document.getElementById("extf_pwhinweis").style.display = "none"; return true;} else {document.getElementById("extf_pwhinweis").style.display = "block"; return false;}

    } function extf_copyvalue(){ document.getElementById("password2").value=document.getElementById("password1").value;checkextfformchecker();}</script><div id="buddypress">
    <div class="page" id="register-page">
        <div class="register-section" id="basic-details-section">';
            if(isset($error_msg)) { $output .='<div style="display:block!important;width:100%; text-align:left;" class="vnr_useremailerror">Eingabe-Fehler! Bitte ändern Sie Ihre Eingabe!</div>'; }  
            if(isset($success)) { $output .='<div class="success">'.$success.'</div>'; }  else {
        
$output .='<form action="" name="signup_form" id="signup_form" onsubmit="return checkextfformchecker()" class="standard-form" method="post" enctype="multipart/form-data">
                <label>Anrede</label> 
                <select id="prenom" name="prenom" class="input">
                    <option value="Keine">auswählen</option>
                    <option value="Herr">Herr</option>
                    <option value="Frau">Frau</option>
                    <option value="Keine">Keine</option>
                </select>
                <label>Vorname</label> 
                <input id="com_firstname" name="com_firstname" type="text" class="input" value='.$firstname.' > 
                <label>Nachname</label>  
                <input id="com_lastname" name="com_lastname" type="text" class="input" value='.$lastname.' >
                <label>Benutzername *</label> 
                <input id="com_username" name="com_username" pattern="[a-z0-9äöüß]*" type="text" class="input" required value='.$username.' >
                <div class="vnr_only_numbersletters">(Nur kleine Buchstaben und Zahlen)</div>
                <div id="vnr_useremailerror"   ';
    if(isset($error_msg)) { $output .=' style="display:block; width:100%; text-align:left;">'.$error_msg; }  else { $output .='>'; }
                $output .='</div>
                <label>E-Mail *</label>
                <input id="com_email" name="com_email" type="email" class="input" required value='.$email.' >
                <div class="vnr_emailbestaetigung">(An diese E-Mail schicken wir Ihnen die Anmeldebestätigung)</div>
         
                <label>Passwort *</label>
                <input id="password1" name="com_password" type="password" oninput="extf_copyvalue()" required class="input" />
                <div id="extf_pwhinweis" class="extf_passworthinweisstark">Bitte verwenden Sie ein starkes Passwort!</div>
                <div id="pass-strength-result" style="width:208px;"></div>
        
              
                <input id="password2" name="c_password" type="password"  style="display:none" class="input" />
                <div class="vnr_pwanforderung">(Min. 8 Zeichen, bitte Groß- & Kleinbuchstaben, Zahlen und Sonderzeichen verwenden)
Sonderzeichen Bsp.: ( ) = < > ? _ ! @ # $ % ^ & * </div>
                <label>Kundennummer</label>
                <input id="com_kdnr" name="com_kdnr" type="text" class="input" />
                <label>Postleitzahl *</label>
                <input id="com_plz" name="com_plz" pattern="[0-9]*" type="text" size="5" class="input" required />
                <label>Interessen</label>
                <div>
                    <div class="checkbox">
                        <label class="checkbox">
                            <input id="windows" type="checkbox" name="nldropdown[]" value="Windows"> Windows
                        </label>
                    </div>
                    <div class="checkbox">
                        <label class="checkbox">
                            <input id="linux" type="checkbox" name="nldropdown[]" value="Linux / Open Source"> Linux &amp; Open Source
                        </label>
                    </div>
                    <div class="checkbox">
                        <label class="checkbox">
                            <input id="foto" type="checkbox" name="nldropdown[]" value="Foto / Bildbearbeitung"> Foto &amp; Bildbearbeitung
                        </label>
                    </div>
                    <div class="checkbox">
                        <label class="checkbox">
                            <input id="internet" type="checkbox" name="nldropdown[]" value="Internet / Mobile"> Internet &amp; Mobile
                        </label>
                    </div>
                    <div class="checkbox">
                        <label class="checkbox">
                            <input id="sicherheit" type="checkbox" name="nldropdown[]" value="Sicherheit / Virenschutz"> Sicherheit &amp; Virenschutz
                        </label>
                    </div>
                    <div class="checkbox">
                        <label class="checkbox">
                            <input id="computer" type="checkbox" name="nldropdown[]" value="Computer / Hardware"> Computer &amp; Hardware
                        </label>
                    </div>
                    <div class="checkbox">
                        <label class="checkbox">
                            <input id="office" type="checkbox" name="nldropdown[]" value="Office"> Office
                        </label>
                    </div>
                </div>
                <div class="form-group">
                    <label for="agb" class="control-label">AGB *</label> 
                    <div>
                        <label class="checkbox-inline">
                            <input type="checkbox" name="agb" value="ja" required>Ja, ich habe die&nbsp;<a href="https://club.computerwissen.de/agb" target="_blank" class="agbcheckboxlink">AGB</a>&nbsp;gelesen und akzeptiert.
                        </label>
                    </div>
                </div>
            <div class="clear"></div>
            <input type="submit" name="com_submit" class="produktboxbutton"  value="Jetzt registrieren"/> 
            </form>';
        }

            $output.= '
        </div>
    </div>
</div>';

return $output;  
}

//add registration shortcoode
add_shortcode( 'extensionforge-registration-form', 'extensionforge_registration_shortcode' );



/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-extensionforge-registration-shortcode-activator.php
 */
function activate_extensionforge_registration_shortcode() {
    require_once plugin_dir_path( __FILE__ ) . 'includes/class-extensionforge-registration-shortcode-activator.php';
    Extensionforge_Registration_Shortcode_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-extensionforge-registration-shortcode-deactivator.php
 */
function deactivate_extensionforge_registration_shortcode() {
    require_once plugin_dir_path( __FILE__ ) . 'includes/class-extensionforge-registration-shortcode-deactivator.php';
    Extensionforge_Registration_Shortcode_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_extensionforge_registration_shortcode' );
register_deactivation_hook( __FILE__, 'deactivate_extensionforge_registration_shortcode' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-extensionforge-registration-shortcode.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */




function run_extensionforge_registration_shortcode() {

    $plugin = new Extensionforge_Registration_Shortcode();
    $plugin->run();

}
run_extensionforge_registration_shortcode();
