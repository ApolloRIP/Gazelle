<?php

use Phinx\Migration\AbstractMigration;

class NoZerodateTorrentsGroup extends AbstractMigration {
    public function up() {
        $this->execute("ALTER TABLE torrents_group
            MODIFY Time datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
        ");
    }

    public function down() {
        $this->execute("ALTER TABLE torrents_group
            MODIFY Time datetime NOT NULL DEFAULT '0000-00-00 00:00:00'
        ");
    }
}
