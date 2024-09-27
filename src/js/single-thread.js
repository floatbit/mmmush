export default class SingleThread {
  constructor() {
    this.form = document.getElementById('chat-form');
    this.messagesContainer = document.getElementById('chat-messages');
    this.messageInput = document.getElementById('message-input');
    this.ajaxUrl = '/wp-admin/admin-ajax.php'; // Hardcoded admin-ajax.php URL
    this.init();
  }

  init() {
    this.form.addEventListener('submit', (event) => {
      event.preventDefault();
      this.sendMessage();
    });
  }

  sendMessage() {
    const formData = new FormData(this.form);
    const threadId = formData.get('ThreadId');
    const assistantId = formData.get('AssistantId');
    const message = formData.get('message');

    // Add user message to the chat
    const userMessageElement = document.createElement('div');
    userMessageElement.className = 'message user';
    userMessageElement.textContent = message;
    this.messagesContainer.appendChild(userMessageElement);

    // Make an AJAX request to the server
    fetch(this.ajaxUrl, {
      method: 'POST',
      body: new URLSearchParams({
        action: 'send_message',
        ThreadId: threadId,
        AssistantId: assistantId,
        message: message
      }),
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded'
      }
    }).then(response => {
      if (!response.ok) throw new Error('Network response was not ok');
      this.handleStream(response.body);
    }).catch(error => {
      console.error('Error:', error);
    });
  }

  handleStream(stream) {
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
      this.typeWriterEffect(message);

      // Read the next chunk of the stream
      return reader.read().then(processText.bind(this));
    }.bind(this));
  }

  typeWriterEffect(text) {
    const element = document.createElement('div');
    element.className = 'message assistant';
    this.messagesContainer.appendChild(element);
    let i = 0;
    const type = () => {
      if (i < text.length) {
        element.innerHTML += text.charAt(i);
        i++;
        setTimeout(type, 50); // Adjust typing speed here
      }
    };
    type();
  }
}