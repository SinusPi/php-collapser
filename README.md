# php-collapser
Utility class for displaying PHP object or array structures in customizable form for debugging.

Useful when `var_dump`, `print_r`, `var_export` output too much information for easy reading, `serialize` is obviously out of the question, and you don't have the luxury or just prefer not to add `__debugInfo` to every class your application is using. Runtime-debugging IDEs have various object-inspection tools, so why not have something similar in PHP. Think `__debugInfo` on heavy steroids.

This tool prints - as an HTML table - a given object or array, recursively, optionally performing extra processing:
 - converting objects to arrays in custom ways (`__debugInfo`-style, using given callback functions),
 - formatting fields (using `sprintf` formatters or callback functions),
 - collapsing several fields into one (original fields remain expandable),
 - grouping keys
 
Use:

    $tc = new Collapser([
      'classes'=>[
        'name-of-class'=>[
          'arrayfier'=>function($obj,&$array) {  // convert the object into an array, without recursion, however you like
            return ['header'=>"Custom Name Of Object, optional"]
          },
          'collapse'=>[  // collapse several fields into one, keeping original contents intact for inspection
            [
	      'input'=>["first_name","last_name"],
              'output'=>"name",
              'format'=>"%s %s", // display as John Doe
              // or
              'formatter'=>function(&$vals) { return sprintf("%s. %s",substr($vals['first_name'],0,1),$vals['last_name']); } // display as J. Doe
            ],
          ],
          'format'=>[   // just reformat a field, replacing the original.
            [
              'field'=>"flags",
              'format'=>"...",
              // or
              'formatter'=>function(&$vals) { return "..."; }
            ]
          ],
          'groups'=>[   // group fields into sections
            'main'=>["account_code","username","name","email","state"],
          ],
        ],
      ],
    ]);
    $tc->show($object);


TO DO:
- first convert the object into a tree data structure, and only then display it in HTML table form (or any other form, make it extendable).
- finish Identifiers - support for functions detecting arrays as "objects" by their contents
