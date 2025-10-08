# Search

Search is the main purpose of this plugin, and likely what you will want
to setup first.

To get started, you can head to the utilities area of Craft CMS and update the
index. This will queue a job that will add any known pages from your entries and
products to your results index.

!!!info In order for your pages to be properly indexed, the main content of your site needs to be placed inside a `<main>` tag. This is to prevent your main header, navigation, footer, etc. from being indexed on every page. !!!

<img src="https://xorb.dev/content/utilities-update.png" alt="Search utilities update interface">

If your site updates often or you plan on using hit tracking, you may want to
automatically run this job periodically.

You can also manually add pages that exist on your site to the index here.

<img src="https://xorb.dev/content/utilities-add.png" alt="Search utilities add interface">

Your results will now appear in the index.

<img src="https://xorb.dev/content/results-index.png" alt="Search results index interface">
