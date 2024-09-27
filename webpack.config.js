const path = require('path');

module.exports = {
  resolve: {
    alias: {
      '@': path.resolve('src/js'),
    },
  },

  output: {
    chunkFilename: 'js/[name].js?id=[chunkhash]',
  },
}