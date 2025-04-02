document.addEventListener('DOMContentLoaded', function() {
    // Mobile menu toggle
    const mobileMenuToggle = document.querySelector('.mobile-menu-toggle');
    const mobileMenu = document.querySelector('.mobile-menu');
    
    if (mobileMenuToggle && mobileMenu) {
        mobileMenuToggle.addEventListener('click', function() {
            mobileMenu.classList.toggle('active');
            
            // Toggle hamburger menu animation
            const spans = this.querySelectorAll('span');
            spans.forEach(span => span.classList.toggle('active'));
            
            if (mobileMenu.classList.contains('active')) {
                document.body.style.overflow = 'hidden'; // Prevent scrolling when menu is open
            } else {
                document.body.style.overflow = ''; // Allow scrolling when menu is closed
            }
        });
    }
    
    // Quantity buttons in cart
    const quantityBtns = document.querySelectorAll('.quantity-btn');
    
    if (quantityBtns.length > 0) {
        quantityBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                const input = this.parentElement.querySelector('.quantity-input');
                const currentValue = parseInt(input.value);
                
                if (this.classList.contains('minus') && currentValue > 1) {
                    input.value = currentValue - 1;
                } else if (this.classList.contains('plus')) {
                    input.value = currentValue + 1;
                }
                
                // Trigger change event to update cart
                const event = new Event('change');
                input.dispatchEvent(event);
            });
        });
    }
    
    // Cart quantity change
    const quantityInputs = document.querySelectorAll('.quantity-input');
    
    if (quantityInputs.length > 0) {
        quantityInputs.forEach(input => {
            input.addEventListener('change', function() {
                const productId = this.getAttribute('data-product-id');
                const quantity = this.value;
                
                // Update cart via AJAX
                updateCartItem(productId, quantity);
            });
        });
    }
    
    // Update cart item function
    function updateCartItem(productId, quantity) {
        const xhr = new XMLHttpRequest();
        xhr.open('POST', 'update_cart.php', true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        
        xhr.onload = function() {
            if (this.status === 200) {
                try {
                    const response = JSON.parse(this.responseText);
                    
                    if (response.success) {
                        // Update cart total
                        const cartTotal = document.querySelector('.cart-total');
                        if (cartTotal) {
                            cartTotal.textContent = '$' + response.total.toFixed(2);
                        }
                        
                        // Update item subtotal
                        const itemSubtotal = document.querySelector(`.subtotal-${productId}`);
                        if (itemSubtotal) {
                            itemSubtotal.textContent = '$' + response.item_subtotal.toFixed(2);
                        }
                        
                        // Update cart count
                        const cartCount = document.querySelector('.cart-count');
                        if (cartCount) {
                            cartCount.textContent = response.cart_count;
                        }
                    }
                } catch (e) {
                    console.error('Error parsing response:', e);
                }
            }
        };
        
        xhr.send(`product_id=${productId}&quantity=${quantity}`);
    }
    
    // Product image preview
    const productThumbnails = document.querySelectorAll('.product-thumbnail');
    const mainProductImage = document.querySelector('.main-product-image');
    
    if (productThumbnails.length > 0 && mainProductImage) {
        productThumbnails.forEach(thumbnail => {
            thumbnail.addEventListener('click', function() {
                const imageUrl = this.getAttribute('data-image');
                mainProductImage.src = imageUrl;
                
                // Remove active class from all thumbnails
                productThumbnails.forEach(thumb => thumb.classList.remove('active'));
                
                // Add active class to clicked thumbnail
                this.classList.add('active');
            });
        });
    }
    
    // Form validation
    const forms = document.querySelectorAll('form.validate');
    
    if (forms.length > 0) {
        forms.forEach(form => {
            form.addEventListener('submit', function(e) {
                const requiredFields = form.querySelectorAll('[required]');
                let isValid = true;
                
                requiredFields.forEach(field => {
                    if (!field.value.trim()) {
                        isValid = false;
                        field.classList.add('is-invalid');
                        
                        // Create error message if it doesn't exist
                        let errorMessage = field.nextElementSibling;
                        if (!errorMessage || !errorMessage.classList.contains('error-message')) {
                            errorMessage = document.createElement('div');
                            errorMessage.classList.add('error-message');
                            errorMessage.textContent = 'This field is required';
                            field.parentNode.insertBefore(errorMessage, field.nextSibling);
                        }
                    } else {
                        field.classList.remove('is-invalid');
                        
                        // Remove error message if it exists
                        const errorMessage = field.nextElementSibling;
                        if (errorMessage && errorMessage.classList.contains('error-message')) {
                            errorMessage.remove();
                        }
                    }
                });
                
                if (!isValid) {
                    e.preventDefault();
                }
            });
        });
    }
    
    // Password strength meter
    const passwordInput = document.querySelector('input[type="password"][data-password-strength]');
    
    if (passwordInput) {
        const strengthMeter = document.createElement('div');
        strengthMeter.classList.add('password-strength-meter');
        passwordInput.parentNode.insertBefore(strengthMeter, passwordInput.nextSibling);
        
        const strengthText = document.createElement('div');
        strengthText.classList.add('password-strength-text');
        passwordInput.parentNode.insertBefore(strengthText, strengthMeter.nextSibling);
        
        passwordInput.addEventListener('input', function() {
            const password = this.value;
            let strength = 0;
            
            // Check password length
            if (password.length >= 8) {
                strength += 1;
            }
            
            // Check for lowercase letters
            if (password.match(/[a-z]/)) {
                strength += 1;
            }
            
            // Check for uppercase letters
            if (password.match(/[A-Z]/)) {
                strength += 1;
            }
            
            // Check for numbers
            if (password.match(/[0-9]/)) {
                strength += 1;
            }
            
            // Check for special characters
            if (password.match(/[^a-zA-Z0-9]/)) {
                strength += 1;
            }
            
            // Update strength meter
            strengthMeter.className = 'password-strength-meter';
            strengthText.className = 'password-strength-text';
            
            if (password.length === 0) {
                strengthMeter.classList.add('empty');
                strengthText.textContent = '';
            } else if (strength < 2) {
                strengthMeter.classList.add('weak');
                strengthText.textContent = 'Weak password';
                strengthText.classList.add('weak');
            } else if (strength < 4) {
                strengthMeter.classList.add('medium');
                strengthText.textContent = 'Medium password';
                strengthText.classList.add('medium');
            } else {
                strengthMeter.classList.add('strong');
                strengthText.textContent = 'Strong password';
                strengthText.classList.add('strong');
            }
        });
    }
    
    // Admin product image preview
    const productImageInput = document.querySelector('#product-image');
    const imagePreview = document.querySelector('#image-preview');
    
    if (productImageInput && imagePreview) {
        productImageInput.addEventListener('change', function() {
            const file = this.files[0];
            
            if (file) {
                const reader = new FileReader();
                
                reader.addEventListener('load', function() {
                    imagePreview.src = this.result;
                    imagePreview.style.display = 'block';
                });
                
                reader.readAsDataURL(file);
            }
        });
    }
    
    // Confirm delete
    const deleteButtons = document.querySelectorAll('.delete-btn, .btn-delete');
    
    if (deleteButtons.length > 0) {
        deleteButtons.forEach(button => {
            button.addEventListener('click', function(e) {
                if (!confirm('Are you sure you want to delete this item? This action cannot be undone.')) {
                    e.preventDefault();
                }
            });
        });
    }
}); 