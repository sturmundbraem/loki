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

        var header = document.createElement('div');
        header.className = 'ai-prompt-menu-header';

        var headerTitle = document.createElement('span');
        headerTitle.className = 'ai-prompt-menu-title';
        headerTitle.textContent = buttonField;
        header.appendChild(headerTitle);

        var closeBtn = document.createElement('button');
        closeBtn.type = 'button';
        closeBtn.className = 'ai-prompt-menu-close';
        closeBtn.textContent = 'x';
        closeBtn.addEventListener('click', function() {
            menu.remove();
        })
        header.appendChild(closeBtn);
        menu.appendChild(header);

        // Loop through each prompt and create a menu button for it
        // key = the label shown to the user (e.g. "summarize")
        // prompts[key] = the actual prompt text sent to the AI

        var list = document.createElement('div');
        list.className = 'ai-prompt-menu-list';

        for (var promptKey in prompts) {
            var menuItem = document.createElement('button');
            menuItem.type = 'button';              // Prevents form submission
            var labelText = prompts[promptKey].label;
            if (prompts[promptKey].createDraft === '1') {
                labelText = labelText + ' (Draft)';
            }
            menuItem.textContent = labelText;
            menuItem.dataset.prompt = prompts[promptKey].text;
            menuItem.dataset.provider = prompts[promptKey].provider;
            menuItem.dataset.createDraft = prompts[promptKey].createDraft;
            list.appendChild(menuItem);
        }
        menu.appendChild(list);
        // Position the dropdown menu right below the wand button
        // getBoundingClientRect() returns the button's position on screen
        var rect = btn.getBoundingClientRect();
        menu.style.position = 'fixed';          // Fixed = positioned relative to the browser window
        menu.style.top = rect.bottom + 'px';    // Below the button
        menu.style.left = rect.left + 'px';     // Aligned to the button's left edge
        document.body.appendChild(menu);         // Add the menu to the page

        var isDragging = false;
        var dragOffsetX = 0;
        var dragOffsetY = 0;

        header.addEventListener('mousedown', function(e) {
            if (e.target === closeBtn) return;
            isDragging = true;
            var menuRect = menu.getBoundingClientRect();
            dragOffsetX = e.clientX - menuRect.left;
            dragOffsetY = e.clientY - menuRect.top;
            e.preventDefault();
        });

        document.addEventListener('mousemove', function(e) {
            if (!isDragging) return;
            menu.style.left = (e.clientX - dragOffsetX) + 'px';
            menu.style.top = (e.clientY - dragOffsetY) + 'px';
        });

        document.addEventListener('mouseup', function() {
            isDragging = false;
        });

            list.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            var item = e.target.closest('button');
            if (!item) return;
            
            var selectedPrompt = item.dataset.prompt;
            var selectedProvider = item.dataset.provider;
            var selectedCreateDraft = item.dataset.createDraft;
            var entryIdInput = document.querySelector('input[name="elementId"]');
            var siteIdInput = document.querySelector('input[name="siteId"]');
            
            document.querySelectorAll('.ai-wand-btn').forEach(function(b) { b.disabled = true; });
            
            menu.remove();

            // Show a loading spinner over the target field while we wait for the AI.
            // Find the field container, then float a fixed-position overlay over it.
            var loadingField = btn.closest('.field');
            var overlay = document.createElement('div');
            overlay.className = 'ai-loading-overlay';
            overlay.innerHTML = '<div class="spinner"></div>';
            if (loadingField) {
                var fieldRect = loadingField.getBoundingClientRect();
                overlay.style.position = 'fixed';
                overlay.style.top = fieldRect.top + 'px';
                overlay.style.left = fieldRect.left + 'px';
                overlay.style.width = fieldRect.width + 'px';
                overlay.style.height = fieldRect.height + 'px';
                document.body.appendChild(overlay);
            }

            // Grab whatever the user has currently typed (not yet saved to DB)
            var liveValues = {};
            document.querySelectorAll(
                'input[name^="fields["], textarea[name^="fields["]'
            ).forEach(function(el) {
                var m = el.name.match(/^fields\[([^\]]+)\]$/);
                if (m) liveValues[m[1]] = el.value;
            });

            var titleInput = document.querySelector('input[name="title"]');
            if (titleInput) liveValues.__title = titleInput.value;

            fetch(Craft.getActionUrl('craft-cp-ai/content/generate'), {
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
                    prompt: selectedPrompt,
                    createDraft: selectedCreateDraft,
                    liveValues: liveValues,
                })
            })
            .then(function(response) { 
                if (!response.ok) {
                    throw new Error('Server returned ' + response.status);
                }
                return response.json(); 
            })

            .then(function(data) {
                overlay.remove();
                document.querySelectorAll('.ai-wand-btn').forEach(function(b) { b.disabled = false; });
                if (data.error) {
                    alert('Error: ' + data.error);
                } else if (data.draftUrl) {
                    Craft.cp.displayNotice('Draft created!');
                    window.location.href = data.draftUrl;
                } else {
                    var fieldEl = document.querySelector('[name="fields[' + data.fieldHandle + ']"]');

                    // CKEditor stores its instance on a .ck-editor__editable descendant of the
                    // field container, not on the textarea. Search inside the field for one that
                    // actually carries a ckeditorInstance.
                    var editorInstance = fieldEl.ckeditorInstance;
                    if (!editorInstance) {
                        var fieldContainer = fieldEl.closest('.field');
                        if (fieldContainer) {
                            fieldContainer.querySelectorAll('.ck-editor__editable').forEach(function(el) {
                                if (el.ckeditorInstance && !editorInstance) {
                                    editorInstance = el.ckeditorInstance;
                                }
                            });
                        }
                    }

                    if (editorInstance) {
                        editorInstance.setData(data.generatedContent);
                    } else {
                        fieldEl.value = data.generatedContent;
                        fieldEl.dispatchEvent(new Event('input', { bubbles: true }));
                    }
                    
                    Craft.cp.displayNotice('Field filled — review and save when ready.');
                }

            })
            .catch(function(error) {
                overlay.remove();
                document.querySelectorAll('.ai-wand-btn').forEach(function(b) { b.disabled = false; });
                alert('Error: ' + error.message);
            });

        });
    }
});