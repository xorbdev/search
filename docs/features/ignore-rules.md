# Ignore Rules

Ignore rules allow you to specify pages you do not want indexed.

Let's say your site includes a calendar system that allows you to navigate
infinitely into the past or future. It would not be very desirable to index all
those pages.

<img src="/content/add-ignore-rule.png" alt="Add ignore rule interface">

## Regular Expressions

In the above example, all items under the `/calendar` path would be ignored. This
might not be desirable with your setup. To get around this you can use regular
expressions to target dynamic URIs.

The below example would ignore the URI `/calendar/2024-04`.

<img src="/content/add-ignore-rule-regex.png" alt="Add ignore rule interface using regular expressions">

!!!info Redirects support [PCRE Pattern Syntax](https://www.php.net/manual/en/reference.pcre.pattern.syntax.php).
By default any `/` and `?` not inside parenthesis will be escaped. To prevent
this escaping, include opening and closing forward slashes and optional flags.
The insensitive flag is included by default unless overriden. !!!

## Ignore Absolutely

If you are using the sitemap feature, there are times you may wish to include
a page in your sitemap, but not in your search results. Leave this untoggled if
that is the case.

!!!info You will need to update your search index before any changes to your
ignore rules take effect. !!!
