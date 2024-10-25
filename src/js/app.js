import SingleThread from '@/single-thread'
import SingleAssistant from '@/single-assistant'
//import UserFilesCreate from '@/blocks/user-files-create'

(function ($) {
  if (document.querySelector('.single-thread')) {
    new SingleThread()
  }
  if (document.querySelector('.single-assistant')) {
    new SingleAssistant(document.querySelector('.single-assistant'))
  }
  // if (document.querySelector('.block-user-files-create')) {
  //   new UserFilesCreate()
  // }
})($)
