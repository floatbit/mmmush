import { marked } from 'marked';

export default class SingleThread {
  constructor() {
    this.form = document.getElementById('chat-form');
    this.messagesContainer = document.getElementById('chat-messages');
    this.messageInput = document.getElementById('message-input');
    this.submitButton = this.form.querySelector('button[type="submit"]');
    this.loadingMessage = this.form.querySelector('.loading');
    this.ajaxUrl = '/wp-admin/admin-ajax.php';
    this.init();
  }

  init() {
    this.form.addEventListener('submit', this.handleSubmit.bind(this));
    this.messageInput.addEventListener('keydown', this.handleKeyDown.bind(this));
  }

  handleSubmit(event) {
    event.preventDefault();
    this.sendMessage();
  }

  handleKeyDown(event) {
    if (event.shiftKey && event.key === 'Enter') {
      event.preventDefault();
      if (this.messageInput.value.trim() !== '') {
        this.sendMessage();
      }
    }
  }

  sendMessage() {
    const formData = new FormData(this.form);
    const threadId = formData.get('ThreadId');
    const assistantId = formData.get('AssistantId');
    const message = formData.get('message').trim();

    if (!message) return;

    this.messageInput.value = '';
    this.toggleLoading(true);

    this.addMessage('user', message);

    const element = this.addMessage('assistant', '');
    const url = `/am-endpoint/?ThreadId=${threadId}&AssistantId=${assistantId}&message=${encodeURIComponent(message)}`;
    const eventSource = new EventSource(url);

    let fullMessage = '';
    eventSource.onmessage = (event) => {
      const eventData = JSON.parse(event.data.replace('data: ', '').trim());
      if (eventData.event === 'message') {
        fullMessage += eventData.message;
        element.innerHTML = marked.parse(fullMessage);
      } else if (eventData.event === 'complete') {
        this.handleComplete(eventSource);
      }
    };

    eventSource.onerror = () => this.handleError(eventSource);
  }

  addMessage(type, content) {
    const messageElement = document.createElement('div');
    messageElement.className = `message ${type}`;
    messageElement.textContent = content;
    this.messagesContainer.appendChild(messageElement);
    return messageElement;
  }

  toggleLoading(isLoading) {
    this.loadingMessage.classList.toggle('hidden', !isLoading);
    this.submitButton.classList.toggle('hidden', isLoading);
  }

  handleComplete(eventSource) {
    console.log('Stream completed');
    eventSource.close();
    this.toggleLoading(false);
  }

  handleError(eventSource) {
    console.error('EventSource failed');
    eventSource.close();
    this.toggleLoading(false);
  }
}