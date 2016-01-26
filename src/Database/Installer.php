<?php
/*
 * Fusio
 * A web-application to create dynamically RESTful APIs
 *
 * Copyright (C) 2015-2016 Christoph Kappestein <k42b3.x@gmail.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace Fusio\Impl\Database;

use DateTime;
use Doctrine\DBAL\Connection;
use Fusio\Impl\Base;

/**
 * Installer
 *
 * @author  Christoph Kappestein <k42b3.x@gmail.com>
 * @license http://www.gnu.org/licenses/agpl-3.0
 * @link    http://fusio-project.org
 */
class Installer
{
    protected $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function install($schemaVersion)
    {
        $version = $this->doInstall($schemaVersion);

        if ($version instanceof VersionInterface) {
            $this->connection->beginTransaction();

            $version->executeInstall($this->connection);

            $this->connection->commit();
        }
    }

    public function upgrade($fromVersion, $toVersion)
    {
        $indexFrom   = $this->getIndexOf($fromVersion);
        $indexTo     = $this->getIndexOf($toVersion);
        $upgradePath = $this->getPathBetweenVersions($fromVersion, $toVersion);

        foreach ($upgradePath as $schemaVersion) {
            // install version
            $version = $this->doInstall($schemaVersion);

            if ($version instanceof VersionInterface) {
                // we execute the upgrade only if we are jumping to a new
                // version
                if ($indexTo > $indexFrom) {
                    $this->connection->beginTransaction();

                    $version->executeUpgrade($this->connection);

                    $this->connection->commit();
                }
            }
        }
    }

    /**
     * Returns the complete upgrade path
     *
     * @return array
     */
    public function getUpgradePath()
    {
        return [
            '0.2.1',
            '0.2.0',
        ];
    }

    /**
     * Returns the upgrade path between two versions
     *
     * @param string $fromVersion
     * @param string $toVersion
     * @return array
     */
    public function getPathBetweenVersions($fromVersion, $toVersion)
    {
        $path      = array_reverse($this->getUpgradePath());
        $indexFrom = $this->getIndexOf($fromVersion);
        $indexTo   = $this->getIndexOf($toVersion);

        // downgrade is not possible
        if ($indexTo < $indexFrom) {
            return [];
        }

        if (isset($path[$indexTo]) && isset($path[$indexFrom])) {
            return array_slice($path, $indexFrom + 1, $indexTo - $indexFrom);
        }

        return [];
    }

    protected function getIndexOf($version)
    {
        $upgradePath = array_reverse($this->getUpgradePath());
        foreach ($upgradePath as $index => $schemaVersion) {
            if (version_compare($schemaVersion, $version, '==')) {
                return $index;
            }
        }
        return null;
    }

    protected function doInstall($schemaVersion)
    {
        $version = self::getVersion($schemaVersion);
        if ($version instanceof VersionInterface) {
            $fromSchema = $this->connection->getSchemaManager()->createSchema();
            $toSchema   = $version->getSchema();
            $queries    = $fromSchema->getMigrateToSql($toSchema, $this->connection->getDatabasePlatform());

            $this->connection->beginTransaction();

            foreach ($queries as $query) {
                $this->connection->query($query);
            }

            // insert installation entry
            $now = new DateTime();

            $this->connection->insert('fusio_meta', [
                'version'     => Base::getVersion(),
                'installDate' => $now->format('Y-m-d H:i:s'),
            ]);

            $this->connection->commit();

            return $version;
        } else {
            return null;
        }
    }

    public static function getVersion($version)
    {
        $version   = str_pad(str_replace('.', '', $version), 3, '0');
        $className = 'Fusio\Impl\Database\Version\Version' . $version;

        if (class_exists($className)) {
            return new $className();
        } else {
            return null;
        }
    }
}
