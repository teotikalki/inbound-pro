<?php

/**
*   FUNCTION TO SEND SUBSCRIBER TO MAILCHIMP 
*/

function inboundnow_mailchimp_add_subscriber($target_list , $subscriber)
{
	$api_key = get_option( 'inboundnow_mailchimp_api_key' , 0 );				
	
	if (!$api_key) {
		return;
	}
	
	$MailChimp = new MailChimp($api_key);

	/* get double optin setting */
	$mailchimp_double_optin =  get_option('inboundnow_mailchimp_double_optin' , 'true' );
	
	/* get ready for groupings */
	if (isset($subscriber['groupings']))
	{
	}
	 

	/* Create Merge Varss */
	$merge_vars = inbound_mailchimp_create_merge_vars( $MailChimp , $subscriber , $target_list );
	
	$args = array(
		'id'                => $target_list,
		'email'             => array('email'=>$subscriber['wpleads_email_address']),
		'merge_vars'        => $merge_vars,
		'double_optin'      => $mailchimp_double_optin,
		'update_existing'   => true,
		'replace_interests' => false,
		'send_welcome'      => false,
	);
	
	$args = apply_filters('inboundnow_mailchimp_args' , $args , $subscriber );
	
	
	$result = $MailChimp->call('lists/subscribe', $args );	
	
	$debug = 0;
	if ($debug==1)
	{
		var_dump($args);
		var_dump($result);
		exit;
	}
}

/**
 *   Create merge vars
 */
function inbound_mailchimp_create_merge_vars( $MailChimp , $data , $target_list ) {
	
	/* Check if merge vals have already been generated by this list in the last hour */
	$added =  get_transient( 'inbound_mailchimp_update_merge_vals_' . $target_list );
	
	unset( $data['wpleads_email_address'] );
	

	foreach ($data as $key => $value ) {
		
		/* ignore hidden inbound related form fields */
		if ( strstr( $key , 'inbound_' ) ) {
			continue;
		}
		
		/* Generate merge fields */
		$field = inbound_mailchimp_get_field_type( $key );
		$merge_vars[ $field['tag'] ] = $value;
		
		/* Skip api call to add merge fields if recently added or if native merge field  */
		if ($added || $field['tag'] =='FNAME' || $field['tag'] == 'LNAME') {
			continue;
		}
		
		$args = array(
			'id' => $target_list,
			'tag' => $field['tag'],
			'name' => $field['name'],
			'options' => array(
				'field_type' => $field['field_type'],
				'public' => false
				)
		);
		
		$result = $MailChimp->call('/lists/merge-var-add', $args );
		
	}
	

	set_transient( 'inbound_mailchimp_update_merge_vals_' . $target_list , true , 60 * 60 );
	
	return $merge_vars;
}

function inbound_mailchimp_get_field_type( $key ) {

	switch( $key ) {
		case 'wpleads_full_name' :
			return 	array(
						'field_type' => 'text',
						'tag' => 'FNAME',
						'name' => 'First Name'
					);
			break;
		case 'wpleads_first_name' :
			return 	array(
						'field_type' => 'text',
						'tag' => 'FNAME',
						'name' => 'First Name'
					);
			break;
		case 'wpleads_last_name' :
			return 	array(
						'field_type' => 'text',
						'tag' => 'LNAME',
						'name' => 'Last Name'
					);
			break;
		case 'wpleads_shipping_zip_code' :
			return 	array(
						'field_type' => 'zip',
						'tag' => 'ZIP',
						'name' => 'Postal Code'
					);
			break;
		case 'wpleads_billing_zip_code' :
			return 	array(
						'field_type' => 'zip',
						'tag' => 'ZIP',
						'name' => 'Postal Code'
					);
			break;
		case 'wpleads_zip':
			return 	array(
						'field_type' => 'zip',
						'tag' => 'ZIP',
						'name' => 'Postal Code'
					);
			break;
		case 'wpleads_mobile_phone':
			return 	array(
						'field_type' => 'phone',
						'tag' => 'PHONE',
						'name' => 'Phone'
					);
			break;
		case 'wpleads_work_phone':
			return 	array(
						'field_type' => 'phone',
						'tag' => 'PHONE',
						'name' => 'Phone'
					);
			break;
		case 'wpleads_shipping_address_1':
			return 	array(
						'field_type' => 'address',
						'tag' => 'ADDRESS',
						'name' => 'Address'
					);
			break;
		case 'wpleads_billing_address_1':
			return 	array(
						'field_type' => 'address',
						'tag' => 'ADDRESS',
						'name' => 'Address'
					);
			break;
		case 'wpleads_notes':
			return 	array(
						'field_type' => 'address',
						'tag' => 'ADDRESS',
						'name' => 'Address'
					);
			break;
		case 'wpleads_country_code':
			return 	array(
						'field_type' => 'text',
						'tag' => 'CNTRYCODE',
						'name' => 'Country Code'
					);
			break;
		case 'wpleads_notes':
			return 	array(
						'field_type' => 'text',
						'tag' => 'NOTES',
						'name' => 'Notes'
					);
			break;
		default:
			$key = str_replace('wpleads' , '' , $key);
			$key = str_replace('_' , '' , $key);
			$key = strtoupper($key);
			$key = substr( $key , 0 , 10 );
			return 	array(
						'field_type' => 'text',
						'tag' => $key,
						'name' => $key
					);
			break;
		
	}

}

/* ADD SUBSCRIBER ON LANDING PAGE CONVERSION / CTA CONVERSION */

//add_action('inbound_store_lead_post','inboundnow_mailchimp_landing_page_integratation');
function inboundnow_mailchimp_landing_page_integratation($data)
{		
	if (get_post_meta($data['lp_id'],'inboundnow-mailchimp-mailchimp_integration',true))
	{
		$target_list = get_post_meta($data['lp_id'],'inboundnow-mailchimp-mailchimp_list',true);
		
		inboundnow_mailchimp_add_subscriber( $target_list , $data );		
	}				
}



/* ADD SUBSCRIBER ON INBOUNDNOW FORM SUBMISSION */
add_action('inboundnow_form_submit_actions','inboundnow_mailchimp_inboundnow_form_integratation' , 10 , 2 );
function inboundnow_mailchimp_inboundnow_form_integratation($form_post_data , $form_meta_data )
{		

	$subscriber['wpleads_email_address'] = (isset($form_post_data['wpleads_email_address'])) ? $form_post_data['wpleads_email_address'] : '';

	$subscriber['wpleads_first_name'] = (isset($form_post_data['wpleads_first_name'])) ? $form_post_data['wpleads_first_name'] : '';
	
	$subscriber['wpleads_last_name'] = (isset($form_post_data['wpleads_last_name'])) ? $form_post_data['wpleads_last_name'] : "";

	$subscriber = array_merge( $subscriber , $form_post_data );
	
	if (!$subscriber['wpleads_last_name']) {
		$first_name_array = explode(' ' , $subscriber['wpleads_first_name'] );
		if ( count( $first_name_array ) > 1 ) {
			$subscriber['wpleads_first_name'] = $first_name_array[0];
			$subscriber['wpleads_last_name'] = $first_name_array[1];
		}
	}
	
	$form_settings = $form_meta_data['inbound_form_values'][0];
	parse_str($form_settings, $form_settings);
	
	if ($form_settings['inbound_shortcode_mailchimp_enable']=='on')
	{		
		$target_list = $form_settings['inbound_shortcode_mailchimp_list_id'];
		inboundnow_mailchimp_add_subscriber($target_list , $subscriber);	
	}
}