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

namespace Fusio\Impl\Backend\Api\Action;

use Fusio\Impl\Authorization\ProtectionTrait;
use Fusio\Impl\Form;
use PSX\Controller\ApiAbstract;

/**
 * ListActions
 *
 * @author  Christoph Kappestein <k42b3.x@gmail.com>
 * @license http://www.gnu.org/licenses/agpl-3.0
 * @link    http://fusio-project.org
 */
class ListActions extends ApiAbstract
{
    use ProtectionTrait;

    /**
     * @Inject
     * @var \Fusio\Engine\Parser\ParserInterface
     */
    protected $actionParser;

    public function doIndex()
    {
        $this->setBody(array(
            'actions' => $this->actionParser->getClasses()
        ));
    }

    public function doDetail()
    {
        $className = $this->getParameter('class');
        $form      = $this->actionParser->getForm($className);

        if ($form instanceof Form\Container) {
            $this->setBody($form);
        } else {
            throw new \RuntimeException('Invalid action class');
        }
    }
}
