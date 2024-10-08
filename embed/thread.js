const scriptElement = document.currentScript;
const scriptSrc = scriptElement.src;
const BASE_URL = new URL(scriptSrc).origin;

const markedScript = document.createElement('script');
markedScript.src = `${BASE_URL}/embed//marked.min.js`;
document.head.appendChild(markedScript);

const styleSheet = document.createElement('link');
styleSheet.rel = 'stylesheet';
styleSheet.href = `${BASE_URL}/embed/styles.css`;
document.head.appendChild(styleSheet);

async function allybox(config) {
    const embedDiv = document.getElementById('allybox-embed');
    const chatContainer = document.getElementById('allybox-chat-container');
    if (chatContainer) {
        chatContainer.innerHTML = `
        <div id="allybox-chat-messages">
            <!-- Messages will be added here -->
        </div>
        <div>
            <form id="allybox-chat-form" action="">
                <div class="textarea-container">
                    <textarea id="allybox-message-input" name="message" placeholder="${config.placeholderText || 'Ask me a question'}"></textarea>
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
        const endpoint = `${BASE_URL}/am-endpoint/`;
        const stopMessage = form.querySelector('.stop-message');
        const assistantEmbedId = document.querySelector('input[name="assistantEmbedId"]').value;
        let eventSource = null;
        let runId = null;
        
        try {
            const threadEmbedId = await checkAndCreateThread(assistantEmbedId);
            console.log('Thread Embed ID:', threadEmbedId);

            // Show the embed
            embedDiv.style.display = 'flex';

            form.addEventListener('submit', (event) => {
                event.preventDefault();
                if (messageInput.value.trim() === '') {
                    messageInput.focus();
                } else {
                    sendMessage();
                }
            });

            messageInput.addEventListener('keydown', (event) => {
                if ((event.shiftKey || event.metaKey) && event.key === 'Enter') {
                    event.preventDefault();
                    if (messageInput.value.trim() !== '') {
                        sendMessage();
                    }
                }
            });

            stopMessage.addEventListener('click', () => {
                //console.log('Stop message clicked');
                stopRun();
            });
            
            function sendMessage() {
                if (!loadingMessage.classList.contains('hidden')) {
                    return; // Check if loading is active
                }

                const formData = new FormData(form);
                const assistantEmbedId = formData.get('assistantEmbedId');
                const message = formData.get('message').trim();

                if (!message) return;

                messageInput.value = '';
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
                        stopMessage.classList.remove('hidden');
                        if (eventData.run_id) {
                            runId = eventData.run_id;
                        }
                    } else if (eventData.event === 'complete') {
                        handleComplete(eventSource);
                    }
                };

                eventSource.onerror = () => handleError(eventSource);
            }

            function addMessage(type, content) {
                const messageElement = document.createElement('div');
                messageElement.className = `message ${type}`;
                messageElement.textContent = content;
                messagesContainer.appendChild(messageElement);
                return messageElement;
            }

            function toggleLoading(isLoading) {
                loadingMessage.classList.toggle('hidden', !isLoading);
                submitButton.classList.toggle('hidden', isLoading);
                if (isLoading === false) {
                    stopMessage.classList.add('hidden');
                }
            }

            function handleComplete(eventSource) {
                //console.log('Stream completed');
                eventSource.close();
                runId = null;
                toggleLoading(false);
            }

            function handleError(eventSource) {
                //console.error('EventSource failed');
                eventSource.close();
                runId = null;
                toggleLoading(false);
            }

            function stopRun() {
                if (runId) {
                    fetch(`${BASE_URL}/wp-admin/admin-ajax.php`, {
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
                        if (!response.ok) {
                            throw new Error('Network response was not ok');
                        }
                        eventSource.close();
                        runId = null;
                        toggleLoading(false);
                    })
                    .catch(error => {
                        console.error('Error stopping run:', error);
                    });
                }
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
    function checkAndCreateThread(assistantEmbedId) {
        return new Promise((resolve, reject) => {
            const cookieName = `allybox.threads.${assistantEmbedId}.threadEmbedId`;
            let threadEmbedId = getCookie(cookieName);

            if (threadEmbedId) {
                resolve(threadEmbedId);
                getPreviousMessages(threadEmbedId, assistantEmbedId); // Call to get previous messages
            } else {
                fetch(`${BASE_URL}/wp-admin/admin-ajax.php`, {
                    method: 'POST',
                    body: new URLSearchParams({
                        action: 'create_new_thread',
                        assistantEmbedId: assistantEmbedId
                    }),
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.data.thread_embed_id) {
                        setCookie(cookieName, data.data.thread_embed_id, 7); // Cookie expires in 7 days
                        resolve(data.data.thread_embed_id);
                        getPreviousMessages(data.data.thread_embed_id, assistantEmbedId); // Call to get previous messages
                    } else {
                        reject(new Error('Failed to create a new thread: ' + data.message));
                    }
                })
                .catch(error => {
                    reject(error);
                });
            }
        });
    }

    function getPreviousMessages(threadEmbedId, assistantEmbedId) {
        fetch(`${BASE_URL}/wp-admin/admin-ajax.php`, {
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
            if (data.success) {
                if (data.data.messages) {
                    for (const message of data.data.messages) {
                        const element = addMessage(message.role, '');
                        let messageText = message.content;
                        if (message.role === 'assistant') {
                            element.innerHTML = marked.parse(messageText);
                        } else {
                            element.textContent = messageText;
                        }
                    }
                    setTimeout(scrollToBottom, 1000);
                }
            }
        })
        .catch(error => {
            console.error('Error fetching previous messages:', error);
        });
    }

    function scrollToBottom() {
        const messagesContainer = document.getElementById('allybox-chat-messages');
        messagesContainer.scrollTop = messagesContainer.scrollHeight;
    }

    function removeSourceTags(input) {
        // Regular expression to match the pattern 【number:number†source】
        const regex = /【\d+:\d+†source】/g;
        // Replace the matched pattern with an empty string
        return input.replace(regex, '');
    }

    document.addEventListener('DOMContentLoaded', () => {
        document.body.addEventListener('click', (event) => {
            const link = event.target.closest('a');
            if (link && !link.target) { // Check if the link does not already have a target
                event.preventDefault(); // Prevent the default action
                window.open(link.href, '_blank'); // Open the link in a new window
            }
        });
    });
}
