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
                    <textarea id="allybox-message-input" name="message" placeholder="Ask me about"></textarea>
                </div>
                <input type="hidden" name="assistantEmbedId" value="${config.assistantEmbedId}">
                <div class="text-right">
                    <button type="submit">Send</button>
                    <span class="loading hidden">......</span>
                </div>
            </form>
        </div>
        <div class="allybox-chat-footer">made on <a href="https://allybox.app" target="_blank">allybox</a></div>
        `;

        const form = document.getElementById('allybox-chat-form');
        const messagesContainer = document.getElementById('allybox-chat-messages');
        const messageInput = document.getElementById('allybox-message-input');
        const submitButton = form.querySelector('button[type="submit"]');
        const loadingMessage = form.querySelector('.loading');
        const ajaxUrl = `${BASE_URL}/wp-admin/admin-ajax.php`;

        const assistantEmbedId = document.querySelector('input[name="assistantEmbedId"]').value;
        try {
            const threadEmbedId = await checkAndCreateThread(assistantEmbedId);
            //console.log('Thread Embed ID:', threadEmbedId);

            // Show the embed
            embedDiv.style.display = 'flex';

            form.addEventListener('submit', (event) => {
                event.preventDefault();
                sendMessage();
            });

            messageInput.addEventListener('keydown', (event) => {
                if (event.shiftKey && event.key === 'Enter') {
                    event.preventDefault();
                    if (messageInput.value.trim() !== '') {
                        sendMessage();
                    }
                }
            });

            function sendMessage() {
                const formData = new FormData(form);
                const assistantEmbedId = formData.get('assistantEmbedId');
                const message = formData.get('message');

                if (message.trim() === '') {
                    return; // Do nothing if the message is empty
                }

                // Clear out the textarea message
                messageInput.value = '';

                // Show the loading message and hide submit button
                messageInput.classList.add('hidden');
                loadingMessage.classList.remove('hidden');
                submitButton.classList.add('hidden');

                // Add user message to the chat
                const userMessageElement = document.createElement('div');
                userMessageElement.className = 'message user';
                userMessageElement.textContent = message;
                messagesContainer.appendChild(userMessageElement);

                // Scroll to bottom after adding the user message
                scrollToBottom();

                // Make an AJAX request to the server
                fetch(ajaxUrl, {
                    method: 'POST',
                    body: new URLSearchParams({
                        action: 'embed_send_message',
                        AssistantEmbedId: assistantEmbedId,
                        ThreadEmbedId: threadEmbedId,
                        message: message
                    }),
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    }
                }).then(response => {
                    if (!response.ok) throw new Error('Network response was not ok');
                    handleStream(response.body);
                }).finally(() => {
                    // Hide loading message and show submit button
                    loadingMessage.classList.add('hidden');
                    submitButton.classList.remove('hidden');
                    messageInput.classList.remove('hidden');

                    // Scroll to bottom after loading is done
                    scrollToBottom();
                });
            }

            function handleStream(stream) {
                const reader = stream.getReader();
                const decoder = new TextDecoder();

                reader.read().then(function processText({ done, value }) {
                    if (done) {
                        return;
                    }

                    const text = decoder.decode(value, { stream: true });
                    const eventData = JSON.parse(text.replace('data: ', '').trim());
                    const message = eventData.message;

                    // Update the message container with typewriter effect
                    typeWriterEffect(message);

                    // Read the next chunk of the stream
                    return reader.read().then(processText.bind(this));
                }.bind(this));
            }

            function typeWriterEffect(text) {
                const element = document.createElement('div');
                element.className = 'message assistant';
                messagesContainer.appendChild(element);

                const htmlContent = marked.parse(text);
                let i = 0;
                const type = () => {
                    if (i < htmlContent.length) {
                        element.innerHTML = htmlContent.substring(0, i * 5);
                        i++;
                        setTimeout(type, 10); // Adjust typing speed here
                    }
                };
                type();
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

    function scrollToBottom() {
        const messagesContainer = document.getElementById('allybox-chat-messages');
        messagesContainer.scrollTop = messagesContainer.scrollHeight;
    }
}
