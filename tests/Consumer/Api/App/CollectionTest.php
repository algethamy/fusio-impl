<?php
/*
 * Fusio is an open source API management platform which helps to create innovative API solutions.
 * For the current version and information visit <https://www.fusio-project.org/>
 *
 * Copyright 2015-2023 Christoph Kappestein <christoph.kappestein@gmail.com>
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace Fusio\Impl\Tests\Consumer\Api\App;

use Fusio\Impl\Table\App;
use Fusio\Impl\Tests\Fixture;
use Fusio\Impl\Tests\Normalizer;
use PSX\Framework\Test\ControllerDbTestCase;

/**
 * CollectionTest
 *
 * @author  Christoph Kappestein <christoph.kappestein@gmail.com>
 * @license http://www.apache.org/licenses/LICENSE-2.0
 * @link    https://www.fusio-project.org
 */
class CollectionTest extends ControllerDbTestCase
{
    public function getDataSet(): array
    {
        return Fixture::getDataSet();
    }

    public function testGet()
    {
        $response = $this->sendRequest('/consumer/app', 'GET', array(
            'User-Agent'    => 'Fusio TestCase',
            'Authorization' => 'Bearer b8f6f61bd22b440a3e4be2b7491066682bfcde611dbefa1b15d2e7f6522d77e2'
        ));

        $body = (string) $response->getBody();
        $body = Normalizer::normalize($body);

        $expect = <<<'JSON'
{
    "totalResults": 2,
    "startIndex": 0,
    "itemsPerPage": 16,
    "entry": [
        {
            "id": 2,
            "userId": 1,
            "status": 1,
            "name": "Developer",
            "appKey": "[uuid]",
            "date": "[datetime]"
        },
        {
            "id": 1,
            "userId": 1,
            "status": 1,
            "name": "Backend",
            "appKey": "[uuid]",
            "date": "[datetime]"
        }
    ]
}
JSON;

        $this->assertEquals(200, $response->getStatusCode(), $body);
        $this->assertJsonStringEqualsJsonString($expect, $body, $body);
    }

    public function testGetUnauthorized()
    {
        $response = $this->sendRequest('/consumer/app', 'GET', array(
            'User-Agent' => 'Fusio TestCase',
        ));

        $body = (string) $response->getBody();
        $data = \json_decode($body);

        $this->assertEquals(401, $response->getStatusCode(), $body);
        $this->assertFalse($data->success);
        $this->assertStringStartsWith('Missing authorization header', $data->message);
    }

    public function testPost()
    {
        $response = $this->sendRequest('/consumer/app', 'POST', array(
            'User-Agent'    => 'Fusio TestCase',
            'Authorization' => 'Bearer b8f6f61bd22b440a3e4be2b7491066682bfcde611dbefa1b15d2e7f6522d77e2'
        ), json_encode([
            'status' => 3, // status and userID are ignored so it doesnt matter
            'name'   => 'Foo',
            'url'    => 'http://google.com',
            'scopes' => ['foo', 'bar']
        ]));

        $body   = (string) $response->getBody();
        $expect = <<<'JSON'
{
    "success": true,
    "message": "App successfully created"
}
JSON;

        $this->assertEquals(201, $response->getStatusCode(), $body);
        $this->assertJsonStringEqualsJsonString($expect, $body, $body);

        // check database
        $sql = $this->connection->createQueryBuilder()
            ->select('id', 'status', 'user_id', 'name', 'url')
            ->from('fusio_app')
            ->orderBy('id', 'DESC')
            ->setFirstResult(0)
            ->setMaxResults(1)
            ->getSQL();

        $row = $this->connection->fetchAssociative($sql);

        $this->assertEquals(6, $row['id']);
        $this->assertEquals(App::STATUS_ACTIVE, $row['status']);
        $this->assertEquals(1, $row['user_id']);
        $this->assertEquals('Foo', $row['name']);
        $this->assertEquals('http://google.com', $row['url']);
    }

    public function testPut()
    {
        $response = $this->sendRequest('/consumer/app', 'PUT', array(
            'User-Agent'    => 'Fusio TestCase',
            'Authorization' => 'Bearer b8f6f61bd22b440a3e4be2b7491066682bfcde611dbefa1b15d2e7f6522d77e2'
        ), json_encode([
            'foo' => 'bar',
        ]));

        $body = (string) $response->getBody();

        $this->assertEquals(404, $response->getStatusCode(), $body);
    }

    public function testDelete()
    {
        $response = $this->sendRequest('/consumer/app', 'DELETE', array(
            'User-Agent'    => 'Fusio TestCase',
            'Authorization' => 'Bearer b8f6f61bd22b440a3e4be2b7491066682bfcde611dbefa1b15d2e7f6522d77e2'
        ), json_encode([
            'foo' => 'bar',
        ]));

        $body = (string) $response->getBody();

        $this->assertEquals(404, $response->getStatusCode(), $body);
    }
}
