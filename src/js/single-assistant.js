export default class SingleAssistant {
  constructor(el) {
    this.el = el;
    this.init();
  }

  init() {
    this.setupDeleteFileButtons();
  }

  setupDeleteFileButtons() {
    const deleteButtons = this.el.querySelectorAll('.delete-file');
    deleteButtons.forEach(button => {
      button.addEventListener('click', (event) => {
        event.preventDefault();
        const fileItem = event.target.closest('.file');
        const confirmButton = fileItem.querySelector('.confirm-delete-file');
        confirmButton.classList.remove('hidden');
      });
    });

    const fileItems = this.el.querySelectorAll('.file');
    fileItems.forEach(fileItem => {
      fileItem.addEventListener('mouseleave', () => {
        const confirmButton = fileItem.querySelector('.confirm-delete-file');
        confirmButton.classList.add('hidden');
        
      });
    });
  }
}