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

namespace Fusio\Impl\Service\User;

use Fusio\Impl\Service\Mail\MailerInterface;
use Fusio\Impl\Service;
use PSX\Framework\Config\ConfigInterface;

/**
 * Mailer
 *
 * @author  Christoph Kappestein <christoph.kappestein@gmail.com>
 * @license http://www.apache.org/licenses/LICENSE-2.0
 * @link    https://www.fusio-project.org
 */
class Mailer
{
    private Service\Config $configService;
    private MailerInterface $mailer;
    private ConfigInterface $config;

    public function __construct(Service\Config $configService, MailerInterface $mailer, ConfigInterface $config)
    {
        $this->configService = $configService;
        $this->mailer = $mailer;
        $this->config = $config;
    }

    public function sendActivationMail(string $name, string $email, string $token)
    {
        $this->sendMail('mail_register', $email, [
            'apps_url' => $this->config->get('fusio_apps_url'),
            'name' => $name,
            'email' => $email,
            'token' => $token
        ]);
    }

    public function sendResetPasswordMail(string $name, string $email, string $token)
    {
        $this->sendMail('mail_pw_reset', $email, [
            'apps_url' => $this->config->get('fusio_apps_url'),
            'name' => $name,
            'email' => $email,
            'token' => $token
        ]);
    }

    public function sendPointsThresholdMail(string $name, string $email, int $points)
    {
        $this->sendMail('mail_points', $email, [
            'apps_url' => $this->config->get('fusio_apps_url'),
            'name' => $name,
            'email' => $email,
            'points' => $points
        ]);
    }

    private function sendMail(string $template, string $email, array $parameters)
    {
        $subject = $this->configService->getValue($template . '_subject');
        $body    = $this->configService->getValue($template . '_body');

        foreach ($parameters as $key => $value) {
            $body = str_replace('{' . $key . '}', $value, $body);
        }

        $this->mailer->send($subject, [$email], $body);
    }
}
