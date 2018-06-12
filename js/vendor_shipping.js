
// Shipping tab js
(function($){
	 
    $(document).ready(function(){
		
		 $('.dps-main-wrapper').on('click', 'a.dps-shipping-add1', function(e) {
            e.preventDefault();
			var self = $(this),
                data = {
                   
                    action  : 'wpdps_add_new_location'
                };
			 $.post( dokan.ajaxurl, data, function(response) {
					
					
                    if( response.success ) {
                    
					 var html = response.data;
					 $( ".dokan-shipping-location-wrapper" ).append( response.data);
						

						$('.tips').tooltip();

						
					  
                    }else{
						console.log('Here');
					}
                });
           
        });

         $('.dokan-shipping-location-wrapper').on('change', '.dps_country_selection_wp', function() {
			
            var self = $(this),
                data = {
                    country_id : self.find(':selected').val(),
                    action  : 'wpdps_select_weight_by_country'
                };

            if ( self.val() == '' || self.val() == 'everywhere' ) {
                self.closest('.dps-shipping-location-content').find('table.dps-shipping-states tbody').html('');
            } else {
            
                $.post( dokan.ajaxurl, data, function(response) {
					
			
                    if( response.success ) {
                        self.closest('.dps-shipping-location-content').find('table.dps-shipping-states tbody').html(response.data);
					 
					  
					
					  
                    }else{
						console.log('Here');
					}
                });
                    
                
            }
        });
		
		
        

        $('.dokan-shipping-location-wrapper').on('click', 'a.dps-shipping-remove', function(e) {
            e.preventDefault();
            $(this).closest('.dps-shipping-location-content').remove();
            $dpsElm = $('.dokan-shipping-location-wrapper').find('.dps-shipping-location-content');

            if( $dpsElm.length == 1) {
                $dpsElm.first().find('a.dps-shipping-remove').hide();
            }
        });
		
        $('.dokan-shipping-location-wrapper').on('click', 'a.dps-add-dokan', function(e) {
            e.preventDefault();

            var row = $(this).closest('tr').first().clone().appendTo($(this).closest('table.dps-shipping-states'));
            row.find('input,select').val('');
            row.find('a.dps-remove').show();
            $('.tips').tooltip();
        });

        $('.dokan-shipping-location-wrapper').on('click', 'a.dps-remove-dokan', function(e) {
            e.preventDefault();

            if( $(this).closest('table.dps-shipping-states').find( 'tr' ).length == 1 ){
                $(this).closest('.dps-shipping-location-content').find('td.dps_shipping_location_cost').show();
            }

            $(this).closest('tr').remove();


        });

       

        $wrap = $('.dokan-shipping-location-wrapper').find('.dps-shipping-location-content');

        if( $wrap.length == 1) {
            $wrap.first().find('a.dps-shipping-remove').hide();
        }
		var chkform=true;
		 $('.dokan-shipping-location-wrapper').on('change', '.wf,.wto', function() {
			 chkform=true;
			 
			  var chkvalue=$(this).val()*1;
			  var chkclass=$(this).attr('class');
			  var chkindex=($(this).closest('tr').index())-1;
			 
			  
			
			  $(this).closest('table').find('tr').each(function(key) {
					if(key>chkindex)return false;
			
					var weight=[];
					$(this).find('input').each(function(index) {
						if(index>1)return false;
						weight[index]=$(this).val()*1;
						
					});
					
					
					
					if(weight[0]<=chkvalue && weight[1]>chkvalue){
						chkform=false;
						$(this).after('<tr id=error'+key+' class="error"><td colspan=4><span>Weight range not valid</span></td></tr>');
					
					
						return false;
					}else{
						$('#error'+key).remove();
					}
					
					
				});
			
				
			 
		 });
		 $("form#shipping-form").submit(function(e){
			 if(!chkform){
				 
				 e.preventDefault();
			 }
			 
			
		})
		 
    });
	



})(jQuery);


