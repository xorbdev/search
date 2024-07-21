# Query Param Rules

The search plugin cannot tell which query params are valid and which are
not. In order to prevent the search results from being flooded with duplicate
pages, query params are ignored by default.

With query param rules you can inform the indexer to take specific queries
on specific URIs into account.

The below example shows how you would setup query param rules for pagination.

<img src="https://xorb.dev/content/add-query-param-rule.png" alt="Add query param rule interface">

!!!info You will need to update your search index before any changes to your
ignore rules take effect. !!!

## Regular Expressions

!!!info Redirects support [PCRE Pattern Syntax](https://www.php.net/manual/en/reference.pcre.pattern.syntax.php).
By default any `/` and `?` not inside parenthesis will be escaped. To prevent
this escaping, include opening and closing forward slashes and optional flags.
The insensitive flag is included by default unless overridden. !!!
