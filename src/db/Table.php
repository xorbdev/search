<?php
namespace xorb\search\db;

abstract class Table
{
    public const HITS = '{{%search_hits}}';
    public const IGNORE_RULES = '{{%search_ignore_rules}}';
    public const TERM_MAPS = '{{%search_term_maps}}';
    public const TERM_PRIORITIES = '{{%search_term_priorities}}';
    public const TERM_PRIORITIES_INDEX = '{{%search_term_priorities_index}}';
    public const QUERY_PARAM_RULES = '{{%search_query_param_rules}}';
    public const QUERIES = '{{%search_queries}}';
    public const REDIRECTS = '{{%search_redirects}}';
    public const RESULTS = '{{%search_results}}';
    public const TASKS = '{{%search_tasks}}';
}
