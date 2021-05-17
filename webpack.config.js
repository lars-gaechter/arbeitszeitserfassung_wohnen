const path = require('path');

module.exports = {
    entry: './src/js/app.js',
    output: {
        path: path.resolve(__dirname, 'public','js'),
        filename: 'app.js',
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