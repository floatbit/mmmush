const allyboxScriptElement = document.currentScript;
const allyboxScriptSrc = allyboxScriptElement.src;
const allyboxBaseUrl = new URL(allyboxScriptSrc).origin;

// This function initializes the allybox chat interface.
async function allybox(config) {
    const embedDiv = document.getElementById('allybox-embed');
    const chatContainer = document.getElementById('allybox-chat-container');
    const hideHistory = config.hideHistory || false; // Get hideHistory from config
    const initialMessageText = config.initialMessage || config.initialMessageText || null; // Support both naming conventions
    const initialTopics = config.initialTopics || null;

    // Load marked library
    const markedScript = document.createElement('script');
    markedScript.src = `${allyboxBaseUrl}/embed/marked.min.js`;
    
    // Wait for marked script to load
    const markedLoaded = new Promise((resolve) => {
        markedScript.onload = resolve;
    });
    document.head.appendChild(markedScript);

    const styleSheet = document.createElement('link');
    styleSheet.rel = 'stylesheet';
    styleSheet.href = `${allyboxBaseUrl}/embed/styles.css`;
    document.head.appendChild(styleSheet);


    // Sets up the chat interface with a form for user input and a container for displaying messages.
    if (chatContainer) {    
        await markedLoaded;
        chatContainer.innerHTML = `
        <div id="allybox-chat-messages"></div>
        <div>
            <form id="allybox-chat-form" action="">
                <div class="textarea-container">
                    <textarea id="allybox-message-input" name="message" placeholder="${config.placeholderText || 'Ask me a question'}" maxlength="200"></textarea>
                    <span class="allybox-input-length">
                        <span class="allybox-input-length-current"></span>
                    </span>
                </div>
                <div class="text-right">
                    <button type="submit">Send</button>
                    <div class="loader hidden">
                        <span class="spinner"></span>
                        <span class="stop-message hidden"></span>
                    </div>
                </div>
                <input type="hidden" name="assistantEmbedId" value="${config.assistantEmbedId}">
            </form>
        </div>
        <div class="allybox-chat-footer">made on <a href="https://allybox.app" target="_blank">allybox</a></div>
        `;

        const form = document.getElementById('allybox-chat-form');
        const messagesContainer = document.getElementById('allybox-chat-messages');
        const messageInput = document.getElementById('allybox-message-input');
        const submitButton = form.querySelector('button[type="submit"]');
        const loadingMessage = form.querySelector('.loader');
        const endpoint = `${allyboxBaseUrl}/am-endpoint/`;
        const stopMessage = form.querySelector('.stop-message');
        const assistantEmbedId = document.querySelector('input[name="assistantEmbedId"]').value;
        let eventSource = null;
        let runId = null;

        // This function adds a message to the chat interface.
        function addMessage(type, content) {
            console.log('addMessage', type, content);
            const messageElement = document.createElement('div');
            messageElement.className = `message ${type}`;
            if (type === 'assistant' || type === 'initial' || type === 'topics') {
                if (content) {
                    if (typeof marked !== 'undefined') {
                        messageElement.innerHTML = marked.parse(content);
                    } else {
                        // Fallback if marked isn't loaded
                        messageElement.textContent = content;
                    }
                } else {
                    messageElement.innerHTML = '<span class="assistant-loading"></span>';
                }
            } else {
                messageElement.textContent = content;
            }
            messagesContainer.appendChild(messageElement);
            scrollToBottom(); // Make sure to scroll after adding content
            return messageElement;
        }

        function addInitialMessage(messageText) {
            if (messageText) {
                addMessage('initial', messageText);
            }
            if (initialTopics) {
                const topicLinks = Object.entries(initialTopics).map(([key, value]) => `<a href="#" class="allybox-topic">${key}</a>`).join('');
                addMessage('topics', `${topicLinks}`);

            }
        }

        // This function smoothly scrolls to the bottom of the chat messages.
        function scrollToBottom() {
            const messagesContainer = document.getElementById('allybox-chat-messages');
            const targetScrollTop = messagesContainer.scrollHeight;
            const startScrollTop = messagesContainer.scrollTop;
            const distance = targetScrollTop - startScrollTop;
            const duration = 500; // duration in milliseconds
            let startTime = null;

            function animation(currentTime) {
                if (!startTime) startTime = currentTime;
                const timeElapsed = currentTime - startTime;
                const progress = Math.min(timeElapsed / duration, 1); // Ensure progress does not exceed 1
                const easeInOutQuad = progress < 0.5 ? 2 * progress * progress : -1 + (4 - 2 * progress) * progress; // Easing function

                messagesContainer.scrollTop = startScrollTop + distance * easeInOutQuad;

                if (timeElapsed < duration) {
                    requestAnimationFrame(animation);
                }
            }

            requestAnimationFrame(animation);
        }

        // This function checks if a thread cookie exists for a given assistantEmbedId. 
        // If it does, it resolves with the existing threadEmbedId and fetches previous messages. 
        // If it doesn't, it creates a new thread by sending a POST request to the server, 
        // sets a cookie with the new threadEmbedId, and also fetches previous messages for the new thread.
        try {
            const threadEmbedId = await checkAndCreateThread(assistantEmbedId, hideHistory);
            console.log('Thread Embed ID:', threadEmbedId);
            embedDiv.style.display = 'flex';

            form.addEventListener('submit', (event) => {
                event.preventDefault();
                if (messageInput.value.trim()) {
                    sendMessage();
                } else {
                    messageInput.focus();
                }
            });

            // This function handles the keydown event for the message input field.
            // It checks if the shift key or meta key (on Mac) is pressed and if the Enter key is pressed.
            // If so, it prevents the default action and sends the message.
            messageInput.addEventListener('keydown', (event) => {
                if ((event.shiftKey || event.metaKey) && event.key === 'Enter') {
                    event.preventDefault();
                    if (messageInput.value.trim()) {
                        sendMessage();
                    }
                }
            });

            // Add event listener to the message input
            messageInput.addEventListener('input', updateInputLength);

            stopMessage.addEventListener('click', stopRun);

            // This function sends a message to the assistant and displays the response.
            async function sendMessage() {
                if (!loadingMessage.classList.contains('hidden')) return;

                const formData = new FormData(form);
                const message = formData.get('message').trim();

                if (!message) return;

                messageInput.value = '';
                updateInputLength();
                toggleLoading(true);
                addMessage('user', message);

                const element = addMessage('assistant', '');
                const url = `${endpoint}?ThreadEmbedId=${threadEmbedId}&AssistantEmbedId=${assistantEmbedId}&message=${encodeURIComponent(message)}`;
                eventSource = new EventSource(url);
                scrollToBottom();

                let fullMessage = '';
                eventSource.onmessage = (event) => {
                    const eventData = JSON.parse(event.data.replace('data: ', '').trim());
                    if (eventData.event === 'message') {
                        fullMessage += eventData.message;
                        const cleanedMessage = removeSourceTags(fullMessage);
                        element.innerHTML = marked.parse(cleanedMessage);
                        linksNewWindow(element);
                        stopMessage.classList.remove('hidden');
                        if (eventData.run_id) runId = eventData.run_id;
                    } else if (eventData.event === 'complete') {
                        handleComplete(eventSource);
                    }
                };

                eventSource.onerror = () => handleError(eventSource);
            }

            // This function toggles the loading state of the submit button.
            function toggleLoading(isLoading) {
                loadingMessage.classList.toggle('hidden', !isLoading);
                submitButton.classList.toggle('hidden', isLoading);
                if (!isLoading) stopMessage.classList.add('hidden');
            }

            // This function handles the completion of the message stream.
            function handleComplete(eventSource) {
                eventSource.close();
                runId = null;
                toggleLoading(false);
            }

            // This function handles errors during the event source connection.
            function handleError(eventSource) {
                eventSource.close();
                runId = null;
                toggleLoading(false);
            }

            // This function stops the current run of the assistant.
            function stopRun() {
                if (runId) {
                    fetch(`${allyboxBaseUrl}/wp-admin/admin-ajax.php`, {
                        method: 'POST',
                        body: new URLSearchParams({
                            action: 'stop_run',
                            RunId: runId,
                            ThreadEmbedId: threadEmbedId
                        }),
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded'
                        }
                    })
                    .then(response => {
                        if (!response.ok) throw new Error('Network response was not ok');
                        eventSource.close();
                        runId = null;
                        toggleLoading(false);
                    })
                    .catch(error => console.error('Error stopping run:', error));
                }
            }

            // This function sets links to open in a new window.
            function linksNewWindow(element) {
                const links = element.querySelectorAll('a');
                links.forEach(link => link.setAttribute('target', '_blank'));
            }

            // This function removes source tags from the message content.
            function removeSourceTags(input) {
                const regex = /【\d+:\d+†source】/g;
                return input.replace(regex, '');
            }

            // Function to update the input length percentage
            function updateInputLength() {
                const maxLength = 200; // Maximum length of the input
                const currentLength = messageInput.value.length; // Current length of the input
                const percentage = (currentLength / maxLength) * 100; // Calculate percentage

                // Update the width of the .allybox-input-length-current element
                const inputLengthCurrent = document.querySelector('.allybox-input-length-current');
                inputLengthCurrent.style.width = `${percentage}%`;
            }

        } catch (error) {
            console.error('Error:', error);
        }
    }

    // Function to get a cookie by name
    function getCookie(name) {
        const value = `; ${document.cookie}`;
        const parts = value.split(`; ${name}=`);
        if (parts.length === 2) return parts.pop().split(';').shift();
    }

    // Function to set a cookie
    function setCookie(name, value, days) {
        const d = new Date();
        d.setTime(d.getTime() + (days * 24 * 60 * 60 * 1000));
        const expires = `expires=${d.toUTCString()}`;
        document.cookie = `${name}=${value};${expires};path=/`;
    }

    // Function to check if the thread cookie exists and create a new thread if it doesn't
    async function checkAndCreateThread(assistantEmbedId, hideHistory) {
        return new Promise(async (resolve, reject) => {
            const cookieName = `allybox.threads.${assistantEmbedId}.threadEmbedId`;
            let threadEmbedId = getCookie(cookieName);

            if (threadEmbedId) {
                resolve(threadEmbedId);
                if (!hideHistory) {
                    await getPreviousMessages(threadEmbedId, assistantEmbedId);
                }
                addInitialMessage(initialMessageText);
            } else {
                try {
                    const response = await fetch(`${allyboxBaseUrl}/wp-admin/admin-ajax.php`, {
                        method: 'POST',
                        body: new URLSearchParams({
                            action: 'create_new_thread',
                            assistantEmbedId: assistantEmbedId
                        }),
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded'
                        }
                    });
                
                    const data = await response.json();
                
                    if (data.success && data.data.thread_embed_id) {
                        const newThreadId = data.data.thread_embed_id;
                        setCookie(cookieName, newThreadId, 2000);
                        if (!hideHistory) {
                            await getPreviousMessages(newThreadId, assistantEmbedId);
                        }
                        addInitialMessage(initialMessageText);
                        resolve(newThreadId);
                    } else {
                        reject(new Error('Failed to create a new thread: ' + data.message));
                    }
                } catch (error) {
                    reject(error);
                }
            }
        });
    }

    // This function fetches previous messages for a given threadEmbedId and assistantEmbedId.
    function getPreviousMessages(threadEmbedId, assistantEmbedId) {
        return fetch(`${allyboxBaseUrl}/wp-admin/admin-ajax.php`, {
            method: 'POST',
            body: new URLSearchParams({
                action: 'get_previous_messages',
                ThreadEmbedId: threadEmbedId,
                AssistantEmbedId: assistantEmbedId
            }),
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success && data.data.messages) {
                for (const message of data.data.messages) {
                    const element = addMessage(message.role, '');
                    let messageText = removeSourceTags(message.content);
                    if (message.role === 'assistant') {
                        element.innerHTML = marked.parse(messageText);
                    } else {
                        element.textContent = messageText;
                    }
                    linksNewWindow(element);
                }
                setTimeout(scrollToBottom, 1000);
            }
        })
        .catch(error => {
            console.error('Error fetching previous messages:', error);
        });
    }
}