# GX2CMS Templating Engine

Required:
- xamin/handlebars
- masterminds/html5

To have the package self-contained, both libraries got extracted and integrated into this package.

## Reserved keywords:
- component-clientlib-root, for the root of the web root of the clientlibs (i.e. /clientlibs/component/)
- template-clientlib-root, for the root of the web root of the clientlibs (i.e. /clientlibs/template/)
- <gx2cms-inject-stylesheet>${component-clientlib-root}/path/to/stylesheet.css</gx2cms-inject-stylesheet>. Used in component (recommended) or template.
- <gx2cms-inject-javascript>${component-clientlib-root}/path/to/javascript.js</gx2cms-inject-javascript>. Used in component (recommended) or template.
- gx2cms-stylesheet-placeholder, the place holder for stylesheet injection. Used in template.
- gx2cms-javascript-placeholder, the place holder for javascript injection. Use in template.