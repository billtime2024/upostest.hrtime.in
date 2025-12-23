/**
 * JavaScript functionality for Convert Sell to Purchase feature
 */

$(document).ready(function() {
    // Handle Convert to Purchase button click
    $(document).on('click', '.convert-to-purchase-btn', function(e) {
        e.preventDefault();
        
        var url = $(this).data('href');
        var container = $(this).data('container');
        
        // Show loading state
        var button = $(this);
        var originalText = button.html();
        button.html('<i class="fas fa-spinner fa-spin"></i> ' + button.text());
        button.prop('disabled', true);
        
        // Load modal content
        $.ajax({
            url: url,
            method: 'GET',
            dataType: 'html',
            success: function(response) {
                // Show modal with loaded content
                $(container).html(response).modal('show');
                
                // Re-enable button
                button.html(originalText);
                button.prop('disabled', false);
            },
            error: function(xhr) {
                // Handle error
                button.html(originalText);
                button.prop('disabled', false);
                
                var errorMessage = 'An error occurred while loading the modal.';
                
                if (xhr.responseJSON && xhr.responseJSON.msg) {
                    errorMessage = xhr.responseJSON.msg;
                } else if (xhr.responseText) {
                    errorMessage = xhr.responseText;
                }
                
                // Show error message
                toastr.error(errorMessage, 'Error');
            }
        });
    });
    
    // Handle form submission in the modal
    $(document).on('submit', '#convert-to-purchase-form', function(e) {
        e.preventDefault();
        
        var form = $(this);
        var submitButton = form.find('button[type="submit"]');
        var originalText = submitButton.html();
        
        // Validate form
        if (!form[0].checkValidity()) {
            form[0].reportValidity();
            return;
        }
        
        // Show loading state
        submitButton.html('<i class="fas fa-spinner fa-spin"></i> Converting...');
        submitButton.prop('disabled', true);
        
        // Submit form via AJAX
        $.ajax({
            url: form.attr('action'),
            method: 'POST',
            data: form.serialize(),
            dataType: 'json',
            success: function(response) {
                // Hide modal
                $('.view_modal').modal('hide');
                
                if (response.success) {
                    // Show success message
                    toastr.success(response.msg, 'Success');
                    
                    // Redirect to new purchase
                    if (response.redirect_url) {
                        setTimeout(function() {
                            window.location.href = response.redirect_url;
                        }, 1000);
                    } else {
                        // Reload current page
                        setTimeout(function() {
                            location.reload();
                        }, 1000);
                    }
                } else {
                    // Show error message
                    toastr.error(response.msg || 'Conversion failed.', 'Error');
                }
            },
            error: function(xhr) {
                // Handle error
                submitButton.html(originalText);
                submitButton.prop('disabled', false);
                
                var errorMessage = 'An error occurred during conversion.';
                
                if (xhr.responseJSON && xhr.responseJSON.msg) {
                    errorMessage = xhr.responseJSON.msg;
                } else if (xhr.responseText) {
                    errorMessage = xhr.responseText;
                }
                
                // Show error message
                toastr.error(errorMessage, 'Error');
            }
        });
    });
    
    // Handle modal close - reset form
    $(document).on('hidden.bs.modal', '.view_modal', function() {
        // Reset form if it exists
        if ($('#convert-to-purchase-form').length) {
            $('#convert-to-purchase-form')[0].reset();
        }
    });
    
});