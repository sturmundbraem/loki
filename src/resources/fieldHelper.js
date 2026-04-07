// Listen for all clicks on the page
// We use event delegation: one listener on the whole document
// instead of adding listeners to each button individually
document.addEventListener('click', function(event) {

    // Check if the clicked element (or its parent) is a wand button
    // .closest() walks up the DOM tree looking for a match
    var btn = event.target.closest('.ai-wand-btn');

    // Only run this code if a wand button was clicked
    if (btn) {

        // Read the field handle and type from the button's data attributes
        // These were set in Plugin.php when the button was created
        var buttonField = btn.dataset.field;   // e.g. "subtitle"
        var buttonType = btn.dataset.type;     // e.g. "PlainText"

        // If a dropdown menu is already open, close it and stop
        // This makes the wand button work as a toggle (click to open, click to close)
        var existingMenu = document.querySelector('.ai-prompt-menu');
        if (existingMenu) {
            existingMenu.remove();
            return;
        }

        var prompts = {};
        // First check if this specific field has assigned prompts
        if (aiFieldAssignments[buttonField] && aiFieldAssignments[buttonField].length > 0) {
            var assigned = aiFieldAssignments[buttonField];
            for (var i = 0; i < assigned.length; i++) {
                var key = assigned[i];
                if (aiPrompts[key]) {
                    prompts[key] = aiPrompts[key];
                }
            }
        } else {
            // Fallback: show prompts based on field type (allPlainText / allCKEditor)
            for (var key in aiPrompts) {
                var p = aiPrompts[key];
                if (buttonType === 'PlainText' && p.allPlainText === '1') {
                    prompts[key] = p;
                }
                if (buttonType === 'CKEditor' && p.allCKEditor === '1') {
                    prompts[key] = p;
                }
            }
        }



        // Create the dropdown menu container
        var menu = document.createElement('div');
        menu.className = 'ai-prompt-menu';

        // Loop through each prompt and create a menu button for it
        // key = the label shown to the user (e.g. "summarize")
        // prompts[key] = the actual prompt text sent to the AI
        for (var promptKey in prompts) {
            var menuItem = document.createElement('button');
            menuItem.type = 'button';              // Prevents form submission
            menuItem.textContent = prompts[promptKey].label;
            menuItem.dataset.prompt = prompts[promptKey].text;
            menuItem.dataset.provider = prompts[promptKey].provider;
            menu.appendChild(menuItem);
        }

        // Position the dropdown menu right below the wand button
        // getBoundingClientRect() returns the button's position on screen
        var rect = btn.getBoundingClientRect();
        menu.style.position = 'fixed';          // Fixed = positioned relative to the browser window
        menu.style.top = rect.bottom + 'px';    // Below the button
        menu.style.left = rect.left + 'px';     // Aligned to the button's left edge
        document.body.appendChild(menu);         // Add the menu to the page

        menu.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            var item = e.target.closest('button');
            if (!item) return;
            
            var selectedPrompt = item.dataset.prompt;
            var selectedProvider = item.dataset.provider;
            var entryIdInput = document.querySelector('input[name="elementId"]');
            var siteIdInput = document.querySelector('input[name="siteId"]');
            
            document.querySelectorAll('.ai-wand-btn').forEach(function(b) { b.disabled = true; });
            
            menu.remove();

            fetch(Craft.getActionUrl('stubr-automatisations/content/generate'), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': Craft.csrfTokenValue,
                    'Accept': 'application/json',
                },
                body: JSON.stringify({
                    entryId: entryIdInput.value,
                    provider: selectedProvider,
                    siteId: siteIdInput.value,
                    fieldHandle: buttonField,
                    prompt: selectedPrompt
                })
            })
            .then(function(response) { 
                if (!response.ok) {
                    throw new Error('Server returned ' + response.status);
                }
                return response.json(); 
            })

            .then(function(data) {
                document.querySelectorAll('.ai-wand-btn').forEach(function(b) { b.disabled = false; });
                if (data.error) {
                    alert('Error: ' + data.error);
                } else {
                    Craft.cp.displayNotice('Draft created!');
                    window.location.href = data.draftUrl;
                }
            })
            .catch(function(error) {
                alert('Error: ' + error.message);
            });

        });


        // Close the menu when clicking anywhere outside of it
        // setTimeout with 0ms delays this to the next browser cycle
        // Without it, the current click (that opened the menu) would immediately close it
        setTimeout(function() {
            document.addEventListener('click', function closeMenu(e) {
                // If the click was NOT inside the menu, close it
                if (!menu.contains(e.target)) {
                    menu.remove();
                    // Remove this listener so it doesn't keep running after menu is gone
                    document.removeEventListener('click', closeMenu);
                }
            });
        }, 0);
    }
});