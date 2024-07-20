# Console Commands

!!!info You can automate the updating of your index by setting up a cron job
to run either the `update` or `queue update` command depending on your
preference. !!!

## Update

This command will run all tasks relating to updating your search index.

```bash
php craft search/results
```

## Queue Update

This command will also run all tasks relating to updating your search index
only it will use the Craft CMS Job Queue system. It is equivalent to running
update under Utilities.

```bash
php craft search/results/queue-update
```

## Add Assets

This command will add or remove assets based on whether they are marked as
searchable or not.

```bash
php craft search/results/add-assets
```

## Add Known Pages

This command will add or remove known pages based on your entries and products.

```bash
php craft search/results/add-known-pages
```

## Add New Pages

This command will add new pages found from hit tracking.

```bash
php craft search/results/add-new-pages
```

## Update Page Score

This command will update the page score of your results.

```bash
php craft search/results/update-page-score
```

## Update Results

This command will update the status of your results. The status is determined
based on your rules and settings you have set.

For example if you updated your ignore rules, you would need to run this task
for those changes to take effect.

```bash
php craft search/results/update-results
```

## Update Term Priorities Index

For speed considerations, search term priorities need to be indexed. This task
takes care of that.

If you update your term priorities, you will need to run this task for those
changes to take effect.

```bash
php craft search/results/update-term-priorities-index
```
