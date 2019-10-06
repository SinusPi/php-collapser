# php-collapser
Utility class for displaying PHP object or array structures in customizable form for debugging.

Useful when `var_dump`, `print_r`, `var_export` output too much information for easy reading, `serialize` is obviously out of the question, and you don't have the luxury or just prefer not to add `__debugInfo` to every class your application is using. Runtime-debugging IDEs have various object-inspection tools, so why not have something similar in PHP. Think `__debugInfo` on heavy steroids.

This tool prints - as an HTML table - a given object or array, recursively, optionally performing extra processing:
 - converting objects to arrays in custom ways (`__debugInfo`-style, using given callback functions),
 - formatting fields (using `sprintf` formatters or callback functions),
 - collapsing several fields into one (original fields remain expandable),
 - grouping keys
 
