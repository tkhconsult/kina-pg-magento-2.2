require(["jquery"],
  function($){
    $(document).ready(function() {
      $('#payment_us_kinabank_gateway_prod_url').prop('disabled', true);
      $('#payment_us_kinabank_gateway_test_mode').change(function() {
        if($(this).val() == '1') {
          $('#payment_us_kinabank_gateway_prod_url').val('test');
        } else {
          $('#payment_us_kinabank_gateway_prod_url').val('prod');
        }
      }).trigger('change');
    })
  }
);