# Variables

## Querying Results

To query your results, you can call `craft.xsearch.results` in your twig
template.

This will return a `Result` element configured in search mode for the current site.

```twig
{% set searchQuery = craft.app.request.getParam('q') %}
{% set offset = offset ?? 0 %}
{% set limit = limit ?? 20 %}

{% set resultsQuery = craft.xsearch.results(searchQuery).offset(offset).limit(limit) %}
```

## Example Output

```twig
{% set results = resultsQuery.all() %}

{% for result in results %}
<article>
  <h2>
    <a href="{{ result.resultUrl }}">
      {{ result.resultTitle }}
    </a>
  </h2>
  {% if result.resultDescription %}
    <p>
      {{ result.resultDescription }}
    </p>
  {% endif %}
</article>
{% endfor %}
```
