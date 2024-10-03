import { marked } from 'marked';

export default class SingleThread {
  constructor() {
    this.form = document.getElementById('chat-form');
    this.messagesContainer = document.getElementById('chat-messages');
    this.messageInput = document.getElementById('message-input');
    this.submitButton = this.form.querySelector('button[type="submit"]');
    this.loadingMessage = this.form.querySelector('.loading');
    this.ajaxUrl = '/wp-admin/admin-ajax.php'; // Hardcoded admin-ajax.php URL
    this.init();

    this.ttt();
  }

  init() {
    this.form.addEventListener('submit', (event) => {
      event.preventDefault();
      this.sendMessage();
    });

    this.messageInput.addEventListener('keydown', (event) => {
      if (event.shiftKey && event.key === 'Enter') {
        event.preventDefault();
        if (this.messageInput.value.trim() !== '') {
          this.sendMessage();
        }
      }
    });
  }

  sendMessage() {
    const formData = new FormData(this.form);
    const threadId = formData.get('ThreadId');
    const assistantId = formData.get('AssistantId');
    const message = formData.get('message');

    if (message.trim() === '') {
      return; // Do nothing if the message is empty
    }

    // Clear out the textarea message
    this.messageInput.value = '';

    // Show the loading message and hide submit button
    this.loadingMessage.classList.remove('hidden');
    this.submitButton.classList.add('hidden');

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
    }).finally(() => {
      // Hide loading message and show submit button
      this.loadingMessage.classList.add('hidden');
      this.submitButton.classList.remove('hidden');
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

    const htmlContent = marked.parse(text);
    let i = 0;
    const type = () => {
      if (i < htmlContent.length) {
        element.innerHTML = htmlContent.substring(0, i + 1);
        i++;
        setTimeout(type, 0); // Adjust typing speed here
      }
    };
    type();
  }

  ttt() {
   
    console.log('ttt');
    
    const eventSource = new EventSource('/sse-endpoint/');

// Log each event as it's received
eventSource.onmessage = function(event) {
    console.log('Received message:', event.data);
    };

    // Handle connection errors
    eventSource.onerror = function(error) {
      console.error('EventSource failed:', error);
    };


  }

}