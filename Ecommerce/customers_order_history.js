// Helper function to generate star icons HTML
function createStarIcons(rating) {
    let html = '';
    let currentRating = parseFloat(rating);
    // Round to nearest half
    currentRating = Math.round(currentRating * 2) / 2; 

    for (let i = 1; i <= 5; i++) {
        let iconClass = 'far fa-star'; // Empty star
        if (i <= currentRating) {
            iconClass = 'fas fa-star'; // Full star
        } else if (i - 0.5 === currentRating) {
            iconClass = 'fas fa-star-half-alt'; // Half star
        }
        html += `<i class="${iconClass}" style="color: #ffc107;"></i>`;
    }
    return html;
}

document.addEventListener('DOMContentLoaded', function() {
    // --- ROLE CHECK ---
    // Sinusuri kung ang user role ay 'user'. 
    // Kung hindi 'user', hindi gagana ang mga functions sa ibaba.
    const userRole = localStorage.getItem('user_role'); // O kung saan man nakasave ang role mo (e.g. session variable)
    
    // Tandaan: Siguraduhin na ang 'user_role' ay naka-set sa browser session/localStorage
    if (userRole !== 'user') {
        console.warn('Access restricted: Only "user" role can access these functions.');
        return; 
    }

    const writeReviewModal = document.getElementById('writeReviewModal');
    const viewReviewModal = document.getElementById('viewReviewModal');
    const rrRequestProductModal = document.getElementById('rrRequestProductModal');
    const rrViewDetailModal = document.getElementById('rrViewDetailModal');
    const imageViewModal = document.getElementById('imageViewModal');
    const closeBtns = document.querySelectorAll('.close-btn');

    // Close functionality for all modals
    closeBtns.forEach(btn => {
        btn.onclick = function() {
            document.getElementById(this.dataset.modal).style.display = 'none';
            // Clear any submission status messages on close
            const statusBox = document.getElementById('review-submission-status');
            if (statusBox) {
                statusBox.style.display = 'none';
            }
        }
    });
    window.onclick = function(event) {
        if (event.target === writeReviewModal) {
            writeReviewModal.style.display = 'none';
            document.getElementById('review-submission-status').style.display = 'none';
        } else if (event.target === viewReviewModal) {
            viewReviewModal.style.display = 'none';
        } else if (event.target === rrRequestProductModal) {
            rrRequestProductModal.style.display = 'none';
        } else if (event.target === rrViewDetailModal) {
            rrViewDetailModal.style.display = 'none';
        } else if (event.target === imageViewModal) {
            imageViewModal.style.display = 'none';
        }
    };

    // Function to show a temporary message
    function showMessage(type, message) {
        const statusBox = document.getElementById('review-submission-status');
        statusBox.style.display = 'block';
        statusBox.className = 'message-box ' + (type === 'success' ? 'success-message' : 'error-message');
        statusBox.innerHTML = (type === 'success' ? '<i class="fas fa-check-circle"></i> ' : '<i class="fas fa-exclamation-circle"></i> ') + message;
    }

    // --- REVIEW FUNCTIONS ---
    // 1. Open Write Review Modal
    document.querySelectorAll('.open-review-modal').forEach(button => {
        button.addEventListener('click', function() {
            const orderProductId = this.dataset.orderProductId;
            const productName = this.dataset.productName;

            document.getElementById('modalOrderProductId').value = orderProductId;
            document.getElementById('modalProductName').textContent = productName;
            
            // Reset form inputs and status
            document.getElementById('reviewForm').reset();
            document.getElementById('fileCount').textContent = 'No file selected';
            document.getElementById('review-submission-status').style.display = 'none';

            writeReviewModal.style.display = 'flex';
        });
    });
    
    // Update file count display for review form
    document.getElementById('review_images_input').addEventListener('change', function() {
        const count = this.files.length;
        if (count > 3) {
             alert('Maximum 3 images allowed.');
             this.value = ''; // Clear selection
             document.getElementById('fileCount').textContent = 'No file selected';
             return;
        }
        document.getElementById('fileCount').textContent = count > 0 ? `${count} file(s) selected` : 'No file selected';
    });

    // 2. Submit Review (AJAX)
    const reviewForm = document.getElementById('reviewForm');
    if (reviewForm) {
        reviewForm.addEventListener('submit', async function(e) {
            e.preventDefault();

            const submitButton = document.getElementById('submitReviewButton');
            const originalText = submitButton.innerHTML;
            
            submitButton.disabled = true;
            submitButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Submitting...';

            const formData = new FormData(this);
            const orderProductId = formData.get('order_product_id');

            try {
                const response = await fetch('../database/submit_review.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json(); 
                
                if (data.success) {
                    showMessage('success', data.message);
                    
                    // Close modal after a short delay
                    setTimeout(() => {
                        writeReviewModal.style.display = 'none';
                        window.location.reload(); 
                    }, 1500);

                } else {
                    showMessage('error', data.message);
                }
            } catch (error) {
                console.error('Error submitting review:', error);
                showMessage('error', 'Nagkaroon ng network error. Pakisubukang muli.');
            } finally {
                submitButton.disabled = false;
                submitButton.innerHTML = originalText;
            }
        });
    }


    // 3. Open View Review Modal
    document.addEventListener('click', function(event) {
        if (event.target.closest('.open-review-view-modal')) {
            const button = event.target.closest('.open-review-view-modal');
            const productName = button.dataset.productName;
            const rating = button.dataset.reviewRating;
            const comment = button.dataset.reviewComment;
            const date = button.dataset.reviewDate;
            const imagePathsString = button.dataset.reviewImages;

            document.getElementById('viewReviewModal').querySelector('h3 span').textContent = productName;
            document.getElementById('viewReviewRatingScore').textContent = rating;
            document.getElementById('viewReviewComment').textContent = comment;
            document.getElementById('viewReviewDate').textContent = date;

            document.getElementById('viewReviewRatingStars').innerHTML = createStarIcons(rating);

            const imageContainer = document.getElementById('viewReviewImages');
            const noImagesMessage = document.getElementById('noReviewImages');
            imageContainer.innerHTML = '';
            
            if (imagePathsString) {
                const imagePaths = imagePathsString.split('|||').filter(path => path.trim() !== '');
                if (imagePaths.length > 0) {
                    noImagesMessage.style.display = 'none';
                    imagePaths.forEach(path => {
                        const img = document.createElement('img');
                        img.src = '../' + path; 
                        img.alt = 'Review Proof Image';
                        img.style.width = '100px';
                        img.style.height = '100px';
                        img.style.objectFit = 'cover';
                        img.style.borderRadius = '5px';
                        img.style.cursor = 'pointer';
                        img.style.border = '2px solid var(--border-color)';
                        img.addEventListener('click', function() {
                            document.getElementById('proofImageDisplay').src = this.src; 
                            imageViewModal.style.display = 'flex';
                        });
                        imageContainer.appendChild(img);
                    });
                } else {
                    noImagesMessage.style.display = 'block';
                    imageContainer.appendChild(noImagesMessage);
                }
            } else {
                noImagesMessage.style.display = 'block';
                imageContainer.appendChild(noImagesMessage);
            }

            viewReviewModal.style.display = 'flex';
        }
    });


    // --- R/R FUNCTIONS ---
    // 1. Open Product-Specific Return/Refund Request Modal
    document.querySelectorAll('.open-rr-product-modal').forEach(button => {
        button.addEventListener('click', function() {
            const orderId = this.dataset.orderId;
            const productId = this.dataset.productId;
            const productName = this.dataset.productName;
            const maxQuantity = this.dataset.productQuantity;

            document.getElementById('rrModalOrderId').value = orderId;
            document.getElementById('rrModalProductId').value = productId;
            document.getElementById('rrModalProductName').textContent = productName;
            
            document.getElementById('maxQuantity').textContent = maxQuantity;
            const quantityInput = document.getElementById('quantity_to_return');
            quantityInput.max = maxQuantity;
            quantityInput.value = 1; 

            document.getElementById('rrForm').reset();
            document.getElementById('rrFileCount').textContent = 'No file selected';

            rrRequestProductModal.style.display = 'flex';
        });
    });
    
    document.getElementById('rr_proof_image').addEventListener('change', function() {
        const count = this.files.length;
        document.getElementById('rrFileCount').textContent = count > 0 ? `${count} file(s) selected` : 'No file selected';
    });


    // 2. Submit Return/Refund Request (AJAX)
    const rrForm = document.getElementById('rrForm');
    if (rrForm) {
        rrForm.addEventListener('submit', async function(e) {
            e.preventDefault();

            const submitButton = document.getElementById('rrSubmitButton');
            const originalText = submitButton.innerHTML;
            
            submitButton.disabled = true;
            submitButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Submitting...';

            const formData = new FormData(this);
            
            if (!formData.get('request_type') || !formData.get('reason').trim() || !formData.get('quantity_to_return')) {
                alert('Please complete all required fields (Type, Reason, and Quantity).');
                submitButton.disabled = false;
                submitButton.innerHTML = originalText;
                return;
            }

            try {
                const response = await fetch('../database/customer_return_refund.php', { 
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json(); 
                
                submitButton.disabled = false;
                submitButton.innerHTML = originalText;

                if (data.success) {
                    alert(data.message);
                    window.location.reload(); 
                } else {
                    alert('Error: ' + data.message);
                }
            } catch (error) {
                submitButton.disabled = false;
                submitButton.innerHTML = originalText;
                console.error('Error submitting RR request:', error);
                alert('Nagkaroon ng network error. Pakisubukang muli.');
            }
        });
    }
    
    // 3. View R/R Details Modal
    document.querySelectorAll('.btn-view-rr-details').forEach(button => {
        button.addEventListener('click', function() {
            const requestId = this.dataset.requestId;
            const orderId = this.dataset.orderId;
            const productName = this.dataset.productName;
            const status = this.dataset.status;
            const type = this.dataset.type;
            const quantity = this.dataset.quantity;
            const reason = this.dataset.reason;
            const adminNotes = this.dataset.adminNotes;
            const proofPath = this.dataset.proofPath;
            
            document.getElementById('viewRRRequestId').textContent = requestId;
            document.getElementById('viewRROrderId').textContent = orderId;
            document.getElementById('viewRRProductName').textContent = productName;
            document.getElementById('viewRRType').textContent = type;
            document.getElementById('viewRRQuantity').textContent = quantity;
            document.getElementById('viewRRReason').textContent = reason;

            const statusBadge = document.getElementById('viewRRStatusBadge');
            statusBadge.textContent = status;
            let statusClass = status.toLowerCase();
            
            if (statusClass === 'accepted' || statusClass === 'refunded') {
                statusClass = 'accepted';
            } else if (statusClass === 'declined' || statusClass === 'cancelled') {
                statusClass = 'declined';
            } else {
                statusClass = 'pending';
            }
            statusBadge.className = `rr-status-badge ${statusClass}`;
            
            const adminNotesBlock = document.getElementById('viewRRAdminNotesBlock');
            if (adminNotes) {
                document.getElementById('viewRRAdminNotes').textContent = adminNotes;
                adminNotesBlock.style.display = 'block';
            } else {
                adminNotesBlock.style.display = 'none';
            }
            
            const viewProofButton = document.getElementById('viewRRProofButton');
            const fullProofPath = proofPath ? '../uploads/rr_proofs/' + proofPath : null; 

            if (fullProofPath) {
                viewProofButton.style.display = 'inline-flex';
                const old_element = document.getElementById('viewRRProofButton');
                const new_element = old_element.cloneNode(true);
                old_element.replaceWith(new_element);
                
                document.getElementById('viewRRProofButton').addEventListener('click', function() {
                    document.getElementById('proofImageDisplay').src = fullProofPath;
                    imageViewModal.style.display = 'flex';
                });
            } else {
                viewProofButton.style.display = 'none';
            }

            const cancelButton = document.querySelector('.btn-cancel-rr-details');
            cancelButton.dataset.requestId = requestId; 
            
            if (statusClass === 'pending' || statusClass === 'processing') {
                cancelButton.style.display = 'inline-flex';
            } else {
                cancelButton.style.display = 'none';
            }

            rrViewDetailModal.style.display = 'flex';
        });
    });
    
    // 4. Cancel Request Logic
    async function cancelReturnRefundRequest() {
        if (!confirm('Are you sure you want to cancel this Return/Refund request?')) {
            return;
        }

        const requestId = this.dataset.requestId;
        
        try {
            const response = await fetch('../database/customer_return_refund.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=cancel_request&request_id=${requestId}`
            });
            
            const data = await response.json();

            if (data.success) {
                alert(data.message);
                window.location.reload();
            } else {
                alert('Cancellation Error: ' + data.message);
            }
        } catch (error) {
            console.error('Error cancelling RR request:', error);
            alert('Nagkaroon ng network error sa pag-cancel. Pakisubukang muli.');
        }
    }

    document.querySelector('.btn-cancel-rr-details').addEventListener('click', cancelReturnRefundRequest);
});