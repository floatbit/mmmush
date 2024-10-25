import Dropzone from 'dropzone';

export default class UserFilesCreate {
  constructor(el) {
    this.init()
  }

  init() {
    // Initialize Dropzone
    Dropzone.autoDiscover = false; // Prevent auto initialization

    const dropzone = new Dropzone("#fileUploadForm", {
      maxFilesize: 15, // Max file size in MB
      acceptedFiles: ".pdf, .json, .txt",
      addRemoveLinks: true,
    });
  }
}
