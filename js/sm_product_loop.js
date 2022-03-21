jQuery(function ($) {
  
    //   ! Deletes Add To Cart Button when added to cart
    $(document.body).on(
    "added_to_cart",
    function (e, fragments, cart_hash, this_button) {
      this_button.remove();
    }
  );
});



document.addEventListener('DOMContentLoaded', function () {
  //   ! Isotope
  var elem = document.querySelector(".sm_product-loop--grid");
  var buttons = document.querySelectorAll(".sm_product-loop--filter li");
  
  var iso = new Isotope(elem, {
    // options
    itemSelector: ".sm_product-loop--grid_item",
    layoutMode: "fitRows",
  });



  buttons.forEach(button => {
      button.addEventListener('click', (e)=>{
        // only work with buttons
        if (!matchesSelector(e.target, "li")) {
          return;
        }
        var filterValue = e.target.getAttribute("data-filter");
          iso.arrange({ filter: filterValue });
      })
  });

});








// document
//   .querySelector(".ajax_add_to_cart")
//   .addEventListener("click", function (e) {
//     e.preventDefault();

//     console.log(this.dataset.product_id);

//     var $thisbutton = $(this);
//     var data = {
//       action: "woocommerce_ajax_add_to_cart",
//       product_id: this.dataset.product_id,
//       product_sku: "",
//       quantity: "1",
//       variation_id: "variation_id",
//     };

//     $(document.body).trigger("adding_to_cart", [$thisbutton, data]);

//     $.ajax({
//       type: "post",
//       url: wc_add_to_cart_params.ajax_url,
//       data: data,
//       beforeSend: function (response) {
//         $thisbutton.removeClass("added").addClass("loading");
//       },
//       complete: function (response) {
//         $thisbutton.addClass("added").removeClass("loading");
//       },
//       success: function (response) {
//         if (response.error && response.product_url) {
//           window.location = response.product_url;
//           return;
//         } else {
//           $(document.body).trigger("added_to_cart", [
//             response.fragments,
//             response.cart_hash,
//             $thisbutton,
//           ]);
//         }
//       },
//     });
//   });
