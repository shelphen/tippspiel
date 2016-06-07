<html>
    <head>
        <base href="/">

        <title>Angular 2 QuickStart</title>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <!-- Material Design Lite -->
        <link rel="stylesheet" href="/node_modules/material-design-lite/material.min.css">
        <script src="/node_modules/material-design-lite/material.min.js"></script>
        <link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons">

        <link rel="stylesheet" href="/node_modules/ng2-toastr/bundles/ng2-toastr.min.css">

        <link rel="stylesheet" href="styles.css">

        <!-- Polyfill(s) for older browsers -->
        <script src="node_modules/es6-shim/es6-shim.min.js"></script>

        <script src="node_modules/zone.js/dist/zone.js"></script>
        <script src="node_modules/reflect-metadata/Reflect.js"></script>
        <script src="node_modules/systemjs/dist/system.src.js"></script>

        <script src="systemjs.config.js"></script>
        <script>
            System.import('app').catch(function(err){ console.error(err); });
        </script>
    </head>
    <body>
        <soe-app>Loading...</soe-app>
    </body>
</html>
