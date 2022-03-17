const path = require('path');

var config = {
    entry: './source/js/_main.js',
    output: {
        path: path.resolve(__dirname, 'public','js'),
        filename: 'main.js',
    }
};

module.exports = (env, argv) => {    
      if (argv.mode === 'production') {
        config.mode = "production";
      }
      return config;
};