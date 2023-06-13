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

namespace Fusio\Impl\Event\App;

use Fusio\Impl\Authorization\UserContext;
use Fusio\Impl\Event\EventAbstract;
use Fusio\Impl\Table\Generated\AppRow;
use Fusio\Model\Backend\AppUpdate;

/**
 * UpdatedEvent
 *
 * @author  Christoph Kappestein <christoph.kappestein@gmail.com>
 * @license http://www.apache.org/licenses/LICENSE-2.0
 * @link    https://www.fusio-project.org
 */
class UpdatedEvent extends EventAbstract
{
    private AppUpdate $app;
    private AppRow $existing;

    public function __construct(AppUpdate $app, AppRow $existing, UserContext $context)
    {
        parent::__construct($context);

        $this->app      = $app;
        $this->existing = $existing;
    }

    public function getApp(): AppUpdate
    {
        return $this->app;
    }

    public function getExisting(): AppRow
    {
        return $this->existing;
    }
}
