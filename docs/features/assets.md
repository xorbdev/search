# Assets

While searching pages is a given, it can also be desirable to search assets.
Your site might include documentation stored in PDFs. It would be great if
your users could easily find this documentation.

## Searchable PDFs

The search plugin can index the text content of your PDF assets making them
searchable. Because they are included in the same index as pages, they can
appear in the same results as pages with little effort.

## The Searchable Field

In order to make assets searchable, you must first add a searchable field.

The plugin includes a custom field named **Searchable** for this purpose, but
internally it just checks if the field's value evaluates to true or is a list
that contains the ID of the site being indexed.

You can give it whatever handle you wish so long as it matches the value in
your settings.

<img src="/content/searchable-field.png" alt="Add field interface">

## Asset Volume Field layout

You will then need to add the field to your asset volume's field layout.

<img src="/content/asset-volume-field-layout.png" alt="Asset volume field layout interface">

## Marking Your Assets As Searchable

Now all you have to do to make an asset searchable is to toggle the field on.

!!!info You will need to update your search index after toggling your
searchable field on or off for the changes to take effect. !!!

<img src="/content/searchable-asset-single-site.png" alt="">

If you have multiple sites, the **Searchable** field will let you specify which
sites you wish for this asset to be searchable on.

<img src="/content/searchable-asset-multi-site.png" alt="">
