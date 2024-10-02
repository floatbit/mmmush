import SingleThread from '@/single-thread'
import SingleAssistant from '@/single-assistant'

(function ($) {
  if (document.querySelector('.single-thread')) {
    new SingleThread()
  }
  if (document.querySelector('.single-assistant')) {
    new SingleAssistant(document.querySelector('.single-assistant'))
  }
})($)