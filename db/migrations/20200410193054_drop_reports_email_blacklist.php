<?php

use Phinx\Migration\AbstractMigration;

class DropReportsEmailBlacklist extends AbstractMigration {

    public function up() {
        $this->table('reports_email_blacklist')->drop()->update();
    }

    public function down() {
        $this->table('reports_email_blacklist', ['id' => false, 'primary_key' => ['ID']])
            ->addColumn('ID',         'integer', ['null' => false, 'limit' => '10', 'identity' => 'enable'])
            ->addColumn('Type',       'integer', ['null' => false, 'default' => '0', 'limit' => MysqlAdapter::INT_TINY])
            ->addColumn('UserID',     'integer', ['null' => false, 'limit' => '10'])
            ->addColumn('Time',       'datetime', ['null' => false, 'default' => '0000-00-00 00:00:00'])
            ->addColumn('Checked',    'integer', ['null' => false, 'default' => '0', 'limit' => MysqlAdapter::INT_TINY])
            ->addColumn('ResolverID', 'integer', ['null' => true, 'default' => '0', 'limit' => '10'])
            ->addColumn('Email',      'string', ['null' => false, 'default' => '', 'limit' => 255])
            ->addIndex(['Time'],   ['name' => 'Time', 'unique' => false])
            ->addIndex(['UserID'], ['name' => 'UserID', 'unique' => false])
            ->create();
    }
}
