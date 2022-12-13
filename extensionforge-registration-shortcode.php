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
       // wp_redirect( get_option('home') );// redirect to home page
       // exit;
    }

    if(isset( $_POST['com_submit'])){
        $prenom=sanitize_text_field( $_REQUEST['prenom'] );
        $firstname=sanitize_text_field( $_REQUEST['com_firstname'] );
        $lastname=sanitize_text_field( $_REQUEST['com_lastname']);
        $username = sanitize_text_field(  $_REQUEST['com_username'] );
        $email = sanitize_text_field(  $_REQUEST['com_email']  );
        $kdnr = sanitize_text_field( $_REQUEST['com_kdnr']);
        $plz = sanitize_text_field( $_REQUEST['com_plz']);
        $password = sanitize_text_field( $_REQUEST['com_password']);
        if(isset($_REQUEST['nldropdown'])){$interests = $_REQUEST['nldropdown'];  } 


        $table_name = $wpdb->prefix . 'signups';
        $jetzt = wp_date('Y-m-d H:i:s');
        
        $token = wp_generate_password( 32, false );

         $default_newuser = array(
        'user_pass' =>  $password,
        'user_login' => $username,
        'user_email' => $email,
        'first_name' => $firstname,
        'last_name' => $lastname,
        'user_registered' => $jetzt
        );

        if(count($interests)>0){
        $sinterests = implode("," , $interests); }

        $user_id = wp_insert_user($default_newuser);
        $status = $user_id;
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

        $success ="";
        $error_msg="";
       
        if (is_wp_error($status))  {
             $error_msg = __('Username or Email already registered. Please try another one.',""); 
        } 
        else{
            $user_id=$status;
            
            
            $success= __('<div class="vnr_register_success">Vielen Dank für Ihre kostenlose Registrierung im Computerwissen-Club.<br />Ihre Anmeldedaten sind bei uns angekommen.<br /><br />In Kürze erhalten Sie eine E-Mail, in der wir Ihre Angaben zur Registrierung noch einmal für Sie zusammengefasst haben.<br />Der Betreff der E-Mail lautet:<br /> >>> Wichtig: Bitte noch bestätigen: Ihre Registrierung im Computerwissen-Club <<< <br /><br />Damit wir sicherstellen können, dass Sie der Inhaber der angegebenen Adresse sind, müssen Sie diese bitte noch bestätigen mit einem Klick auf den Bestätigungslink.<br /><br />Dann ist Ihre Anmeldung im Club abgeschlossen.<br /><br />Wir freuen uns, Sie bald als neues Club-Mitglied begrüßen zu dürfen.<br />Ihr Team vom Computerwissen-Club</div>',""); 
            
        }  
    }
}

$output ='<div id="buddypress">
    <div class="page" id="register-page">
        <div class="register-section" id="basic-details-section">';
            if(isset($error_msg)) { $output .='<div class="error">'.$error_msg.'</div>'; }  
            if(isset($success)) { $output .='<div class="success">'.$success.'</div>'; }  else {
        
$output .='<form action="" name="signup_form" id="signup_form" class="standard-form" method="post" enctype="multipart/form-data">
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
                <input id="com_username" name="com_username" pattern="[a-zA-Z0-9äÄöÖüÜß]*" type="text" class="input" required value='.$username.' >
                <div class="vnr_only_numbersletters">(Nur Buchstaben und Zahlen)</div>
                <label>E-Mail *</label>
                <input id="com_email" name="com_email" type="email" class="input" required value='.$email.' >
                <div class="vnr_emailbestaetigung">(An diese E-Mail schicken wir Ihnen die Anmeldebestätigung)</div>
         
                <label>Passwort *</label>
                <input id="password1" name="com_password" type="password" required class="input" />
            
                <div id="pass-strength-result" style="width:208px;"></div>
        
                <label>Passwort Wiederholung</label>
                <input id="password2" name="c_password" type="password" class="input" />
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
                            <input type="checkbox" name="agb" value="ja" required>
                            <a href="https://club.computerwissen.de/agb" target="_blank" class="agbcheckboxlink">Ja, ich habe die AGBs gelesen und akzeptiert.</a>
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
