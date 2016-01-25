AsseticGulpBundle
============

This bundle provides a command `assetic:gulp` that generates a JSON file suitable for use with gulp

Example config
```js
[
    {
        "sources": [
            "\/home\/user\/project\/app\/..\/vendor\/twitter\/bootstrap\/dist\/js\/bootstrap.js"
        ],
        "destination": {
            "path": "\/home\/user\/project/app\/..\/web\/assetic",
            "file": "bootstrap_js.js"
        },
        "types": [
            "js"
        ]
    },
    {
        "sources": [
            "\/home\/user\/project\/app\/..\/vendor\/twitter\/bootstrap\/dist\/css\/bootstrap.css",
            "\/home\/user\/project\/app\/..\/vendor\/twitter\/bootstrap\/dist\/css\/bootstrap-theme.css"
        ],
        "destination": {
            "path": "\/home\/user\/project\/app\/..\/web\/assetic",
            "file": "bootstrap_css.css"
        },
        "types": [
            "css"
        ]
    }
]

```

Such file can be processed with [example gulpfile.js](https://github.com/biozshock/AsseticGulpBundle/blob/master/Resources/gulp/gulpfile.js):
