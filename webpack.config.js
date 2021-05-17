const path = require('path');

module.exports = {
    entry: './source/js/_main.js',
    output: {
        path: path.resolve(__dirname, 'public','js'),
        filename: 'main.js',
    },
    mode: 'production',
    module: {
        rules:[
            {
                test: /.(scss|css)$/,
                use:
                    [
                        {
                            options: { reloadAll: true },
                        },
                        'css-loader',
                        'sass-loader',
                        'node-sass'
                    ]
            }
        ]
    }
};