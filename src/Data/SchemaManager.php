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

namespace Fusio\Impl\Data;

use Doctrine\DBAL\Connection;
use PSX\Data\Schema\InvalidSchemaException;
use PSX\Data\Schema\SchemaManagerInterface;

/**
 * SchemaManager
 *
 * @author  Christoph Kappestein <k42b3.x@gmail.com>
 * @license http://www.gnu.org/licenses/agpl-3.0
 * @link    http://fusio-project.org
 */
class SchemaManager implements SchemaManagerInterface
{
    protected $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function getSchema($schemaId)
    {
        $sql = 'SELECT schema.name,
				       schema.cache
				  FROM fusio_schema `schema`
				 WHERE schema.id = :id';

        $row = $this->connection->fetchAssoc($sql, array('id' => $schemaId));

        if (!empty($row)) {
            $cache = $row['cache'];

            if (!empty($cache)) {
                return unserialize($cache);
            } else {
                throw new InvalidSchemaException('Cache is not available for schema ' . $row['name']);
            }
        } else {
            throw new InvalidSchemaException('Invalid schema id');
        }
    }
}
