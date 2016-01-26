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

namespace Fusio\Impl\Table;

use PSX\Sql\TableAbstract;

/**
 * App
 *
 * @author  Christoph Kappestein <k42b3.x@gmail.com>
 * @license http://www.gnu.org/licenses/agpl-3.0
 * @link    http://fusio-project.org
 */
class App extends TableAbstract
{
    const STATUS_ACTIVE      = 0x1;
    const STATUS_PENDING     = 0x2;
    const STATUS_DEACTIVATED = 0x3;
    const STATUS_DELETED     = 0x4;

    const BACKEND  = 1;
    const CONSUMER = 2;

    public function getName()
    {
        return 'fusio_app';
    }

    public function getColumns()
    {
        return array(
            'id' => self::TYPE_INT | self::AUTO_INCREMENT | self::PRIMARY_KEY,
            'userId' => self::TYPE_INT,
            'status' => self::TYPE_INT,
            'name' => self::TYPE_VARCHAR,
            'url' => self::TYPE_VARCHAR,
            'appKey' => self::TYPE_VARCHAR,
            'appSecret' => self::TYPE_VARCHAR,
            'date' => self::TYPE_DATETIME,
        );
    }

    public function getAuthorizedApps($userId)
    {
        $sql = '    SELECT userGrant.id,
                           userGrant.date AS createDate,
                           userGrant.appId AS app_id,
                           app.name AS app_name,
                           app.url AS app_url
                      FROM fusio_user_grant userGrant
                INNER JOIN fusio_app app
                        ON userGrant.appId = app.id
                     WHERE userGrant.allow = 1
                       AND userGrant.userId = :userId
                       AND app.status = :status';

        return $this->connection->fetchAll($sql, [
            'userId' => $userId,
            'status' => self::STATUS_ACTIVE
        ]);
    }

    public function getByAppKeyAndSecret($appKey, $appSecret)
    {
        $sql = 'SELECT id,
                       userId
                  FROM fusio_app
                 WHERE appKey = :app_key
                   AND appSecret = :app_secret
                   AND status = :status';

        return $this->connection->fetchAssoc($sql, array(
            'app_key'    => $appKey,
            'app_secret' => $appSecret,
            'status'     => self::STATUS_ACTIVE,
        ));
    }
}
