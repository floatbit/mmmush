function MMMush(config) {
    const embedDiv = document.getElementById('mmmush-embed');
    const chatContainer = document.getElementById('mmmush-chat-container');
    if (chatContainer) {
        chatContainer.innerHTML = `
            <div id="mmmush-chat-messages">
                <!-- Messages will be added here -->
            </div>
            <div>
                <form id="mmmush-chat-form" action="">
                    <div>
                        <textarea id="mmmush-message-input" name="message" placeholder="Message"></textarea>
                    </div>
                    <input type="hidden" name="ThreadId" value="${config.threadId}">
                    <div class="text-right">
                        <button type="submit">Send</button>
                        <span class="loading hidden">......</span>
                    </div>
                </form>
            </div>
        `;

        const form = document.getElementById('mmmush-chat-form');
        const messagesContainer = document.getElementById('mmmush-chat-messages');
        const messageInput = document.getElementById('mmmush-message-input');
        const submitButton = form.querySelector('button[type="submit"]');
        const loadingMessage = form.querySelector('.loading');
        const ajaxUrl = 'http://mmmush.localhost/wp-admin/admin-ajax.php';

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
            const threadId = formData.get('ThreadId');
            const message = formData.get('message');

            if (message.trim() === '') {
                return; // Do nothing if the message is empty
            }

            // Clear out the textarea message
            messageInput.value = '';

            // Show the loading message and hide submit button
            loadingMessage.classList.remove('hidden');
            submitButton.classList.add('hidden');

            // Add user message to the chat
            const userMessageElement = document.createElement('div');
            userMessageElement.className = 'message user';
            userMessageElement.textContent = message;
            messagesContainer.appendChild(userMessageElement);

            // Make an AJAX request to the server
            fetch(ajaxUrl, {
                method: 'POST',
                body: new URLSearchParams({
                    action: 'embed_send_message',
                    ThreadId: threadId,
                    message: message
                }),
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                }
            }).then(response => {
                if (!response.ok) throw new Error('Network response was not ok');
                handleStream(response.body);
            }).catch(error => {
                console.error('Error:', error);
            }).finally(() => {
                // Hide loading message and show submit button
                loadingMessage.classList.add('hidden');
                submitButton.classList.remove('hidden');
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

            let i = 0;
            const type = () => {
                if (i < text.length) {
                    element.innerHTML = text.substring(0, i + 1);
                    i++;
                    setTimeout(type, 10); // Adjust typing speed here
                }
            };
            type();
        }
    }
}
