/**
 * Tatrafest Import Orders - Admin JavaScript
 */

jQuery(document).ready(function ($) {
  const $form = $("#tatrafest-import-form");
  const $message = $("#tatrafest-import-message");
  const $spinner = $form.find(".spinner");
  const $orderInput = $("#orderNumber");
  const $submitBtn = $form.find('button[type="submit"]');

  // Form submission
  $form.on("submit", function (e) {
    e.preventDefault();

    const orderNumber = $orderInput.val().trim();

    if (!orderNumber) {
      showMessage("error", "Proszę podać numer zamówienia");
      return;
    }

    // Validate that input is numeric
    if (!/^\d+$/.test(orderNumber)) {
      showMessage("error", "Numer zamówienia musi zawierać tylko cyfry");
      return;
    }

    // Disable button and show spinner
    $submitBtn.prop("disabled", true);
    $spinner.addClass("is-active");

    // Make AJAX request
    $.ajax({
      url: tatrafestImportOrders.ajaxUrl,
      type: "POST",
      data: {
        action: "tatrafest_import_order",
        nonce: tatrafestImportOrders.nonce,
        orderNumber: orderNumber,
      },
      success: function (response) {
        if (response.success) {
          showMessage(
            "success",
            response.data.message +
              "<br/>ID Zamówienia WooCommerce: " +
              response.data.order_id,
          );
          $orderInput.val("");
        } else {
          showMessage("error", response.data.message || "Błąd podczas importu");
        }
      },
      error: function () {
        showMessage("error", "Błąd komunikacji z serwerem");
      },
      complete: function () {
        // Enable button and hide spinner
        $submitBtn.prop("disabled", false);
        $spinner.removeClass("is-active");
      },
    });
  });

  /**
   * Show message to user
   */
  function showMessage(type, message) {
    $message.html(message);
    $message.removeClass("notice-success notice-error");

    if (type === "success") {
      $message.addClass("notice-success");
    } else if (type === "error") {
      $message.addClass("notice-error");
    }

    $message.show();

    // Auto-hide success message after 5 seconds
    if (type === "success") {
      setTimeout(function () {
        $message.fadeOut("slow");
      }, 5000);
    }
  }

  // Clear message when typing in input
  $orderInput.on("input", function () {
    if ($message.is(":visible")) {
      $message.fadeOut("slow");
    }
  });
});
