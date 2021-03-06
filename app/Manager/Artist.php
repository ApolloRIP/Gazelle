<?php

namespace Gazelle\Manager;

class Artist extends \Gazelle\Base {
    protected const ROLE_KEY = 'artist_role';

    protected $role;

    protected $groupId; // torrent or request context
    protected $userId; // who is manipulating the torrents_artists or requests_artists tables

    public function __construct() {
        parent::__construct();
        if (($this->role = $this->cache->get_value(self::ROLE_KEY)) === false) {
            $this->db->prepared_query("
                SELECT slug, artist_role_id, sequence, name, title, collection
                FROM artist_role
                ORDER BY artist_role_id
            ");
            $this->role = $this->db->to_array('slug', MYSQLI_ASSOC, false);
            $this->cache->cache_value(self::ROLE_KEY, $this->role, 86400 * 30);
        }
    }

    public function findById(int $id, int $revisionId) {
        $artistId = $this->db->scalar("
            SELECT ArtistID FROM artists_group WHERE ArtistID = ?
            ", $id
        );
        return is_null($artistId) ? null : new \Gazelle\Artist($artistId, $revisionId);
    }

    public function findByName(string $name, int $revisionId) {
        $artistId = $this->db->scalar("
            SELECT ArtistID FROM artists_group WHERE Name = ?
            ", trim($name)
        );
        return is_null($artistId) ? null : new \Gazelle\Artist($artistId, $revisionId);
    }

    public function create($name) {
        $this->db->prepared_query('
            INSERT INTO artists_group (Name)
            VALUES (?)
            ', $name
        );
        $artistId = $this->db->inserted_id();

        $this->db->prepared_query('
            INSERT INTO artists_alias (ArtistID, Name)
            VALUES (?, ?)
            ', $artistId, $name
        );
        $aliasId = $this->db->inserted_id();

        $this->cache->increment('stats_artist_count');

        return [$artistId, $aliasId];
    }

    public function setGroupId(int $groupId) {
        $this->groupId = $groupId;
        return $this;
    }

    public function setUserId(int $userId) {
        $this->userId = $userId;
        return $this;
    }

    public function sectionName(int $sectionId): ?string {
        return (new \Gazelle\ReleaseType)->findExtendedNameById($sectionId);
    }

    public function sectionLabel(int $sectionId): string {
        return strtolower(str_replace(' ', '_', $this->sectionName($sectionId)));
    }

    public function sectionTitle(int $sectionId): string {
        return (new \Gazelle\ReleaseType)->sectionTitle($sectionId);
    }

    public function addToGroup(int $artistId, int $aliasId, int $role): int {
        $this->db->prepared_query("
            INSERT IGNORE INTO torrents_artists
                   (GroupID, UserID, ArtistID, AliasID, artist_role_id, Importance)
            VALUES (?,       ?,      ?,        ?,       ?,              ?)
            ", $this->groupId, $this->userId, $artistId, $aliasId, $role, (string)$role
        );
        return $this->db->affected_rows();
    }

    public function addToRequest(int $artistId, int $aliasId, int $role): int {
        $this->db->prepared_query("
            INSERT IGNORE INTO requests_artists
                   (RequestID, ArtistID, AliasID, artist_role_id, Importance)
            VALUES (?,         ?,        ?,       ?,              ?)
            ", $this->groupId, $artistId, $aliasId, $role, (string)$role
        );
        return $this->db->affected_rows();
    }
}
