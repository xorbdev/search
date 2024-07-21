# Redirects

Often times you will remove or move pages around on your site. Previously
indexed pages that are no longer available will be marked with a 404 error
code.

Using this information you can easily set up redirects.

<img src="https://xorb.dev/content/add-redirect.png" alt="Add redirect interface">

## Regular Expressions

In the above example, all pages under `/cookie/` will be redirected to instead
be under `/biscuit/`.

!!!info Redirects support [PCRE Pattern Syntax](https://www.php.net/manual/en/reference.pcre.pattern.syntax.php).
By default any `/` and `?` not inside parenthesis will be escaped. To prevent
this escaping, include opening and closing forward slashes and optional flags.
The insensitive flag is included by default unless overridden. !!!

## Tracking 404 Errors

Any previously indexed page will get marked with a 404 error if it no longer
exists.

In order to track 404 errors on pages that are not indexed, you will need to
enable the `Track Page Hits` and `Track 404 Errors` settings.

<img src="https://xorb.dev/content/track-page-hits.png" alt="Track page hits setting">

<img src="https://xorb.dev/content/track-404-errors.png" alt="Track 404 errors setting">

With these two settings enabled, any page that a user landed on that resulted
in a 404 will be added to the result index with an error status.
