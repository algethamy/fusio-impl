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

namespace Fusio\Impl\Action;

use Fusio\Impl\ActionTestCaseTrait;
use Fusio\Impl\App;
use Fusio\Impl\Form\Builder;
use Fusio\Impl\MongoTestCase;
use PSX\Data\Record;
use PSX\Test\Environment;

/**
 * MongoDeleteTest
 *
 * @author  Christoph Kappestein <k42b3.x@gmail.com>
 * @license http://www.gnu.org/licenses/agpl-3.0
 * @link    http://fusio-project.org
 */
class MongoDeleteTest extends MongoTestCase
{
    use ActionTestCaseTrait;

    public function testHandle()
    {
        $action = new MongoDelete();
        $action->setConnection(Environment::getService('connection'));
        $action->setConnector(Environment::getService('connector'));
        $action->setTemplateFactory(Environment::getService('template_factory'));
        $action->setResponse(Environment::getService('response'));

        $parameters = $this->getParameters([
            'connection'   => 3,
            'propertyName' => 'foo',
            'collection'   => 'app_news',
            'criteria'     => '{"id": {{ request.uriFragment("id") }}}',
        ]);

        $response = $action->handle($this->getRequest('POST', ['id' => 2]), $parameters, $this->getContext());

        $body = [];
        $body['success'] = true;
        $body['message'] = 'Execution was successful';

        $this->assertInstanceOf('Fusio\Engine\ResponseInterface', $response);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals([], $response->getHeaders());
        $this->assertEquals($body, $response->getBody());

        $row    = $this->collection->findOne(['id' => 2]);
        $expect = null;

        $this->assertEquals($expect, $row);
    }

    public function testGetForm()
    {
        $action  = new MongoDelete();
        $builder = new Builder();
        $factory = Environment::getService('form_element_factory');

        $action->configure($builder, $factory);

        $this->assertInstanceOf('Fusio\Impl\Form\Container', $builder->getForm());
    }
}
