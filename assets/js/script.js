// Wait for the DOM to be fully loaded
document.addEventListener('DOMContentLoaded', function() {
    // Handle form validation
    const forms = document.querySelectorAll('.needs-validation');
    
    Array.from(forms).forEach(form => {
        form.addEventListener('submit', event => {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            
            form.classList.add('was-validated');
        }, false);
    });
    
    // Job search form
    const searchForm = document.getElementById('jobSearchForm');
    if (searchForm) {
        searchForm.addEventListener('submit', function(e) {
            // Allow empty searches but prevent default to handle with JS if needed
            const keyword = document.getElementById('keyword').value.trim();
            const location = document.getElementById('location').value.trim();
            const jobType = document.getElementById('jobType').value;
            
            // If all fields are empty, show a message
            if (keyword === '' && location === '' && jobType === '') {
                e.preventDefault();
                alert('Please enter at least one search criterion');
            }
        });
    }
    
    // Toggle password visibility
    const togglePassword = document.querySelector('.toggle-password');
    if (togglePassword) {
        togglePassword.addEventListener('click', function() {
            const passwordField = document.querySelector(this.getAttribute('toggle'));
            const type = passwordField.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordField.setAttribute('type', type);
            
            // Toggle icon
            this.classList.toggle('fa-eye');
            this.classList.toggle('fa-eye-slash');
        });
    }
    
    // Job application form - handle file input changes
    const resumeInput = document.getElementById('resume');
    if (resumeInput) {
        resumeInput.addEventListener('change', function() {
            const fileName = this.files[0]?.name || 'No file chosen';
            const fileLabel = document.querySelector('.custom-file-label');
            if (fileLabel) {
                fileLabel.textContent = fileName;
            }
        });
    }
    
    // Job description preview in job posting form
    const jobDescriptionInput = document.getElementById('description');
    const jobDescriptionPreview = document.getElementById('descriptionPreview');
    
    if (jobDescriptionInput && jobDescriptionPreview) {
        jobDescriptionInput.addEventListener('input', function() {
            jobDescriptionPreview.innerHTML = this.value;
        });
    }
    
    // Handle delete confirmations
    const deleteButtons = document.querySelectorAll('.delete-btn');
    
    deleteButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            if (!confirm('Are you sure you want to delete this item?')) {
                e.preventDefault();
            }
        });
    });
    
    // Handle status change confirmations
    const statusChangeButtons = document.querySelectorAll('.status-change-btn');
    
    statusChangeButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            if (!confirm('Are you sure you want to change the status?')) {
                e.preventDefault();
            }
        });
    });
});