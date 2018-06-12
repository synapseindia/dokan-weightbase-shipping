<?php	
	$user_id         = get_current_user_id();
	$dps_country_rates= get_user_meta( $user_id, '_dps_country_rates', true );
	$dps_state_rates  = get_user_meta( $user_id, '_dps_state_rates', true );
	$dps_weight_rates  = get_user_meta( $user_id, '_dps_weight_rates', true );
	$dps_threshold_values  = get_user_meta( $user_id, '_dps_threshold_value', true );
	$dps_country_values  = get_user_meta( $user_id, '_dps_country', true );
	
	$dps_threshold_value=$dps_threshold_values['threshold'];
	
	
	
	
	
	$dps_pt= get_user_meta( $user_id, '_dps_pt', true );
	$country_obj     = new WC_Countries();
	$countries       = $country_obj->countries;
	$states          = $country_obj->states;?>
	<div class="dokan-form-group">
		<div class="dokan-w12 dps-main-wrapper">
           <div class="dokan-shipping-location-wrapper">
				<p class="dokan-page-help"><?php _e( 'Add the countries you deliver your products to. You can specify states as well. If the shipping price is same except some countries/states, there is an option <strong>Everywhere Else</strong>, you can use that.', 'dokan' ) ?></p>

                <?php 
			
				if ( $dps_country_rates ) : $ccount=0; ?>
				
					<?php 
								
						
					 foreach ( $dps_country_rates as $country => $country_rate ) : 
						
					?>
						<div class="dps-shipping-location-content">
							<table class="dps-shipping-table">
                                <tbody>
									<tr class="dps-shipping-location">
                                        <td width="40%">
                                            <label for=""><?php _e( 'Ship to', 'dokan' ); ?>
                                            <span class="dokan-tooltips-help tips" title="<?php _e( 'The country you ship to', 'dokan' ); ?>">
                                            <i class="fa fa-question-circle"></i></span></label>
                                            <select   name="dps_dokan_country_to[]" class="dokan-form-control dps_country_selection_wp" id="dps_country_selection<?php echo $country;?>"  <!--onchange="getDropDownValue('<?php echo $country;?>')-->">
                                                <?php dokan_country_dropdown( $countries, $country, true ); ?>
                                            </select>
                                        </td>
                                        <td class="dps_shipping_location_cost">
                                            <label for=""><?php _e( 'Flat Shipping', 'dokan' ); ?>
                                            <span class="dokan-tooltips-help tips" title="<?php _e( 'If the shipping price is same for all the states, use this field. If not, manually add the states below', 'dokan' ); ?>">
                                            <i class="fa fa-question-circle"></i></span></label>
                                            <div class="dokan-input-group">
                                                <span class="dokan-input-group-addon"><?php echo get_woocommerce_currency_symbol(); ?></span>
                                                <input type="text" placeholder="0.00" class="dokan-form-control" name="dps_dokan_country_to_price[]" value="<?php echo esc_attr( $country_rate); ?>">
                                            </div>
                                        </td>
										<td class="dps_shipping_location_cost">
                                            <label for=""><?php _e( 'Threshold Price', 'dokan' ); ?>
                                            <span class="dokan-tooltips-help tips" title="<?php _e( 'If the total price ', 'dokan' ); ?>">
                                            <i class="fa fa-question-circle"></i></span></label>
                                            <div class="dokan-input-group">
                                                <span class="dokan-input-group-addon"><?php echo get_woocommerce_currency_symbol(); ?></span>
                                                <input type="text" placeholder="0.00" class="dokan-form-control" name="dps_threshold_value[]" value="<?php echo esc_attr( $dps_threshold_value[$country] ); ?>">
                                            </div>
                                        </td>
										
                                    </tr>

                                    <tr class="dps-shipping-states-wrapper">
                                        <table class="dps-shipping-states">
                                            <tbody>
                                               <?php if ( $dps_weight_rates ): ?>
                                                  <?php if ( isset( $dps_weight_rates[$country] ) ): 
														$cnt=0;
													?>
														<?php foreach ( $dps_weight_rates[$country]['wf'] as $weight => $weight_rate ): ;?>
															<?php 
															if ( isset( $dps_weight_rates[$country]['wf'][$cnt]) && !empty( $dps_weight_rates[$country]['wf'] ) ): 
															
															?>

                                                                <tr>
                                                                    <td>
                                                                        <label for=""><?php _e( 'Weight From (KG)', 'dokan' ) ?>
                                                                        <span class="dokan-tooltips-help tips" title="<?php _e( 'Weight From' ); ?>">
                                                                        <i class="fa fa-question-circle"></i></span></label>
                                                                        <input data="<?php echo $country ?>" class="wf" name="dps_weightrange[<?php echo $country ?>][wf][]" class="dokan-form-control dps_state_selection_wp" type="text" value="<?php echo $dps_weight_rates[$country]['wf'][$cnt] ?>">
                                                                            
                                                                        
                                                                    </td>
																	<td>
                                                                        <label for=""><?php _e( 'Weight To (KG)', 'dokan' ) ?>
                                                                        <span class="dokan-tooltips-help tips" title="<?php _e( 'Weight From' ); ?>">
                                                                        <i class="fa fa-question-circle"></i></span></label>
                                                                        <input data="<?php echo $country ?>" class="wto" name="dps_weightrange[<?php echo $country ?>][wto][]" class="dokan-form-control dps_state_selection_wp" type="text" value="<?php echo $dps_weight_rates[$country]['wto'][$cnt]  ?>">
                                                                            
                                                                        
                                                                    </td>
                                                                    <td>
                                                                        <label for=""><?php _e( 'Cost', 'dokan' ); ?>
                                                                        <span class="dokan-tooltips-help tips" title="<?php _e( 'Shipping price for this state', 'dokan' ); ?>">
                                                                        <i class="fa fa-question-circle"></i></span></label>
                                                                        <div class="dokan-input-group">
                                                                            <span class="dokan-input-group-addon"><?php echo get_woocommerce_currency_symbol(); ?></span>
                                                                            <input data="<?php echo $country ?>" class="rate" type="text" placeholder="0.00" value="<?php echo $dps_weight_rates[$country]['rate'][$cnt] ?>" class="dokan-form-control" name="dps_weightrange[<?php echo $country; ?>][rate][]">
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

                                                           

                                                            <?php endif  ?>
															<?php $cnt++;?>
                                                        <?php endforeach ?>

                                                    <?php endif ?>

                                                <?php endif ?>
                                            </tbody>
                                        </table>
                                    </tr>
                                </tbody>
                            </table>
                            <a href="#" class="dps-shipping-remove"><i class="fa fa-remove"></i></a>
                        </div>

                    <?php $ccount++; endforeach; ?>

                <?php else: ?>

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
                                        <label for=""><?php _e( 'Flat Shipping', 'dokan' ); ?>
                                        <span class="dokan-tooltips-help tips" title="<?php _e( 'If the shipping price is same for all the states, use this field. If not, manually add the states below', 'dokan' ); ?>">
                                        <i class="fa fa-question-circle"></i></span></label>
                                        <div class="dokan-input-group">
                                            <span class="dokan-input-group-addon"><?php echo get_woocommerce_currency_symbol(); ?></span>
                                            <input type="text" placeholder="0.00" class="dokan-form-control" name="dps_dokan_country_to_price[]">
                                        </div>
                                    </td>
									<td class="dps_shipping_location_cost">
										<label for=""><?php _e( 'Threshold Price', 'dokan' ); ?>
										<span class="dokan-tooltips-help tips" title="<?php _e( 'If the total price ', 'dokan' ); ?>">
										<i class="fa fa-question-circle"></i></span></label>
										<div class="dokan-input-group">
											<span class="dokan-input-group-addon"><?php echo get_woocommerce_currency_symbol(); ?></span>
											<input type="text" placeholder="0.00" class="dokan-form-control" name="dps_threshold_value[]" value="<?php echo esc_attr( $dps_threshold_value[$country] ); ?>">
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
                <?php endif; ?>

                </div>
                <a href="#" class="dokan-btn dokan-btn-default dps-shipping-add1 dokan-right"><?php _e( 'Add Location', 'dokan' ); ?></a>
            </div>
        </div>

  



	