<?php
 
/**
 * Plugin Name: WeightBase Shipping
 * Plugin URI: 
 * Description: Custom Shipping Method for WooCommerce
 * Version: 1.0.0
 * Author: Manish
 * Author URI: http://synapseindia.com
 * License: GPL-3.0+
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 * Domain Path: /lang
 * Text Domain: synapse
 */
 
if ( ! defined( 'WPINC' ) ) {
 
    die;
 
}
// Add css/script for vendor 
function vendor_enqueue() {
    
	wp_enqueue_script( 'my_custom_script',  plugins_url('js/vendor_shipping.js', __FILE__));
	wp_enqueue_style( 'custom_wp_admin_css', plugins_url('css/style.css', __FILE__) );
}
add_action( 'wp_enqueue_scripts', 'vendor_enqueue' );
// Add css/script for admin 
function admin_enqueue() {
    

     wp_enqueue_style( 'custom_wp_admin_css', plugins_url('css/dokan_admin_style.css', __FILE__) );
	 wp_enqueue_script( 'my_custom_script',  plugins_url('js/admin_shipping.js', __FILE__));
	 
}
add_action( 'admin_enqueue_scripts', 'admin_enqueue' );
// calculate per seller custom shipping
function calculate_per_seller_custom( $amount, $price, $products, $destination_country, $destination_state  ) { 
        $amount = 0.0;
        $price = array();
		$pweight=0;
		$warehouse_productweight=0;
        $seller_products = array();
		$baseshippingprice=true;
		$priceliesonweightrange=false;
		$_enable_warehouseflag=false;
        foreach ( $products as $product ) {
            $seller_id                     = get_post_field( 'post_author', $product['product_id'] );
            $seller_products[$seller_id][] = $product;
        }

        if ( $seller_products ) {

            foreach ( $seller_products as $seller_id => $products ) {

                if ( !is_shipping_enabled_for_seller( $seller_id ) ) {
                    continue;
                }

                if ( !has_shipping_enabled_product( $products ) ) {
                    continue;
                }

                $default_shipping_price     = get_user_meta( $seller_id, '_dps_shipping_type_price', true );
                $default_shipping_add_price = get_user_meta( $seller_id, '_dps_additional_product', true );

                $downloadable_count = 0;
				$productprice=0;
				if(!isset($price[ $seller_id ]['totalp_price'])){
					$price[ $seller_id ]['totalp_price']=0;
				}
				$productcnt=0;
                foreach ( $products as $product ) {
				
                   if ( is_product_disable_shipping_weight( $product['product_id'] ) ) {
                        continue;
                   }
				   $_enable_warehouse=get_metadata('post',$product['product_id'],'_enable_warehouse',true);
				   if($_enable_warehouse==1) continue;
					
                    if ( isset( $product['variation_id'] ) ) {
                        $is_virtual      = get_post_meta( $product['variation_id'], '_virtual', true );
                        $is_downloadable = get_post_meta( $product['variation_id'], '_downloadable', true );
                    } else {
                        $is_virtual      = get_post_meta( $product['product_id'], '_virtual', true );
                        $is_downloadable = get_post_meta( $product['product_id'], '_downloadable', true );
                    }

                    if ( ( $is_virtual == 'yes' ) || ( $is_downloadable == 'yes' ) ) {
                        $downloadable_count++;
                        continue;
                    }
					
					if ( get_post_meta( $product['product_id'], '_overwrite_shipping', true ) == 'yes' ) {
						$default_shipping_qty_price = get_post_meta( $product['product_id'], '_additional_qty', true );
						$price[ $seller_id ]['addition_price'][] = get_post_meta( $product['product_id'], '_additional_price', true );
					} else {
						$default_shipping_qty_price = get_user_meta( $seller_id, '_dps_additional_qty', true );
						$price[ $seller_id ]['addition_price'][] = 0;
					}
					
					//This is the base price and will be the starting shipping price for each product
					$price[ $seller_id ]['default'] = $default_shipping_price;
					
					//Every second product of same type will be charged with this price
					if ( $product['quantity'] > 1 ) {
						$price[ $seller_id ]['qty'][] = ( ( $product['quantity'] - 1 ) * $default_shipping_qty_price );
					} else {
						$price[ $seller_id ]['qty'][] = 0;
					}
					$pweight+=$product['data']->get_weight()* $product['quantity'];
					$price[ $seller_id ]['totalp_price'] += $product['line_total'];
					$productcnt++;
					
               }
				
				
				
				 if ( $productcnt > 1 ) {
                    $price[ $seller_id ]['add_product'] =  (int) $default_shipping_add_price * ( $productcnt - ( 1 + $downloadable_count ) );
                } else {
                    $price[ $seller_id ]['add_product'] = 0;
                }

                $dps_country_rates = get_user_meta( $seller_id, '_dps_country_rates', true );
				$dps_weight_rates         = get_user_meta( $seller_id, '_dps_weight_rates', true );
				$dps_threshold_values = get_user_meta( $seller_id, '_dps_threshold_value', true );
				$dps_threshold_value=$dps_threshold_values['threshold'];
				
				if ( isset( $dps_weight_rates[$destination_country] ) ){
					
					$baseshippingprice=false;
					$cnt=0;
					foreach ( $dps_weight_rates[$destination_country]['wf'] as $weight => $weight_rate ){
						 $weightfrom=$dps_weight_rates[$destination_country]['wf'][$cnt];
						 $weightto=$dps_weight_rates[$destination_country]['wto'][$cnt];
						 $weightrate=$dps_weight_rates[$destination_country]['rate'][$cnt];
						//echo $price[$seller_id]['state_rates']=$weightrate;
						
						if($weightfrom<$pweight && $weightto>=$pweight){
							$priceliesonweightrange=true;
							 $price[$seller_id]['state_rates']=$weightrate;
						}
						
						
						 $cnt++;
					}
					
					
				}
				
				if(isset($dps_threshold_value[$destination_country])){
					if($dps_threshold_value[$destination_country]>0){
						if($price[$seller_id]['totalp_price']>$dps_threshold_value[$destination_country]){
							$price[$seller_id]['state_rates']=0;
						}
					}
					
				}
				
				
               
            }
        }

        if ( !empty( $price ) ) {
            foreach ( $price as $s_id => $value ) {
                $amount = $amount + ( ( isset( $value['addition_price'] ) ? array_sum( $value['addition_price'] ) : 0 )  + ( isset( $value['qty'] ) ? array_sum( $value['qty'] ) : 0 ) +$value['add_product'] + ( isset($value['state_rates']) ? $value['state_rates'] : 0 ) );
				
				if($baseshippingprice){
					$amount+=( isset($value['default']) ? $value['default'] : 0 );
				}
            }
        }
		$warehouseship=warehouse_shipping( $products, $destination_country);
		
        return $amount+$warehouseship;
    }
// calculate per seller custom warehouse shipping	
function warehouse_shipping( $products, $destination_country){
		$pweight=0;
		$amount=0;
		$baseshippingprice=false;
		foreach ( $products as $product ) {
			$_enable_warehouse=get_metadata('post',$product['product_id'],'_enable_warehouse',true);
			if($_enable_warehouse!=1) continue;
			if ( is_product_disable_shipping_weight( $product['product_id'] ) ) {
                        continue;
		   }
			$_enable_warehouse=get_metadata('post',$product['product_id'],'_enable_warehouse',true);
			if ( isset( $product['variation_id'] ) ) {
				$is_virtual      = get_post_meta( $product['variation_id'], '_virtual', true );
				$is_downloadable = get_post_meta( $product['variation_id'], '_downloadable', true );
			} else {
				$is_virtual      = get_post_meta( $product['product_id'], '_virtual', true );
				$is_downloadable = get_post_meta( $product['product_id'], '_downloadable', true );
			}

			if ( ( $is_virtual == 'yes' ) || ( $is_downloadable == 'yes' ) ) {
				$downloadable_count++;
				continue;
			}
			$_enable_warehouse=get_metadata('post',$product['product_id'],'_enable_warehouse',true);
	
			// if warehouse shipping enable for this product
			if($_enable_warehouse==1){
				
				$pweight+=$product['data']->get_weight()* $product['quantity'];
			}
			$price[ $seller_id ]['totalp_price'] += $product['line_total'];
		}
		
					
		$dps_country_rates= get_option('_dps_country_rates', true );
		$dps_weight_rates  = get_option('_dps_weight_rates', true );
		$dps_threshold_values  = get_option('_dps_threshold_value', true );
		$dps_threshold_value=$dps_threshold_values['threshold'];
		if ( isset( $dps_weight_rates[$destination_country] ) ){
					
			$baseshippingprice=false;
			$cnt=0;
			foreach ( $dps_weight_rates[$destination_country]['wf'] as $weight => $weight_rate ){
				 $weightfrom=$dps_weight_rates[$destination_country]['wf'][$cnt];
				 $weightto=$dps_weight_rates[$destination_country]['wto'][$cnt];
				 $weightrate=$dps_weight_rates[$destination_country]['rate'][$cnt];
				//echo $price[$seller_id]['state_rates']=$weightrate;
				
				if($weightfrom<$pweight && $weightto>=$pweight){
					$priceliesonweightrange=true;
					 $price[$seller_id]['shipping_rates']=$weightrate;
				}
				
				
				 $cnt++;
			}
		}
		
		if(isset($dps_threshold_value[$destination_country])){
			if($dps_threshold_value[$destination_country]>0){
				if($price[$seller_id]['totalp_price']>$dps_threshold_value[$destination_country]){
					$price[$seller_id]['shipping_rates']=0;
				}
			}
		}
		
		if ( !empty( $price ) ) {
            foreach ( $price as $s_id => $value ) {
                $amount = $amount + (  ( isset($value['shipping_rates']) ? $value['shipping_rates'] : 0 ) );
				
				
            }
        }
		
		return $amount;
}
// check product is disable

function is_product_disable_shipping_weight( $product_id ) {
        $enabled = get_post_meta( $product_id, '_disable_shipping', true );

        if ( $enabled == 'yes' ) {
            return true;
        }

        return false;
}
add_filter('dokan_shipping_calculate_amount','calculate_per_seller_custom',10,5);
add_action( 'woocommerce_after_checkout_validation', 'validate_country1' );
// validate shipping country.
function validate_country1( $posted ) { 
		// print_r($posted); exit;
        $shipping_method = WC()->session->get( 'chosen_shipping_methods' );

        // per product shipping was not chosen
        if ( ! is_array( $shipping_method ) || !in_array( 'dokan_product_shipping', $shipping_method ) ) {
            return;
        }

        if ( isset( $posted['ship_to_different_address'] ) && $posted['ship_to_different_address'] == '1' ) {
            $shipping_country = $posted['shipping_country'];
        } else {
            $shipping_country = $posted['billing_country'];
        }

        
        $packages = WC()->shipping->get_packages();
        $packages = reset( $packages );

        if ( !isset( $packages['contents'] ) ) {
            return;
        }

        $products = $packages['contents'];
		
        $destination_country = isset( $packages['destination']['country'] ) ? $packages['destination']['country'] : '';
        $destination_state = isset( $packages['destination']['state'] ) ? $packages['destination']['state'] : '';

        $errors = array();
		foreach ( $products as $product ) {
            $seller_id                     = get_post_field( 'post_author', $product['product_id'] );
          
        }
        foreach ( $products as $key => $product) {

            $seller_id = get_post_field( 'post_author', $product['product_id'] );
			  $seller_products[$seller_id][] = $product;
			
			
			
			
			
            if ( !is_shipping_enabled_for_seller( $seller_id ) ) {
                continue;
            }

            if ( is_product_disable_shipping( $product['product_id'] ) ) {
                continue;
            }

            
            

         
        }
		 if ( $seller_products ) {

            foreach ( $seller_products as $seller_id => $products ) {
				if(!isset($price[ $seller_id ]['totalp_price'])){
					$price[ $seller_id ]['totalp_price']=0;
				}
				 foreach ( $products as $product ) {
					$_enable_warehouse=get_metadata('post',$product['product_id'],'_enable_warehouse',true);
					$has_found = false;
					if($_enable_warehouse==1){
						$dps_country_rates= get_option('_dps_country_rates', true );
						 if (array_key_exists( $destination_country, $dps_country_rates ) ) {
							 $has_found = true;
						 }
						$price[ $seller_id ]['warehouse']['totalp_price'] += $product['line_total'];
						$warehouse_pweight+=$product['data']->get_weight()* $product['quantity'];
						
						
					}
					else{
						$dps_country_rates = get_user_meta( $seller_id, '_dps_country_rates', true );
						if (array_key_exists( $destination_country, $dps_country_rates ) ) {
							 $has_found = true;
						 }
						$pweight+=$product['data']->get_weight()* $product['quantity'];
						$ptitle.=get_the_title( $product['product_id']);
						$price[ $seller_id ]['vendor']['totalp_price'] += $product['line_total'];
					}
					
					
				 }
				 $has_found = false;
				
				 if($pweight>0){
			
					$dps_country_rates = get_user_meta( $seller_id, '_dps_country_rates', true );
					$dps_weight_rates         = get_user_meta( $seller_id, '_dps_weight_rates', true );
					$dps_threshold_values = get_user_meta( $seller_id, '_dps_threshold_value', true );
					$dps_threshold_value=$dps_threshold_values['threshold'];
					if ( isset( $dps_weight_rates[$destination_country] ) ){
						$cnt=0;
						foreach ( $dps_weight_rates[$destination_country]['wf'] as $weight => $weight_rate ){
							 $weightfrom=$dps_weight_rates[$destination_country]['wf'][$cnt];
							 $weightto=$dps_weight_rates[$destination_country]['wto'][$cnt];
							 $weightrate=$dps_weight_rates[$destination_country]['rate'][$cnt];
							
							
							if($weightfrom<$pweight && $weightto>=$pweight){
								$has_found = true;
							
							}
							
							
							 $cnt++;
						}
					}
					if(isset($dps_threshold_value[$destination_country])){	
							if($dps_threshold_value[$destination_country]>0){
								if($price[$seller_id]['vendor']['totalp_price']>$dps_threshold_value[$destination_country]){
									$has_found = true;
								}
							}
						}
				 }
				 if($warehouse_pweight>0){
			
					$dps_country_rates= get_option('_dps_country_rates', true );
					$dps_weight_rates  = get_option('_dps_weight_rates', true );
					$dps_threshold_values  = get_option('_dps_threshold_value', true );
					$dps_threshold_value=$dps_threshold_values['threshold'];
					if ( isset( $dps_weight_rates[$destination_country] ) ){
						$cnt=0;
						foreach ( $dps_weight_rates[$destination_country]['wf'] as $weight => $weight_rate ){
							 $weightfrom=$dps_weight_rates[$destination_country]['wf'][$cnt];
							 $weightto=$dps_weight_rates[$destination_country]['wto'][$cnt];
							 $weightrate=$dps_weight_rates[$destination_country]['rate'][$cnt];
							
							
							if($weightfrom<$warehouse_pweight && $weightto>=$warehouse_pweight){
								$has_found = true;
							
							}
							
							
							 $cnt++;
						}
					}
					if(isset($dps_threshold_value[$destination_country])){	
						if($dps_threshold_value[$destination_country]>0){
							if($price[$seller_id]['warehouse']['totalp_price']>$dps_threshold_value[$destination_country]){
								$has_found = true;
							}
						}
					}
				 }
				 if(!$has_found){
					 $errors[] = sprintf('Your order has not been processed as it exceeds our usual shipping weights. Please either split your order into multiple shipments or contact us at info@eartheries.com to discuss special shipping arrangements.');
				 }
			}
		 }
		if ( $errors ) {
           
			
			if ( count( $errors ) == 1 ) {
                $message = sprintf( implode( ', ', $errors ) );
            } else {
                $message = sprintf( __( 'These products do not ship to your chosen location.: %s', 'dokan' ), implode( ', ', $errors ) );
            }
			
            wc_add_notice( $message, 'error' );
        }
}
add_action( 'dokan_admin_menu', 'weightrange_admin_menu' );

// Add Weight table menu in admin.
function weightrange_admin_menu() {
	$parent_slug = 'dokan';
    $page_title = 'Weight Range Table';
    $menu_title = 'Weight Table';
    $capability = 'manage_options';
    $menu_slug = 'weight-table';
    $function = 'myplguin_admin_page';
    $icon_url = '';
    $position = 6;

   
	add_submenu_page( $parent_slug, $page_title, $menu_title, $capability, $menu_slug, $function );
	
}
// Add Weight table range into databse.
function myplguin_admin_page(){
	 if ( isset( $_POST['dokan_update_shipping_options'] ) && wp_verify_nonce( $_POST['dokan_admin_shipping_form_field_nonce'], 'dokan_admin_shipping_form_field' ) ) {
		
		 if ( isset( $_POST['dps_dokan_country_to'] ) ) {
			foreach ($_POST['dps_dokan_country_to'] as $key => $value) {
				$country = $value;
				$c_price = floatval( $_POST['dps_dokan_country_to_price'][$key] );

				if( !$c_price && empty( $c_price ) ) {
					$c_price = 0;
				}

				if ( !empty( $value ) ) {
					$rates[$country] = $c_price;
				}
				
				$threshold_value = floatval( $_POST['dps_threshold_value'][$key] );

				if( !$threshold_value && empty( $threshold_value ) ) {
					$threshold_value = 0;
				}

				if ( !empty( $value ) ) {
					$threshold_values['threshold'][$country] = $threshold_value;
				}
				
				
			}
		}

		update_option('_dps_country_rates', $rates );
		update_option('_dps_threshold_value', $threshold_values );
		update_option('_dps_weight_rates', $_POST['dps_weightrange'] );
	}

	include 'weighttable_form.php';
}
// Add custom warehouse field in dokan vendor product edit page.
function addcustom_field_product(){
	global $post;
        
//would normally get printed to the screen/output to browser
 
	$pid=$post->ID;
	$_enable_warehouse=get_metadata('post',$pid,'_enable_warehouse',true);
	$_warehouse_flag=get_metadata('post',$pid,'_warehouse_flag',true);
	$chk='';
	if($_enable_warehouse==1) $chk='checked=checked';
	if($_warehouse_flag=='A' && $_enable_warehouse==1) $disabled=' disabled=disabled';
	$head='<div class="dokan-section-heading" data-togglehandler="dokan_product_shipping_tax">
        <h2><i class="fa fa-truck" aria-hidden="true"></i>Manage Warehouse</h2>
        <p>Manage Warehouse for this product</p>
        <a href="#" class="dokan-section-toggle">
            <i class="fa fa-sort-desc fa-flip-vertical" aria-hidden="true"></i>
        </a>
        <div class="dokan-clearfix"></div>
    </div>';
	$output= ' <div class="dokan-edit-row dokan-clearfix">'.$head.'<div class="dokan-section-content"><div class="dokan-form-group">
                <label class=""> <input type="checkbox" id="_enable_warehouse" class="_enable_warehouse"  name="_enable_warehouse"  value="1"' .$chk. $disabled .'>Ships from Eartheries Warehouse- By checking this box I have agreed to the terms and condition set out by Eartheries Limited regarding warehousing
				</label>
				<input type="hidden" class="_warehouse_flag" name="_warehouse_flag" id="_warehouse_flag" value="0">
				</div></div></div>';
				
				
	echo $output1="<script>
		$('.dokan-product-edit-form').on('change', '._enable_warehouse', function() {
			
			$('#_warehouse_flag').val('2');
				
		});
		 
	
	</script>";
		echo $output ;	
	
}

add_action( 'dokan_product_edit_after_shipping','addcustom_field_product' );

// Add warehouse custom field in admin wocommerce product into shipping section.
function my_woo_custom_warehouse_field() {
	global $post;
            
	$pid=$post->ID;
	$_enable_warehouse=get_metadata('post',$pid,'_enable_warehouse',true);
  $field = array(
	  'label' => 'Enable Warehouse', // Text in Label
	  'class' => '',
	  'style' => '',
	  'wrapper_class' => '_enable_warehouse',
	  'value' => $_enable_warehouse, // if empty, retrieved from post meta where id is the meta_key
	  'id' => '_enable_warehouse', // required
	  'name' => '_enable_warehouse', //name will set from id if empty
	  'cbvalue' => '1',
	  'custom_attributes' => '', // array of attributes 
	  'description' => 'Ships from Eartheries Warehouse- By checking this box I have agreed to the terms and condition set out by Eartheries Limited regarding warehousing'
  
	);
	$argshidden = array(
	  'value' => '0',
	  'class' => '_warehouse_flag',
	  'id' => '_warehouse_flag',
	  'name' => '_warehouse_flag' 
	);
	woocommerce_wp_hidden_input( $argshidden );
  
	woocommerce_wp_checkbox( $field );
}
add_action( 'woocommerce_product_options_shipping', 'my_woo_custom_warehouse_field' );

add_action( 'woocommerce_process_product_meta', 'save_custom_field' );
add_action( 'dokan_process_product_meta', 'save_custom_field' );
// save custom field data.
function save_custom_field( $post_id ) {
  $user = wp_get_current_user();
  $_warehouse_flag = isset( $_POST['_warehouse_flag'] ) ? $_POST['_warehouse_flag'] : '';
  $product = wc_get_product( $post_id );
  
  if($_warehouse_flag==1){
	  $product->update_meta_data( '_warehouse_flag', 'A' );
  }
  if($_warehouse_flag==2){
	  $product->update_meta_data( '_warehouse_flag', 'V' );
  }
  $_enable_warehouse = isset( $_POST['_enable_warehouse'] ) ? $_POST['_enable_warehouse'] : '0';
  
  
  $product->update_meta_data( '_enable_warehouse', $_enable_warehouse );
  if($_enable_warehouse==1){
	  $categories = [ 'Warehouse Product'];
	  wp_set_object_terms( $post_id, $categories, 'warehouses' );
  }
  else{
	  $categories = [ 'Warehouse Product'];
	  wp_set_object_terms( $post_id, null, 'warehouses' );
  }
  
  $product->save();
  
 
}
// vendor dshboard shipping form
function custom_content_after_shippingform() {
	include ('vendor_shipping_form.php');
}
add_action( 'dokan_shipping_settings_form_bottom', 'custom_content_after_shippingform' );
// load weight table by country
	function load_weight_by_country() {

        $country_id  = $_POST['country_id'];
        $country_obj = new WC_Countries();
        $states      = $country_obj->states;

        ob_start();
      
    ?>
		<tr>
			<td>
				<label for=""><?php _e( 'Weight From (KG)', 'dokan' ); ?></label>
				<input data="<?php echo $country_id ?>" class="wf" type="text" name="dps_weightrange[<?php echo $country_id ?>][wf][]" class="dokan-form-control dps_state_selection_wp" placeholder="Weight From">
			</td>
			<td>
				<label for=""><?php _e( 'Weight To (KG)', 'dokan' ); ?></label>
				<input data="<?php echo $country_id ?>" class="wto" type="text" name="dps_weightrange[<?php echo $country_id ?>][wto][]" class="dokan-form-control dps_state_selection_wp" placeholder="Weight To">
			</td>
			<td>
				<label for=""><?php _e( 'Cost', 'dokan' ); ?></label>
				<div class="dokan-input-group">
					<span class="dokan-input-group-addon"><?php echo get_woocommerce_currency_symbol(); ?></span>
					<input data="<?php echo $country_id ?>" class="rate" type="text" placeholder="0.00" class="dokan-form-control" name="dps_weightrange[<?php echo $country_id ?>][rate][]">
				</div>
			</td>
			<td width="15%">
				<label for=""></label>
				<div>
					<a class="dps-add-dokan" href="#"><i class="fa fa-plus"></i></a>
					<a class="dps-remove-dokan" href="#"><i class="fa fa-minus"></i></a>
				</div>
			</td>
		</tr>
        <?php
        
		$data = ob_get_clean();

        wp_send_json_success( $data );
    }
	add_action( 'wp_ajax_wpdps_select_weight_by_country', 'load_weight_by_country' );
	 function array_except($array, $keys) {
		return array_diff_key($array, array_flip((array) $keys));   
	} 
	// add new shipping location
	function add_new_location(){
		$country_obj     = new WC_Countries();
		$countriesa       = $country_obj->countries;
		 ob_start();
		// echo '<pre>';
		$user = wp_get_current_user();
		//print_r($user);
		if ( current_user_can( 'manage_options' ) ) {
			$dps_country_rates= get_option('_dps_country_rates' );
			//print_r($dps_country_rates);
		}
		else{
			$user_id         = get_current_user_id();
			$dps_country_rates= get_user_meta( $user_id, '_dps_country_rates', true );
		}
		
		
		  
		 $countries = array_except($countriesa, array_keys($dps_country_rates));
		
		
		
		 
		
		?>
		<!-- Render Via js for add black location field -->
		<div class="dps-shipping-location-content">
			<table class="dps-shipping-table">
				<tbody>

					<tr class="dps-shipping-location">
						<td>
							<label for=""><?php _e( 'Ship to', 'dokan' ); ?>
							<span class="dokan-tooltips-help tips" title="<?php _e( 'The country you ship to', 'dokan' ); ?>">
							<i class="fa fa-question-circle"></i></span></label>
							<select name="dps_dokan_country_to[]" class="dokan-form-control dps_country_selection_wp" id="dps_country_selection">
								<?php dokan_country_dropdown( $countries, '', true ); ?>
							</select>
						</td>
						<td class="dps_shipping_location_cost">
							<label for=""><?php _e( 'Cost', 'dokan' ); ?>
							<span class="dokan-tooltips-help tips" title="<?php _e( 'standard rate, regardless of weight, size or pieces', 'dokan' ); ?>">
							<i class="fa fa-question-circle"></i></span></label>
							<div class="dokan-input-group">
								<span class="dokan-input-group-addon"><?php echo get_woocommerce_currency_symbol(); ?></span>
								<input type="text" placeholder="0.00" class="dokan-form-control" name="dps_dokan_country_to_price[]">
							</div>
						</td>
						<td class="dps_shipping_location_cost">
							<label for=""><?php _e( 'Threshold Price', 'dokan' ); ?>
							<span class="dokan-tooltips-help tips" title="<?php _e( 'Threshold Price', 'dokan' ); ?>">
							<i class="fa fa-question-circle"></i></span></label>
							<div class="dokan-input-group">
								<span class="dokan-input-group-addon"><?php echo get_woocommerce_currency_symbol(); ?></span>
								<input type="text" placeholder="0.00" class="dokan-form-control" name="dps_threshold_value[]" >
							</div>
					   </td>
					</tr>
					<tr class="dps-shipping-states-wrapper">
						<table class="dps-shipping-states">
							<tbody></tbody>
						</table>
					</tr>
				</tbody>
			</table>
			<a href="#" class="dps-shipping-remove"><i class="fa fa-remove"></i></a>
		</div>
	<?
		$data = ob_get_clean();

        wp_send_json_success( $data );
	} 
	
add_action( 'wp_ajax_wpdps_add_new_location', 'add_new_location' );

// save shipping data into databse by vendor.
function handle_shipping_weight() {
	$user_id = get_current_user_id();
	
	if ( ! is_user_logged_in() ) {
		return;
	}

	if ( ! dokan_is_user_seller( get_current_user_id() ) ) {
		return;
	}

	if ( isset( $_POST['dokan_update_shipping_options'] ) && wp_verify_nonce( $_POST['dokan_shipping_form_field_nonce'], 'dokan_shipping_form_field' ) ) {

		

   
	
		if ( isset( $_POST['dps_dokan_country_to'] ) ) {

			foreach ($_POST['dps_dokan_country_to'] as $key => $value) {
				$country = $value;
				$c_price = floatval( $_POST['dps_dokan_country_to_price'][$key] );

				if( !$c_price && empty( $c_price ) ) {
					$c_price = 0;
				}

				if ( !empty( $value ) ) {
					$rates[$country] = $c_price;
				}
				
				$threshold_value = floatval( $_POST['dps_threshold_value'][$key] );

				if( !$threshold_value && empty( $threshold_value ) ) {
					$threshold_value = 0;
				}

				if ( !empty( $value ) ) {
					$threshold_values['threshold'][$country] = $threshold_value;
				}
				
				
			}
		}

		update_user_meta( $user_id, '_dps_country_rates', $rates );
		
		update_user_meta( $user_id, '_dps_threshold_value', $threshold_values );
		update_user_meta( $user_id, '_dps_weight_rates', $_POST['dps_weightrange'] );
		

 
		$shipping_url = dokan_get_navigation_url( 'settings/shipping' );
		wp_redirect( add_query_arg( array( 'message' => 'shipping_saved' ), $shipping_url ) );
		exit();
	}
		
}
add_action('dokan_after_shipping_options_updated', 'handle_shipping_weight');
// is shipping enabled for seller.
function is_shipping_enabled_for_seller( $seller_id ) {
	$enabled = get_user_meta( $seller_id, '_dps_shipping_enable', true );

	if ( $enabled == 'yes' ) {
		return true;
	}

	return false;
}
// has shipping enabled for product.
function has_shipping_enabled_product( $products ) {

	foreach ( $products as $product ) {
		if ( !is_product_disable_shipping( $product['product_id'] ) ) {
			return true;
		}
	}

	return false;
}
// is product disable shipping .
function is_product_disable_shipping( $product_id ) {
	$enabled = get_post_meta( $product_id, '_disable_shipping', true );

	if ( $enabled == 'yes' ) {
		return true;
	}

	return false;
}



//hook into the init action and call create_book_taxonomies when it fires
add_action( 'init', 'func_create_warehouse_taxonomy', 0 );
 
//create a custom taxonomy 
 
function func_create_warehouse_taxonomy() {
 
// Add new taxonomy, make it hierarchical like categories
//first do the translations part for GUI
 
  $labels = array(
    'name' => _x( 'Warehouse', 'taxonomy general name' ),
    'singular_name' => _x( 'Warehouse', 'taxonomy singular name' ),
    'search_items' =>  __( 'Search Warehouse' ),
    'all_items' => __( 'All Warehouse' ),
    'parent_item' => __( 'Parent Warehouse' ),
    'parent_item_colon' => __( 'Parent Warehouse:' ),
    'edit_item' => __( 'Edit Warehouse' ), 
    'update_item' => __( 'Update Warehouse' ),
    'add_new_item' => __( 'Add New Warehouse' ),
    'new_item_name' => __( 'New Warehouse Name' ),
    'menu_name' => __( 'Warehouses' ),
  );    
 
// Now register the taxonomy
 
  register_taxonomy('warehouses',array('product'), array(
    'hierarchical' => true,
    'labels' => $labels,
    'show_ui' => true,
    'show_admin_column' => true,
    'query_var' => true,
    'rewrite' => array( 'slug' => 'warehouse' , 'with_front' => false),
  ));
 
}
// First we create a function
function list_terms_custom_taxonomy( $atts ) {
 
// Inside the function we extract custom taxonomy parameter of our shortcode
 
    extract( shortcode_atts( array(
        'custom_taxonomy' => 'Warehouse Product',
    ), $atts ) );
 
	// arguments for function wp_list_categories
	$args = array( 
	taxonomy => 'warehouses',
	title_li => 'Warehouse Filter',
	show_count=>true,
	style=>''


	);
	$categories = get_categories($args);
	
	$output = '';
	if($categories) {
		$output = '<div id="custom_html-4" class="widget_text one_fourth widget box widget_custom_html last"><h3>Warehouse Filter</h3><div class="textwidget custom-html-widget"><ul class="product-categories">';
		foreach($categories as $category) {
			$output .= '<li class="cat-item cat-item-146 cat-parent"><a href="'.get_category_link( $category->term_id ).'" title="' . esc_attr( sprintf( __( "View all posts in %s" ), $category->name ) ) . '">'.$category->cat_name.'</a><span class="count">'.$category->count .'</span></li>';
		}
		$output .= "</ul></div></div>";
	}
	echo $output;

}
 
// Add a shortcode that executes our function
add_shortcode( 'ct_terms', 'list_terms_custom_taxonomy' );


// add  custom text into mail with item name.
function action_woocommerce_order_item_meta_start( $item_name, $item, $order ) { 
	
	$product_id = $item['product_id'];
	$_enable_warehouse=get_metadata('post',$product_id,'_enable_warehouse',true);
	if($_enable_warehouse==1) 
	return $item_name.' (shipped by warehouse) ';
	else return $item_name;
	
	
} 
add_filter('woocommerce_order_item_name','action_woocommerce_order_item_meta_start',10,3);      

// show product fulfilled by warehouse into product list page.	
function  show_earthriestext($products) {
	global $product;
	$pid=$product->id;
	$_enable_warehouse=get_metadata('post',$pid,'_enable_warehouse',true);

	
	if($_enable_warehouse==1) {
		 $output= '<div style="font-size: 10px;color: #000;">fulfilled by Eartheries warehouse</div>';
		echo sprintf($output);
	}
	
	
}
add_action( 'woocommerce_before_shop_loop_item', 'show_earthriestext', 9 );
add_filter('woocommerce_email_subject_new_order', 'change_admin_email_subject', 1, 2);
// change subject line in order mail.	
function change_admin_email_subject( $subject, $order ) {
	global $woocommerce;
	
	$blogname = wp_specialchars_decode(get_option('blogname'), ENT_QUOTES);
	
	$order = new WC_Order( $order );
	$items = $order->get_items();
	$enable_warehouse=false;
	foreach ( $items as $item ) {
		
		$product_id = $item['product_id'];
		$_enable_warehouse=get_metadata('post',$product_id,'_enable_warehouse',true);
		if($_enable_warehouse==1)  $enable_warehouse=true;
			
	}
	if($enable_warehouse)
	$subject = sprintf( '[%s] New Customer Order (# %s) will be shipped by warehouse', $blogname, $order->id );
	
	

	return $subject;
}


?>