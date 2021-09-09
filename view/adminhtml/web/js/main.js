require(["jquery"],
  function($){
    $(document).ready(function() {
      $('[name="groups[kinabank_gateway][fields][test_url][value]"]').prop('disabled', true);
      $('[name="groups[kinabank_gateway][fields][prod_url][value]"]').prop('disabled', true);
    })
  }
)