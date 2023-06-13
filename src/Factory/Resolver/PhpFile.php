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

namespace Fusio\Impl\Factory\Resolver;

use Fusio\Adapter\Php\Action\PhpEngine;
use Fusio\Engine\Action\RuntimeInterface;
use Fusio\Engine\ActionInterface;
use Fusio\Engine\Factory\ResolverInterface;

/**
 * PhpFile
 *
 * @author  Christoph Kappestein <christoph.kappestein@gmail.com>
 * @license http://www.apache.org/licenses/LICENSE-2.0
 * @link    https://www.fusio-project.org
 */
class PhpFile implements ResolverInterface
{
    private RuntimeInterface $runtime;

    public function __construct(RuntimeInterface $runtime)
    {
        $this->runtime = $runtime;
    }

    public function resolve(string $className): ActionInterface
    {
        $engine = new PhpEngine($this->runtime);
        $engine->setFile($className);
        return $engine;
    }
}
