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

namespace Fusio\Impl\Backend\Api\Statistic;

use DateInterval;
use DatePeriod;
use DateTime;
use Fusio\Impl\Fixture;
use PSX\Test\ControllerDbTestCase;

/**
 * ErrorsPerRouteTest
 *
 * @author  Christoph Kappestein <k42b3.x@gmail.com>
 * @license http://www.gnu.org/licenses/agpl-3.0
 * @link    http://fusio-project.org
 */
class ErrorsPerRouteTest extends ControllerDbTestCase
{
    public function getDataSet()
    {
        return Fixture::getDataSet();
    }

    public function testGet()
    {
        $response = $this->sendRequest('http://127.0.0.1/backend/statistic/errors_per_route', 'GET', array(
            'User-Agent'    => 'Fusio TestCase',
            'Authorization' => 'Bearer da250526d583edabca8ac2f99e37ee39aa02a3c076c0edc6929095e20ca18dcf'
        ));

        $labels = [];
        $data   = [];
        $period = new DatePeriod(new DateTime('-1 month'), new DateInterval('P1D'), new DateTime('+1 day'));

        foreach ($period as $key => $date) {
            $labels[] = $date->format('Y-m-d');
            $data[]   = 0;
        }

        $data[count($data) - 1] = 1;

        $expect = [
            'labels' => $labels,
            'data'   => [$data],
            'series' => ['/backend/action'],
        ];

        $body = (string) $response->getBody();

        $this->assertEquals(null, $response->getStatusCode(), $body);
        $this->assertJsonStringEqualsJsonString(json_encode($expect), $body, $body);
    }
}
