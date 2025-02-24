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

namespace Fusio\Impl\Backend\Action\Marketplace;

use Fusio\Engine\Action\RuntimeInterface;
use Fusio\Engine\ActionAbstract;
use Fusio\Engine\ActionInterface;
use Fusio\Engine\ContextInterface;
use Fusio\Engine\ParametersInterface;
use Fusio\Engine\RequestInterface;
use Fusio\Impl\Authorization\UserContext;
use Fusio\Impl\Service\Marketplace\Installer;
use Fusio\Model\Backend\MarketplaceInstall;
use PSX\Framework\Config\ConfigInterface;
use PSX\Http\Environment\HttpResponse;
use PSX\Http\Exception as StatusCode;

/**
 * Install
 *
 * @author  Christoph Kappestein <christoph.kappestein@gmail.com>
 * @license http://www.apache.org/licenses/LICENSE-2.0
 * @link    https://www.fusio-project.org
 */
class Install implements ActionInterface
{
    private Installer $installerService;
    private ConfigInterface $config;

    public function __construct(Installer $installerService, ConfigInterface $config)
    {
        $this->installerService = $installerService;
        $this->config = $config;
    }

    public function handle(RequestInterface $request, ParametersInterface $configuration, ContextInterface $context): mixed
    {
        if (!$this->config->get('fusio_marketplace')) {
            throw new StatusCode\InternalServerErrorException('Marketplace is not enabled, please change the setting "fusio_marketplace" at the configuration.php to "true" in order to activate the marketplace');
        }

        $body = $request->getPayload();

        assert($body instanceof MarketplaceInstall);

        $app = $this->installerService->install(
            $body,
            UserContext::newActionContext($context)
        );

        return new HttpResponse(201, [], [
            'success' => true,
            'message' => 'App ' . $app->getName() . ' successful installed',
        ]);
    }
}
