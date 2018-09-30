<?php
/*
 * Fusio
 * A web-application to create dynamically RESTful APIs
 *
 * Copyright (C) 2015-2018 Christoph Kappestein <christoph.kappestein@gmail.com>
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

namespace Fusio\Impl\Dependency;

use Doctrine\DBAL;
use Doctrine\DBAL\Tools\Console\Helper\ConnectionHelper;
use Fusio\Impl\Backend\View;
use Fusio\Impl\Base;
use Fusio\Impl\Console;
use Fusio\Impl\EventListener\AuditListener;
use Fusio\Impl\Loader\Context;
use Fusio\Impl\Loader\DatabaseRoutes;
use Fusio\Impl\Loader\Filter\ExternalFilter;
use Fusio\Impl\Loader\Filter\InternalFilter;
use Fusio\Impl\Loader\GeneratorFactory;
use Fusio\Impl\Loader\ResourceListing;
use Fusio\Impl\Loader\RoutingParser;
use Fusio\Impl\Mail\Mailer;
use Fusio\Impl\Mail\TransportFactory;
use Fusio\Impl\Provider\ProviderConfig;
use Fusio\Impl\Provider\ProviderWriter;
use Fusio\Impl\Table;
use PSX\Api\Console as ApiConsole;
use PSX\Api\Listing\CachedListing;
use PSX\Api\Listing\FilterFactory;
use PSX\Framework\Console as FrameworkConsole;
use PSX\Framework\Dependency\DefaultContainer;
use PSX\Schema\Console as SchemaConsole;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command as SymfonyCommand;
use Symfony\Component\Console\Helper\HelperSet;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Container
 *
 * @author  Christoph Kappestein <christoph.kappestein@gmail.com>
 * @license http://www.gnu.org/licenses/agpl-3.0
 * @link    http://fusio-project.org
 */
class Container extends DefaultContainer
{
    use Authorization;
    use Engine;
    use Services;

    /**
     * @return \PSX\Framework\Loader\RoutingParserInterface
     */
    public function getRoutingParser()
    {
        return new DatabaseRoutes($this->get('connection'));
    }

    /**
     * @return \PSX\Framework\Loader\LocationFinderInterface
     */
    public function getLoaderLocationFinder()
    {
        return new RoutingParser($this->get('connection'));
    }

    /**
     * @return \PSX\Api\ListingInterface
     */
    public function getResourceListing()
    {
        $resourceListing = new ResourceListing($this->get('routing_parser'), $this->get('controller_factory'));

        if ($this->get('config')->get('psx_debug')) {
            return $resourceListing;
        } else {
            return new CachedListing($resourceListing, $this->get('cache'));
        }
    }

    /**
     * @return \PSX\Api\Listing\FilterFactoryInterface
     */
    public function getListingFilterFactory()
    {
        $filter = new FilterFactory();
        $filter->addFilter('internal', new InternalFilter());
        $filter->addFilter('external', new ExternalFilter());
        $filter->setDefault('external');

        return $filter;
    }

    /**
     * @return \PSX\Api\GeneratorFactoryInterface
     */
    public function getGeneratorFactory()
    {
        return new GeneratorFactory(
            $this->get('table_manager')->getTable(Table\Scope::class),
            $this->get('config_service'),
            $this->get('annotation_reader'),
            $this->get('config')->get('psx_json_namespace'),
            $this->get('config')->get('psx_url'),
            $this->get('config')->get('psx_dispatch')
        );
    }

    /**
     * @return \Doctrine\DBAL\Connection
     */
    public function getConnection()
    {
        $params = $this->get('config')->get('psx_connection');
        $config = new DBAL\Configuration();
        $config->setFilterSchemaAssetsExpression("~^fusio_~");

        return DBAL\DriverManager::getConnection($params, $config);
    }

    /**
     * @return \Symfony\Component\Console\Application
     */
    public function getConsole()
    {
        $application = new Application('fusio', Base::getVersion());
        $application->setHelperSet(new HelperSet($this->appendConsoleHelpers()));

        $this->appendConsoleCommands($application);

        return $application;
    }

    /**
     * @return \Fusio\Impl\Mail\MailerInterface
     */
    public function getMailer()
    {
        return new Mailer(
            $this->get('config_service'),
            $this->get('logger'),
            TransportFactory::createTransport($this->get('config'))
        );
    }

    /**
     * @return \Fusio\Impl\Provider\ProviderConfig
     */
    public function getProviderConfig()
    {
        $config = new ProviderConfig($this->appendDefaultProviderConfig());

        $providerFile = $this->get('config')->get('fusio_provider');
        if (!empty($providerFile)) {
            $config->merge(ProviderConfig::fromFile($providerFile));
        }

        return $config;
    }

    /**
     * @return \Fusio\Impl\Provider\ProviderWriter
     */
    public function getProviderWriter()
    {
        $file   = $this->get('config')->get('fusio_provider');
        $writer = new ProviderWriter($this->get('provider_config'), $file);

        return $writer;
    }

    protected function appendConsoleCommands(Application $application)
    {
        // psx commands
        $application->add(new FrameworkConsole\ContainerCommand($this));
        $application->add(new FrameworkConsole\RouteCommand($this->get('routing_parser')));
        $application->add(new FrameworkConsole\ServeCommand($this));

        $application->add(new ApiConsole\ParseCommand($this->get('api_manager'), $this->get('generator_factory')));
        $application->add(new ApiConsole\ResourceCommand($this->get('resource_listing'), $this->get('generator_factory')));
        $application->add(new ApiConsole\GenerateCommand($this->get('resource_listing'), $this->get('generator_factory')));

        $application->add(new SchemaConsole\ParseCommand($this->get('schema_manager')));

        // fusio commands
        $application->add(new Console\Action\AddCommand($this->get('system_api_executor_service')));
        $application->add(new Console\Action\ClassCommand($this->get('action_parser')));
        $application->add(new Console\Action\DetailCommand($this->get('action_factory'), $this->get('action_repository'), $this->get('connection_repository')));
        $application->add(new Console\Action\ExecuteCommand($this->get('action_executor_service'), $this->get('table_manager')->getTable(Table\Action::class)));
        $application->add(new Console\Action\ListCommand($this->get('table_manager')->getTable(View\Action::class)));

        $application->add(new Console\App\AddCommand($this->get('system_api_executor_service')));
        $application->add(new Console\App\ListCommand($this->get('table_manager')->getTable(View\App::class)));

        $application->add(new Console\Connection\AddCommand($this->get('system_api_executor_service')));
        $application->add(new Console\Connection\ClassCommand($this->get('connection_parser')));
        $application->add(new Console\Connection\DetailCommand($this->get('connection_factory'), $this->get('action_repository'), $this->get('connection_repository')));
        $application->add(new Console\Connection\ListCommand($this->get('table_manager')->getTable(View\Connection::class)));

        $application->add(new Console\Cronjob\ExecuteCommand($this->get('cronjob_service')));
        $application->add(new Console\Cronjob\ListCommand($this->get('table_manager')->getTable(View\Cronjob::class)));

        $application->add(new Console\Event\ExecuteCommand($this->get('event_executor_service')));

        $application->add(new Console\Migration\ExecuteCommand($this->get('connection'), $this->get('connector')));
        $application->add(new Console\Migration\GenerateCommand($this->get('connection'), $this->get('connector')));
        $application->add(new Console\Migration\LatestCommand($this->get('connection'), $this->get('connector')));
        $application->add(new Console\Migration\MigrateCommand($this->get('connection'), $this->get('connector')));
        $application->add(new Console\Migration\StatusCommand($this->get('connection'), $this->get('connector')));
        $application->add(new Console\Migration\UpToDateCommand($this->get('connection'), $this->get('connector')));
        $application->add(new Console\Migration\VersionCommand($this->get('connection'), $this->get('connector')));

        $application->add(new Console\Schema\AddCommand($this->get('system_api_executor_service')));
        $application->add(new Console\Schema\ExportCommand($this->get('connection')));
        $application->add(new Console\Schema\ListCommand($this->get('table_manager')->getTable(View\Schema::class)));

        $application->add(new Console\System\CheckCommand($this->get('connection')));
        $application->add(new Console\System\CleanCommand());
        $application->add(new Console\System\DeployCommand($this->get('system_deploy_service'), dirname($this->getParameter('config.file')), $this->get('connection'), $this->get('logger')));
        $application->add(new Console\System\ExportCommand($this->get('system_export_service')));
        $application->add(new Console\System\ImportCommand($this->get('system_import_service'), $this->get('connection'), $this->get('logger')));
        $application->add(new Console\System\PushCommand($this->get('system_push_service'), $this->get('config')));
        $application->add(new Console\System\RegisterCommand($this->get('system_import_service'), $this->get('table_manager')->getTable(View\Connection::class), $this->get('connection')));
        $application->add(new Console\System\RestoreCommand($this->get('connection')));
        $application->add(new Console\System\TokenCommand($this->get('app_service'), $this->get('scope_service'), $this->get('table_manager')->getTable(Table\App::class), $this->get('table_manager')->getTable(Table\User::class)));

        $application->add(new Console\User\AddCommand($this->get('user_service')));
        $application->add(new Console\User\ListCommand($this->get('table_manager')->getTable(View\User::class)));

        // symfony commands
        $application->add(new SymfonyCommand\HelpCommand());
        $application->add(new SymfonyCommand\ListCommand());
    }

    /**
     * @return array
     */
    protected function appendConsoleHelpers()
    {
        return array(
            'db' => new ConnectionHelper($this->get('connection')),
            'question' => new QuestionHelper(),
        );
    }

    protected function appendDefaultListener(EventDispatcherInterface $eventDispatcher)
    {
        parent::appendDefaultListener($eventDispatcher);

        $eventDispatcher->addSubscriber(new AuditListener($this->get('table_manager')->getTable(Table\Audit::class)));
    }

    protected function appendDefaultConfig()
    {
        return array_merge(parent::appendDefaultConfig(), array(
            'fusio_project_key'      => '42eec18ffdbffc9fda6110dcc705d6ce',
            'fusio_app_per_consumer' => 16,
            'fusio_app_approval'     => false,
            'fusio_grant_implicit'   => true,
            'fusio_expire_implicit'  => 'PT1H',
            'fusio_expire_app'       => 'P2D',
            'fusio_expire_backend'   => 'PT1H',
            'fusio_expire_consumer'  => 'PT1H',

            'psx_context_factory'    => function(){
                return new Context();
            },
        ));
    }

    protected function appendDefaultProviderConfig()
    {
        return [
            'action' => [
                \Fusio\Adapter\File\Action\FileProcessor::class,
                \Fusio\Adapter\Http\Action\HttpProcessor::class,
                \Fusio\Adapter\Php\Action\PhpProcessor::class,
                \Fusio\Adapter\Php\Action\PhpSandbox::class,
                \Fusio\Adapter\Sql\Action\SqlTable::class,
                \Fusio\Adapter\Util\Action\UtilStaticResponse::class,
                \Fusio\Adapter\V8\Action\V8Processor::class,
            ],
            'connection' => [
                \Fusio\Adapter\Http\Connection\Http::class,
                \Fusio\Adapter\Sql\Connection\Sql::class,
                \Fusio\Adapter\Sql\Connection\SqlAdvanced::class,
            ],
            'payment' => [
            ],
            'user' => [
                \Fusio\Impl\Provider\User\Facebook::class,
                \Fusio\Impl\Provider\User\Github::class,
                \Fusio\Impl\Provider\User\Google::class,
            ],
        ];
    }
}
