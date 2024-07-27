<?php
namespace xorb\search\db;

use xorb\search\Plugin;

abstract class Table
{
    public const HITS = '{{%' . Plugin::HANDLE . '_hits}}';
    public const IGNORE_RULES = '{{%' . Plugin::HANDLE . '_ignore_rules}}';
    public const TERM_MAPS = '{{%' . Plugin::HANDLE . '_term_maps}}';
    public const TERM_PRIORITIES = '{{%' . Plugin::HANDLE . '_term_priorities}}';
    public const TERM_PRIORITIES_INDEX = '{{%' . Plugin::HANDLE . '_term_priorities_index}}';
    public const QUERY_PARAM_RULES = '{{%' . Plugin::HANDLE . '_query_param_rules}}';
    public const QUERIES = '{{%' . Plugin::HANDLE . '_queries}}';
    public const REDIRECTS = '{{%' . Plugin::HANDLE . '_redirects}}';
    public const RESULTS = '{{%' . Plugin::HANDLE . '_results}}';
    public const TASKS = '{{%' . Plugin::HANDLE . '_tasks}}';
}
