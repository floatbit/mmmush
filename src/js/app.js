import SingleThread from '@/single-thread'

(function ($) {
  if (document.querySelector('.single-thread')) {
    new SingleThread()
  }
})($)