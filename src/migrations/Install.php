<?php
namespace xorb\search\migrations;

use Craft;
use craft\db\Migration;
use craft\db\Table as CraftTable;
use craft\helpers\MigrationHelper;
use xorb\search\db\Table;
use xorb\search\Plugin;
use xorb\search\elements\Result as ResultElement;

class Install extends Migration
{
    public function safeUp()
    {
        if ($this->db->getIsMysql()) {
            $this->createTable(
                Table::RESULTS,
                [
                    'id' => $this->primaryKey(),
                    'resultType' => $this->enum('resultType', ['page', 'asset'])->notNull(),
                    'resultId' => $this->integer()->defaultValue(null),
                    'resultTitle' => $this->string(250)->defaultValue(null),
                    'resultUrl' => $this->string(250)->notNull(),
                    'resultDescription' => $this->text()->defaultValue(null),
                    'resultHash' => $this->string(32)->defaultValue(null),
                    'mainHash' => $this->string(32)->defaultValue(null),
                    'mainData' => $this->longText()->defaultValue(null),
                    'score' => $this->integer()->notNull()->defaultValue(0),
                    'searchPriority' => $this->integer()->notNull()->defaultValue(0),
                    'searchIgnore' => $this->boolean()->notNull()->defaultValue(false),
                    'sitemapPriority' => $this->integer()->notNull()->defaultValue(50),
                    'sitemapChangefreq' => $this->enum(
                            'sitemapChangefreq',
                            [
                                'always', 'hourly', 'daily', 'weekly', 'monthly',
                                'yearly', 'never',
                            ]
                        )->notNull()->defaultValue('weekly'),
                    'sitemapIgnore' => $this->boolean()->notNull()->defaultValue(false),
                    'rulesIgnore' => $this->boolean()->notNull()->defaultValue(false),
                    'error' => $this->boolean()->notNull()->defaultValue(false),
                    'errorCode' => $this->integer()->defaultValue(null),
                    'dateResultModified' => $this->dateTime()->defaultValue(null),
                    'dateMainModified' => $this->dateTime()->defaultValue(null),
                    'dateUnavailableAfter' => $this->dateTime()->defaultValue(null),
                    'dateError' => $this->dateTime()->defaultValue(null),
                    'dateCreated' => $this->dateTime()->notNull(),
                    'dateUpdated' => $this->dateTime()->notNull(),
                    'uid' => $this->uid(),
                ]
            );

            $sql = 'CREATE FULLTEXT INDEX ' .
                $this->db->quoteTableName($this->db->getIndexName()) . ' ON ' .
                $this->db->quoteTableName(Table::RESULTS) . ' ' .
                '(' . $this->db->quoteColumnName('mainData') . ')';

            $this->db->createCommand($sql)->execute();
        } else {
            $this->createTable(
                Table::RESULTS,
                [
                    'id' => $this->primaryKey(),
                    'resultType' => $this->enum('resultType', ['page', 'asset'])->notNull(),
                    'resultId' => $this->integer()->defaultValue(null),
                    'resultTitle' => $this->string(250)->notNull(),
                    'resultUrl' => $this->string(250)->notNull(),
                    'resultDescription' => $this->text()->defaultValue(null),
                    'resultHash' => $this->string(32)->defaultValue(null),
                    'mainHash' => $this->string(32)->defaultValue(null),
                    'mainData' => $this->longText()->defaultValue(null),
                    'mainData_vector' => $this->db->getSchema()->createColumnSchemaBuilder('tsvector')->notNull(),
                    'score' => $this->integer()->notNull()->defaultValue(0),
                    'searchPriority' => $this->integer()->notNull()->defaultValue(0),
                    'searchIgnore' => $this->boolean()->notNull()->defaultValue(false),
                    'sitemapPriority' => $this->integer()->notNull()->defaultValue(0),
                    'sitemapChangefreq' => $this->enum(
                            'sitemapChangefreq',
                            [
                                'always', 'hourly', 'daily', 'weekly', 'monthly',
                                'yearly', 'never',
                            ]
                        )->notNull()->defaultValue('never'),
                    'sitemapIgnore' => $this->boolean()->notNull()->defaultValue(false),
                    'rulesIgnore' => $this->boolean()->notNull()->defaultValue(false),
                    'error' => $this->boolean()->notNull()->defaultValue(false),
                    'errorCode' => $this->integer()->defaultValue(null),
                    'dateResultModified' => $this->dateTime()->defaultValue(null),
                    'dateMainModified' => $this->dateTime()->defaultValue(null),
                    'dateUnavailableAfter' => $this->dateTime()->defaultValue(null),
                    'dateError' => $this->dateTime()->defaultValue(null),
                    'dateCreated' => $this->dateTime()->notNull(),
                    'dateUpdated' => $this->dateTime()->notNull(),
                    'uid' => $this->uid(),
                ]
            );

            $sql = 'CREATE INDEX ' .
                $this->db->quoteTableName($this->db->getIndexName()) . ' ON ' .
                Table::RESULTS . ' USING GIN([[mainData_vector]] [[pg_catalog]].[[tsvector_ops]]) WITH (FASTUPDATE=YES)';

            $this->db->createCommand($sql)->execute();
        }

        $this->createIndex(null, Table::RESULTS, 'resultType');
        $this->createIndex(null, Table::RESULTS, 'resultUrl');
        $this->createIndex(null, Table::RESULTS, 'resultHash');
        $this->createIndex(null, Table::RESULTS, 'mainHash');
        $this->createIndex(null, Table::RESULTS, 'score');
        $this->createIndex(null, Table::RESULTS, 'searchPriority');
        $this->createIndex(null, Table::RESULTS, 'searchIgnore');
        $this->createIndex(null, Table::RESULTS, 'sitemapIgnore');
        $this->createIndex(null, Table::RESULTS, 'error');
        $this->createIndex(null, Table::RESULTS, 'errorCode');
        $this->createIndex(null, Table::RESULTS, 'dateUnavailableAfter');
        $this->addForeignKey(null, Table::RESULTS, 'id', '{{%elements}}', 'id', 'CASCADE');

        $this->createTable(
            Table::IGNORE_RULES,
            [
                'id' => $this->primaryKey(),
                'siteId' => $this->integer()->defaultValue(null),
                'name' => $this->string(250)->notNull(),
                'resultUrlValue' => $this->string(250)->notNull(),
                'resultUrlComparator' => $this->string(16)->notNull(),
                'absolute' => $this->boolean()->notNull()->defaultValue(false),
				'dateCreated' => $this->dateTime()->notNull(),
                'dateUpdated' => $this->dateTime()->notNull(),
                'uid' => $this->uid(),
            ]
        );
        $this->createIndex(null, Table::IGNORE_RULES, 'siteId');
        $this->addForeignKey(null, Table::IGNORE_RULES, ['siteId'], '{{%sites}}', ['id'], 'CASCADE', 'CASCADE');

        $this->createTable(
            Table::TERM_MAPS,
            [
                'id' => $this->primaryKey(),
                'siteId' => $this->integer()->defaultValue(null),
                'term' => $this->string(250)->notNull(),
                'alternate' => $this->string(250)->notNull(),
                'normalizedTerm' => $this->string(250)->notNull(),
                'normalizedAlternate' => $this->string(250)->notNull(),
				'dateCreated' => $this->dateTime()->notNull(),
                'dateUpdated' => $this->dateTime()->notNull(),
                'uid' => $this->uid(),
            ]
        );
        $this->createIndex(null, Table::TERM_MAPS, 'siteId');
        $this->createIndex(null, Table::TERM_MAPS, 'term');
        $this->createIndex(null, Table::TERM_MAPS, 'alternate');
        $this->createIndex(null, Table::TERM_MAPS, 'normalizedTerm');
        $this->createIndex(null, Table::TERM_MAPS, 'normalizedAlternate');
        $this->addForeignKey(null, Table::TERM_MAPS, ['siteId'], '{{%sites}}', ['id'], 'CASCADE', 'CASCADE');

        $this->createTable(
            Table::TERM_PRIORITIES,
            [
                'id' => $this->primaryKey(),
                'siteId' => $this->integer()->defaultValue(null),
                'term' => $this->string(250)->notNull(),
                'normalizedTerm' => $this->string(250)->notNull(),
                'resultUrlValue' => $this->string(250)->notNull(),
                'resultUrlComparator' => $this->string(16)->notNull(),
                'searchPriority' => $this->integer()->notNull(),
				'dateCreated' => $this->dateTime()->notNull(),
                'dateUpdated' => $this->dateTime()->notNull(),
                'uid' => $this->uid(),
            ]
        );
        $this->createIndex(null, Table::TERM_PRIORITIES, 'siteId');
        $this->createIndex(null, Table::TERM_PRIORITIES, 'term');
        $this->createIndex(null, Table::TERM_PRIORITIES, 'normalizedTerm');
        $this->addForeignKey(null, Table::TERM_PRIORITIES, ['siteId'], '{{%sites}}', ['id'], 'CASCADE', 'CASCADE');

        $this->createTable(
            Table::TERM_PRIORITIES_INDEX,
            [
                'id' => $this->primaryKey(),
                'siteId' => $this->integer()->defaultValue(null),
                'termPriorityId' => $this->integer()->notNull(),
                'resultId' => $this->integer()->notNull(),
            ]
        );
        $this->createIndex(null, Table::TERM_PRIORITIES, 'siteId');
        $this->createIndex(null, Table::TERM_PRIORITIES_INDEX, 'termPriorityId');
        $this->createIndex(null, Table::TERM_PRIORITIES_INDEX, 'resultId');
        $this->createIndex(null, Table::TERM_PRIORITIES_INDEX, ['resultId', 'termPriorityId'], true);
        $this->addForeignKey(null, Table::TERM_PRIORITIES, ['siteId'], '{{%sites}}', ['id'], 'CASCADE', 'CASCADE');
        $this->addForeignKey(null, Table::TERM_PRIORITIES_INDEX, ['termPriorityId'], Table::TERM_PRIORITIES, ['id'], 'CASCADE');
        $this->addForeignKey(null, Table::TERM_PRIORITIES_INDEX, ['resultId'], Table::RESULTS, ['id'], 'CASCADE');

        $this->createTable(
            Table::QUERY_PARAM_RULES,
            [
                'id' => $this->primaryKey(),
                'siteId' => $this->integer()->defaultValue(null),
                'name' => $this->string(250)->notNull(),
                'resultUrlValue' => $this->string(250)->notNull(),
                'resultUrlComparator' => $this->string(16)->notNull(),
                'queryParamKey' => $this->string(250)->defaultValue(null),
                'queryParamValue' => $this->string(250)->defaultValue(null),
                'queryParamComparator' => $this->string(16)->defaultValue(null),
				'dateCreated' => $this->dateTime()->notNull(),
                'dateUpdated' => $this->dateTime()->notNull(),
                'uid' => $this->uid(),
            ]
        );
        $this->createIndex(null, Table::QUERY_PARAM_RULES, 'siteId');
        $this->addForeignKey(null, Table::QUERY_PARAM_RULES, ['siteId'], '{{%sites}}', ['id'], 'CASCADE', 'CASCADE');

        $this->createTable(
            Table::HITS,
            [
                'id' => $this->bigPrimaryKey(),
                'siteId' => $this->integer()->notNull(),
                'url' => $this->string(250)->notNull(),
                'dateHit' => $this->dateTime()->notNull(),
            ]
        );
        $this->createIndex(null, Table::HITS, 'siteId');
        $this->createIndex(null, Table::HITS, 'url');
        $this->createIndex(null, Table::HITS, 'dateHit');
        $this->addForeignKey(null, Table::HITS, ['siteId'], '{{%sites}}', ['id'], 'CASCADE', 'CASCADE');

        $this->createTable(
            Table::QUERIES,
            [
                'id' => $this->bigPrimaryKey(),
                'siteId' => $this->integer()->notNull(),
                'query' => $this->string(250)->notNull(),
                'dateQuery' => $this->dateTime()->notNull(),
            ]
        );
        $this->createIndex(null, Table::QUERIES, 'siteId');
        $this->createIndex(null, Table::QUERIES, 'query');
        $this->createIndex(null, Table::QUERIES, 'dateQuery');
        $this->addForeignKey(null, Table::QUERIES, ['siteId'], '{{%sites}}', ['id'], 'CASCADE', 'CASCADE');

        $this->createTable(
            Table::REDIRECTS,
            [
                'id' => $this->primaryKey(),
                'siteId' => $this->integer()->defaultValue(null),
                'fromUrl' => $this->string(250)->notNull(),
                'toUrl' => $this->string(250)->defaultValue(null),
				'type' => $this->enum('type', ['301', '302', '410'])->notNull()->defaultValue('301'),
                'regex' => $this->boolean()->notNull()->defaultValue(false),
                'ignoreQueryParams' => $this->boolean()->notNull()->defaultValue(false),
                'priority' => $this->integer()->notNull()->defaultValue(0),
				'dateCreated' => $this->dateTime()->notNull(),
                'dateUpdated' => $this->dateTime()->notNull(),
                'uid' => $this->uid(),
            ]
        );
        $this->createIndex(null, Table::REDIRECTS, 'siteId');
        $this->addForeignKey(null, Table::REDIRECTS, ['siteId'], '{{%sites}}', ['id'], 'CASCADE', 'CASCADE');

        $this->createTable(
            Table::TASKS,
            [
                'id' => $this->bigPrimaryKey(),
                'siteId' => $this->integer(),
                'task' => $this->string(250)->notNull(),
                'dateLast' => $this->dateTime()->defaultValue(null),
                'dateStart' => $this->dateTime()->defaultValue(null),
                'running' => $this->boolean()->notNull()->defaultValue(false),
            ]
        );
        $this->createIndex(null, Table::TASKS, 'siteId');
        $this->createIndex(null, Table::TASKS, 'task');
        $this->createIndex(null, Table::TASKS, ['siteId', 'task'], true);
        $this->addForeignKey(null, Table::TASKS, ['siteId'], '{{%sites}}', ['id'], 'CASCADE', 'CASCADE');

        $this->insert(CraftTable::FIELDLAYOUTS, ['type' => ResultElement::class]);
    }

    public function safeDown()
    {
        $tables = [
            Table::TASKS,
            Table::REDIRECTS,
            Table::QUERIES,
            Table::HITS,
            Table::QUERY_PARAM_RULES,
            Table::TERM_PRIORITIES_INDEX,
            Table::TERM_PRIORITIES,
            Table::TERM_MAPS,
            Table::IGNORE_RULES,
            Table::RESULTS,
        ];

        foreach ($tables as $table) {
            if (!$this->db->tableExists($table)) {
                continue;
            }

            // Handles foreign keys
            MigrationHelper::dropTable($table, $this);
        }

        // Remove elements rows
        $this->delete(CraftTable::ELEMENTS, ['type' => ResultElement::class]);

        // Remove project config
        Craft::$app->projectConfig->remove(Plugin::PROJECT_CONFIG_PATH);

        $this->delete(CraftTable::FIELDLAYOUTS, ['type' => ResultElement::class]);
    }
}
