document.addEventListener('DOMContentLoaded', function() {
    
    // Allow only numbers in input fields with class 'only-numbers'
    document.querySelectorAll('.only-numbers').forEach(function(input) {
        input.addEventListener('keypress', function(event) {
            if (!/^\d$/.test(event.key)) {
                event.preventDefault();
            }
        });
    }); 

    // Allow only letters in input fields with class 'only-letters'
    document.querySelectorAll('.only-letters').forEach(function(input) {
        input.addEventListener('keypress', function(event) {
            if (!/^[a-zA-Z]$/.test(event.key)) {
                event.preventDefault();
            }
        });
    });
});