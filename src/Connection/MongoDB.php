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

namespace Fusio\Impl\Connection;

use Fusio\Engine\ConnectionInterface;
use Fusio\Engine\Form\BuilderInterface;
use Fusio\Engine\Form\ElementFactoryInterface;
use Fusio\Engine\ParametersInterface;
use MongoClient;

/**
 * MongoDB
 *
 * @author  Christoph Kappestein <k42b3.x@gmail.com>
 * @license http://www.gnu.org/licenses/agpl-3.0
 * @link    http://fusio-project.org
 */
class MongoDB implements ConnectionInterface
{
    public function getName()
    {
        return 'Mongo-DB';
    }

    /**
     * @param \Fusio\Engine\ParametersInterface $config
     * @return \MongoDB
     */
    public function getConnection(ParametersInterface $config)
    {
        $rawOptions = $config->get('options');
        if (!empty($rawOptions)) {
            parse_str($rawOptions, $options);

            $client = new MongoClient($config->get('url'), $options);
        } else {
            $client = new MongoClient($config->get('url'));
        }

        return $client->selectDB($config->get('database'));
    }

    public function configure(BuilderInterface $builder, ElementFactoryInterface $elementFactory)
    {
        $builder->add($elementFactory->newInput('url', 'Url', 'text', 'The connection string for the database i.e. <code>mongodb://localhost:27017</code>. Click <a ng-click="help.showDialog(\'help/connection/mongodb.md\')">here</a> for more informations.'));
        $builder->add($elementFactory->newInput('options', 'Options', 'text', 'Optional options for the connection. Click <a ng-click="help.showDialog(\'help/connection/mongodb.md\')">here</a> for more informations.'));
        $builder->add($elementFactory->newInput('database', 'Database', 'text', 'The name of the database which is used upon connection'));
    }
}
