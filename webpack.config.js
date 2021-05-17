const path = require('path');

module.exports = {
    entry: './source/js/_main.js',
    output: {
        path: path.resolve(__dirname, 'public','js'),
        filename: 'main.js',
    },
    mode: 'production'
};