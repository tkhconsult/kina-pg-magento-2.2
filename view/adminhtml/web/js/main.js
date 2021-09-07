require(["jquery"],
  function($){
    $(document).ready(function() {
      $('#payment_us_kinabank_gateway_prod_url').prop('disabled', true);
      $('#payment_us_kinabank_gateway_test_url').prop('disabled', true);
    })
  }
)