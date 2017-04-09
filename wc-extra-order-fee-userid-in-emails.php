<?php
/*
  Plugin Name: Extra WooCommerce per-customer order fee plugin
  Plugin URI: mechanical-pie.com
  Description: Set extra order fee per-customer in WooCommerce
  Version: 1.0
  Author: mechanical-pie 
  
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly  

/**
 * Add new fields above 'Update' button.
 *
 * @param WP_User $user User object.
 */

add_action( 'show_user_profile', 'abaka_extra_user_profile_fields', 200 );
add_action( 'edit_user_profile', 'abaka_extra_user_profile_fields', 200 );
//show new field on back-end
function abaka_extra_user_profile_fields( $user ) {
?>
  <h3><?php _e("Extra profile information", "blank"); ?></h3>
  <table class="form-table">
    <tr>
      <th><label for="extra_order_fee"><?php _e("Kleine orderkosten"); ?></label></th>
      <td>
      <?php if(current_user_can('administrator')):?>
        <input type="text" name="extra_order_fee" id="extra_order_fee" class="regular-text" 
            value="<?php echo esc_attr( get_the_author_meta( 'extra_order_fee', $user->ID ) ); ?>" /><br />
        <span class="description"><?php _e("Please enter extra order fee for customer."); ?></span>
		<?php else:?>
			<span><?php echo esc_attr( get_the_author_meta( 'extra_order_fee', $user->ID ) ); ?></span>
		<?php endif;?>        
    </td>
    </tr>
  </table>
<?php
}
//validate new field
add_action( 'user_profile_update_errors', 'validate_extra' );
function validate_extra(&$errors, $update = null, &$user  = null)
{
    if (!empty($_POST['extra_order_fee']) && !is_numeric($_POST['extra_order_fee']))
    {
        $errors->add('extra_order_fee', "<strong>ERROR</strong>: Extra order fee must be a number");
    }
}

//save new field
add_action( 'personal_options_update', 'abaka_save_extra_user_profile_fields' );
add_action( 'edit_user_profile_update', 'abaka_save_extra_user_profile_fields' );

function abaka_save_extra_user_profile_fields( $user_id ) {
  $saved = false;
  if ( current_user_can( 'edit_user', $user_id ) ) {

    update_user_meta( $user_id, 'extra_order_fee', $_POST['extra_order_fee'] );
    $saved = true;
  }
  return true;
}


//add custom woocommerce order fee based on extra_order_fee field
add_action( 'woocommerce_cart_calculate_fees','woocommerce_custom_surcharge' );
function woocommerce_custom_surcharge() {
  global $woocommerce;
  	//check if admin
	if ( is_admin() && ! defined( 'DOING_AJAX' ) )
		return;
	//get user id and set up variables for user meta query
	$user_id = get_current_user_id();
	$key = 'extra_order_fee';
	$single = true;
	//get fee 
	$fee = get_user_meta( $user_id, $key, $single );
	//add fee
	if ($fee!=0) {
		$woocommerce->cart->add_fee( 'Kleine orderkosten', $fee, true, '' );
	}	
}


?>

<?php
//display user's extra order fee in their My Account Page
add_action( 'woocommerce_edit_account_form', 'abaka_woocommerce_edit_account_form' );
function abaka_woocommerce_edit_account_form() {
  $user_id = get_current_user_id();
  $user = get_userdata( $user_id );
  if ( !$user )
    return;
  $extra_order_fee = get_user_meta( $user_id, 'extra_order_fee', true );

  ?>
  <fieldset>
    <legend>Extra information</legend>
    <p class="form-row form-row-thirds">
		Kleine orderkosten: € <?php echo $extra_order_fee;?>
    </p>
    <p class="form-row form-row-thirds">
		Klantnummer: <?php echo $user_id;?>
    </p>    
  </fieldset>
  <?php
}

add_action( 'woocommerce_email_after_order_table', 'wc_add_user_id_to_emails', 15, 2 );
function wc_add_user_id_to_emails( $order, $is_admin_email ) {
    $order_id = $order->id;
    $user_id = $order->user_id;
    echo "<br />";
    echo "<p><strong>Klantnummer: </strong> $user_id </p>";
}


/**
 * Display field value on the order edit page
 */
add_action( 'woocommerce_admin_order_data_after_billing_address', 'abaka_checkout_field_display_admin_order_meta', 10, 1 );
function abaka_checkout_field_display_admin_order_meta($order){
    $order_id = $order->id;
    $user_id = $order->user_id;	
    echo '<p><strong>'.__('Klantnummer').':</strong> <br/>' . $user_id . '</p>';
}

// Extra Billing field for tracking number in Admin Order Meta
add_filter( 'woocommerce_admin_shipping_fields', 'abaka_extra_admin_shipping_fields' );
function abaka_extra_admin_shipping_fields( $fields ) {
	global $theorder;
	$fields['tracking_number'] = array(
		'label' 	=> __( 'Tracking number', 'woocommerce' ),
		'value' 	=> get_post_meta( $theorder->id, '_shipping_tracking_number', true ),
	);

	return $fields;
}
