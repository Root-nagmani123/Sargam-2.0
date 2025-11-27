const observer = new MutationObserver(function (mutationsList) {
    mutationsList.forEach(function (mutation) {
        mutation.addedNodes.forEach(function (node) {
            if (node.nodeType === 1) { // Element
                if (node.matches('.only-numbers') || node.querySelector('.only-numbers')) {
                    bindOnlyNumbers(node);
                }
                if (node.matches('.only-letters') || node.querySelector('.only-letters')) {
                    bindOnlyLetters(node);
                }
				if (node.matches('.letters-with-space') || node.querySelector('.letters-with-space')) {
                    bindLettersWithSpace(node);
                }
            }
        });
    });
});

observer.observe(document.body, {
    childList: true,
    subtree: true
});

function bindOnlyNumbers(context = document) {
    context.querySelectorAll('.only-numbers').forEach(function(input) {
        input.addEventListener('keypress', function(event) {
            if (!/^\d$/.test(event.key)) {
                event.preventDefault();
            }
        });
    });
}

function bindOnlyLetters(context = document) {
    context.querySelectorAll('.only-letters').forEach(function(input) {
        input.addEventListener('keypress', function(event) {
            if (!/^[a-zA-Z]$/.test(event.key)) {
                event.preventDefault();
            }
        });
    });
}

function bindLettersWithSpace(context = document) {
    context.querySelectorAll('.letters-with-space').forEach(function (input) {
        input.addEventListener('input', function () {
            this.value = this.value.replace(/[^A-Za-z ]/g, '');
        });
    });
}


// Initial bind
bindOnlyNumbers();
bindOnlyLetters();
bindLettersWithSpace();
