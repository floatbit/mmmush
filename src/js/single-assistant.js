export default class SingleAssistant {
  constructor(el) {
    this.el = el;
    this.init();
  }

  init() {
    this.setupDeleteFileButtons();
    this.setupShowMoreButton();
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

  setupShowMoreButton() {
    const showMoreButton = this.el.querySelector('.show-more');
    showMoreButton.addEventListener('click', (event) => {
      event.preventDefault();
      this.el.classList.toggle('instructions-expanded');
      // Add the class to div.instructions
      const instructionsDiv = this.el.querySelector('div.instructions');
      instructionsDiv.classList.toggle('instructions-expanded');
      
      // Remove the show more button
      showMoreButton.classList.add('hidden'); // or showMoreButton.style.display = 'none';
    });
  }
}
