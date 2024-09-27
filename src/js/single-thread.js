export default class SingleThread {
  constructor() {
    this.form = document.getElementById('chat-form');
    this.messagesContainer = document.getElementById('chat-messages');
    this.init();
  }

  init() {
    this.form.addEventListener('submit', this.handleSubmit.bind(this));
  }

  async handleSubmit(event) {
    event.preventDefault();
    const formData = new FormData(this.form);
    const message = formData.get('message');
    const threadId = formData.get('ThreadId');
    const assistantId = formData.get('AssistantId');

    if (!message.trim()) return;

    this.addMessage(message, 'user');
    this.form.reset();

    try {
      const response = await this.sendMessage(threadId, assistantId, message);
      if (response.success && response.data && response.data.content) {
        this.addMessage(response.data.content, 'assistant');
      } else {
        throw new Error('Invalid response format');
      }
    } catch (error) {
      console.error('Error sending message:', error);
      this.addMessage('An error occurred. Please try again.', 'error');
    }
  }

  addMessage(content, sender) {
    const messageElement = document.createElement('div');
    messageElement.classList.add('message', sender);
    messageElement.textContent = content;
    this.messagesContainer.appendChild(messageElement);
    this.messagesContainer.scrollTop = this.messagesContainer.scrollHeight;
  }

  async sendMessage(threadId, assistantId, message) {
    const response = await fetch('/wp-admin/admin-ajax.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded',
      },
      body: new URLSearchParams({
        action: 'send_message',
        thread_id: threadId,
        assistant_id: assistantId,
        message: message,
      }),
    });

    if (!response.ok) {
      throw new Error('Network response was not ok');
    }

    return await response.json();
  }
}